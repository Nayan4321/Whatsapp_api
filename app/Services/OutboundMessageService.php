<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Sends an agent's reply through the Cloud API and records it, so every
 * outbound message is both delivered and monitored.
 */
class OutboundMessageService
{
    public function __construct(protected KeywordFlagService $flags)
    {
    }

    /**
     * Send a free-form text reply in an existing conversation.
     *
     * @throws \RuntimeException if the 24h window is closed (use a template instead)
     */
    public function sendText(Conversation $conversation, User $agent, string $body): Message
    {
        if (! $conversation->windowOpen()) {
            throw new \RuntimeException('The 24-hour reply window is closed. Send a template message to re-open the conversation.');
        }

        $number = $conversation->number;
        $contact = $conversation->contact;

        // Record first (status=queued) so nothing is lost if the API call fails.
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'whatsapp_number_id' => $number->id,
            'sender_user_id' => $agent->id,
            'direction' => 'out',
            'type' => 'text',
            'body' => $body,
            'status' => 'queued',
            'sent_at' => now(),
        ]);

        // Monitor outbound content (the main "chatting fairly" check).
        $this->flags->scan($message);

        try {
            $result = WhatsAppCloudService::for($number)->sendText($contact->wa_id, $body);
            $wamid = data_get($result, 'messages.0.id');
            $message->update(['wamid' => $wamid, 'status' => 'sent', 'raw' => $result]);
        } catch (\Throwable $e) {
            $message->update(['status' => 'failed', 'error' => $this->errorMessage($e)]);
            throw $e;
        }

        DB::transaction(function () use ($conversation, $agent) {
            $conversation->forceFill([
                'last_message_at' => now(),
                // First reply claims the conversation for this agent if unassigned.
                'assigned_user_id' => $conversation->assigned_user_id ?: $agent->id,
                'status' => $conversation->status === 'resolved' ? 'open' : $conversation->status,
            ])->save();
        });

        AuditLogger::log('sent_message', $conversation, ['message_id' => $message->id]);

        return $message;
    }

    /**
     * Send a media file (image / video / document / audio) that an agent
     * uploaded. The file is stored locally (for the transcript) and uploaded
     * to Meta, then sent by media id so nothing is publicly exposed.
     */
    public function sendMedia(Conversation $conversation, User $agent, \Illuminate\Http\UploadedFile $file, ?string $caption = null): Message
    {
        if (! $conversation->windowOpen()) {
            throw new \RuntimeException('The 24-hour reply window is closed. Send a template message to re-open the conversation.');
        }

        $number = $conversation->number;
        $contact = $conversation->contact;
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $type = $this->mediaType($mime);

        // Store a copy for the monitored transcript.
        $path = $file->store('wa-media/out/'.$number->id, 'public');
        $absolute = \Illuminate\Support\Facades\Storage::disk('public')->path($path);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'whatsapp_number_id' => $number->id,
            'sender_user_id' => $agent->id,
            'direction' => 'out',
            'type' => $type,
            'body' => $caption,
            'media_path' => $path,
            'media_mime' => $mime,
            'status' => 'queued',
            'sent_at' => now(),
        ]);

        if ($caption) {
            $this->flags->scan($message);
        }

        try {
            $mediaId = WhatsAppCloudService::for($number)->uploadMedia($absolute, $mime);
            $result = WhatsAppCloudService::for($number)->sendMedia($contact->wa_id, $type, $mediaId, $caption, true);
            $message->update(['wamid' => data_get($result, 'messages.0.id'), 'status' => 'sent', 'raw' => $result]);
        } catch (\Throwable $e) {
            $message->update(['status' => 'failed', 'error' => $this->errorMessage($e)]);
            throw $e;
        }

        $this->touch($conversation, $agent);
        AuditLogger::log('sent_media', $conversation, ['message_id' => $message->id, 'type' => $type]);

        return $message;
    }

    /** Send a location pin. */
    public function sendLocation(Conversation $conversation, User $agent, float $lat, float $lng, ?string $name = null, ?string $address = null): Message
    {
        if (! $conversation->windowOpen()) {
            throw new \RuntimeException('The 24-hour reply window is closed. Send a template message to re-open the conversation.');
        }

        $number = $conversation->number;
        $contact = $conversation->contact;

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'whatsapp_number_id' => $number->id,
            'sender_user_id' => $agent->id,
            'direction' => 'out',
            'type' => 'location',
            'body' => trim(($name ? $name.' ' : '')."($lat, $lng)"),
            'status' => 'queued',
            'sent_at' => now(),
        ]);

        try {
            $result = WhatsAppCloudService::for($number)->sendLocation($contact->wa_id, $lat, $lng, $name, $address);
            $message->update(['wamid' => data_get($result, 'messages.0.id'), 'status' => 'sent', 'raw' => $result]);
        } catch (\Throwable $e) {
            $message->update(['status' => 'failed', 'error' => $this->errorMessage($e)]);
            throw $e;
        }

        $this->touch($conversation, $agent);
        AuditLogger::log('sent_location', $conversation, ['message_id' => $message->id]);

        return $message;
    }

    protected function mediaType(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            default => 'document',
        };
    }

    protected function touch(Conversation $conversation, User $agent): void
    {
        $conversation->forceFill([
            'last_message_at' => now(),
            'assigned_user_id' => $conversation->assigned_user_id ?: $agent->id,
            'status' => $conversation->status === 'resolved' ? 'open' : $conversation->status,
        ])->save();
    }

    /** Start/re-open a conversation with an approved template message. */
    public function sendTemplate(Conversation $conversation, User $agent, string $template, string $language, array $components = []): Message
    {
        $number = $conversation->number;
        $contact = $conversation->contact;

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'whatsapp_number_id' => $number->id,
            'sender_user_id' => $agent->id,
            'direction' => 'out',
            'type' => 'template',
            'body' => "[template: {$template}]",
            'status' => 'queued',
            'sent_at' => now(),
        ]);

        try {
            $result = WhatsAppCloudService::for($number)->sendTemplate($contact->wa_id, $template, $language, $components);
            $wamid = data_get($result, 'messages.0.id');
            $message->update(['wamid' => $wamid, 'status' => 'sent', 'raw' => $result]);
        } catch (\Throwable $e) {
            $message->update(['status' => 'failed', 'error' => $this->errorMessage($e)]);
            throw $e;
        }

        $conversation->forceFill([
            'last_message_at' => now(),
            'assigned_user_id' => $conversation->assigned_user_id ?: $agent->id,
        ])->save();

        AuditLogger::log('sent_template', $conversation, ['message_id' => $message->id, 'template' => $template]);

        return $message;
    }

    protected function errorMessage(\Throwable $e): string
    {
        if ($e instanceof \Illuminate\Http\Client\RequestException && $e->response) {
            return (string) $e->response->body();
        }

        return $e->getMessage();
    }
}

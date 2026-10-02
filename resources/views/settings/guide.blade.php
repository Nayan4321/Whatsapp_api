@extends('layouts.app')
@section('title','Setup Guide')
@section('heading','Setup Guide — connect numbers for monitoring')
@section('content')

<div class="space-y-6 max-w-3xl">

    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 text-sm">
        This is a <strong>read-only monitor</strong>. Agents keep using their normal <strong>WhatsApp Business app</strong> — nobody logs in here except you. Once a number is connected to Meta <strong>Coexistence</strong>, every message (client → agent and agent → client) and up to 6 months of history flow into this dashboard automatically.
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold mb-2">Your webhook details (paste into Meta)</h2>
        <div class="text-sm space-y-2">
            <div><div class="text-slate-500">Callback URL</div>
                <code class="block bg-slate-50 border rounded px-3 py-2 break-all">{{ $webhookUrl }}</code></div>
            <div><div class="text-slate-500">Verify token</div>
                <code class="block bg-slate-50 border rounded px-3 py-2 break-all">{{ $verifyToken }}</code></div>
            <p class="text-slate-600">Subscribe the webhook to <strong>messages</strong> <em>and</em> <strong>smb_message_echoes</strong> (the agent-side echoes), plus <strong>message history</strong> if offered.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold mb-3">Step by step</h2>
        <ol class="list-decimal list-inside text-sm space-y-2 text-slate-700">
            <li>Each agent keeps their number in the <strong>WhatsApp Business app</strong> as normal.</li>
            <li>Connect that number to <strong>Coexistence</strong> (WhatsApp Business app + Cloud API on the same number) via Meta's Embedded Signup for Business-app users — directly if your app has Tech Provider access, or through a BSP (e.g. 360dialog).</li>
            <li>Point that number's webhook to the <strong>Callback URL</strong> and <strong>Verify token</strong> above, and subscribe to <strong>messages</strong> + <strong>smb_message_echoes</strong>.</li>
            <li>In this app → <a href="{{ route('settings.numbers.create') }}" class="text-indigo-600 underline">Numbers → Add number</a>, enter the number's <strong>Phone number ID</strong>, <strong>WABA ID</strong> and <strong>access token</strong> (so the dashboard can match its webhooks and fetch media).</li>
            <li>Done. Messages (both directions) and history appear under <a href="{{ route('supervisor.conversations') }}" class="text-indigo-600 underline">Conversations</a> automatically.</li>
        </ol>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold mb-3">Where to get each value</h2>
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500 border-b"><tr><th class="py-2">Value</th><th class="py-2">Where</th></tr></thead>
            <tbody class="divide-y">
                <tr><td class="py-2 font-medium">Phone number ID</td><td class="py-2">developers.facebook.com → your App → WhatsApp → API Setup</td></tr>
                <tr><td class="py-2 font-medium">WABA ID</td><td class="py-2">same API Setup page</td></tr>
                <tr><td class="py-2 font-medium">Permanent access token</td><td class="py-2">Business Settings → System Users → generate token with <code>whatsapp_business_messaging</code> + <code>whatsapp_business_management</code></td></tr>
                <tr><td class="py-2 font-medium">App Secret</td><td class="py-2">App → Settings → Basic → put in <code>core/.env</code> as <code>WHATSAPP_APP_SECRET</code></td></tr>
            </tbody>
        </table>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900">
        <strong>Note:</strong> capturing the <em>agent's</em> replies requires Coexistence (the <code>smb_message_echoes</code> webhook). Without it, the dashboard still shows clients' incoming messages, but not what agents send back.
    </div>

</div>
@endsection

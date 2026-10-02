@extends('layouts.app')
@section('title','Dashboard')
@section('heading','WhatsApp monitoring')
@section('content')
<div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
    @php($cards = [
        ['Numbers',$stats['numbers'],'bg-slate-700'],
        ['Contacts',$stats['contacts'],'bg-slate-700'],
        ['Conversations',$stats['conversations'],'bg-indigo-600'],
        ['Messages today',$stats['messages_today'],'bg-blue-600'],
        ['From clients today',$stats['inbound_today'],'bg-teal-600'],
        ['From agents today',$stats['outbound_today'],'bg-violet-600'],
    ])
    @foreach($cards as [$label,$val,$color])
        <div class="rounded-xl p-4 text-white {{ $color }} shadow">
            <div class="text-3xl font-bold">{{ $val }}</div>
            <div class="text-xs opacity-90 mt-1">{{ $label }}</div>
        </div>
    @endforeach
</div>

<div class="bg-white rounded-xl shadow">
    <div class="px-4 py-3 border-b flex items-center justify-between">
        <h2 class="font-semibold">Recent conversations</h2>
        <a href="{{ route('supervisor.conversations') }}" class="text-sm text-indigo-600">View all →</a>
    </div>
    <table class="w-full text-sm">
        <thead class="text-left text-slate-500 border-b">
            <tr><th class="px-4 py-2">Client</th><th class="px-4 py-2">Number (agent line)</th><th class="px-4 py-2">Last message</th><th></th></tr>
        </thead>
        <tbody class="divide-y">
        @forelse($recentConversations as $c)
            <tr>
                <td class="px-4 py-2 font-medium">{{ $c->contact?->displayName() }}</td>
                <td class="px-4 py-2">{{ $c->number->label ?? '' }}</td>
                <td class="px-4 py-2 text-slate-500 text-xs">{{ $c->last_message_at?->diffForHumans() }}</td>
                <td class="px-4 py-2 text-right"><a class="text-indigo-600" href="{{ route('supervisor.conversation',$c) }}">Open →</a></td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No conversations yet. Once numbers are connected via Meta Coexistence, messages will appear here.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection

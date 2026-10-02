@extends('layouts.app')
@section('title','Setup Guide')
@section('heading','Setup Guide — connect a WhatsApp number')
@section('content')

<div class="space-y-6 max-w-3xl">

    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4">
        <h2 class="font-semibold mb-2">Your webhook details (you'll paste these into Meta)</h2>
        <div class="text-sm space-y-2">
            <div>
                <div class="text-slate-500">Callback URL</div>
                <code class="block bg-white border rounded px-3 py-2 break-all">{{ $webhookUrl }}</code>
            </div>
            <div>
                <div class="text-slate-500">Verify token</div>
                <code class="block bg-white border rounded px-3 py-2 break-all">{{ $verifyToken }}</code>
            </div>
            <p class="text-slate-500">Subscribe the webhook to the <strong>messages</strong> field.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold mb-3">What you need from Meta (per number)</h2>
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500 border-b"><tr><th class="py-2">Value</th><th class="py-2">Where to get it</th></tr></thead>
            <tbody class="divide-y">
                <tr><td class="py-2 font-medium">Phone number ID</td><td class="py-2">developers.facebook.com → your App → <strong>WhatsApp → API Setup</strong></td></tr>
                <tr><td class="py-2 font-medium">WhatsApp Business Account (WABA) ID</td><td class="py-2">same <strong>API Setup</strong> page</td></tr>
                <tr><td class="py-2 font-medium">Permanent access token</td><td class="py-2">Business Settings → <strong>System Users</strong> → create a system user → <strong>Generate token</strong> with <code>whatsapp_business_messaging</code> + <code>whatsapp_business_management</code>. Use a <strong>permanent</strong> token (the temporary one expires in 24h).</td></tr>
                <tr><td class="py-2 font-medium">App Secret</td><td class="py-2">your App → <strong>Settings → Basic</strong> (paste into <code>WHATSAPP_APP_SECRET</code> in <code>core/.env</code>)</td></tr>
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold mb-3">Step by step</h2>
        <ol class="list-decimal list-inside text-sm space-y-2 text-slate-700">
            <li>Create/choose a <strong>Meta Business account</strong> at business.facebook.com.</li>
            <li>At <strong>developers.facebook.com</strong>, create an App and add the <strong>WhatsApp</strong> product.</li>
            <li>Under <strong>WhatsApp → API Setup</strong>, add your business phone number (or migrate an existing WhatsApp Business <em>app</em> number to the Cloud API). Copy the <strong>Phone number ID</strong> and <strong>WABA ID</strong>.</li>
            <li>Create a <strong>System User</strong> with a <strong>permanent access token</strong> (permissions above).</li>
            <li>In your App → <strong>WhatsApp → Configuration → Edit</strong>: set the <strong>Callback URL</strong> and <strong>Verify token</strong> shown at the top of this page, and subscribe to <strong>messages</strong>.</li>
            <li>Put your <strong>App Secret</strong> into <code>core/.env</code> as <code>WHATSAPP_APP_SECRET=…</code> and run <code>php core/artisan optimize:clear</code>.</li>
            <li>Come back here → <a href="{{ route('settings.numbers.create') }}" class="text-indigo-600 underline">Add Number</a> and paste the Phone number ID, WABA ID and token.</li>
            <li>Send a WhatsApp message <strong>to</strong> that number from your phone — it should appear in the Inbox. Reply to confirm.</li>
        </ol>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold mb-3">Connecting more numbers / different accounts</h2>
        <p class="text-sm text-slate-700">Each number carries its own token and WABA, so to connect a completely separate Meta/business account you just <strong>Add Number</strong> again with that account's values. Point every account's webhook to the <strong>same</strong> Callback URL above — messages are routed to the right number automatically. Assign each team's agents only to their numbers under <a href="{{ route('settings.users.index') }}" class="text-indigo-600 underline">Users</a>.</p>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold mb-3">How agents use it on their phone</h2>
        <ol class="list-decimal list-inside text-sm space-y-2 text-slate-700">
            <li>Agent opens <code>{{ url('/') }}</code> on their phone and logs in with the account you create for them under <a href="{{ route('settings.users.index') }}" class="text-indigo-600 underline">Users</a>.</li>
            <li>In the browser menu they tap <strong>“Add to Home Screen”</strong> — it installs like an app (icon, full screen, notifications).</li>
            <li>They see only conversations on the numbers you assigned to them, and reply from there.</li>
        </ol>
    </div>

</div>
@endsection

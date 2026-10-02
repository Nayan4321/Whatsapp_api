<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · Team Inbox</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full bg-slate-100 text-slate-800">
<div class="min-h-full">
    <header class="bg-slate-900 text-white">
        <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-14">
            <div class="flex items-center gap-2 font-semibold">
                <span>👁️</span><span class="hidden sm:inline">WhatsApp Monitor</span>
            </div>
            <nav class="flex items-center gap-1 text-sm">
                @auth
                    @if(auth()->user()->isSupervisor())
                        <a href="{{ route('supervisor.dashboard') }}" class="px-3 py-1.5 rounded hover:bg-white/10">Dashboard</a>
                        <a href="{{ route('supervisor.conversations') }}" class="px-3 py-1.5 rounded bg-indigo-500 hover:bg-indigo-600">Conversations</a>
                    @endif
                    @if(auth()->user()->isOwner())
                        <span class="mx-1 text-white/30">|</span>
                        <a href="{{ route('settings.guide') }}" class="px-3 py-1.5 rounded hover:bg-white/10">Setup Guide</a>
                        <a href="{{ route('settings.numbers.index') }}" class="px-3 py-1.5 rounded hover:bg-white/10">Numbers</a>
                        <a href="{{ route('settings.users.index') }}" class="px-3 py-1.5 rounded hover:bg-white/10 hidden md:inline">Viewers</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="inline ml-1">@csrf
                        <button class="px-3 py-1.5 rounded hover:bg-white/10">Logout</button>
                    </form>
                @endauth
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-indigo-50 text-indigo-700 px-4 py-2 text-sm">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 text-red-700 px-4 py-2 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold">@yield('heading', View::yieldContent('title'))</h1>
            @yield('actions')
        </div>

        @yield('content')
    </main>
</div>
</body>
</html>

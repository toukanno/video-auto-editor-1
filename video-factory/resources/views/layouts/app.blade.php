<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'VideoFactory AI') }} - @yield('title', 'ダッシュボード')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="{{ route('videos.index') }}" class="text-xl font-bold text-indigo-600">VideoFactory AI</a>
                    <div class="hidden sm:flex space-x-4">
                        <a href="{{ route('videos.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('videos.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:text-gray-900' }}">動画一覧</a>
                        <a href="{{ route('videos.create') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('videos.create') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:text-gray-900' }}">アップロード</a>
                        <a href="{{ route('renders.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('renders.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:text-gray-900' }}">レンダリング</a>
                        <a href="{{ route('caption-styles.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('caption-styles.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:text-gray-900' }}">テロップ設定</a>
                        <a href="{{ route('settings.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('settings.*') ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:text-gray-900' }}">設定 / ログ</a>
                    </div>
                </div>
                <div class="flex items-center"><span class="text-sm text-gray-500">{{ Auth::user()->name ?? 'Demo User' }}</span></div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <div class="mb-6 rounded-md bg-green-50 p-4" x-data="{ show: true }" x-show="show"><div class="flex"><div class="ml-3"><p class="text-sm font-medium text-green-800">{{ session('success') }}</p></div><div class="ml-auto"><button @click="show = false" class="text-green-500 hover:text-green-700">&times;</button></div></div></div>
        @endif
        @if($errors->any())
            <div class="mb-6 rounded-md bg-red-50 p-4"><ul class="list-disc list-inside text-sm text-red-800">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @yield('content')
    </main>
</body>
</html>

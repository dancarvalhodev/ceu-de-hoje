<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Céu de Hoje</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100">
    <header class="border-b border-slate-800">
        <nav class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
            <a href="{{ route('home') }}" class="text-lg font-semibold">Céu de Hoje</a>

            <div class="flex items-center gap-4 text-sm">
                @guest
                    <a href="{{ route('login') }}" class="hover:underline">Entrar</a>
                    <a href="{{ route('register') }}" class="hover:underline">Criar conta</a>
                @endguest

                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hover:underline">Sair</button>
                    </form>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-10">
        {{ $slot }}
    </main>
</body>
</html>

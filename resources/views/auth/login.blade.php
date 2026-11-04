<x-layout>
    <h1 class="text-2xl font-bold">Entrar</h1>

    <form method="POST" action="{{ route('login.store') }}" class="mt-6 max-w-sm space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm">E-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                class="mt-1 w-full rounded border border-slate-700 bg-slate-900 px-3 py-2">
            @error('email')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm">Senha</label>
            <input id="password" name="password" type="password" required
                class="mt-1 w-full rounded border border-slate-700 bg-slate-900 px-3 py-2">
            @error('password')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="rounded bg-indigo-600 px-4 py-2 font-semibold hover:bg-indigo-500">
            Entrar
        </button>
    </form>
</x-layout>

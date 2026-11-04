<x-layout>
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Seus locais</h1>
        <a href="{{ route('locations.create') }}" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold hover:bg-indigo-500">
            Novo local
        </a>
    </div>

    @if ($locations->isEmpty())
        <p class="mt-6 text-slate-300">Você ainda não cadastrou nenhum lugar de onde observa o céu.</p>
    @else
        <ul class="mt-6 divide-y divide-slate-800">
            @foreach ($locations as $location)
                <li class="flex items-center justify-between py-3">
                    <div>
                        <p class="font-semibold">{{ $location->name }}</p>
                        <p class="text-sm text-slate-400">
                            {{ $location->latitude }}, {{ $location->longitude }} · {{ $location->timezone }}
                        </p>
                    </div>

                    <div class="flex items-center gap-4 text-sm">
                        <a href="{{ route('locations.edit', $location) }}" class="hover:underline">Editar</a>

                        <form method="POST" action="{{ route('locations.destroy', $location) }}"
                            onsubmit="return confirm('Excluir este local?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:underline">Excluir</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-layout>

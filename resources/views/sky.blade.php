<x-layout>
    <h1 class="text-2xl font-bold">Céu agora</h1>

    @if ($locations->isEmpty())
        <p class="mt-6 text-slate-300">
            Você ainda não cadastrou nenhum lugar de onde observa o céu.
            <a href="{{ route('locations.create') }}" class="text-indigo-400 hover:underline">Cadastrar um local</a>
        </p>
    @else
        <form method="GET" action="{{ route('sky') }}" class="mt-6 flex items-end gap-2">
            <div class="flex-1">
                <label for="location" class="block text-sm">Local</label>
                <select id="location" name="location"
                    class="mt-1 w-full rounded border border-slate-700 bg-slate-900 px-3 py-2">
                    @foreach ($locations as $option)
                        <option value="{{ $option->id }}" @selected($option->is($location))>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold hover:bg-indigo-500">
                Ver
            </button>
        </form>

        @if ($score === null)
            <p class="mt-8 rounded border border-amber-700 bg-amber-950 px-4 py-3 text-amber-300">
                Previsão indisponível agora, tente de novo em alguns minutos.
            </p>
        @else
            <div class="mt-8 rounded border border-slate-800 p-6 text-center">
                <p class="text-sm text-slate-400">Pontuação de observabilidade</p>
                <p class="mt-2 text-6xl font-bold">{{ number_format($score, 1, ',', '') }}</p>
                <p class="mt-1 text-sm text-slate-400">de 0 a 10</p>
            </div>
        @endif

        <dl class="mt-6 grid grid-cols-2 gap-4">
            @if ($cloudCover !== null)
                <div class="rounded border border-slate-800 p-4">
                    <dt class="text-sm text-slate-400">Nuvens agora</dt>
                    <dd class="mt-1 text-2xl font-semibold">{{ $cloudCover }}%</dd>
                </div>
            @endif
            <div class="rounded border border-slate-800 p-4">
                <dt class="text-sm text-slate-400">Lua</dt>
                <dd class="mt-1 text-2xl font-semibold">{{ $moonName }}</dd>
                <dd class="text-sm text-slate-400">{{ round($moonIllumination * 100) }}% iluminada</dd>
            </div>
        </dl>
    @endif
</x-layout>

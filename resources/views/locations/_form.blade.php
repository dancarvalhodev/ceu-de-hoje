@csrf

<div>
    <label for="name" class="block text-sm">Nome</label>
    <input id="name" name="name" type="text" value="{{ old('name', $location->name) }}" required autofocus
        placeholder="Ex.: Sítio em Cunha"
        class="mt-1 w-full rounded border border-slate-700 bg-slate-900 px-3 py-2">
    @error('name')
        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
    @enderror
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="latitude" class="block text-sm">Latitude</label>
        <input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" required
            value="{{ old('latitude', $location->latitude) }}" placeholder="-23.074"
            class="mt-1 w-full rounded border border-slate-700 bg-slate-900 px-3 py-2">
        @error('latitude')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="longitude" class="block text-sm">Longitude</label>
        <input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" required
            value="{{ old('longitude', $location->longitude) }}" placeholder="-44.959"
            class="mt-1 w-full rounded border border-slate-700 bg-slate-900 px-3 py-2">
        @error('longitude')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>

<div>
    <button type="button" data-geolocation class="text-sm text-indigo-400 hover:underline">
        Usar minha localização
    </button>
    <p data-geolocation-status class="mt-1 text-sm text-slate-400"></p>
</div>

<div>
    <label for="timezone" class="block text-sm">Fuso horário</label>
    <select id="timezone" name="timezone" required
        class="mt-1 w-full rounded border border-slate-700 bg-slate-900 px-3 py-2">
        @foreach (DateTimeZone::listIdentifiers() as $timezone)
            <option value="{{ $timezone }}" @selected(old('timezone', $location->timezone) === $timezone)>
                {{ $timezone }}
            </option>
        @endforeach
    </select>
    @error('timezone')
        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
    @enderror
</div>

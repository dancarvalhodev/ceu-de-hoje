<x-mail::message>
# O céu está bom hoje

Em **{{ $location->name }}**, a pontuação de observabilidade agora é
**{{ number_format($score, 1, ',', '') }}** de 10.

- Nuvens: {{ $cloudCover }}%
- Lua: {{ $moonName }}, {{ round($moonIllumination * 100) }}% iluminada

<x-mail::button :url="route('sky', ['location' => $location->id])">
Ver o céu agora
</x-mail::button>

Bons céus,<br>
{{ config('app.name') }}
</x-mail::message>

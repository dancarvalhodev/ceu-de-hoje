<x-layout>
    <h1 class="text-2xl font-bold">Editar local</h1>

    <form method="POST" action="{{ route('locations.update', $location) }}" class="mt-6 max-w-md space-y-4">
        @method('PUT')
        @include('locations._form')

        <button type="submit" class="rounded bg-indigo-600 px-4 py-2 font-semibold hover:bg-indigo-500">
            Salvar
        </button>
    </form>
</x-layout>

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $locations = $request->user()->locations()->orderBy('name')->get();

        return view('locations.index', ['locations' => $locations]);
    }

    public function create(): View
    {
        $location = new Location(['timezone' => 'America/Sao_Paulo']);

        return view('locations.create', ['location' => $location]);
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $request->user()->locations()->create($request->validated());

        return redirect()->route('locations.index')->with('status', 'Local cadastrado.');
    }

    public function edit(Location $location): View
    {
        return view('locations.edit', ['location' => $location]);
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $location->update($request->validated());

        return redirect()->route('locations.index')->with('status', 'Local atualizado.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return redirect()->route('locations.index')->with('status', 'Local excluído.');
    }
}

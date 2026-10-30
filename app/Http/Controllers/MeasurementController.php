<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeasurementRequest;
use App\Http\Requests\UpdateMeasurementRequest;
use App\Models\Measurement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class MeasurementController extends Controller
{
    /**
     * Show the measurement history and the form.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Measurements/Index', [
            'measurements' => $user->measurements()->paginate(12)->withQueryString(),
            'current' => $user->currentMeasurement(),
        ]);
    }

    /**
     * Store a new measurement for the authenticated user.
     */
    public function store(StoreMeasurementRequest $request): RedirectResponse
    {
        $request->user()->measurements()->create($request->validated());

        return Redirect::route('measurements.index')
            ->with('success', 'Measurement saved.');
    }

    /**
     * Update an existing measurement.
     */
    public function update(UpdateMeasurementRequest $request, Measurement $measurement): RedirectResponse
    {
        $this->authorize('update', $measurement);

        $measurement->update($request->validated());

        return Redirect::route('measurements.index')
            ->with('success', 'Measurement updated.');
    }
}

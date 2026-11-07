<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnergyLogRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnergyLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = $request->user()->energyLogs()
            ->orderByDesc('date')
            ->paginate(30);

        return view('energy.index', compact('logs'));
    }

    /**
     * Upsert the user's energy level for a given day (one log per day).
     */
    public function store(StoreEnergyLogRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $log = $request->user()->energyLogs()->whereDate('date', $data['date'])->first();
        $log
            ? $log->update(['energy_level' => $data['energy_level']])
            : $request->user()->energyLogs()->create($data);

        return back()->with('success', 'Energy level saved.');
    }

    public function destroy(Request $request, int $log): RedirectResponse
    {
        $request->user()->energyLogs()->whereKey($log)->delete();

        return back()->with('success', 'Log removed.');
    }
}

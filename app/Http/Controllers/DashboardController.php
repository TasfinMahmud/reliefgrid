<?php

namespace App\Http\Controllers;

use App\Dashboard\WidgetRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The dashboard is assembled from the widgets each feature registers in
 * app/Dashboard/Widgets (see App\Dashboard\WidgetRegistry).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $widgets = WidgetRegistry::all();

        $cards = $widgets->flatMap(fn ($w) => $w->cards())->values();
        $actions = $widgets->flatMap(fn ($w) => $w->actions())
            ->filter(fn ($a) => $request->user()->can($a[3], $a[4]))
            ->values();
        $panels = $widgets->filter(fn ($w) => $w->view() !== null)->values();

        return view('dashboard', compact('cards', 'actions', 'panels'));
    }
}

<?php

namespace App\Dashboard;

use Illuminate\Support\Collection;

/**
 * Discovers every widget class in app/Dashboard/Widgets.
 */
class WidgetRegistry
{
    /** @return Collection<int, Widget> */
    public static function all(): Collection
    {
        $widgets = collect(glob(app_path('Dashboard/Widgets/*.php')) ?: [])
            ->map(fn (string $file) => 'App\\Dashboard\\Widgets\\'.basename($file, '.php'))
            ->filter(fn (string $class) => class_exists($class) && is_subclass_of($class, Widget::class))
            ->map(fn (string $class) => app($class))
            ->sortBy(fn (Widget $widget) => $widget->order())
            ->values();

        return $widgets;
    }
}

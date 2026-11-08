<?php

namespace App\Dashboard;

/**
 * A dashboard widget contributed by one feature.
 *
 * Each feature ships its own widget class in app/Dashboard/Widgets; the
 * WidgetRegistry discovers them automatically, so adding a feature never
 * requires editing the dashboard controller or the dashboard view.
 */
interface Widget
{
    /** Sort order on the dashboard (lower first). */
    public function order(): int;

    /**
     * Stat cards: each entry is [label, value, url, sub-text].
     *
     * @return array<int, array{0:string,1:int|string,2:string,3:string}>
     */
    public function cards(): array;

    /**
     * Quick-action buttons for the page header: each entry is
     * [label, url, css-class, policy-ability, policy-model-class].
     *
     * @return array<int, array{0:string,1:string,2:string,3:string,4:string}>
     */
    public function actions(): array;

    /** Blade view for the widget panel, or null for cards only. */
    public function view(): ?string;

    /** Bootstrap column width (1–12) for the panel. */
    public function width(): int;

    /** @return array<string,mixed> data passed to the panel view */
    public function data(): array;
}

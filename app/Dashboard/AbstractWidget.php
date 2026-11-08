<?php

namespace App\Dashboard;

abstract class AbstractWidget implements Widget
{
    public function actions(): array
    {
        return [];
    }

    public function view(): ?string
    {
        return null;
    }

    public function width(): int
    {
        return 6;
    }

    public function data(): array
    {
        return [];
    }
}

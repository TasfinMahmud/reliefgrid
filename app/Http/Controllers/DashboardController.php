<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $stats = [
            'users_active' => User::where('is_active', true)->count(),
            'blocks' => Block::count(),
            'items' => Item::active()->count(),
        ];

        return view('dashboard', compact('stats'));
    }
}

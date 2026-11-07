<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Household;
use App\Models\Item;
use App\Models\Token;
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
            'households_active' => Household::active()->count(),
            'households_total' => Household::count(),
            'tokens_active' => Token::where('status', 'active')->count(),
        ];

        $recentHouseholds = Household::with('block')->orderByDesc('id')->limit(8)->get();

        return view('dashboard', compact('stats', 'recentHouseholds'));
    }
}

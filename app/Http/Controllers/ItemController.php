<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Item::class);

        return view('items.index', ['items' => Item::orderBy('category')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        $this->authorize('create', Item::class);

        return view('items.form', ['item' => new Item(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Item::class);
        $item = Item::create($this->validated($request));

        return redirect()->route('items.index')->with('success', "Item {$item->name} created.");
    }

    public function edit(Item $item): View
    {
        $this->authorize('update', $item);

        return view('items.form', ['item' => $item]);
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $this->authorize('update', $item);
        $item->update($this->validated($request, $item));

        return redirect()->route('items.index')->with('success', "Item {$item->name} updated.");
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?Item $item = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('items', 'code')->ignore($item?->id)],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(array_keys(Item::CATEGORIES))],
            'unit' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Block;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BlockController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Block::class);

        $blocks = Block::query()->orderBy('code')->get();

        return view('blocks.index', ['blocks' => $blocks]);
    }

    public function create(): View
    {
        $this->authorize('create', Block::class);

        return view('blocks.form', ['block' => new Block]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Block::class);
        $block = Block::create($this->validated($request));

        return redirect()->route('blocks.index')->with('success', "Block {$block->code} created.");
    }

    public function edit(Block $block): View
    {
        $this->authorize('update', $block);

        return view('blocks.form', ['block' => $block]);
    }

    public function update(Request $request, Block $block): RedirectResponse
    {
        $this->authorize('update', $block);
        $block->update($this->validated($request, $block));

        return redirect()->route('blocks.index')->with('success', "Block {$block->code} updated.");
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?Block $block = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('blocks', 'code')->ignore($block?->id)],
            'name' => ['required', 'string', 'max:100'],
            'camp' => ['nullable', 'string', 'max:100'],
            'sector' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'estimated_population' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}

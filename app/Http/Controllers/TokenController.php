<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Token;
use App\Services\HouseholdService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Feature 1 – Token issuance, reissue, revocation and the printable QR card.
 */
class TokenController extends Controller
{
    public function __construct(private readonly HouseholdService $service) {}

    /** Issue a first token (or a token after reactivation). */
    public function store(Request $request, Household $household): RedirectResponse
    {
        $this->authorize('issueToken', $household);
        $token = $this->service->issueToken($household, $request->user());

        return redirect()->route('tokens.card', $token)->with('success', "Token {$token->shortUid()} issued.");
    }

    /** Replace a lost/damaged token. The old one is kept in history. */
    public function reissue(Request $request, Household $household): RedirectResponse
    {
        $this->authorize('reissueToken', $household);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'outcome' => ['required', 'in:lost,revoked'],
        ]);

        $token = $this->service->reissueToken($household, $data['reason'], $request->user(), $data['outcome'] === 'lost');

        return redirect()->route('tokens.card', $token)->with('success', "Replacement token {$token->shortUid()} issued.");
    }

    public function revoke(Request $request, Token $token): RedirectResponse
    {
        $this->authorize('revokeToken', $token->household);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $this->service->revokeToken($token, $data['reason'], $request->user());

        return redirect()->route('households.show', $token->household)->with('success', "Token {$token->shortUid()} revoked.");
    }

    /** Printable QR token card. */
    public function card(Token $token): View
    {
        $this->authorize('view', $token->household);
        $token->load(['household.block', 'issuedBy']);

        return view('tokens.card', ['token' => $token]);
    }

    /** Look a token up by its UID (used by the search box; scanning arrives with Feature 3). */
    public function lookup(Request $request): RedirectResponse
    {
        $uid = trim((string) $request->input('token_uid'));
        $token = Token::where('token_uid', $uid)->first();
        if (! $token) {
            return back()->withErrors(['token_uid' => 'No token with that identifier exists.']);
        }

        return redirect()->route('households.show', $token->household)
            ->with('info', "Token {$token->shortUid()} is {$token->status->label()} and belongs to household {$token->household->code}.");
    }
}

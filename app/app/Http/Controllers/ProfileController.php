<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = Auth::user();

        return view('profile.edit', [
            'user' => $user,
            'stats' => [
                'conversations' => $user->conversations()->count(),
                'orders' => $user->orders()->count(),
                'price_alerts' => $user->priceAlerts()->where('is_active', true)->count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($validated);

        return back()->with('status', 'Profile updated.');
    }

    /**
     * Kept separate from update() so a mistyped current password only
     * fails the password form, not the name/email one sitting above it -
     * the two are unrelated actions in the same view.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('status', 'Password changed.');
    }

    /**
     * A checkbox, not a required field - an unchecked box simply omits the
     * key from the request body, so its absence (not an explicit "false")
     * is what turns the preference off.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        Auth::user()->update([
            'include_international_stores' => $request->boolean('include_international_stores'),
        ]);

        return back()->with('status', 'Search preferences updated.');
    }
}

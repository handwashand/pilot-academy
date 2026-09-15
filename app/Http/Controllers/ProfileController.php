<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A signed-in person's own profile on the student site.
 *
 * It edits what belongs to the person: their name, email, the name printed on
 * future certificates, and their password. Partner company and role are set
 * *about* somebody by an administrator, so they are shown here but never
 * editable.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('academy.profile', [
            'user' => $request->user()->load('company'),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'certificate_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user->forceFill([
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            // Empty means "print my name", so store nothing rather than a blank.
            'certificate_name' => filled($data['certificate_name'] ?? null) ? trim($data['certificate_name']) : null,
        ])->save();

        return redirect()->route('academy.profile')->with('profile_saved', 'details');
    }

    /**
     * Change a password, or set a first one.
     *
     * Someone who joined by invite link has a random password nobody knows, so
     * asking for their "current password" would lock them out of ever setting
     * one. They are already signed in — the personal link is full access — so
     * setting a first password without it grants nothing new. Once a password
     * has been set, changing it needs the current one.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $rules = ['password' => ['required', 'string', 'min:8', 'confirmed']];

        if ($user->hasOwnPassword()) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        // Its own error bag: the details form on the same page must not show
        // these errors.
        $data = $request->validateWithBag('password', $rules);

        $user->forceFill([
            'password' => $data['password'],
            'password_set_at' => now(),
        ])->save();

        return redirect()->to(route('academy.profile').'#password')->with('profile_saved', 'password');
    }
}

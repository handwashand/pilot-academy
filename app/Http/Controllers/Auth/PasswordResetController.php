<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * "Forgot password?", for partners and staff alike: both login pages send
 * people here.
 *
 * Nothing on these pages says whether an address has an account. Otherwise
 * anyone could type addresses one at a time and learn which partners are
 * customers — so an unknown address gets the same answer as a known one, and
 * the form never validates that the address exists.
 *
 * The links themselves are Laravel's: hashed in the database, good for an hour
 * (config/auth.php), one use each, and one per minute per person.
 */
class PasswordResetController extends Controller
{
    public function showRequest()
    {
        return view('academy.auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        // Format only. `exists:users` would leak through a validation error
        // exactly what the shared message above is careful not to say.
        $data = $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($data);

        return back()->with('status', __t('auth.forgot.sent'));
    }

    public function showReset(Request $request, string $token)
    {
        return view('academy.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($data, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'password_set_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            // Expired, already used, tampered with: one answer for all of them.
            return back()->withErrors(['email' => __t('auth.reset.invalid')]);
        }

        return redirect()->route('login')->with('status', __t('auth.reset.done'));
    }
}

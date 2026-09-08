<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentAuthController extends Controller
{
    public function showLogin()
    {
        return view('academy.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();
        $request->user()->recordLogin();

        return redirect()->intended(route('academy.home'));
    }

    public function showRegister()
    {
        return view('academy.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => User::ROLE_LEARNER,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->recordLogin();

        return redirect()->route('academy.home');
    }

    /**
     * "Try" / invite link: ask for a name + email, then create (or resume)
     * a passwordless student account and sign in.
     */
    public function showJoin()
    {
        return view('academy.auth.join');
    }

    public function join(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user && ! $user->isLearner()) {
            throw ValidationException::withMessages([
                'email' => 'This email belongs to a staff account — please use Log in.',
            ]);
        }

        if (! $user) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Str::random(40), // passwordless; access via link
                'role' => User::ROLE_LEARNER,
            ]);
            $user->ensureLoginToken();
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $user->recordLogin();

        return redirect()->route('academy.home');
    }

    /**
     * Personal magic link: sign in by a per-user access token.
     */
    public function enter(Request $request, string $token)
    {
        $user = User::where('login_token', $token)->first();

        abort_unless($user, 404);

        Auth::login($user, true);
        $request->session()->regenerate();
        $user->recordLogin();

        return redirect()->route('academy.home');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('academy.home');
    }
}

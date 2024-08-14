<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Traits\ActionLoggable;
 
class LoginController extends Controller
{
    use ActionLoggable;

    /**
     * Handle an authentication attempt.
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $this->logAction('The user login.', ['user_id' => auth()->id()], 'info');
            return redirect()->intended('home');
        }
 
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $this->logAction('The user logout.', ['user_id' => auth()->id()], 'info');
        return redirect('/login');
    }
}
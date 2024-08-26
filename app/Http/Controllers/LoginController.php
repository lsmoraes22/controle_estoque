<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Traits\ActionLoggable;
use App\Models\SectorPermission;
use App\Models\Permission;
 
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
            $SectorPermission = SectorPermission::where('sector_id', auth()->user()->sector_id)
            ->whereIn('read_write', ['R','W'])
            ->where('level', '<=', auth()->user()->level)->get()->toArray();
            $listSectorPermission = [];
            foreach($SectorPermission as $k=>$v){ $listSectorPermission[]=$v["permission_id"]; }
            $Permission = Permission::whereIn('id',$listSectorPermission)->get()->toArray();
            $sessionPermission = [];
            foreach($Permission as $k=>$v){
                $sessionPermission[$v["menu"]]['icon_menu']  = $v['icon_menu'];
                $sessionPermission[$v["menu"]]['menu_label'] = $v['menu_label'];
                $sessionPermission[$v["menu"]]['menu'] = $v['menu'];
                $sessionPermission[$v["menu"]]['routes'][] = [
                    'route_label' => $v['route_label'],
                    'icon_route' => $v['icon_route'],
                    'route' => $v['route']
                ];
            }
            session()->put('permissions', $sessionPermission);
            return redirect()->intended('home');
        }
 
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $authLogout = auth()->id();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $this->logAction('The user logout.', ['user_id' => $authLogout], 'info');
        return redirect('/login');
    }
}
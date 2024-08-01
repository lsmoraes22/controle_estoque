<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSectorPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !$user->sector) {
            session()->flash('message', 'Your user does not have access to this location!');
            return redirect('/home');
        }

        // Obtendo o nome da rota ou a URI da rota
        $routeName = $request->route()->getName();
        $routeUri = $request->route()->uri();

        // Verificando a permissão com base na URI ou no nome da rota
        $permission = $user->sector->permissions()
            ->where(function ($query) use ($routeUri, $routeName) {
                $query->where('permissions.route', $routeUri)
                      ->orWhere('permissions.route', $routeName);
            })
            ->wherePivot('enabled', true)
            ->first(); 

        if (!$permission || $permission->pivot->level < $user->level) {
            session()->flash('errorMessage', 'Your user does not have access to this location!');
            return redirect('/home');
        }

        // Armazenar na sessão persistente
        session()->put('ReadWriteSession', $permission->pivot->read_write);

        return $next($request);
    }
}

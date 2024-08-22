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

        // Buscar as permissões de leitura e escrita 
        $permissions = $user->sector->permissions()
        ->where(function ($query) use ($routeUri, $routeName) {
            $query->where('permissions.route', $routeUri)
                ->orWhere('permissions.route', $routeName);
        })
        ->wherePivot('enabled', true)
        ->whereIn('sector_permissions.read_write', ['R', 'W'])
        ->get();

        // Inicializar variáveis para armazenar as permissões
        $readPermission = null;
        $writePermission = null;

        // Separar as permissões de leitura e escrita
        foreach ($permissions as $perm) {
            if ($perm->pivot->read_write === 'R') {
                if($user->level>=$perm->pivot->level){
                    $readPermission = $perm;
                }
            } elseif ($perm->pivot->read_write === 'W') {
                if($user->level>=$perm->pivot->level){
                    $writePermission = $perm;
                }
            }
        }

        // Aplicar a regra de prioridade
        //se a permissao de Leitura for maior do
        //sobrepor a escrita se form maior
        if(!isset($readPermission->pivot->level)) $readPermission = $writePermission ?? false;
        if(!isset($writePermission->pivot->read_write)) $writePermission = $readPermission ?? false;

        if ($readPermission->pivot->level > $writePermission->pivot->level ) {
            $writePermission = $readPermission; 
        }

        // Verificar se o usuário tem nível suficiente para acessar a rota
        if (!$readPermission || $readPermission->pivot->level >= $user->level) {
            session()->flash('messageError', 'Your user does not have access to this location!');
            return redirect('/home');
        }
        
        // Armazenar na sessão persistente
        session()->put('ReadWriteSession', $writePermission->pivot->read_write);

        return $next($request);
    }
}

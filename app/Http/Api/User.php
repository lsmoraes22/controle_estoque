<?php

namespace App\Http\Api;
use App\Http\Controllers\Controller;
use App\Models\User as U;
use Illuminate\Http\Request;

Class User extends Controller
{
    public function index(Request $request )
    {
        $query = U::query();
        if($request->has('name')){
            $query->where('name', 'LIKE', "%{$request->name}%");
        } 
        if($request->has('email')){
            $query->where('email', 'LIKE', "%{$request->email}%");
        }
        if($request->has('sector')){
            return $query->whereSector_id($request->sector);
        }
        return $query->paginate(perPage:5);
    }
    public function store(Request $request )
    {
        return response()->json(U::create($request->all()), status: 201);
    }
    public function show(int $user)
    {
        $userModel = U::find($user);
        if($userModel===null){
            return response()->json(['message' => 'User not found!'], status: 404);
        }
        return $userModel;
    }

    public function update(int $user, Request $request)
    {
        return U::where('id', $user)->update($request->all());
    }

    public function destroy(int $user){
        U::destroy($user);
        return response()->noContent();
    }

    public function enable(int $user)
    {
        return U::where('id', $user)->update(['enabled'=>true]);
    }

    public function disable(int $user)
    {
        return U::where('id', $user)->update(['enabled' => false]);
    }

}


<?php

namespace App\Http\Api;
use App\Http\Controllers\Controller;
use App\Models\User as U;
use Illuminate\Http\Request;

Class User extends Controller
{
    public function index()
    { 
        return U::all();
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

}


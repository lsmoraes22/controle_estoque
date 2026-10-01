<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \Illuminate\Support\Facades\Auth;
use App\Http\Api;

Route::middleware('auth:sanctum')->group(function(){
    Route::apiResource('/user', Api\User::class);
    Route::patch('/user/{id}/enable', [Api\User::class, 'enable']);
    Route::patch('/user/{id}/disable', [Api\User::class, 'disable']);
    Route::post('/upload/nf/inbound', function (Request $request){
        return Api\XmlUpload::uploadInbound($request);
    });
});

Route::post('/login',function (Request $request){
    $credentials = $request->only(['email','password']);
    if(Auth::attempt($credentials)===false){
        return response()->json(data:'Unauthorized',status:401);
    }
    $user = Auth::user();
    $user->tokens()->delete();
    $token = $user->createToken('token');
    return response()->json($token->plainTextToken);
});
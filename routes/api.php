<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Api;

Route::apiResource('/user', Api\User::class);
Route::patch('/user/{id}/enable', [Api\User::class, 'enable']);
Route::patch('/user/{id}/disable', [Api\User::class, 'disable']);
/*
Route::post('/tokens/create', function (Request $request) {
    $token = $request->user()->createToken($request->token_name);
    return ['token' => $token->plainTextToken];
});
/**/
Route::post('/upload/nf/inbound', function (Request $request){
    Api\XmlUpload::uploadInbound($request);
});

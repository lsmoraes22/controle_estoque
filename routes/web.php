<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;
use App\Livewire as Wire;
use App\Http\Middleware as M;
use Illuminate\Http\Request;

// Rotas protegidas pela autenticação

// Route::prefix('inventory_controller')->group(function (){
    Route::middleware('auth')->group(function () {
        Route::get('/', Wire\Main::class)->name('home');
        Route::get('/home', Wire\Main::class)->name('dashboard');
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        Route::get('/users', Wire\UserManagement::class)->name('users.index')->middleware( M\CheckSectorPermission::class ); 
        Route::get('/sectors', Wire\SectorManagement::class)->name('sectors.index')->middleware(M\CheckSectorPermission::class); 
        Route::get('/permissions', Wire\SectorPermissionManagement::class)->name('permissions.index')->middleware(M\CheckSectorPermission::class );
        Route::get('/suppliers', Wire\SupplierManagement::class)->name('suppliers.index')->middleware(M\CheckSectorPermission::class );
        Route::get('/nfe_xml_default', Wire\NfeXmlDefault::class)->name('nfe_xml_default.index')->middleware(M\CheckSectorPermission::class );
    });
// });

// Rotas de login e logout
Route::middleware('guest')->group(function () {
    Route::get('/login', Wire\Login::class)->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('store');
});


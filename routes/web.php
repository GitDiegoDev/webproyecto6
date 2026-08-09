<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/auto-login', function () {
    $user = \App\Models\User::where('email', 'admin@example.com')->first();
    if (!$user) {
        $user = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
    }
    $user->role = 'administrador';
    $user->save();
    auth()->login($user);
    return redirect('/importar');
});

Route::get('/importar', function () {
    return view('importar');
})->name('importar');

Route::get('/ficha-gestion/{cuotaId}', function ($cuotaId) {
    return view('ficha', ['cuotaId' => $cuotaId]);
})->name('ficha-gestion');

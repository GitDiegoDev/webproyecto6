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

Route::get('/agenda', function () {
    return view('agenda');
})->name('agenda');

Route::get('/mensajes', function () {
    return view('mensajes');
})->name('mensajes');

Route::get('/cobrador', function () {
    return view('cobrador');
})->name('cobrador');

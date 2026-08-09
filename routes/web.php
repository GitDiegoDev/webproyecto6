<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/importar', function () {
    return view('importar');
})->name('importar');

Route::get('/ficha-gestion/{cuotaId}', function ($cuotaId) {
    return view('ficha', ['cuotaId' => $cuotaId]);
})->name('ficha-gestion');

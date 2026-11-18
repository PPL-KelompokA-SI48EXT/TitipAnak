<?php

use App\Http\Controllers\SeederDataController;
use Illuminate\Support\Facades\Route;

Route::get('/data-seeder', SeederDataController::class)->name('data-seeder');

Route::get('/', function () {
    return view('welcome');
});

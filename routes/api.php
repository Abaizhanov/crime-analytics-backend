<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CrimeController;

Route::get('/stats/year', [CrimeController::class, 'statsByYear']);
Route::get('/map', [CrimeController::class, 'map']);

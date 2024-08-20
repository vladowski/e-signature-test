<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::prefix('auth')->post('/login', [AuthenticatedSessionController::class, 'store']);

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoverknowerController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ask', [GoverknowerController::class, 'ask']);
Route::options('/ask', function () {
    return response('', 200);
});

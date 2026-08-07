<?php
use Illuminate\Support\Facades\Route;
Route::get('/', fn() => response()->json(['service' => 'JPStore API', 'status' => 'running', 'version' => '1.0']));

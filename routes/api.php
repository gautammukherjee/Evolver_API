<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\api\NodeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [UserController::class, 'login']);
// Route::post('/login','App\Http\Controllers\api\UserController@login');

Route::get('/getNodeSelects', [NodeController::class, 'getNodeSelects']);
Route::post('/getSourceNode', [NodeController::class, 'getSourceNode']);
Route::post('/getDestinationNode', [NodeController::class, 'getDestinationNode']);
Route::post('/getMasterLists', [NodeController::class, 'getMasterLists']);
Route::get('/getEdgeType', [NodeController::class, 'getEdgeType']);
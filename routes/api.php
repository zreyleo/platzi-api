<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('sanctum/token', 'UserTokenController');

Route::apiResource('products', 'ProductController')
    ->middleware('auth:sanctum');

Route::apiResource('categories', 'CategoryController')
    ->middleware('auth:sanctum');

Route::post('newsletter', 'NewsletterController@send');

Route::post('rate/products/{product}', 'RatingController@rateProduct')
    ->middleware('auth:sanctum');

Route::post('rate/users/{user}', 'RatingController@rateUser')
    ->middleware('auth:sanctum');

Route::get('server-error', function () {
    abort(500);
});


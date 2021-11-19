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

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', 'ProductController');

    Route::apiResource('categories', 'CategoryController');

    Route::post('newsletter', 'NewsletterController@send');

    Route::post('rate/products/{product}', 'RatingController@rateProduct');

    Route::post('rate/users/{user}', 'RatingController@rateUser');

    Route::post('rating/{rating}/approve', 'RatingController@approve');

    Route::get('rating', 'RatingController@list');
});

Route::post('sanctum/token', 'UserTokenController');

Route::get('server-error', function () {
    abort(500);
});



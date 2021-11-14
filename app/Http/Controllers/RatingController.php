<?php

namespace App\Http\Controllers;

use App\Product;
use App\User;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function rateProduct(Request $request, Product $product)
    {
        $request->validate([
            'score' => 'required'
        ]);

        /** @var User $user */
        $user = auth()->user();

        $user->rate($product, $request->get('score'));

        return response()->json([
            'data' => 'Todo ok'
        ]);
    }

    public function rateUser(Request $request, User $user)
    {
        $request->validate([
            'score' => 'required'
        ]);

        /** @var User $user */
        $user = auth()->user();

        $user->rate($user, $request->get('score'));

        return response()->json([
            'data' => 'Todo ok'
        ]);
    }
}

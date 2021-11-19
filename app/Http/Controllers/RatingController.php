<?php

namespace App\Http\Controllers;

use App\Http\Resources\RatingResource;
use App\Product;
use App\Rating;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

    public function approve(Rating $rating)
    {
        Gate::authorize('admin', $rating);

        $rating->approve();
        $rating->save();

        return response()->json();
    }

    public function list(Request $request)
    {
        $builder = Rating::query();

        if ($request->has('approved')) {
            $builder->whereNotNull('approved_at');
        } else if ($request->has('notApproved')) {
            $builder->whereNull('approved_at');
        }

        return RatingResource::collection($builder->get());
    }
}

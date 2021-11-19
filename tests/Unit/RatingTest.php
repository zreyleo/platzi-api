<?php

namespace Tests\Unit;

use App\Product;
use App\Rating;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_belongs_to_many_users()
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Product $product */
        $product = factory(Product::class)->create();

        $user->rate($product, 5);

        // dd($user->ratings()->get());
        // dd($product->ratings()->get())

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->ratings(Product::class)->get());
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $product->qualifiers(User::class)->get());
    }

    public function test_averageRating()
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var User $user2 */
        $user2 = factory(User::class)->create();
        /** @var Product $product */
        $product = factory(Product::class)->create();

        $user->rate($product, 5);
        $user2->rate($product, 5);

        $this->assertEquals(5, $product->averageRating(User::class));
    }

    public function test_rating_model()
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Product $product */
        $product = factory(Product::class)->create();

        $user->rate($product, 5);

        /** @var Rating $rating */
        $rating = Rating::first();

        $this->assertInstanceOf(User::class, $rating->qualifier);
        $this->assertInstanceOf(Product::class, $rating->rateable);
    }

    public function test_user_can_rate_user()
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Product $product */
        $user2 = factory(User::class)->create();

        $user->rate($user2, 5);

        /** @var Rating $rating */
        $rating = Rating::all()->last();

        $this->assertInstanceOf(User::class, $rating->rateable);
        $this->assertInstanceOf(User::class, $rating->qualifier);
    }
}

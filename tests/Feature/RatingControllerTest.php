<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

use App\Product;
use App\Rating;
use App\User;

class RatingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(
            factory(User::class)->create()
        );
    }

    public function test_user_can_rate_product_using_api()
    {
        $product = factory(Product::class)->create();

        $this->post('api/rate/products/' . $product->id, [
            'score' => 10
        ]);

        $rating = Rating::first();

        $this->assertInstanceOf(Product::class, $rating->rateable);
        $this->assertInstanceOf(User::class, $rating->qualifier);
    }

    public function test_user_can_rate_user_using_api()
    {
        $user = factory(User::class)->create();

        // dd($user);

        $this->post('api/rate/users/' . $user->id, [
            'score' => 10
        ]);

        $rating = Rating::first();

        $this->assertInstanceOf(User::class, $rating->rateable);
        $this->assertInstanceOf(User::class, $rating->qualifier);
    }
}

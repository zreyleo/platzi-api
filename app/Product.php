<?php

namespace App;

use App\Utils\CanBeRated;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use CanBeRated;

    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

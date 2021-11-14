<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

use App\Console\Commands\SendVerficationEmailCommand;

class NewsletterController extends Controller
{
    public function send()
    {
        Artisan::call(SendVerficationEmailCommand::class);

        return response()->json([
            'data' => 'Todo ok'
        ]);
    }
}

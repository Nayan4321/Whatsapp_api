<?php

namespace App\Http\Controllers;

class GuideController extends Controller
{
    public function index()
    {
        return view('settings.guide', [
            'webhookUrl' => url('/webhook/whatsapp'),
            'verifyToken' => config('whatsapp.verify_token'),
        ]);
    }
}

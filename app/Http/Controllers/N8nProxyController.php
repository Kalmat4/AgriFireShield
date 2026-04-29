<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class N8nProxyController extends Controller
{
    private const WEBHOOK_URL = 'https://valtmar.app.n8n.cloud/webhook-test/AgroFireshield';

    public function analyze(Request $request)
    {
        $response = Http::timeout(30)->withoutVerifying()->post(self::WEBHOOK_URL, $request->all());

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type') ?? 'application/json');
    }
}

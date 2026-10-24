<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class FirecrawlController extends Controller
{
    /**
     * Scrape a URL and return clean Markdown.
     */
    public function scrape(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $apiKey = config('services.firecrawl.key');

        if (empty($apiKey)) {
            return response()->json(['error' => 'Firecrawl API key is not configured.'], 500);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.firecrawl.dev/v2/scrape', [
                    'url' => $validated['url'],
                    'formats' => ['markdown'],
                    'onlyMainContent' => true,
                ]);
        } catch (ConnectionException $e) {
            return response()->json(['error' => 'Could not connect to Firecrawl.'], 502);
        }

        if ($response->failed()) {
            return response()->json([
                'error' => 'Firecrawl request failed.',
                'details' => $response->json('error') ?? 'Unknown error',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'markdown' => $response->json('data.markdown'),
            'metadata' => $response->json('data.metadata'),
        ]);
    }
}

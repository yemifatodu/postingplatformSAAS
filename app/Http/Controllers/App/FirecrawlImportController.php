<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Session-authenticated "Import from URL" for the post composer.
 * Scrapes a page through Firecrawl and returns plain text for the editor.
 */
final class FirecrawlImportController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $apiKey = config('services.firecrawl.key');

        if (empty($apiKey)) {
            return response()->json(['message' => 'Import from URL is not configured.'], 503);
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
            return response()->json(['message' => 'Could not reach the import service.'], 502);
        }

        if ($response->failed()) {
            return response()->json(['message' => 'Could not import that page.'], 422);
        }

        $markdown = (string) $response->json('data.markdown', '');
        $title = (string) $response->json('data.metadata.title', '');

        // Drop images and flatten links to their text: the composer is plain text.
        $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $markdown) ?? $markdown;
        $text = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        $text = trim($text);

        if ($text === '') {
            return response()->json(['message' => 'No readable content found on that page.'], 422);
        }

        return response()->json([
            'title' => $title,
            'text' => Str::limit($text, 5000, ''),
            'source' => $validated['url'],
        ]);
    }
}

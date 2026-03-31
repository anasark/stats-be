<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApifyService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('apify.api_key');
        $this->baseUrl = config('apify.base_url');
    }

    public function fetchPlatformStats(string $platform): array
    {
        $actorMap = [
            'tiktok'   => 'clockworks/tiktok-profile-scraper',
            'facebook' => 'apify/facebook-pages-scraper',
        ];

        $actorId = $actorMap[$platform] ?? null;

        if (!$actorId) {
            Log::warning("ApifyService: No actor mapped for platform [{$platform}]");
            return [];
        }

        try {
            $runResponse = Http::withToken($this->apiKey)
                ->post("{$this->baseUrl}/acts/{$actorId}/runs", [
                    'memory' => 256,
                ]);

            if (!$runResponse->successful()) {
                Log::error("ApifyService: Failed to start actor for [{$platform}]", [
                    'status' => $runResponse->status(),
                    'body'   => $runResponse->body(),
                ]);
                return [];
            }

            $runId = $runResponse->json('data.id');

            // Poll until finished (max 60s)
            $maxAttempts = 12;
            $attempt     = 0;
            do {
                sleep(5);
                $statusResponse = Http::withToken($this->apiKey)
                    ->get("{$this->baseUrl}/acts/{$actorId}/runs/{$runId}");
                $status = $statusResponse->json('data.status');
                $attempt++;
            } while (!in_array($status, ['SUCCEEDED', 'FAILED', 'ABORTED']) && $attempt < $maxAttempts);

            if ($status !== 'SUCCEEDED') {
                Log::warning("ApifyService: Actor run did not succeed for [{$platform}]", ['status' => $status]);
                return [];
            }

            $datasetId = $statusResponse->json('data.defaultDatasetId');

            $dataResponse = Http::withToken($this->apiKey)
                ->get("{$this->baseUrl}/datasets/{$datasetId}/items", [
                    'format' => 'json',
                    'clean'  => true,
                ]);

            return $dataResponse->json() ?? [];
        } catch (\Throwable $e) {
            Log::error("ApifyService: Exception for [{$platform}]: " . $e->getMessage());
            return [];
        }
    }
}

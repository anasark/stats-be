<?php

namespace App\Jobs;

use App\Models\ApifyStat;
use App\Models\UserPreference;
use App\Services\ApifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchApifyDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public function handle(ApifyService $apifyService): void
    {
        $platforms = UserPreference::query()
            ->pluck('platforms')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        foreach ($platforms as $platform) {
            try {
                $data = $apifyService->fetchPlatformStats($platform);

                if (!empty($data)) {
                    ApifyStat::create([
                        'platform'   => $platform,
                        'data'       => $data,
                        'fetched_at' => now(),
                    ]);
                    Log::info("FetchApifyDataJob: Stored stats for [{$platform}]");
                }
            } catch (\Throwable $e) {
                Log::error("FetchApifyDataJob: Error for [{$platform}]: " . $e->getMessage());
            }
        }
    }
}

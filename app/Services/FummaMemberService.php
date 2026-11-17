<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FummaMemberService
{
    /**
     * Returns true only when FUMMA answers data.active == "1".
     * Any failure (timeout, non-200, bad JSON) returns false so member creation never breaks.
     */
    public function isActive(string $phone): bool
    {
        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->asJson()
                ->post('https://fumma.co.in/api/memberenable', [
                    'mobile' => $phone,
                ]);

            if (!$response->successful()) {
                Log::warning('FUMMA check failed: HTTP ' . $response->status() . ' for ' . $phone);
                return false;
            }

            return (string) data_get($response->json(), 'data.active') === '1';
        } catch (\Throwable $e) {
            Log::error('FUMMA check error for ' . $phone . ': ' . $e->getMessage());
            return false;
        }
    }
}
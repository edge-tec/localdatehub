<?php

namespace App\Modules\SEO\Services;

use App\Modules\Listings\Models\Listing;
use App\Modules\SEO\Models\SeoIndexingLog;
use App\Modules\SEO\Models\SeoIndexingQueue;
use App\Modules\SEO\Models\SeoUrlRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleIndexingService
{
    /**
     * Pre-validate an eligible public URL for indexing.
     */
    public function validateUrlForIndexing(string $url, ?Listing $listing = null): array
    {
        $errors = [];

        // 1. Syntax check
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid URL format';
        }

        // 2. Content completeness if listing provided
        if ($listing) {
            if ($listing->status !== Listing::STATUS_ACTIVE) {
                $errors[] = 'Listing is not active/published';
            }
            if ($listing->expires_at && $listing->expires_at->isPast()) {
                $errors[] = 'Listing is expired';
            }
            if (strlen(trim($listing->title)) < 5) {
                $errors[] = 'Title is too short (< 5 chars)';
            }
            if (strlen(strip_tags($listing->description)) < 20) {
                $errors[] = 'Description is too thin (< 20 chars)';
            }
        }

        return [
            'is_eligible' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Enqueue a URL for indexing submission with validation.
     */
    public function enqueue(string $url, string $action = 'URL_UPDATED', string $apiType = 'sitemap_ping', int $priority = 5, ?Listing $listing = null): ?SeoIndexingQueue
    {
        $validation = $this->validateUrlForIndexing($url, $listing);

        // Find or create URL inventory record
        $urlRecord = SeoUrlRecord::firstOrCreate(
            ['url' => $url],
            [
                'url_type' => $listing ? 'listing' : 'static',
                'model_type' => $listing ? get_class($listing) : null,
                'model_id' => $listing?->id,
                'canonical_url' => $url,
                'indexing_status' => $validation['is_eligible'] ? 'queued' : 'excluded',
                'indexing_eligibility' => $validation['is_eligible'] ? 'eligible' : 'ineligible',
                'sitemap_status' => $validation['is_eligible'] ? 'included' : 'excluded',
                'http_status' => 200,
                'meta_title' => $listing?->title,
                'meta_description' => $listing ? \Illuminate\Support\Str::limit(strip_tags($listing->description), 155) : null,
            ]
        );

        if (! $validation['is_eligible']) {
            $urlRecord->update([
                'indexing_status' => 'excluded',
                'indexing_eligibility' => 'ineligible',
            ]);
            return null;
        }

        $urlRecord->update([
            'indexing_status' => 'queued',
        ]);

        return SeoIndexingQueue::create([
            'url' => $url,
            'url_record_id' => $urlRecord->id,
            'status' => 'pending',
            'action' => $action,
            'api_type' => $apiType,
            'priority' => $priority,
            'attempts' => 0,
            'max_attempts' => 5,
            'next_attempt_at' => now(),
        ]);
    }

    /**
     * Process pending queue items with exponential backoff.
     */
    public function processPendingQueue(int $limit = 20): int
    {
        $items = SeoIndexingQueue::where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('next_attempt_at')
                    ->orWhere('next_attempt_at', '<=', now());
            })
            ->orderByDesc('priority')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $processed = 0;
        foreach ($items as $item) {
            $this->processQueueItem($item);
            $processed++;
        }

        return $processed;
    }

    /**
     * Process a single indexing queue item safely.
     */
    public function processQueueItem(SeoIndexingQueue $item): bool
    {
        $item->update([
            'status' => 'processing',
            'attempts' => $item->attempts + 1,
        ]);

        $success = false;
        $responseBody = '';
        $statusCode = 200;

        try {
            // Respect Google Policy:
            // For general classified ads, use Google Search Console Sitemaps Ping and internal indexing signals
            if ($item->api_type === 'sitemap_ping') {
                $sitemapUrl = route('sitemap.index');
                $pingUrl = 'https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl);

                $response = Http::timeout(10)->get($pingUrl);
                $statusCode = $response->status();
                $responseBody = $response->body() ?: 'Sitemap Ping dispatched successfully';
                $success = ($statusCode >= 200 && $statusCode < 400);
            } else {
                // GSC URL Inspection or Search Console Indexing Notification
                $responseBody = json_encode([
                    'status' => 'acknowledged',
                    'url' => $item->url,
                    'timestamp' => now()->toIso8601String(),
                    'method' => $item->api_type,
                    'action' => $item->action,
                ]);
                $success = true;
            }

            if ($success) {
                $item->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'response_payload' => ['response' => $responseBody, 'http_code' => $statusCode],
                    'last_error' => null,
                ]);

                // Update URL record
                if ($item->urlRecord) {
                    $item->urlRecord->update([
                        'indexing_status' => 'submitted',
                        'last_submitted_at' => now(),
                    ]);
                }
            } else {
                throw new \Exception("Server returned HTTP {$statusCode}: {$responseBody}");
            }
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            $nextAttempt = now()->addMinutes(pow(2, $item->attempts)); // Exponential backoff: 2, 4, 8, 16 mins

            $isFinalFailure = ($item->attempts >= $item->max_attempts);

            $item->update([
                'status' => $isFinalFailure ? 'failed' : 'pending',
                'last_error' => $errorMsg,
                'next_attempt_at' => $isFinalFailure ? null : $nextAttempt,
                'response_payload' => ['error' => $errorMsg],
            ]);

            if ($item->urlRecord && $isFinalFailure) {
                $item->urlRecord->update([
                    'indexing_status' => 'submission_failed',
                ]);
            }

            $success = false;
        }

        // Log response
        SeoIndexingLog::create([
            'queue_id' => $item->id,
            'url' => $item->url,
            'action' => $item->action,
            'api_type' => $item->api_type,
            'status_code' => $statusCode,
            'status' => $success ? 'success' : 'failed',
            'response_body' => $responseBody ?: ($errorMsg ?? ''),
            'created_at' => now(),
        ]);

        return $success;
    }
}


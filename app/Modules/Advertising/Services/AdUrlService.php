<?php

namespace App\Modules\Advertising\Services;

class AdUrlService
{
    /**
     * Validate that an outbound destination URL uses a safe web scheme (http/https).
     */
    public static function isSafeDestinationUrl(?string $url): bool
    {
        if ($url === null || trim($url) === '') {
            return false;
        }

        $trimmed = trim($url);
        if (!filter_var($trimmed, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }

    /**
     * Validate image source URL: allows local /storage/... paths or safe http(s) URLs.
     */
    public static function isSafeImageUrl(?string $url): bool
    {
        if ($url === null || trim($url) === '') {
            return false;
        }

        $trimmed = trim($url);
        if (str_starts_with($trimmed, '/storage/') && !str_contains($trimmed, '..')) {
            return true;
        }

        return self::isSafeDestinationUrl($trimmed);
    }

    /**
     * Append tracking parameters (e.g., click_id, subid) to an affiliate URL
     * without re-encoding, reordering, or mutating any existing query parameters.
     *
     * @param  array<string, string|int|null>  $params
     */
    public static function buildAffiliateRedirectUrl(string $rawUrl, array $params = []): string
    {
        $filtered = array_filter($params, fn ($v) => $v !== null && $v !== '');
        if ($filtered === []) {
            return $rawUrl;
        }

        $fragment = '';
        $base = $rawUrl;
        $hashPos = strpos($rawUrl, '#');
        if ($hashPos !== false) {
            $base = substr($rawUrl, 0, $hashPos);
            $fragment = substr($rawUrl, $hashPos);
        }

        $existingQuery = (string) parse_url($base, PHP_URL_QUERY);
        $existingKeys = [];
        if ($existingQuery !== '') {
            foreach (explode('&', $existingQuery) as $pair) {
                if ($pair === '') {
                    continue;
                }
                $eqPos = strpos($pair, '=');
                $key = $eqPos === false ? $pair : substr($pair, 0, $eqPos);
                $existingKeys[urldecode($key)] = true;
            }
        }

        $toAppend = [];
        foreach ($filtered as $key => $value) {
            $cleanKey = preg_replace('/[^A-Za-z0-9_\-\.]/', '', (string) $key);
            if ($cleanKey === '' || isset($existingKeys[$cleanKey])) {
                continue;
            }
            $toAppend[] = rawurlencode($cleanKey) . '=' . rawurlencode((string) $value);
        }

        if ($toAppend === []) {
            return $rawUrl;
        }

        $separator = str_contains($base, '?')
            ? (str_ends_with($base, '?') || str_ends_with($base, '&') ? '' : '&')
            : '?';

        return $base . $separator . implode('&', $toAppend) . $fragment;
    }

    /**
     * Sanitize optional HTML embed snippets, stripping script/event handlers if needed
     * or only allowing trusted iframe/img/a markup.
     */
    public static function sanitizeHtmlSnippet(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        // Strip inline JS event handlers (onmouseover=, onclick=, onerror=, etc.) and javascript: URIs
        $cleaned = preg_replace('/\bon[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $cleaned = preg_replace('/javascript\s*:/i', '#blocked:', $cleaned) ?? $cleaned;

        return trim($cleaned);
    }
}


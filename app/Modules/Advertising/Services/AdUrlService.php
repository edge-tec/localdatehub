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
     * Sanitize optional HTML embed snippets using a strict DOM allowlist.
     * Strips scripts, iframes, objects, embeds, SVG, event handlers, inline styles, and dangerous URI schemes.
     */
    public static function sanitizeHtmlSnippet(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $allowedTags = [
            'div', 'span', 'p', 'a', 'strong', 'b', 'em', 'i', 'u', 'br',
            'small', 'ul', 'ol', 'li', 'img', 'picture', 'source',
        ];
        $blockedWithChildren = [
            'script', 'style', 'iframe', 'object', 'embed', 'applet',
            'svg', 'math', 'form', 'input', 'button', 'textarea', 'select',
            'link', 'meta', 'base', 'noscript', 'template',
        ];
        $globalAttrs = ['class', 'id', 'title', 'alt', 'width', 'height', 'loading', 'decoding', 'aria-label', 'aria-hidden'];
        $tagAttrs = [
            'a' => ['href', 'target', 'rel'],
            'img' => ['src', 'srcset'],
            'source' => ['src', 'srcset', 'media', 'type'],
        ];

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $wrapped = '<!DOCTYPE html><html><body><div id="__ldh_sanitize_root__">' . $html . '</div></body></html>';
        $dom->loadHTML(mb_convert_encoding($wrapped, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('__ldh_sanitize_root__');
        if (!$root) {
            return null;
        }

        self::sanitizeDomNode($root, $allowedTags, $blockedWithChildren, $globalAttrs, $tagAttrs);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        $out = trim($out);
        return $out === '' ? null : $out;
    }

    /**
     * Recursively sanitize DOM child nodes in place.
     */
    protected static function sanitizeDomNode(
        \DOMNode $node,
        array $allowedTags,
        array $blockedWithChildren,
        array $globalAttrs,
        array $tagAttrs
    ): void {
        if (!$node->hasChildNodes()) {
            return;
        }

        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMComment) {
                $node->removeChild($child);
                continue;
            }

            if (!$child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, $blockedWithChildren, true)) {
                $node->removeChild($child);
                continue;
            }

            self::sanitizeDomNode($child, $allowedTags, $blockedWithChildren, $globalAttrs, $tagAttrs);

            if (!in_array($tag, $allowedTags, true)) {
                // Unwrap disallowed non-executable wrapper tags (e.g. font/center), preserving sanitized text/children
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            $permitted = array_merge($globalAttrs, $tagAttrs[$tag] ?? []);
            $attrsToRemove = [];
            foreach ($child->attributes as $attr) {
                $attrName = strtolower($attr->nodeName);
                if (str_starts_with($attrName, 'on') || !in_array($attrName, $permitted, true)) {
                    $attrsToRemove[] = $attr->nodeName;
                    continue;
                }

                if (in_array($attrName, ['href', 'src'], true)) {
                    $cleanUrl = self::sanitizeSnippetUrl($attr->nodeValue ?? '');
                    if ($cleanUrl === null) {
                        $attrsToRemove[] = $attr->nodeName;
                    } else {
                        $child->setAttribute($attrName, $cleanUrl);
                    }
                } elseif ($attrName === 'srcset') {
                    // Reject any javascript:/data:/vbscript: inside srcset
                    if (preg_match('/(?:javascript|data|vbscript)\s*:/i', (string) $attr->nodeValue)) {
                        $attrsToRemove[] = $attr->nodeName;
                    }
                }
            }

            foreach ($attrsToRemove as $attrName) {
                $child->removeAttribute($attrName);
            }

            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
        }
    }

    /**
     * Allow only safe http(s) or relative URLs inside HTML snippets.
     */
    protected static function sanitizeSnippetUrl(string $url): ?string
    {
        $trimmed = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($trimmed === '') {
            return null;
        }

        // Strip ASCII control chars & whitespace often used to obfuscate javascript:
        $normalized = preg_replace('/[\x00-\x20\x7f]+/', '', strtolower($trimmed)) ?? '';
        if (str_starts_with($normalized, 'javascript:') || str_starts_with($normalized, 'data:') || str_starts_with($normalized, 'vbscript:')) {
            return null;
        }

        if (str_starts_with($trimmed, '/') && !str_starts_with($trimmed, '//')) {
            return $trimmed;
        }

        $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $trimmed : null;
    }
}


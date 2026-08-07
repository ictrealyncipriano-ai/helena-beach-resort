<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Lightweight allow-list HTML sanitizer for admin-entered rich text
 * (e.g. cottage descriptions that are rendered with {!! !!} on public pages).
 *
 * Only the tags/attributes on the allow-list survive; everything else —
 * <script>, <iframe>, event-handler attributes, javascript:/data: URLs — is
 * stripped (the text content is kept, just de-nested). Output is safe to
 * render with {!! !!}.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'b', 'i', 'u', 's',
        'ul', 'ol', 'li', 'blockquote',
        'h2', 'h3', 'h4', 'h5', 'h6',
        'a', 'img', 'code', 'pre',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title'],
        'img' => ['src', 'alt', 'title'],
    ];

    public function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument('1.0', 'UTF-8');

            // Wrap in a full document declaring UTF-8 so non-ASCII content
            // round-trips correctly; the wrapper is stripped afterwards.
            $document->loadHTML(
                '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'.$html.'</body></html>'
            );
            libxml_clear_errors();

            $xpath = new DOMXPath($document);

            foreach ($xpath->query('//*') as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                $tag = strtolower($node->nodeName);

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    $parent = $node->parentNode;
                    while ($node->firstChild !== null) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                    continue;
                }

                foreach (iterator_to_array($node->attributes) as $attribute) {
                    $name = strtolower($attribute->nodeName);
                    $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

                    if (! in_array($name, $allowed, true)) {
                        $node->removeAttribute($attribute->nodeName);
                        continue;
                    }

                    if (in_array($name, ['href', 'src'], true)
                        && preg_match('#^\s*(javascript|vbscript|data):#i', (string) $attribute->nodeValue)) {
                        $node->removeAttribute($attribute->nodeName);
                    }
                }
            }

            // Serialize and strip the wrapper (doctype, html/head/body).
            $output = $document->saveHTML();
            $output = (string) preg_replace('/^<!DOCTYPE html>\s*/i', '', $output);
            $output = (string) preg_replace('{<html><head>.*?</head><body>}is', '', $output);
            $output = (string) preg_replace('{</body></html>$}is', '', $output);

            return trim($output);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}

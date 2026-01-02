<?php

namespace InSquare\PimcoreSimpleSearchBundle\Service\Text;

class TextNormalizer
{
    public static function normalize(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Strip HTML tags
        $text = strip_tags($text);

        // Remove zero-width characters
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text) ?? $text;

        // Normalize whitespace (replace multiple spaces/newlines with single space)
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        // Trim
        $text = trim($text);

        return $text !== '' ? $text : null;
    }

    /**
     * @param array<int, string|null> $parts
     */
    public static function join(array $parts, string $glue = " "): ?string
    {
        $normalized = array_filter(
            array_map([self::class, 'normalize'], $parts),
            static fn($part): bool => $part !== null
        );

        if (empty($normalized)) {
            return null;
        }

        $joined = implode($glue, $normalized);

        return self::normalize($joined);
    }
}

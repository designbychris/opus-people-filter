<?php

namespace ClientPeopleFilter;

defined('ABSPATH') || exit;

final class FilterState
{
    public static function isActive(): bool
    {
        return isset($_GET['people_filter'])
            && sanitize_text_field(wp_unslash($_GET['people_filter'])) === '1';
    }

    public static function keyword(): string
    {
        if (!isset($_GET['people_q'])) {
            return '';
        }

        return sanitize_text_field(wp_unslash($_GET['people_q']));
    }

    public static function letter(): string
    {
        if (!isset($_GET['people_letter'])) {
            return '';
        }

        $letter = strtoupper(
            substr(
                sanitize_text_field(wp_unslash($_GET['people_letter'])),
                0,
                1
            )
        );

        return preg_match('/^[A-Z]$/', $letter) ? $letter : '';
    }

    public static function taxonomyValue(string $taxonomy): string
    {
        $key = 'people_' . sanitize_key($taxonomy);

        if (!isset($_GET[$key])) {
            return '';
        }

        return sanitize_title(wp_unslash($_GET[$key]));
    }
}

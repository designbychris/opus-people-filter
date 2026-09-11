<?php

namespace ClientPeopleFilter;

defined('ABSPATH') || exit;

final class Assets
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
    }

    public function registerAssets(): void
    {
        wp_register_style(
            'client-people-filter',
            CPF_URL . 'assets/css/people-filter.css',
            [],
            CPF_VERSION
        );

        wp_register_script(
            'client-people-filter',
            CPF_URL . 'assets/js/people-filter.js',
            [],
            CPF_VERSION,
            true
        );
    }
}

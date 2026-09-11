<?php

namespace ClientPeopleFilter;

defined('ABSPATH') || exit;

final class Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        (new Assets())->register();
        (new Shortcode())->register();
        (new PeopleQuery())->register();

        add_filter('body_class', [$this, 'bodyClasses']);
    }

    /**
     * @param string[] $classes
     * @return string[]
     */
    public function bodyClasses(array $classes): array
    {
        if (FilterState::isActive()) {
            $classes[] = 'cpf-filter-active';
        } else {
            $classes[] = 'cpf-filter-inactive';
        }

        return $classes;
    }
}

<?php

namespace ClientPeopleFilter;

use WP_Query;

defined('ABSPATH') || exit;

final class PeopleQuery
{
    /**
     * Elementor Loop Grid Query ID.
     */
    private const QUERY_ID = 'client_people_filter';

    public function register(): void
    {
        add_action(
            'elementor/query/' . self::QUERY_ID,
            [$this, 'filterElementorQuery'],
            10,
            1
        );
    }

    public function filterElementorQuery(WP_Query $query): void
    {
        /**
         * CRITICAL PERFORMANCE RULE:
         * Until the visitor activates a filter, force the Elementor query
         * to return no People records.
         */
        if (!FilterState::isActive()) {
            $query->set('post__in', [0]);
            $query->set('posts_per_page', 1);
            $query->set('no_found_rows', true);
            return;
        }

        $keyword = FilterState::keyword();

        if ($keyword !== '') {
            $query->set('s', $keyword);
        }

        $letter = FilterState::letter();

        if ($letter !== '') {
            /**
             * Recommended data model:
             *
             * _people_sort_letter = first letter of surname, e.g. "S"
             *
             * Change this key with the filter below if your site already
             * stores the surname initial elsewhere.
             */
            $letterMetaKey = (string) apply_filters(
                'cpf/letter_meta_key',
                '_people_sort_letter'
            );

            $metaQuery   = (array) $query->get('meta_query');
            $metaQuery[] = [
                'key'     => $letterMetaKey,
                'value'   => $letter,
                'compare' => '=',
            ];

            $query->set('meta_query', $metaQuery);
        }

        /**
         * Taxonomies are intentionally configurable.
         *
         * Example:
         * add_filter('cpf/filter_taxonomies', function () {
         *     return ['person_location', 'person_department', 'person_role'];
         * });
         */
        $taxonomies = (array) apply_filters('cpf/filter_taxonomies', []);

        if ($taxonomies !== []) {
            $taxQuery = (array) $query->get('tax_query');

            foreach ($taxonomies as $taxonomy) {
                $taxonomy = sanitize_key((string) $taxonomy);

                if ($taxonomy === '' || !taxonomy_exists($taxonomy)) {
                    continue;
                }

                $value = FilterState::taxonomyValue($taxonomy);

                if ($value === '') {
                    continue;
                }

                $taxQuery[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => [$value],
                ];
            }

            if ($taxQuery !== []) {
                if (count($taxQuery) > 1 && !isset($taxQuery['relation'])) {
                    $taxQuery['relation'] = 'AND';
                }

                $query->set('tax_query', $taxQuery);
            }
        }

        /**
         * Optional hook for client-specific query changes.
         */
        do_action('cpf/after_elementor_query', $query);
    }
}

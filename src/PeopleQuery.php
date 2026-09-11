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

    /**
     * Client taxonomy defaults.
     *
     * @var string[]
     */
    private const DEFAULT_TAXONOMIES = [
        'staffmember_divisions',
        'staffmember_locations',
        'staffmember_roles',
        'staffmember_specialisms',
        'staffmember_sectors',
    ];

    public function register(): void
    {
        add_action(
            'elementor/query/' . self::QUERY_ID,
            [$this, 'filterElementorQuery'],
            10,
            1
        );

        /**
         * A-Z fallback for existing staff data.
         *
         * This only affects a query when our Elementor query hook has set the
         * private cpf_surname_letter query variable.
         */
        add_filter('posts_where', [$this, 'filterSurnameLetter'], 10, 2);
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
             * If the site has a dedicated surname-initial field, define it
             * with the cpf/letter_meta_key filter and we will use that.
             *
             * Otherwise we fall back to the last word of post_title, which
             * works for normal "First Last" staff-member titles.
             */
            $letterMetaKey = (string) apply_filters(
                'cpf/letter_meta_key',
                ''
            );

            if ($letterMetaKey !== '') {
                $metaQuery   = (array) $query->get('meta_query');
                $metaQuery[] = [
                    'key'     => $letterMetaKey,
                    'value'   => $letter,
                    'compare' => '=',
                ];

                $query->set('meta_query', $metaQuery);
            } else {
                $query->set('cpf_surname_letter', $letter);
            }
        }

        $taxonomies = (array) apply_filters(
            'cpf/filter_taxonomies',
            self::DEFAULT_TAXONOMIES
        );

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

        do_action('cpf/after_elementor_query', $query);
    }

    /**
     * Filter by the first letter of the final word in post_title.
     *
     * Example:
     * "Jane Smith" => S
     *
     * This gives the existing client site working A-Z filtering without
     * requiring a migration or a new surname meta field first.
     */
    public function filterSurnameLetter(string $where, WP_Query $query): string
    {
        $letter = strtoupper(
            sanitize_text_field(
                (string) $query->get('cpf_surname_letter')
            )
        );

        if (!preg_match('/^[A-Z]$/', $letter)) {
            return $where;
        }

        global $wpdb;

        $like = $wpdb->esc_like($letter) . '%';

        $where .= $wpdb->prepare(
            " AND SUBSTRING_INDEX(TRIM({$wpdb->posts}.post_title), ' ', -1) LIKE %s",
            $like
        );

        return $where;
    }
}

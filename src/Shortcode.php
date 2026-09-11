<?php

namespace ClientPeopleFilter;

defined('ABSPATH') || exit;

final class Shortcode
{
    public function register(): void
    {
        add_shortcode('people_filter', [$this, 'render']);
    }

    /**
     * Usage:
     *
     * [people_filter]
     *
     * [people_filter
     *     taxonomies="person_location,person_department,person_role"
     *     labels="Location,Department,Role"
     * ]
     *
     * @param array<string,mixed>|string $atts
     */
    public function render($atts = []): string
    {
        $atts = shortcode_atts(
            [
                'taxonomies'         => 'staffmember_divisions,staffmember_locations,staffmember_roles,staffmember_specialisms,staffmember_sectors,accreditations',
                'labels'             => 'Divisions,Locations,Roles,Specialisms,Sectors,Accreditations',
                'show_search'        => 'yes',
                'show_alphabet'      => 'yes',
                'search_placeholder' => __('Search by name or keyword', 'client-people-filter'),
                'submit_label'       => __('Search', 'client-people-filter'),
                'reset_label'        => __('Reset', 'client-people-filter'),
                'intro_text'         => __('Search by name or choose a filter to find a member of our team.', 'client-people-filter'),
            ],
            is_array($atts) ? $atts : [],
            'people_filter'
        );

        wp_enqueue_style('client-people-filter');
        wp_enqueue_script('client-people-filter');

        $taxonomies = $this->csv($atts['taxonomies']);
        $labels     = $this->csv($atts['labels']);

        ob_start();
        ?>
        <section class="cpf" data-cpf-filter>
            <form class="cpf__form" method="get" action="<?php echo esc_url($this->currentUrlWithoutQuery()); ?>">
                <input type="hidden" name="people_filter" value="1">

                <?php if ($atts['show_search'] === 'yes') : ?>
                    <div class="cpf__search">
                        <label class="screen-reader-text" for="cpf-people-q">
                            <?php esc_html_e('Search people', 'client-people-filter'); ?>
                        </label>

                        <input
                            id="cpf-people-q"
                            class="cpf__search-input"
                            type="search"
                            name="people_q"
                            value="<?php echo esc_attr(FilterState::keyword()); ?>"
                            placeholder="<?php echo esc_attr($atts['search_placeholder']); ?>"
                        >

                        <button class="cpf__submit" type="submit">
                            <?php echo esc_html($atts['submit_label']); ?>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_alphabet'] === 'yes') : ?>
                    <?php $this->renderAlphabet(); ?>
                <?php endif; ?>

                <?php if ($taxonomies !== []) : ?>
                    <div class="cpf__selects">
                        <?php foreach ($taxonomies as $index => $taxonomy) : ?>
                            <?php
                            if (!taxonomy_exists($taxonomy)) {
                                continue;
                            }

                            $label = $labels[$index] ?? $this->taxonomyLabel($taxonomy);
                            $this->renderTaxonomySelect($taxonomy, $label);
                            ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="cpf__actions">
                    <?php if ($atts['show_search'] !== 'yes') : ?>
                        <button class="cpf__submit" type="submit">
                            <?php echo esc_html($atts['submit_label']); ?>
                        </button>
                    <?php endif; ?>

                    <?php if (FilterState::isActive()) : ?>
                        <a class="cpf__reset" href="<?php echo esc_url($this->currentUrlWithoutQuery()); ?>">
                            <?php echo esc_html($atts['reset_label']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (!FilterState::isActive() && $atts['intro_text'] !== '') : ?>
                <div class="cpf__intro" data-cpf-intro>
                    <?php echo esc_html($atts['intro_text']); ?>
                </div>
            <?php endif; ?>
        </section>
        <?php

        return (string) ob_get_clean();
    }

    private function renderAlphabet(): void
    {
        $active = FilterState::letter();

        ?>
        <nav class="cpf__alphabet" aria-label="<?php esc_attr_e('Filter people by surname initial', 'client-people-filter'); ?>">
            <?php foreach (range('A', 'Z') as $letter) : ?>
                <?php
                $url = add_query_arg(
                    [
                        'people_filter' => '1',
                        'people_letter' => $letter,
                    ],
                    $this->currentUrlWithoutQuery()
                );
                ?>
                <a
                    class="cpf__letter<?php echo $active === $letter ? ' is-active' : ''; ?>"
                    href="<?php echo esc_url($url); ?>"
                    <?php echo $active === $letter ? 'aria-current="page"' : ''; ?>
                >
                    <?php echo esc_html($letter); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }

    private function renderTaxonomySelect(string $taxonomy, string $label): void
    {
        $terms = get_terms(
            [
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
            ]
        );

        if (is_wp_error($terms) || $terms === []) {
            return;
        }

        $name    = 'people_' . sanitize_key($taxonomy);
        $current = FilterState::taxonomyValue($taxonomy);

        ?>
        <label class="cpf__field">
            <span class="cpf__field-label"><?php echo esc_html($label); ?></span>
            <select class="cpf__select" name="<?php echo esc_attr($name); ?>">
                <option value="">
                    <?php
                    printf(
                        esc_html__('All %s', 'client-people-filter'),
                        esc_html($label)
                    );
                    ?>
                </option>

                <?php foreach ($terms as $term) : ?>
                    <option
                        value="<?php echo esc_attr($term->slug); ?>"
                        <?php selected($current, $term->slug); ?>
                    >
                        <?php echo esc_html($term->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php
    }

    private function taxonomyLabel(string $taxonomy): string
    {
        $object = get_taxonomy($taxonomy);

        if ($object && isset($object->labels->singular_name)) {
            return (string) $object->labels->singular_name;
        }

        return ucwords(str_replace(['-', '_'], ' ', $taxonomy));
    }

    /**
     * @return string[]
     */
    private function csv(string $value): array
    {
        $items = array_map('trim', explode(',', $value));
        $items = array_filter($items, static fn(string $item): bool => $item !== '');

        return array_values($items);
    }

    private function currentUrlWithoutQuery(): string
    {
        global $wp;

        if (isset($wp->request)) {
            return home_url('/' . ltrim((string) $wp->request, '/'));
        }

        return home_url('/');
    }
}

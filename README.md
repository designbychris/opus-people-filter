# Client People Filter

A small WordPress plugin that provides a search-first People directory for an existing Elementor Loop Grid.

## Why

Simply hiding an Elementor Loop Grid with CSS does **not** improve the initial query/render cost.

This plugin hooks Elementor's custom Query ID and returns zero records until the visitor activates a filter.

## Elementor setup

1. Install and activate the plugin.
2. Add the shortcode:

   `[people_filter]`

3. Edit the existing Elementor Loop Grid.
4. In the Loop Grid query settings set:

   **Query ID:** `client_people_filter`

5. In Advanced → CSS Classes add:

   `cpf-people-results`

The initial page load will now return no Loop Grid records.

## Taxonomy filters

Example shortcode:

`[people_filter taxonomies="person_location,person_department,person_role" labels="Location,Department,Role"]`

Then tell the query layer which taxonomies are allowed:

```php
add_filter('cpf/filter_taxonomies', function () {
    return [
        'person_location',
        'person_department',
        'person_role',
    ];
});
```

This can live in the theme, a small site plugin, or eventually in a plugin settings screen.

## A-Z surname filtering

For reliable surname filtering, store the surname initial in post meta:

`_people_sort_letter = S`

You can change the meta key:

```php
add_filter('cpf/letter_meta_key', function () {
    return 'your_existing_meta_key';
});
```

## Current Phase

0.1.0 provides:

- Elementor Loop Grid Query ID integration.
- Zero-result initial query.
- Keyword search.
- A-Z filtering.
- Optional taxonomy filters.
- Reset link.
- URL persistence / bookmarkable filtered states.
- Automatic select submission.
- CSS hook to hide the result widget until filtering starts.

## Next logical phase

- Client-specific CPT / ACF mapping.
- Automatic surname/sort-letter synchronization.
- AJAX results refresh without a full page reload.
- Loading / empty states.
- Optional settings screen for mapping post type, taxonomies and surname field.
- Automated PHPUnit coverage.


## 0.1.1

Client data-mapping update:

- Adds default filters for:
  - `staffmember_divisions`
  - `staffmember_locations`
  - `staffmember_roles`
  - `staffmember_specialisms`
  - `staffmember_sectors`
  - `accreditations`
- A-Z filtering now works immediately against the surname-like final word in the post title.
- A dedicated surname-initial meta field can still override the fallback through `cpf/letter_meta_key`.


## 0.1.2 — Living Results

- Removes `accreditations` from the default filter controls.
- Keeps Divisions, Locations, Roles, Specialisms and Sectors.
- Adds progressive AJAX filtering for:
  - A-Z links
  - taxonomy dropdown changes
  - keyword search
  - reset
- Keeps normal GET URLs as the fallback, so filtering still works without JavaScript.
- Keeps browser back/forward navigation working.
- Updates the URL after each filter without a full page reload.
- Reuses the existing Elementor Loop Grid rendering rather than rebuilding the card template.
- Adds accessible loading/result announcements and reduced-motion support.


## 0.1.3 — Stable Filter Bar

- Fixes taxonomy dropdowns disappearing after an A-Z AJAX request.
- Keeps the filter form DOM permanently in place; only Elementor results are replaced.
- Alphabet filtering now preserves currently selected taxonomy filters.
- Browser Back/Forward synchronizes the visible filter controls to the URL.
- Reset is available dynamically without rebuilding the filter form.


## 0.1.4 — Interface & Responsive Polish

- Polishes the full filter interface without changing the query architecture.
- Adds branded CSS variables for easy client-specific colour changes.
- Styles search, A-Z controls, dropdowns, buttons and Reset consistently.
- Adds stronger hover, focus and active states.
- Adds visible AJAX loading feedback.
- Adds a subtle results reveal after AJAX refresh.
- Adds responsive layouts:
  - 5-column taxonomy layout on desktop
  - 2-column layout on tablet
  - single-column filters on mobile
  - wrapped A-Z controls for smaller screens
- Adds reduced-motion support.

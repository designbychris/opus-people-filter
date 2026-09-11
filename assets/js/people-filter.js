(function () {
    'use strict';

    var RESULT_SELECTOR = '.cpf-people-results';
    var FILTER_SELECTOR = '[data-cpf-filter]';

    function getFilterRoot() {
        return document.querySelector(FILTER_SELECTOR);
    }

    function getResults() {
        return document.querySelector(RESULT_SELECTOR);
    }

    function getStatus(root) {
        return root ? root.querySelector('[data-cpf-status]') : null;
    }

    function announce(root, message) {
        var status = getStatus(root);

        if (!status) {
            return;
        }

        status.textContent = '';

        window.setTimeout(function () {
            status.textContent = message;
        }, 30);
    }

    function setLoading(isLoading) {
        var root = getFilterRoot();
        var results = getResults();

        if (root) {
            root.classList.toggle('is-loading', isLoading);
            root.setAttribute('aria-busy', isLoading ? 'true' : 'false');
        }

        if (results) {
            results.classList.toggle('is-loading', isLoading);
            results.setAttribute('aria-busy', isLoading ? 'true' : 'false');
        }

        if (isLoading) {
            announce(root, 'Loading people results.');
        }
    }

    function isFilterActive(url) {
        var parsed = new URL(url, window.location.href);

        return parsed.searchParams.get('people_filter') === '1';
    }

    function syncBodyState(url) {
        var active = isFilterActive(url);

        document.body.classList.toggle('cpf-filter-active', active);
        document.body.classList.toggle('cpf-filter-inactive', !active);
    }

    /**
     * Keep the existing filter DOM permanently in place.
     *
     * Replacing the entire filter markup caused browser/Elementor styling
     * state to be lost after an AJAX request. Instead we simply synchronize
     * the controls with the URL.
     */
    function syncFilterState(url) {
        var root = getFilterRoot();

        if (!root) {
            return;
        }

        var parsed = new URL(url, window.location.href);
        var params = parsed.searchParams;
        var form = root.querySelector('.cpf__form');

        if (form) {
            var search = form.querySelector('[name="people_q"]');

            if (search) {
                search.value = params.get('people_q') || '';
            }

            form.querySelectorAll('.cpf__select').forEach(function (select) {
                select.value = params.get(select.name) || '';
            });
        }

        var activeLetter = params.get('people_letter') || '';

        root.querySelectorAll('.cpf__letter').forEach(function (link) {
            var linkUrl = new URL(link.href, window.location.href);
            var letter = linkUrl.searchParams.get('people_letter') || '';
            var isActive = letter === activeLetter;

            link.classList.toggle('is-active', isActive);

            if (isActive) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });

        var reset = root.querySelector('[data-cpf-reset]');

        if (reset) {
            reset.hidden = !isFilterActive(url);
        }

        var intro = root.querySelector('[data-cpf-intro]');

        if (intro) {
            intro.hidden = isFilterActive(url);
        }
    }

    function copyResultsMarkup(sourceDocument) {
        var current = getResults();
        var incoming = sourceDocument.querySelector(RESULT_SELECTOR);

        if (current && incoming) {
            current.replaceWith(incoming);
            return;
        }

        if (current && !incoming) {
            current.innerHTML = '';
            current.hidden = true;
            return;
        }

        if (!current && incoming) {
            var root = getFilterRoot();

            if (root && root.parentNode) {
                root.parentNode.insertBefore(incoming, root.nextSibling);
            }
        }
    }

    function executeElementorFrontend() {
        if (
            window.elementorFrontend &&
            window.elementorFrontend.elementsHandler &&
            typeof window.elementorFrontend.elementsHandler.runReadyTrigger === 'function'
        ) {
            var results = getResults();

            if (results && window.jQuery) {
                window.elementorFrontend.elementsHandler.runReadyTrigger(
                    window.jQuery(results)
                );
            }
        }
    }

    function loadUrl(url, pushState) {
        setLoading(true);

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('People filter request failed.');
                }

                return response.text();
            })
            .then(function (html) {
                var parser = new DOMParser();
                var nextDocument = parser.parseFromString(html, 'text/html');

                copyResultsMarkup(nextDocument);
                syncBodyState(url);
                syncFilterState(url);

                if (pushState) {
                    window.history.pushState(
                        { cpfPeopleFilter: true },
                        '',
                        url
                    );
                }

                executeElementorFrontend();

                announce(getFilterRoot(), 'People results updated.');
            })
            .catch(function () {
                /*
                 * Progressive-enhancement fallback:
                 * the normal URL remains fully functional.
                 */
                window.location.assign(url);
            })
            .finally(function () {
                setLoading(false);
            });
    }

    function formUrl(form) {
        var data = new FormData(form);
        var url = new URL(form.action || window.location.href, window.location.href);

        url.search = '';

        data.forEach(function (value, key) {
            if (String(value).trim() !== '') {
                url.searchParams.set(key, value);
            }
        });

        return url;
    }

    /**
     * Build an alphabet URL from the CURRENT form state so choosing a letter
     * does not discard Location / Role / Specialism / Sector / Division.
     */
    function alphabetUrl(form, link) {
        var url = form ? formUrl(form) : new URL(window.location.href);
        var linkUrl = new URL(link.href, window.location.href);
        var letter = linkUrl.searchParams.get('people_letter');

        url.searchParams.set('people_filter', '1');

        if (letter) {
            url.searchParams.set('people_letter', letter);
        } else {
            url.searchParams.delete('people_letter');
        }

        return url.toString();
    }

    function bindFilter(root) {
        if (!root || root.dataset.cpfBound === '1') {
            return;
        }

        root.dataset.cpfBound = '1';

        var form = root.querySelector('.cpf__form');

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var url = formUrl(form);
                url.searchParams.delete('people_letter');

                loadUrl(url.toString(), true);
            });

            form.querySelectorAll('.cpf__select').forEach(function (select) {
                select.addEventListener('change', function () {
                    loadUrl(formUrl(form).toString(), true);
                });
            });
        }

        root.querySelectorAll('.cpf__alphabet a').forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (
                    event.button !== 0 ||
                    event.metaKey ||
                    event.ctrlKey ||
                    event.shiftKey ||
                    event.altKey
                ) {
                    return;
                }

                event.preventDefault();
                loadUrl(alphabetUrl(form, link), true);
            });
        });

        var reset = root.querySelector('[data-cpf-reset]');

        if (reset) {
            reset.addEventListener('click', function (event) {
                if (
                    event.button !== 0 ||
                    event.metaKey ||
                    event.ctrlKey ||
                    event.shiftKey ||
                    event.altKey
                ) {
                    return;
                }

                event.preventDefault();
                loadUrl(reset.href, true);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = getFilterRoot();

        bindFilter(root);
        syncFilterState(window.location.href);

        window.addEventListener('popstate', function () {
            loadUrl(window.location.href, false);
        });
    });
})();

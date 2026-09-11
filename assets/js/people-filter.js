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

    function syncBodyState(url) {
        var parsed = new URL(url, window.location.href);
        var active = parsed.searchParams.get('people_filter') === '1';

        document.body.classList.toggle('cpf-filter-active', active);
        document.body.classList.toggle('cpf-filter-inactive', !active);
    }

    function copyFilterMarkup(sourceDocument) {
        var current = getFilterRoot();
        var incoming = sourceDocument.querySelector(FILTER_SELECTOR);

        if (!current || !incoming) {
            return;
        }

        current.replaceWith(incoming);
        bindFilter(incoming);
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
            current.style.display = 'none';
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
        var root = getFilterRoot();

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
                copyFilterMarkup(nextDocument);
                syncBodyState(url);

                if (pushState) {
                    window.history.pushState(
                        { cpfPeopleFilter: true },
                        '',
                        url
                    );
                }

                executeElementorFrontend();

                var nextRoot = getFilterRoot();
                announce(nextRoot, 'People results updated.');
            })
            .catch(function () {
                /*
                 * Progressive-enhancement fallback:
                 * if AJAX fails for any reason, use the normal working URL.
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
                loadUrl(formUrl(form), true);
            });

            form.querySelectorAll('.cpf__select').forEach(function (select) {
                select.addEventListener('change', function () {
                    loadUrl(formUrl(form), true);
                });
            });
        }

        root.querySelectorAll('.cpf__alphabet a, .cpf__reset').forEach(function (link) {
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
                loadUrl(link.href, true);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindFilter(getFilterRoot());

        window.addEventListener('popstate', function () {
            loadUrl(window.location.href, false);
        });
    });
})();

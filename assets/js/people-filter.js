(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var filters = document.querySelectorAll('[data-cpf-filter]');

        filters.forEach(function (filter) {
            var selects = filter.querySelectorAll('.cpf__select');

            selects.forEach(function (select) {
                select.addEventListener('change', function () {
                    if (select.form) {
                        select.form.submit();
                    }
                });
            });
        });
    });
})();

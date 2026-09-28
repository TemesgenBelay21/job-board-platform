/**
 * JobBoard - progressive enhancement only.
 *
 * Every interaction here has a working server rendered fallback: navigation
 * links are real <a> elements, forms post normally, and the sidebar is visible
 * on desktop without JavaScript. This file only adds the conveniences.
 */
(function () {
    'use strict';

    /**
     * Toggle a menu, a panel or the mobile sidebar, keeping aria-expanded in
     * sync and closing on Escape or an outside click.
     */
    function initToggles() {
        var menuToggles = document.querySelectorAll('[data-user-menu-toggle]');

        menuToggles.forEach(function (toggle) {
            toggle.addEventListener('click', function (event) {
                event.stopPropagation();

                var menu = toggle.parentElement.querySelector('[data-user-menu]');

                if (!menu) {
                    return;
                }

                var isOpen = toggle.getAttribute('aria-expanded') === 'true';

                // Close any other open menu first.
                closeAllMenus();

                toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                menu.hidden = isOpen;
            });
        });

        function closeAllMenus() {
            document.querySelectorAll('[data-user-menu-toggle][aria-expanded="true"]').forEach(function (toggle) {
                var menu = toggle.parentElement.querySelector('[data-user-menu]');

                toggle.setAttribute('aria-expanded', 'false');

                if (menu) {
                    menu.hidden = true;
                }
            });
        }

        document.addEventListener('click', function () {
            closeAllMenus();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAllMenus();
            }
        });

        // Mobile navigation ----------------------------------------------------
        var navToggle = document.querySelector('[data-nav-toggle]');
        var nav = document.querySelector('[data-nav]');

        if (navToggle && nav) {
            navToggle.addEventListener('click', function () {
                var isOpen = navToggle.getAttribute('aria-expanded') === 'true';

                navToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                nav.classList.toggle('is-open', !isOpen);
                document.body.classList.toggle('nav-open', !isOpen);
            });
        }

        // Dashboard sidebar ----------------------------------------------------
        var sidebarToggle = document.querySelector('[data-sidebar-toggle]');
        var sidebar = document.querySelector('[data-sidebar]');
        var backdrop = document.querySelector('[data-sidebar-backdrop]');

        function setSidebar(open) {
            if (!sidebar) {
                return;
            }

            sidebar.classList.toggle('is-open', open);
            document.body.classList.toggle('sidebar-open', open);

            if (sidebarToggle) {
                sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }

            if (backdrop) {
                backdrop.hidden = !open;
            }
        }

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function () {
                setSidebar(sidebar.classList.contains('is-open') === false);
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                setSidebar(false);
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setSidebar(false);
            }
        });
    }

    /**
     * Ask before running a destructive action. Forms opt in with
     * data-confirm="...".
     */
    function initConfirmations() {
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.getAttribute('data-confirm'))) {
                    event.preventDefault();
                }
            });
        });
    }

    /**
     * Prevent a double submit on slow form posts, without disabling the button
     * permanently if validation fails server side.
     */
    function initSubmitGuards() {
        document.querySelectorAll('form[data-once]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var submit = form.querySelector('[type="submit"]');

                if (submit) {
                    setTimeout(function () {
                        submit.disabled = true;
                    }, 0);
                }
            });
        });
    }

    /**
     * Show the picked file name next to a file input so the user can see what
     * they selected before the upload starts.
     */
    function initFileInputs() {
        document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
            var output = document.getElementById(input.getAttribute('data-preview'));

            if (!output) {
                return;
            }

            input.addEventListener('change', function () {
                var file = input.files && input.files[0];

                if (!file) {
                    output.textContent = '';
                    return;
                }

                var kilobytes = file.size / 1024;
                var size = kilobytes > 1024
                    ? (kilobytes / 1024).toFixed(1) + ' MB'
                    : Math.max(1, Math.round(kilobytes)) + ' KB';

                output.textContent = file.name + ' (' + size + ')';
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initToggles();
            initConfirmations();
            initSubmitGuards();
            initFileInputs();
        });
    } else {
        initToggles();
        initConfirmations();
        initSubmitGuards();
        initFileInputs();
    }
})();

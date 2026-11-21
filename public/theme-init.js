/*
 * Light and dark theme of the whole app.
 *
 * Plain script, loaded without defer in the <head> of every page so that the theme
 * is set before the first paint: Vite only outputs deferred modules, too late to
 * avoid a flash, and the CSP forbids inline scripts. Kept out of Vite on purpose,
 * served with Cache-Control: no-cache (frankenphp/Caddyfile) since its name has no hash.
 *
 * Only place that knows the storage key and how a preference becomes a theme:
 * - the preference is "light", "dark" or "auto" (no key stored: follows the system);
 * - <html> gets data-bs-theme (theme shown) and data-theme-preference (choice);
 * - the theme switcher sends the new preference with an "app:theme" event;
 * - other tabs follow through the "storage" event, "auto" follows the system live.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'theme';
    var root = document.documentElement;
    var systemDark = window.matchMedia('(prefers-color-scheme: dark)');

    function isExplicit(value) {
        return value === 'light' || value === 'dark';
    }

    function storedPreference() {
        try {
            var value = window.localStorage.getItem(STORAGE_KEY);

            return isExplicit(value) ? value : 'auto';
        } catch (error) {
            return 'auto';
        }
    }

    function store(preference) {
        try {
            if (isExplicit(preference)) {
                window.localStorage.setItem(STORAGE_KEY, preference);
            } else {
                window.localStorage.removeItem(STORAGE_KEY);
            }
        } catch (error) {
            // Storage blocked: the choice still applies to the current page
        }
    }

    function apply(preference) {
        var theme = isExplicit(preference) ? preference : (systemDark.matches ? 'dark' : 'light');
        root.setAttribute('data-bs-theme', theme);
        root.setAttribute('data-theme-preference', preference);
    }

    var current = storedPreference();
    apply(current);

    document.addEventListener('app:theme', function (event) {
        var preference = event.detail;
        if (!isExplicit(preference) && preference !== 'auto') {
            return;
        }
        current = preference;
        store(current);
        apply(current);
    });

    window.addEventListener('storage', function (event) {
        if (event.key !== STORAGE_KEY && event.key !== null) {
            return;
        }
        current = storedPreference();
        apply(current);
    });

    systemDark.addEventListener('change', function () {
        if (current === 'auto') {
            apply(current);
        }
    });
})();

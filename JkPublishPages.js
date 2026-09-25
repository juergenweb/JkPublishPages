/**
 * JkPublishPages - admin scripts
 * Written by Jürgen K.
 *
 * All code lives inside the namespace "JkPublishPages" (one single global object), so it does not collide with
 * other scripts. The script does not overwrite window.onload - it registers its own event listener instead.
 *
 * Toggle link in the module configuration: checks or unchecks all template checkboxes at once.
 */

/*jshint esversion: 6 */

(function (window, document) {
    'use strict';

    // create the namespace only once (the script could be loaded twice)
    const JkPublishPages = window.JkPublishPages = window.JkPublishPages || {};

    // selector of the toggle link (namespaced data attribute, see JkPublishPages::addToggle())
    JkPublishPages.toggleSelector = '[data-jkpp-toggle]';

    /**
     * Get all checkboxes that belong to a toggle link (only inside the same Inputfield wrapper)
     * @param {HTMLElement} toggle
     * @returns {HTMLInputElement[]}
     */
    JkPublishPages.getCheckboxes = function (toggle) {
        const wrapper = toggle.closest('.Inputfield') || toggle.parentElement;
        if (!wrapper) return [];
        return Array.prototype.slice.call(wrapper.querySelectorAll('input[type="checkbox"]'));
    };

    /**
     * Check all checkboxes if at least one is unchecked, otherwise uncheck all
     * @param {HTMLElement} toggle
     */
    JkPublishPages.toggle = function (toggle) {
        const checkboxes = JkPublishPages.getCheckboxes(toggle);
        if (!checkboxes.length) return;

        // the new state depends on the current state (not on the number of clicks)
        const check = checkboxes.some(function (checkbox) {
            return !checkbox.checked;
        });

        checkboxes.forEach(function (checkbox) {
            if (checkbox.disabled || checkbox.checked === check) return;
            checkbox.checked = check;
            // let ProcessWire (and other scripts) know about the change, e.g. for the "unsaved changes" warning
            checkbox.dispatchEvent(new Event('change', {bubbles: true}));
        });
    };

    /**
     * Initialize all toggle links on the page (each link only once)
     * @param {Document|HTMLElement} [root]
     */
    JkPublishPages.initToggles = function (root) {
        const scope = root || document;
        Array.prototype.forEach.call(scope.querySelectorAll(JkPublishPages.toggleSelector), function (toggle) {
            if (toggle.getAttribute('data-jkpp-initialized')) return;
            toggle.setAttribute('data-jkpp-initialized', '1');
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                JkPublishPages.toggle(toggle);
            });
        });
    };

    // run after the DOM has been loaded - without overwriting window.onload
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            JkPublishPages.initToggles();
        });
    } else {
        JkPublishPages.initToggles();
    }

})(window, document);

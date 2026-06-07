"use strict";
(() => {
    var _a;
    const mediaWindow = window;
    const settings = mediaWindow.skvnTrackingMediaTabs;
    if (!settings) {
        return;
    }
    let activeScope = settings.initialScope;
    let gridFrame = null;
    // WordPress reads these vars when its DOM-ready handler creates the grid.
    if ((_a = mediaWindow._wpMediaGridSettings) === null || _a === void 0 ? void 0 : _a.queryVars) {
        mediaWindow._wpMediaGridSettings.queryVars[settings.queryKey] = activeScope;
    }
    const updateUrl = (scope, mode) => {
        const url = new URL(window.location.href);
        url.searchParams.set(settings.queryKey, scope);
        url.searchParams.set('mode', mode);
        if (mode === 'grid') {
            window.history.replaceState({}, '', url);
            return;
        }
        window.location.assign(url);
    };
    const setActiveButton = (nav, scope) => {
        nav.querySelectorAll('[data-skvn-tracking-scope]').forEach((button) => {
            const isActive = button.dataset.skvnTrackingScope === scope;
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            button.classList.toggle('is-active', isActive);
        });
    };
    const createNavigation = (container, mode) => {
        const existing = container.querySelector('.skvn-tracking-media-tabs');
        if (existing) {
            return existing;
        }
        const nav = document.createElement('div');
        nav.className = 'skvn-tracking-media-tabs';
        nav.setAttribute('role', 'group');
        nav.setAttribute('aria-label', settings.labels.group);
        ['posts', 'shipment'].forEach((scope) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'skvn-tracking-media-tabs__button';
            button.dataset.skvnTrackingScope = scope;
            button.textContent = settings.labels[scope];
            button.addEventListener('click', () => {
                if (scope === activeScope) {
                    return;
                }
                activeScope = scope;
                setActiveButton(nav, scope);
                if (mode === 'grid' && gridFrame) {
                    gridFrame.state().get('library').props.set(settings.queryKey, scope);
                    updateUrl(scope, 'grid');
                    return;
                }
                updateUrl(scope, 'list');
            });
            nav.append(button);
        });
        // Core upload.php currently places the page header boundary before the grid/table.
        const headerEnd = container.querySelector('.wp-header-end');
        headerEnd === null || headerEnd === void 0 ? void 0 : headerEnd.insertAdjacentElement('afterend', nav);
        if (!headerEnd) {
            container.prepend(nav);
        }
        setActiveButton(nav, activeScope);
        return nav;
    };
    const initializeGrid = (frame) => {
        gridFrame = frame;
        const gridContainer = document.querySelector('#wp-media-grid');
        if (gridContainer) {
            createNavigation(gridContainer, 'grid');
        }
    };
    const gridRoot = document.querySelector('#wp-media-grid');
    if (gridRoot) {
        mediaWindow.jQuery('#wp-media-grid').on('wp-media-grid-ready', (_event, frame) => initializeGrid(frame));
    }
    mediaWindow.jQuery(() => {
        var _a, _b, _c;
        const existingFrame = (_c = (_b = (_a = mediaWindow.wp) === null || _a === void 0 ? void 0 : _a.media) === null || _b === void 0 ? void 0 : _b.frames) === null || _c === void 0 ? void 0 : _c.browse;
        if (gridRoot && existingFrame) {
            initializeGrid(existingFrame);
            return;
        }
        const listContainer = document.querySelector('body.upload-php .wrap');
        if (listContainer) {
            createNavigation(listContainer, 'list');
        }
    });
})();

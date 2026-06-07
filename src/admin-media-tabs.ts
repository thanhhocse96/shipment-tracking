type Scope = 'posts' | 'shipment';

interface MediaTabsSettings {
	initialScope: Scope;
	queryKey: string;
	labels: {
		group: string;
		posts: string;
		shipment: string;
	};
}

interface BackboneProps {
	set(key: string, value: Scope): void;
}

interface MediaLibrary {
	props: BackboneProps;
}

interface MediaFrame {
	state(): {
		get(key: 'library'): MediaLibrary;
	};
}

interface MediaWindow extends Window {
	jQuery: JQueryStatic;
	skvnTrackingMediaTabs: MediaTabsSettings;
	_wpMediaGridSettings?: {
		queryVars: Record<string, unknown>;
	};
	wp?: {
		media?: {
			frames?: {
				browse?: MediaFrame;
			};
		};
	};
}

interface JQueryStatic {
	(selector: string | Document | (() => void)): JQueryLike;
}

interface JQueryLike {
	on(eventName: string, handler: (...args: unknown[]) => void): JQueryLike;
}

(() => {
	const mediaWindow = window as unknown as MediaWindow;
	const settings = mediaWindow.skvnTrackingMediaTabs;

	if (!settings) {
		return;
	}

	let activeScope = settings.initialScope;
	let gridFrame: MediaFrame | null = null;

	// WordPress reads these vars when its DOM-ready handler creates the grid.
	if (mediaWindow._wpMediaGridSettings?.queryVars) {
		mediaWindow._wpMediaGridSettings.queryVars[settings.queryKey] = activeScope;
	}

	const updateUrl = (scope: Scope, mode: 'grid' | 'list'): void => {
		const url = new URL(window.location.href);
		url.searchParams.set(settings.queryKey, scope);
		url.searchParams.set('mode', mode);

		if (mode === 'grid') {
			window.history.replaceState({}, '', url);
			return;
		}

		window.location.assign(url);
	};

	const setActiveButton = (nav: HTMLElement, scope: Scope): void => {
		nav.querySelectorAll<HTMLButtonElement>('[data-skvn-tracking-scope]').forEach((button) => {
			const isActive = button.dataset.skvnTrackingScope === scope;
			button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
			button.classList.toggle('is-active', isActive);
		});
	};

	const createNavigation = (container: Element, mode: 'grid' | 'list'): HTMLElement => {
		const existing = container.querySelector<HTMLElement>('.skvn-tracking-media-tabs');

		if (existing) {
			return existing;
		}

		const nav = document.createElement('div');
		nav.className = 'skvn-tracking-media-tabs';
		nav.setAttribute('role', 'group');
		nav.setAttribute('aria-label', settings.labels.group);

		(['posts', 'shipment'] as Scope[]).forEach((scope) => {
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
		headerEnd?.insertAdjacentElement('afterend', nav);

		if (!headerEnd) {
			container.prepend(nav);
		}

		setActiveButton(nav, activeScope);
		return nav;
	};

	const initializeGrid = (frame: MediaFrame): void => {
		gridFrame = frame;

		const gridContainer = document.querySelector('#wp-media-grid');
		if (gridContainer) {
			createNavigation(gridContainer, 'grid');
		}
	};

	const gridRoot = document.querySelector('#wp-media-grid');
	if (gridRoot) {
		mediaWindow.jQuery('#wp-media-grid').on(
			'wp-media-grid-ready',
			(_event: unknown, frame: unknown) => initializeGrid(frame as MediaFrame)
		);
	}

	mediaWindow.jQuery(() => {
		const existingFrame = mediaWindow.wp?.media?.frames?.browse;

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

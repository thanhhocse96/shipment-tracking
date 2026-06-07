type ZoneId = 'seal' | 'temperature' | 'cargo' | 'uncategorized';
type UploadStatus = 'queued' | 'uploading' | 'success' | 'error';

interface PortalFile {
	id: string;
	file: File;
	zone: ZoneId;
	previewUrl: string;
	fingerprint: string;
	status: UploadStatus;
	progress: number;
	manualAssignment: boolean;
	errorMessage?: string;
	attachmentId?: number;
}

interface PortalError {
	filename: string;
	message: string;
}

interface UploadPortalSettings {
	schemaVersion: string;
	maxFileSize: number;
	allowedTypes: string[];
	labels: {
		empty: string;
		selected: string;
		backendDisabled: string;
	};
}

interface UploadPortalWindow extends Window {
	skvnTrackingUploadPortal?: UploadPortalSettings;
}

const ZONES: ZoneId[] = ['seal', 'temperature', 'cargo', 'uncategorized'];

const ZONE_LABELS: Record<ZoneId, string> = {
	seal: 'Seal & Door Check',
	temperature: 'Temperature Monitoring',
	cargo: 'Cargo Rows',
	uncategorized: 'Uncategorized',
};

class UploadPortal {
	private readonly root: HTMLElement;
	private readonly settings: UploadPortalSettings;
	private readonly files = new Map<string, PortalFile>();
	private readonly fingerprints = new Set<string>();
	private errors: PortalError[] = [];
	private modalReturnFocus: HTMLElement | null = null;

	public constructor(root: HTMLElement, settings: UploadPortalSettings) {
		this.root = root;
		this.settings = settings;
		this.bindEvents();
		this.render();
	}

	private bindEvents(): void {
		this.root.querySelectorAll<HTMLElement>('[data-zone]').forEach((zoneElement) => {
			const zone = this.readZone(zoneElement);
			const input = zoneElement.querySelector<HTMLInputElement>('[data-zone-input]');
			const dropTarget = zoneElement.querySelector<HTMLElement>('.skvn-tracking-zone__drop');

			input?.addEventListener('change', () => {
				this.addFiles(input.files, zone);
				input.value = '';
			});

			dropTarget?.addEventListener('keydown', (event) => {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					input?.click();
				}
			});

			zoneElement.addEventListener('dragenter', (event) => {
				event.preventDefault();
				zoneElement.classList.add('is-dragging-over');
			});
			zoneElement.addEventListener('dragover', (event) => event.preventDefault());
			zoneElement.addEventListener('dragleave', (event) => {
				if (!zoneElement.contains(event.relatedTarget as Node | null)) {
					zoneElement.classList.remove('is-dragging-over');
				}
			});
			zoneElement.addEventListener('drop', (event) => {
				event.preventDefault();
				zoneElement.classList.remove('is-dragging-over');

				const portalFileId = event.dataTransfer?.getData('application/x-skvn-tracking-file');
				if (portalFileId && this.files.has(portalFileId)) {
					this.moveFile(portalFileId, zone);
					return;
				}

				this.addFiles(event.dataTransfer?.files ?? null, zone);
			});

			zoneElement.querySelector<HTMLButtonElement>('[data-zone-more]')?.addEventListener('click', (event) => {
				this.openModal(zone, event.currentTarget as HTMLButtonElement);
			});
		});

		this.root.querySelector<HTMLFormElement>('[data-upload-form]')?.addEventListener('submit', (event) => {
			event.preventDefault();
			const notice = this.root.querySelector<HTMLElement>('[data-submit-notice]');
			if (notice) {
				notice.textContent = this.settings.labels.backendDisabled;
				notice.hidden = false;
				notice.focus();
			}
		});

		this.root.querySelector<HTMLButtonElement>('[data-reset]')?.addEventListener('click', () => {
			this.reset();
		});

		this.root.querySelectorAll<HTMLElement>('[data-modal-close]').forEach((control) => {
			control.addEventListener('click', () => this.closeModal());
		});

		document.addEventListener('keydown', (event) => this.handleModalKeydown(event));
		window.addEventListener('beforeunload', () => this.destroy());
	}

	private readZone(element: HTMLElement): ZoneId {
		const zone = element.dataset.zone;
		return ZONES.includes(zone as ZoneId) ? zone as ZoneId : 'uncategorized';
	}

	private addFiles(fileList: FileList | null, requestedZone: ZoneId): void {
		if (!fileList) {
			return;
		}

		Array.from(fileList).forEach((file) => {
			const validationError = this.validateFile(file);
			const fingerprint = `${file.name}:${file.size}:${file.lastModified}`;

			if (validationError) {
				this.errors.push({ filename: file.name, message: validationError });
				return;
			}

			if (this.fingerprints.has(fingerprint)) {
				this.errors.push({ filename: file.name, message: 'This file is already in the batch.' });
				return;
			}

			const inferredZone = requestedZone === 'uncategorized'
				? this.detectZone(file.name)
				: requestedZone;
			const portalFile: PortalFile = {
				id: this.createId(),
				file,
				zone: inferredZone,
				previewUrl: URL.createObjectURL(file),
				fingerprint,
				status: 'queued',
				progress: 0,
				manualAssignment: requestedZone !== 'uncategorized',
			};

			this.files.set(portalFile.id, portalFile);
			this.fingerprints.add(fingerprint);
		});

		this.render();
	}

	private validateFile(file: File): string | null {
		if (!this.settings.allowedTypes.includes(file.type)) {
			return 'Unsupported format. Use WebP, JPEG or PNG.';
		}

		if (file.size > this.settings.maxFileSize) {
			return 'File exceeds the 20 MB limit.';
		}

		return null;
	}

	private detectZone(filename: string): ZoneId {
		const normalized = filename.toLowerCase();

		if (/(seal|door|lock|niem)/.test(normalized)) {
			return 'seal';
		}
		if (/(temp|temperature|thermo|nhiet)/.test(normalized)) {
			return 'temperature';
		}
		if (/(cargo|row|load|hang)/.test(normalized)) {
			return 'cargo';
		}

		return 'uncategorized';
	}

	private createId(): string {
		if ('randomUUID' in crypto) {
			return crypto.randomUUID();
		}

		return `skvn-${Date.now()}-${Math.random().toString(16).slice(2)}`;
	}

	private moveFile(fileId: string, zone: ZoneId): void {
		const portalFile = this.files.get(fileId);
		if (!portalFile) {
			return;
		}

		portalFile.zone = zone;
		portalFile.manualAssignment = true;
		this.render();
	}

	private removeFile(fileId: string): void {
		const portalFile = this.files.get(fileId);
		if (!portalFile) {
			return;
		}

		URL.revokeObjectURL(portalFile.previewUrl);
		this.fingerprints.delete(portalFile.fingerprint);
		this.files.delete(fileId);
		this.closeModal();
		this.render();
	}

	private render(): void {
		ZONES.forEach((zone) => {
			const zoneFiles = Array.from(this.files.values()).filter((portalFile) => portalFile.zone === zone);
			const zoneElement = this.root.querySelector<HTMLElement>(`[data-zone="${zone}"]`);
			const count = zoneElement?.querySelector<HTMLElement>('[data-zone-count]');
			const previews = zoneElement?.querySelector<HTMLElement>('[data-zone-previews]');
			const moreButton = zoneElement?.querySelector<HTMLButtonElement>('[data-zone-more]');

			if (count) {
				count.textContent = String(zoneFiles.length);
			}
			if (previews) {
				previews.replaceChildren(...zoneFiles.slice(0, 4).map((portalFile) => this.createPreview(portalFile)));
			}
			if (moreButton) {
				const remaining = zoneFiles.length - 4;
				moreButton.hidden = remaining <= 0;
				moreButton.textContent = remaining > 0 ? `View more (+${remaining})` : '';
			}
		});

		const total = this.files.size;
		const totalCount = this.root.querySelector<HTMLElement>('[data-total-count]');
		const submit = this.root.querySelector<HTMLButtonElement>('[data-submit]');
		const reset = this.root.querySelector<HTMLButtonElement>('[data-reset]');

		if (totalCount) {
			totalCount.textContent = total === 0
				? this.settings.labels.empty
				: this.settings.labels.selected.replace('%d', String(total));
		}
		if (submit) {
			submit.disabled = total === 0;
		}
		if (reset) {
			reset.disabled = total === 0;
		}

		this.renderErrors();
	}

	private createPreview(portalFile: PortalFile, modal = false): HTMLElement {
		const card = document.createElement('article');
		card.className = modal
			? 'skvn-tracking-preview skvn-tracking-preview--modal'
			: 'skvn-tracking-preview';
		card.draggable = true;
		card.dataset.fileId = portalFile.id;
		card.addEventListener('dragstart', (event) => {
			event.dataTransfer?.setData('application/x-skvn-tracking-file', portalFile.id);
			if (event.dataTransfer) {
				event.dataTransfer.effectAllowed = 'move';
			}
		});

		const image = document.createElement('img');
		image.src = portalFile.previewUrl;
		image.alt = '';

		const body = document.createElement('div');
		body.className = 'skvn-tracking-preview__body';

		const filename = document.createElement('p');
		filename.className = 'skvn-tracking-preview__name';
		filename.textContent = portalFile.file.name;

		const controls = document.createElement('div');
		controls.className = 'skvn-tracking-preview__controls';

		const zoneSelect = document.createElement('select');
		zoneSelect.className = 'skvn-tracking-preview__select';
		zoneSelect.setAttribute('aria-label', `Move ${portalFile.file.name} to zone`);
		ZONES.forEach((zone) => {
			const option = document.createElement('option');
			option.value = zone;
			option.textContent = ZONE_LABELS[zone];
			option.selected = portalFile.zone === zone;
			zoneSelect.append(option);
		});
		zoneSelect.addEventListener('change', () => this.moveFile(portalFile.id, zoneSelect.value as ZoneId));

		const remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'skvn-tracking-preview__remove';
		remove.textContent = 'Remove';
		remove.setAttribute('aria-label', `Remove ${portalFile.file.name}`);
		remove.addEventListener('click', () => this.removeFile(portalFile.id));

		controls.append(zoneSelect, remove);
		body.append(filename, controls);
		card.append(image, body);
		return card;
	}

	private renderErrors(): void {
		const errorList = this.root.querySelector<HTMLElement>('[data-error-list]');
		if (!errorList) {
			return;
		}

		errorList.hidden = this.errors.length === 0;
		if (this.errors.length === 0) {
			errorList.replaceChildren();
			return;
		}

		const heading = document.createElement('strong');
		heading.textContent = 'Files not added';
		const list = document.createElement('ul');

		this.errors.forEach((error) => {
			const item = document.createElement('li');
			const filename = document.createElement('strong');
			filename.textContent = error.filename;
			item.append(filename, document.createTextNode(`: ${error.message}`));
			list.append(item);
		});

		const dismiss = document.createElement('button');
		dismiss.type = 'button';
		dismiss.textContent = 'Dismiss';
		dismiss.addEventListener('click', () => {
			this.errors = [];
			this.renderErrors();
		});

		errorList.replaceChildren(heading, list, dismiss);
	}

	private openModal(zone: ZoneId, trigger: HTMLElement): void {
		const modal = this.root.querySelector<HTMLElement>('[data-gallery-modal]');
		const title = modal?.querySelector<HTMLElement>('[data-modal-title]');
		const grid = modal?.querySelector<HTMLElement>('[data-modal-grid]');
		if (!modal || !title || !grid) {
			return;
		}

		const zoneFiles = Array.from(this.files.values()).filter((portalFile) => portalFile.zone === zone);
		title.textContent = ZONE_LABELS[zone];
		grid.replaceChildren(...zoneFiles.map((portalFile) => this.createPreview(portalFile, true)));
		modal.hidden = false;
		document.body.classList.add('skvn-tracking-modal-open');
		this.modalReturnFocus = trigger;
		modal.querySelector<HTMLButtonElement>('.skvn-tracking-modal__close')?.focus();
	}

	private closeModal(): void {
		const modal = this.root.querySelector<HTMLElement>('[data-gallery-modal]');
		if (!modal || modal.hidden) {
			return;
		}

		modal.hidden = true;
		document.body.classList.remove('skvn-tracking-modal-open');
		this.modalReturnFocus?.focus();
		this.modalReturnFocus = null;
	}

	private handleModalKeydown(event: KeyboardEvent): void {
		const modal = this.root.querySelector<HTMLElement>('[data-gallery-modal]');
		if (!modal || modal.hidden) {
			return;
		}

		if (event.key === 'Escape') {
			this.closeModal();
			return;
		}

		if (event.key !== 'Tab') {
			return;
		}

		const focusable = Array.from(
			modal.querySelectorAll<HTMLElement>('button:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])')
		);
		if (focusable.length === 0) {
			return;
		}

		const first = focusable[0];
		const last = focusable[focusable.length - 1];
		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	}

	private destroy(): void {
		this.files.forEach((portalFile) => URL.revokeObjectURL(portalFile.previewUrl));
		this.files.clear();
		this.fingerprints.clear();
	}

	private reset(): void {
		this.destroy();
		this.errors = [];
		this.closeModal();
		this.render();
	}
}

(() => {
	const portalWindow = window as UploadPortalWindow;
	const settings = portalWindow.skvnTrackingUploadPortal;
	const root = document.querySelector<HTMLElement>('[data-upload-portal]');

	if (settings && root) {
		new UploadPortal(root, settings);
	}
})();

"use strict";
const ZONES = ['seal', 'temperature', 'cargo', 'uncategorized'];
const ZONE_LABELS = {
    seal: 'Seal & Door Check',
    temperature: 'Temperature Monitoring',
    cargo: 'Cargo Rows',
    uncategorized: 'Uncategorized',
};
class UploadPortal {
    constructor(root, settings) {
        this.files = new Map();
        this.fingerprints = new Set();
        this.errors = [];
        this.modalReturnFocus = null;
        this.root = root;
        this.settings = settings;
        this.bindEvents();
        this.render();
    }
    bindEvents() {
        var _a, _b;
        this.root.querySelectorAll('[data-zone]').forEach((zoneElement) => {
            var _a;
            const zone = this.readZone(zoneElement);
            const input = zoneElement.querySelector('[data-zone-input]');
            const dropTarget = zoneElement.querySelector('.skvn-tracking-zone__drop');
            input === null || input === void 0 ? void 0 : input.addEventListener('change', () => {
                this.addFiles(input.files, zone);
                input.value = '';
            });
            dropTarget === null || dropTarget === void 0 ? void 0 : dropTarget.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    input === null || input === void 0 ? void 0 : input.click();
                }
            });
            zoneElement.addEventListener('dragenter', (event) => {
                event.preventDefault();
                zoneElement.classList.add('is-dragging-over');
            });
            zoneElement.addEventListener('dragover', (event) => event.preventDefault());
            zoneElement.addEventListener('dragleave', (event) => {
                if (!zoneElement.contains(event.relatedTarget)) {
                    zoneElement.classList.remove('is-dragging-over');
                }
            });
            zoneElement.addEventListener('drop', (event) => {
                var _a, _b, _c;
                event.preventDefault();
                zoneElement.classList.remove('is-dragging-over');
                const portalFileId = (_a = event.dataTransfer) === null || _a === void 0 ? void 0 : _a.getData('application/x-skvn-tracking-file');
                if (portalFileId && this.files.has(portalFileId)) {
                    this.moveFile(portalFileId, zone);
                    return;
                }
                this.addFiles((_c = (_b = event.dataTransfer) === null || _b === void 0 ? void 0 : _b.files) !== null && _c !== void 0 ? _c : null, zone);
            });
            (_a = zoneElement.querySelector('[data-zone-more]')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', (event) => {
                this.openModal(zone, event.currentTarget);
            });
        });
        (_a = this.root.querySelector('[data-upload-form]')) === null || _a === void 0 ? void 0 : _a.addEventListener('submit', (event) => {
            event.preventDefault();
            const notice = this.root.querySelector('[data-submit-notice]');
            if (notice) {
                notice.textContent = this.settings.labels.backendDisabled;
                notice.hidden = false;
                notice.focus();
            }
        });
        (_b = this.root.querySelector('[data-reset]')) === null || _b === void 0 ? void 0 : _b.addEventListener('click', () => {
            this.reset();
        });
        this.root.querySelectorAll('[data-modal-close]').forEach((control) => {
            control.addEventListener('click', () => this.closeModal());
        });
        document.addEventListener('keydown', (event) => this.handleModalKeydown(event));
        window.addEventListener('beforeunload', () => this.destroy());
    }
    readZone(element) {
        const zone = element.dataset.zone;
        return ZONES.includes(zone) ? zone : 'uncategorized';
    }
    addFiles(fileList, requestedZone) {
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
            const portalFile = {
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
    validateFile(file) {
        if (!this.settings.allowedTypes.includes(file.type)) {
            return 'Unsupported format. Use WebP, JPEG or PNG.';
        }
        if (file.size > this.settings.maxFileSize) {
            return 'File exceeds the 20 MB limit.';
        }
        return null;
    }
    detectZone(filename) {
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
    createId() {
        if ('randomUUID' in crypto) {
            return crypto.randomUUID();
        }
        return `skvn-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    }
    moveFile(fileId, zone) {
        const portalFile = this.files.get(fileId);
        if (!portalFile) {
            return;
        }
        portalFile.zone = zone;
        portalFile.manualAssignment = true;
        this.render();
    }
    removeFile(fileId) {
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
    render() {
        ZONES.forEach((zone) => {
            const zoneFiles = Array.from(this.files.values()).filter((portalFile) => portalFile.zone === zone);
            const zoneElement = this.root.querySelector(`[data-zone="${zone}"]`);
            const count = zoneElement === null || zoneElement === void 0 ? void 0 : zoneElement.querySelector('[data-zone-count]');
            const previews = zoneElement === null || zoneElement === void 0 ? void 0 : zoneElement.querySelector('[data-zone-previews]');
            const moreButton = zoneElement === null || zoneElement === void 0 ? void 0 : zoneElement.querySelector('[data-zone-more]');
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
        const totalCount = this.root.querySelector('[data-total-count]');
        const submit = this.root.querySelector('[data-submit]');
        const reset = this.root.querySelector('[data-reset]');
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
    createPreview(portalFile, modal = false) {
        const card = document.createElement('article');
        card.className = modal
            ? 'skvn-tracking-preview skvn-tracking-preview--modal'
            : 'skvn-tracking-preview';
        card.draggable = true;
        card.dataset.fileId = portalFile.id;
        card.addEventListener('dragstart', (event) => {
            var _a;
            (_a = event.dataTransfer) === null || _a === void 0 ? void 0 : _a.setData('application/x-skvn-tracking-file', portalFile.id);
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
        zoneSelect.addEventListener('change', () => this.moveFile(portalFile.id, zoneSelect.value));
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
    renderErrors() {
        const errorList = this.root.querySelector('[data-error-list]');
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
    openModal(zone, trigger) {
        var _a;
        const modal = this.root.querySelector('[data-gallery-modal]');
        const title = modal === null || modal === void 0 ? void 0 : modal.querySelector('[data-modal-title]');
        const grid = modal === null || modal === void 0 ? void 0 : modal.querySelector('[data-modal-grid]');
        if (!modal || !title || !grid) {
            return;
        }
        const zoneFiles = Array.from(this.files.values()).filter((portalFile) => portalFile.zone === zone);
        title.textContent = ZONE_LABELS[zone];
        grid.replaceChildren(...zoneFiles.map((portalFile) => this.createPreview(portalFile, true)));
        modal.hidden = false;
        document.body.classList.add('skvn-tracking-modal-open');
        this.modalReturnFocus = trigger;
        (_a = modal.querySelector('.skvn-tracking-modal__close')) === null || _a === void 0 ? void 0 : _a.focus();
    }
    closeModal() {
        var _a;
        const modal = this.root.querySelector('[data-gallery-modal]');
        if (!modal || modal.hidden) {
            return;
        }
        modal.hidden = true;
        document.body.classList.remove('skvn-tracking-modal-open');
        (_a = this.modalReturnFocus) === null || _a === void 0 ? void 0 : _a.focus();
        this.modalReturnFocus = null;
    }
    handleModalKeydown(event) {
        const modal = this.root.querySelector('[data-gallery-modal]');
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
        const focusable = Array.from(modal.querySelectorAll('button:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'));
        if (focusable.length === 0) {
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        }
        else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
    destroy() {
        this.files.forEach((portalFile) => URL.revokeObjectURL(portalFile.previewUrl));
        this.files.clear();
        this.fingerprints.clear();
    }
    reset() {
        this.destroy();
        this.errors = [];
        this.closeModal();
        this.render();
    }
}
(() => {
    const portalWindow = window;
    const settings = portalWindow.skvnTrackingUploadPortal;
    const root = document.querySelector('[data-upload-portal]');
    if (settings && root) {
        new UploadPortal(root, settings);
    }
})();

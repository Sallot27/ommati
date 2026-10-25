/* In-app PDF reader (loaded only on the reading page and the admin book list). */
import * as pdfjs from 'pdfjs-dist/legacy/build/pdf.mjs';
import workerUrl from 'pdfjs-dist/legacy/build/pdf.worker.min.mjs?url';

pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

async function send(url, { method = 'POST', json, form } = {}) {
    const res = await fetch(url, {
        method,
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), ...(json ? { 'Content-Type': 'application/json' } : {}) },
        body: json ? JSON.stringify(json) : form,
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.status === 204 ? null : res.json();
}

export function openPdf(url) {
    return pdfjs.getDocument({ url, disableRange: true, disableStream: true, isEvalSupported: false }).promise;
}

/** Render page 1 small and upload it as the book's cover. */
export async function makeCover(doc, uploadUrl) {
    const page = await doc.getPage(1);
    const base = page.getViewport({ scale: 1 });
    const viewport = page.getViewport({ scale: 360 / base.width });
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(viewport.width);
    canvas.height = Math.round(viewport.height);
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    await page.render({ canvasContext: ctx, viewport }).promise;
    const blob = await new Promise((r) => canvas.toBlob(r, 'image/jpeg', 0.8));
    const form = new FormData();
    form.append('cover', blob, 'cover.jpg');
    await send(uploadUrl, { form });
}

export function pdfReader(cfg) {
    // Kept outside Alpine's reactive state: proxies break PDF.js's private class fields.
    let doc = null;
    let task = null;

    return {
        page: cfg.page,
        pages: 0,
        loading: true,
        error: '',
        rendering: false,
        zoom: 1,
        bookmarks: cfg.bookmarks,
        sheet: false,
        note: '',
        jump: '',
        saveTimer: null,

        async init() {
            try {
                doc = await openPdf(cfg.streamUrl);
                this.pages = doc.numPages;
                this.page = Math.min(Math.max(1, this.page), this.pages);
                this.loading = false;
                await this.$nextTick();
                await this.render();
                this.save();
                if (!cfg.hasCover) makeCover(doc, cfg.coverUrl).catch(() => {});
            } catch (e) {
                console.error('pdf reader:', e);
                this.loading = false;
                this.error = 'تعذّر فتح الكتاب. تأكدي من الاتصال بالإنترنت، أو حمّلي نسخة على الجهاز.';
            }
            window.addEventListener('resize', () => this.render());
            this.swipe();
        },

        async render() {
            if (!doc) return;
            const canvas = this.$refs.canvas;
            const page = await doc.getPage(this.page);
            const width = this.$refs.stage.clientWidth - 16; // stage padding
            const base = page.getViewport({ scale: 1 });
            const scale = (width / base.width) * this.zoom;
            const ratio = window.devicePixelRatio || 1;
            const viewport = page.getViewport({ scale: scale * ratio });
            if (task) task.cancel();
            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);
            canvas.style.width = `${Math.floor(viewport.width / ratio)}px`;
            canvas.style.height = `${Math.floor(viewport.height / ratio)}px`;
            this.rendering = true;
            task = page.render({ canvasContext: canvas.getContext('2d'), viewport });
            try {
                await task.promise;
            } catch {
                /* cancelled by a newer render */
            }
            this.rendering = false;
        },

        go(n) {
            const target = Math.min(Math.max(1, n), this.pages);
            if (target === this.page) return;
            this.page = target;
            this.render();
            this.$refs.stage.scrollTo({ top: 0 });
            window.scrollTo({ top: 0 });
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.save(), 600);
        },
        next() { this.go(this.page + 1); },
        prev() { this.go(this.page - 1); },
        goJump() {
            const n = parseInt(String(this.jump).replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)), 10);
            if (n) this.go(n);
            this.jump = '';
        },
        setZoom(z) {
            this.zoom = Math.min(3, Math.max(1, z));
            this.render();
        },

        save() {
            send(cfg.progressUrl, { json: { page: this.page, pages: this.pages } }).catch(() => {});
        },

        isBookmarked() {
            return this.bookmarks.some((b) => b.page === this.page);
        },
        async addBookmark() {
            const b = await send(cfg.bookmarkUrl, { json: { page: this.page, note: this.note || null } }).catch(() => null);
            if (b) {
                this.bookmarks.push(b);
                this.bookmarks.sort((x, y) => x.page - y.page);
            }
            this.note = '';
            this.sheet = false;
        },
        async removeBookmark(b) {
            await send(`${cfg.bookmarkUrl}/${b.id}`, { method: 'DELETE' }).catch(() => {});
            this.bookmarks = this.bookmarks.filter((x) => x.id !== b.id);
        },

        swipe() {
            let x0 = null;
            const el = this.$refs.stage;
            el.addEventListener('touchstart', (e) => { if (this.zoom === 1) x0 = e.touches[0].clientX; }, { passive: true });
            el.addEventListener('touchend', (e) => {
                if (x0 === null) return;
                const dx = e.changedTouches[0].clientX - x0;
                x0 = null;
                // Arabic books turn right-to-left: swipe right for the next page.
                if (dx > 60) this.next();
                else if (dx < -60) this.prev();
            }, { passive: true });
        },
    };
}

/** Admin book list: generate missing covers one at a time. */
export async function generateMissingCovers(items) {
    for (const { streamUrl, coverUrl, el } of items) {
        try {
            const doc = await openPdf(streamUrl);
            await makeCover(doc, coverUrl);
            doc.destroy();
            if (el) el.dataset.done = '1';
        } catch {
            /* skip this book */
        }
    }
}

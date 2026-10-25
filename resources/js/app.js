import Alpine from 'alpinejs';

/* ---------------- helpers ---------------- */

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

export async function post(url, data = {}) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify(data),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.status === 204 ? null : res.json();
}

const store = {
    get(key, fallback = null) {
        try {
            const v = localStorage.getItem(key);
            return v === null ? fallback : JSON.parse(v);
        } catch {
            return fallback;
        }
    },
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch {
            /* storage unavailable */
        }
    },
};

const AR_DIGITS = '٠١٢٣٤٥٦٧٨٩';
export const ar = (n) => String(n).replace(/\d/g, (d) => AR_DIGITS[d]);

const vibrate = (ms = 15) => {
    if (store.get('vibrate', true) && navigator.vibrate) navigator.vibrate(ms);
};

/** One shared <audio> element so only one recitation plays at a time. */
const player = new Audio();
player.preload = 'auto';

function playOnce(src) {
    return new Promise((resolve, reject) => {
        const cleanup = () => {
            player.removeEventListener('ended', onEnd);
            player.removeEventListener('error', onErr);
            player.removeEventListener('pause', onPause);
        };
        const onEnd = () => { cleanup(); resolve(true); };
        const onErr = () => { cleanup(); reject(new Error('audio')); };
        const onPause = () => { if (!player.ended) { cleanup(); resolve(false); } };
        player.addEventListener('ended', onEnd);
        player.addEventListener('error', onErr);
        player.addEventListener('pause', onPause);
        if (player.src !== src) player.src = src;
        player.currentTime = 0;
        player.play().catch(onErr);
    });
}

/* ---------------- app shell: text size, theme, service worker ---------------- */

function applyPrefs() {
    const html = document.documentElement;
    html.dataset.size = store.get('textSize', 2);
    const theme = store.get('theme', 'auto');
    const dark = theme === 'dark' || (theme === 'auto' && matchMedia('(prefers-color-scheme: dark)').matches);
    html.classList.toggle('dark', dark);
}
applyPrefs();
matchMedia('(prefers-color-scheme: dark)').addEventListener?.('change', applyPrefs);

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

document.addEventListener('alpine:init', () => {
    Alpine.store('prefs', {
        size: store.get('textSize', 2),
        theme: store.get('theme', 'auto'),
        setSize(n) {
            this.size = Math.min(5, Math.max(1, n));
            store.set('textSize', this.size);
            applyPrefs();
        },
        setTheme(t) {
            this.theme = t;
            store.set('theme', t);
            applyPrefs();
        },
    });
});

/* ---------------- prayer times (home + prayer page) ---------------- */

const PRAYERS = ['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'];

function toMinutes(hhmm) {
    const [h, m] = hhmm.split(':').map(Number);
    return h * 60 + m;
}

function to12h(hhmm) {
    let [h, m] = hhmm.split(':').map(Number);
    const suffix = h < 12 ? 'ص' : 'م';
    h = h % 12 || 12;
    return `${ar(h)}:${ar(String(m).padStart(2, '0'))} ${suffix}`;
}

Alpine.data('prayerTimes', (defaults) => ({
    loading: true,
    error: null,
    names: {},
    today: null,
    tomorrow: null,
    next: null,
    countdown: '',
    place: store.get('place', defaults.place),
    lat: store.get('lat', defaults.lat),
    lng: store.get('lng', defaults.lng),
    locating: false,
    timer: null,

    init() {
        this.load();
        this.timer = setInterval(() => this.tick(), 20000);
    },
    destroy() {
        clearInterval(this.timer);
    },
    async load() {
        this.loading = true;
        this.error = null;
        try {
            const res = await fetch(`/api/prayer?lat=${this.lat}&lng=${this.lng}`, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error();
            const data = await res.json();
            this.names = data.names;
            this.today = data.today;
            this.tomorrow = data.tomorrow;
            this.tick();
        } catch {
            this.error = 'تعذّر جلب المواقيت. تأكدي من الاتصال بالإنترنت.';
        } finally {
            this.loading = false;
        }
    },
    nowIn(tz) {
        const parts = new Intl.DateTimeFormat('en-GB', { timeZone: tz, hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).formatToParts(new Date());
        const h = +parts.find((p) => p.type === 'hour').value;
        const m = +parts.find((p) => p.type === 'minute').value;
        return h * 60 + m;
    },
    tick() {
        if (!this.today) return;
        const now = this.nowIn(this.today.timezone);
        let next = PRAYERS.filter((p) => p !== 'Sunrise').find((p) => toMinutes(this.today.timings[p]) > now);
        let mins;
        if (next) {
            mins = toMinutes(this.today.timings[next]) - now;
            this.next = { key: next, name: this.names[next], time: this.today.timings[next], tomorrow: false };
        } else if (this.tomorrow) {
            next = 'Fajr';
            mins = 24 * 60 - now + toMinutes(this.tomorrow.timings.Fajr);
            this.next = { key: next, name: this.names.Fajr, time: this.tomorrow.timings.Fajr, tomorrow: true };
        }
        if (mins !== undefined) {
            const h = Math.floor(mins / 60);
            const m = mins % 60;
            this.countdown = h ? `بعد ${ar(h)} س و${ar(m)} د` : `بعد ${ar(m)} دقيقة`;
        }
    },
    isNext(key) {
        return this.next?.key === key && !this.next?.tomorrow;
    },
    fmt: to12h,
    async setPlace(name, lat, lng) {
        this.place = name;
        this.lat = lat;
        this.lng = lng;
        store.set('place', name);
        store.set('lat', lat);
        store.set('lng', lng);
        post('/prayer/location', { lat, lng, name }).catch(() => {});
        await this.load();
        this.$dispatch('location-changed', { lat, lng });
    },
    locate() {
        if (!navigator.geolocation) {
            this.error = 'الجهاز لا يدعم تحديد الموقع، اختاري المدينة من القائمة.';
            return;
        }
        this.locating = true;
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                this.locating = false;
                this.setPlace('موقعي الحالي', +pos.coords.latitude.toFixed(4), +pos.coords.longitude.toFixed(4));
            },
            () => {
                this.locating = false;
                this.error = 'لم نتمكن من معرفة الموقع. اسمحي للتطبيق بالوصول للموقع أو اختاري المدينة.';
            },
            { enableHighAccuracy: false, timeout: 15000 },
        );
    },
}));

/* ---------------- qibla compass ---------------- */

Alpine.data('qibla', (defaults) => ({
    bearing: 0,
    heading: null,
    supported: 'DeviceOrientationEvent' in window,
    needsPermission: typeof DeviceOrientationEvent?.requestPermission === 'function',
    active: false,
    init() {
        this.compute(store.get('lat', defaults.lat), store.get('lng', defaults.lng));
        window.addEventListener('location-changed', (e) => this.compute(e.detail.lat, e.detail.lng));
        if (this.supported && !this.needsPermission) this.listen();
    },
    compute(lat, lng) {
        const rad = (d) => (d * Math.PI) / 180;
        const kLat = rad(21.4225);
        const kLng = rad(39.8262);
        const φ = rad(lat);
        const Δ = kLng - rad(lng);
        const y = Math.sin(Δ);
        const x = Math.cos(φ) * Math.tan(kLat) - Math.sin(φ) * Math.cos(Δ);
        this.bearing = (((Math.atan2(y, x) * 180) / Math.PI) + 360) % 360;
    },
    async enable() {
        try {
            const r = await DeviceOrientationEvent.requestPermission();
            if (r === 'granted') this.listen();
        } catch {
            /* denied */
        }
    },
    listen() {
        const handler = (e) => {
            let h = null;
            if (typeof e.webkitCompassHeading === 'number') h = e.webkitCompassHeading;
            else if (e.absolute && typeof e.alpha === 'number') h = 360 - e.alpha;
            if (h !== null) {
                this.heading = h;
                this.active = true;
            }
        };
        window.addEventListener('deviceorientationabsolute', handler, true);
        window.addEventListener('deviceorientation', handler, true);
    },
    get needle() {
        return this.heading === null ? this.bearing : this.bearing - this.heading;
    },
    get aligned() {
        if (this.heading === null) return false;
        const d = Math.abs(((this.needle % 360) + 540) % 360 - 180);
        return d < 6;
    },
    ar,
}));

/* ---------------- adhkar counter ---------------- */

Alpine.data('adhkar', (slug, items) => ({
    items: items.map((it) => ({ ...it, left: it.count })),
    finished: false,
    get doneCount() {
        return this.items.filter((i) => i.left === 0).length;
    },
    get progress() {
        return this.items.length ? this.doneCount / this.items.length : 0;
    },
    tap(i) {
        const it = this.items[i];
        if (it.left === 0) return;
        it.left--;
        vibrate(it.left === 0 ? 40 : 12);
        if (it.left === 0) {
            const next = this.items.findIndex((x, j) => j > i && x.left > 0);
            if (next !== -1) {
                this.$nextTick(() => document.getElementById(`zikr-${next}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
            }
            if (this.doneCount === this.items.length && !this.finished) {
                this.finished = true;
                post(`/adhkar/${slug}/done`).catch(() => {});
            }
        }
    },
    reset() {
        this.items.forEach((i) => (i.left = i.count));
        this.finished = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    ar,
}));

/* ---------------- tasbih ---------------- */

Alpine.data('tasbih', () => ({
    presets: [
        { text: 'سُبْحَانَ اللَّهِ', target: 33 },
        { text: 'الْحَمْدُ لِلَّهِ', target: 33 },
        { text: 'اللَّهُ أَكْبَرُ', target: 34 },
        { text: 'لَا إِلَهَ إِلَّا اللَّهُ', target: 100 },
        { text: 'أَسْتَغْفِرُ اللَّهَ', target: 100 },
        { text: 'سُبْحَانَ اللَّهِ وَبِحَمْدِهِ', target: 100 },
        { text: 'اللَّهُمَّ صَلِّ عَلَى مُحَمَّدٍ', target: 100 },
        { text: 'لَا حَوْلَ وَلَا قُوَّةَ إِلَّا بِاللَّهِ', target: 100 },
    ],
    index: store.get('tasbih.index', 0),
    count: store.get('tasbih.count', 0),
    total: store.get('tasbih.total', 0),
    today: store.get('tasbih.today', { d: new Date().toDateString(), n: 0 }),
    init() {
        if (this.today.d !== new Date().toDateString()) this.today = { d: new Date().toDateString(), n: 0 };
    },
    get current() {
        return this.presets[this.index];
    },
    get dash() {
        const c = 2 * Math.PI * 120;
        return c - (Math.min(this.count, this.current.target) / this.current.target) * c;
    },
    tap() {
        this.count++;
        this.total++;
        this.today.n++;
        if (this.count === this.current.target) vibrate([60, 60, 60]);
        else vibrate(10);
        this.save();
    },
    choose(i) {
        this.index = i;
        this.count = 0;
        this.save();
    },
    reset() {
        this.count = 0;
        this.save();
    },
    save() {
        store.set('tasbih.index', this.index);
        store.set('tasbih.count', this.count);
        store.set('tasbih.total', this.total);
        store.set('tasbih.today', this.today);
    },
    ar,
}));

/* ---------------- Quran reader with recitation and tafsir ---------------- */

Alpine.data('quranReader', (surah, reciter, startAyah = null, autoplay = false) => ({
    surah,
    reciter: store.get('reciter', reciter),
    playing: false,
    current: null,
    repeat: 1,
    sheet: null, // ayah number whose sheet is open
    tafsirSource: 'muyassar',
    tafsirText: '',
    tafsirLoading: false,
    stopFlag: false,
    async init() {
        if (startAyah) {
            this.$nextTick(() => document.getElementById(`a-${startAyah}`)?.scrollIntoView({ block: 'center' }));
        }
        if (autoplay) this.playFrom(startAyah || 1);
    },
    notice: '',
    async load() {
        if (!this._data || this._for !== this.reciter) {
            const res = await fetch(`/api/quran/${this.surah}?reciter=${this.reciter}`);
            this._data = await res.json();
            this._for = this.reciter;
        }
        return this._data;
    },
    async urls() {
        return (await this.load()).ayahs.map((a) => a.audio);
    },
    async playFrom(n) {
        this.stop();
        this.stopFlag = false;
        const data = await this.load();
        const wholeSurah = this.reciter.startsWith('mp3.');
        this.notice = '';
        if (wholeSurah && data.full && n === 1) {
            // Whole-surah recording: play it as one track.
            this.playing = true;
            this.current = null;
            for (let r = 0; r < this.repeat; r++) {
                try {
                    if (!(await playOnce(data.full)) || this.stopFlag) break;
                } catch {
                    this.notice = 'تعذّر تشغيل التلاوة، تأكدي من الاتصال بالإنترنت.';
                    break;
                }
            }
            this.playing = false;
            return;
        }
        if (wholeSurah) {
            this.notice = data.full
                ? 'التشغيل من آية محددة متاح بصوت الشيخ الحصري'
                : 'هذه السورة غير متوفرة بصوت هذا القارئ، فتُتلى بصوت الشيخ الحصري';
        }
        const urls = data.ayahs.map((a) => a.audio);
        this.playing = true;
        for (let i = n; i <= urls.length; i++) {
            this.current = i;
            document.getElementById(`a-${i}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            for (let r = 0; r < this.repeat; r++) {
                try {
                    const completed = await playOnce(urls[i - 1]);
                    if (!completed || this.stopFlag) {
                        this.playing = false;
                        return;
                    }
                } catch {
                    this.playing = false;
                    return;
                }
            }
            this.saveLastRead(i);
        }
        this.playing = false;
        this.current = null;
    },
    toggle() {
        if (this.playing) this.stop();
        else this.playFrom(this.current || 1);
    },
    stop() {
        this.stopFlag = true;
        player.pause();
        this.playing = false;
    },
    setReciter(id) {
        this.reciter = id;
        store.set('reciter', id);
        post('/settings', { reciter: id }).catch(() => {});
        if (this.playing) this.playFrom(this.current || 1);
    },
    async openSheet(n) {
        this.sheet = n;
        this.saveLastRead(n);
        this.loadTafsir();
    },
    async loadTafsir() {
        this.tafsirLoading = true;
        this.tafsirText = '';
        try {
            const res = await fetch(`/api/tafsir/${this.surah}/${this.sheet}?source=${this.tafsirSource}`);
            const data = await res.json();
            this.tafsirText = data.text || 'لا يتوفر تفسير لهذه الآية في هذا المصدر.';
        } catch {
            this.tafsirText = 'تعذّر تحميل التفسير، تأكدي من الاتصال بالإنترنت.';
        } finally {
            this.tafsirLoading = false;
        }
    },
    saveLastRead(n) {
        post('/quran/last-read', { s: this.surah, a: n }).catch(() => {});
    },
    ar,
}));

/* ---------------- the Madinah Mushaf, page by page ---------------- */

const mushafFonts = new Map(); // font id -> Promise

function mushafFont(base, id) {
    if (!mushafFonts.has(id)) {
        const file = id === 'B' ? 'QCF4_QBSML.woff2' : `QCF4_Hafs_${String(id).padStart(2, '0')}_W.woff2`;
        const face = new FontFace(`qcf${id}`, `url(${base}/${file})`, { display: 'block' });
        const p = face.load().then((f) => { document.fonts.add(f); return f; });
        p.catch(() => mushafFonts.delete(id));
        mushafFonts.set(id, p);
    }
    return mushafFonts.get(id);
}

Alpine.data('mushaf', (cfg) => ({
    page: cfg.page,
    lines: [],
    font: 1,
    loading: true,
    error: '',
    size: 20,
    active: cfg.focus,
    sel: null,
    sheet: null, // 'ayah' | 'index'
    tab: 'surah',
    q: '',
    jump: '',
    playing: false,
    stopFlag: false,
    reciter: cfg.reciters[store.get('reciter')] ? store.get('reciter') : cfg.reciter,
    reciters: cfg.reciters,
    surahs: cfg.surahs,
    juz: cfg.juz,
    tafsirSource: 'muyassar',
    tafsirText: '',
    tafsirLoading: false,
    _cache: {},
    _seq: 0,
    _saveTimer: null,

    init() {
        this.load(this.page);
        let t;
        window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(() => this.fit(), 120); });
        window.addEventListener('keydown', (e) => {
            if (this.sheet) return;
            if (e.key === 'ArrowLeft') this.next();
            if (e.key === 'ArrowRight') this.prev();
        });
        let x0 = null, y0 = null;
        const el = this.$refs.page;
        el.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; }, { passive: true });
        el.addEventListener('touchend', (e) => {
            if (x0 === null) return;
            const dx = e.changedTouches[0].clientX - x0, dy = e.changedTouches[0].clientY - y0;
            x0 = null;
            if (Math.abs(dx) < 60 || Math.abs(dy) > Math.abs(dx)) return;
            // The mushaf opens right-to-left: swipe to the right for the next page.
            if (dx > 0) this.next(); else this.prev();
        }, { passive: true });
    },

    async fetchPage(n) {
        if (!this._cache[n]) {
            this._cache[n] = fetch(`${cfg.pagesUrl}/${String(n).padStart(3, '0')}.json`).then((r) => {
                if (!r.ok) throw new Error('page');
                return r.json();
            });
            this._cache[n].catch(() => delete this._cache[n]);
        }
        return this._cache[n];
    },

    async load(n) {
        n = Math.min(604, Math.max(1, n));
        const seq = ++this._seq;
        this.loading = true;
        this.error = '';
        try {
            const data = await this.fetchPage(n);
            const ids = new Set([data.font]);
            data.lines.flat().forEach((w) => { if (w.f) ids.add(w.f); });
            await Promise.all([...ids].map((id) => mushafFont(cfg.fontUrl, id)));
            if (seq !== this._seq) return;
            this.page = n;
            this.font = data.font;
            this.lines = data.lines;
            this.loading = false;
            await this.$nextTick();
            this.fit();
            history.replaceState(null, '', `/mushaf/${n}`);
            clearTimeout(this._saveTimer);
            this._saveTimer = setTimeout(() => post(cfg.saveUrl, { page: this.page }).catch(() => {}), 800);
            [n + 1, n - 1].filter((x) => x >= 1 && x <= 604).forEach((x) => this.fetchPage(x).catch(() => {}));
        } catch {
            if (seq !== this._seq) return;
            this.loading = false;
            this.error = 'تعذّر تحميل الصفحة. تأكدي من الاتصال بالإنترنت ثم حاولي مرة أخرى.';
        }
    },

    /* Size the glyphs so the whole page fits the screen, the longest line filling the width. */
    fit() {
        const box = this.$refs.lines;
        if (!box) return;
        const probe = 40;
        box.style.width = '';
        box.style.fontSize = `${probe}px`;
        let widest = 0;
        const widths = [];
        box.querySelectorAll('[data-line="text"]').forEach((line) => {
            let w = 0;
            line.querySelectorAll('span').forEach((s) => { w += s.getBoundingClientRect().width; });
            widths.push([line, w]);
            widest = Math.max(widest, w);
        });
        if (!widest) return;
        // Short lines (end of a surah) are centred, as in the printed mushaf.
        widths.forEach(([line, w]) => line.classList.toggle('mushaf-short', w < widest * 0.8));
        const avail = box.parentElement.clientWidth - 2; // frame content width
        const byWidth = Math.min(probe * (avail / widest) * 0.985, 46);
        // Height left between the page top and the reading bar (lines are 1.95em; the opening pages are shorter).
        const top = box.getBoundingClientRect().top + window.scrollY;
        const room = window.innerHeight - top - 120;
        const byHeight = room / (15 * 1.95 + 0.6);
        // Never shrink below 85% of the width fit — a little scrolling beats tiny letters.
        this.size = this.page <= 2 ? byWidth * 0.9 : Math.max(Math.min(byWidth, byHeight), byWidth * 0.85);
        box.style.fontSize = `${this.size}px`;
        box.style.width = this.size < byWidth ? `${(widest * this.size) / probe / 0.985}px` : '';
    },

    lineKind(line) {
        if (line.some((w) => w.t === 'h')) return 'header';
        if (line.length === 1 && line[0].t === 'b') return 'basmala';
        return 'text';
    },
    glyph(w) { return String.fromCharCode(w.c); },
    family(w) { return `qcf${w.f || this.font}`; },
    isAyah(w) { return !!w.k && w.t !== 'q'; },

    next() { if (this.page < 604) this.load(this.page + 1); },
    prev() { if (this.page > 1) this.load(this.page - 1); },
    goPage(n) { this.sheet = null; this.load(n); },
    goJump() {
        const n = parseInt(String(this.jump).replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)), 10);
        if (n >= 1 && n <= 604) this.goPage(n);
        this.jump = '';
    },

    get meta() { return cfg.pages[this.page - 1] || { s: [], j: 1 }; },
    get surahTitle() { return this.meta.s.map((n) => this.surahs[n - 1].name).join(' · '); },
    get filtered() {
        const q = this.q.replace('سورة', '').trim();
        return q ? this.surahs.filter((s) => s.name.includes(q) || String(s.n) === q) : this.surahs;
    },
    keyLabel(k) {
        if (!k) return '';
        const [s, a] = k.split(':').map(Number);
        return `سورة ${this.surahs[s - 1].name} — الآية ${ar(a)}`;
    },

    select(k) {
        this.sel = k;
        this.active = k;
        this.sheet = 'ayah';
        this.loadTafsir();
    },
    closeSheet() {
        this.sheet = null;
        if (!this.playing) this.active = null;
    },
    async loadTafsir() {
        const [s, a] = this.sel.split(':');
        this.tafsirLoading = true;
        this.tafsirText = '';
        try {
            const res = await fetch(`/api/tafsir/${s}/${a}?source=${this.tafsirSource}`);
            const data = await res.json();
            this.tafsirText = data.text || 'لا يتوفر تفسير لهذه الآية في هذا المصدر.';
        } catch {
            this.tafsirText = 'تعذّر تحميل التفسير، تأكدي من الاتصال بالإنترنت.';
        } finally {
            this.tafsirLoading = false;
        }
    },

    audioUrl(s, a) {
        const r = this.reciters[this.reciter];
        return `https://cdn.islamic.network/quran/audio/${r.bitrate}/${this.reciter}/${this.surahs[s - 1].start + a - 1}.mp3`;
    },
    firstOnPage() {
        const w = this.lines.flat().find((x) => x.t === 'w');
        return w ? w.k : '1:1';
    },
    toggle() {
        if (this.playing) this.stop();
        else this.playFrom(this.active || this.firstOnPage());
    },
    stop() {
        this.stopFlag = true;
        player.pause();
        this.playing = false;
    },
    async playFrom(k) {
        this.stop();
        await new Promise((r) => setTimeout(r, 0));
        this.stopFlag = false;
        this.sheet = null;
        this.playing = true;
        let [s, a] = k.split(':').map(Number);
        try {
            while (s <= 114 && !this.stopFlag) {
                this.active = `${s}:${a}`;
                const p = cfg.ayahPages[s][a - 1];
                if (p !== this.page) await this.load(p);
                if (a === 1 && s !== 1 && s !== 9) {
                    // al-Basmala before each surah, as in the mushaf
                    if (!(await playOnce(this.audioUrl(1, 1))) || this.stopFlag) break;
                }
                if (!(await playOnce(this.audioUrl(s, a))) || this.stopFlag) break;
                a++;
                if (a > this.surahs[s - 1].count) { s++; a = 1; }
            }
        } catch {
            this.error = 'تعذّر تشغيل التلاوة، تأكدي من الاتصال بالإنترنت.';
            setTimeout(() => { this.error = ''; }, 4000);
        }
        this.playing = false;
    },
    setReciter(id) {
        this.reciter = id;
        store.set('reciter', id);
        post('/settings', { reciter: id }).catch(() => {});
        if (this.playing) this.playFrom(this.active);
    },
    ar,
}));

/* ---------------- daily ayah: simple play button ---------------- */

Alpine.data('ayahAudio', (urls) => ({
    playing: false,
    current: -1,
    async play() {
        if (this.playing) {
            player.pause();
            this.playing = false;
            return;
        }
        this.playing = true;
        for (let i = 0; i < urls.length; i++) {
            this.current = i;
            try {
                if (!(await playOnce(urls[i]))) break;
            } catch {
                break;
            }
        }
        this.playing = false;
        this.current = -1;
    },
}));

/* ---------------- memorization session ---------------- */

/*
 * Steps per ayah:
 *   listen  – hear the ayah N times while reading it
 *   read    – read it yourself, words fading progressively
 *   recite  – everything hidden; reveal word by word, then judge yourself
 * After each new ayah, the ayat learned so far today are linked together.
 */
Alpine.data('hifzSession', (cfg) => ({
    surah: cfg.surah,
    name: cfg.name,
    total: cfg.count,
    memorized: new Set(cfg.memorized),
    reciter: store.get('reciter', cfg.reciter),
    target: cfg.target,
    ayahs: [],
    loading: true,
    index: cfg.from, // current ayah number
    learnedToday: [],
    step: 'listen',
    repeats: store.get('hifz.repeats', 3),
    played: 0,
    playing: false,
    fade: 0, // 0 = all shown, 1 = first letters, 2 = first word only, 3 = hidden
    revealed: 0,
    linking: false,
    done: false,
    async init() {
        const res = await fetch(`/api/quran/${this.surah}?reciter=${this.reciter}`);
        const data = await res.json();
        this.ayahs = data.ayahs;
        this.loading = false;
    },
    get ayah() {
        return this.ayahs[this.index - 1];
    },
    get words() {
        return this.ayah ? this.ayah.text.split(' ') : [];
    },
    firstLetter(w) {
        // first base letter plus its diacritics
        const m = w.match(/^[ء-يٱ][ً-ٰٟۖ-ۭ]*/u);
        return m ? m[0] : w.slice(0, 1);
    },
    wordClass(i) {
        if (this.step === 'listen') return '';
        if (this.step === 'recite') return i < this.revealed ? '' : 'w-hidden';
        if (this.fade === 0) return '';
        if (this.fade === 1) return 'w-hint';
        if (this.fade === 2) return i === 0 ? '' : 'w-hidden';
        return 'w-hidden';
    },
    async listen() {
        if (this.playing) {
            player.pause();
            this.playing = false;
            return;
        }
        this.playing = true;
        store.set('hifz.repeats', this.repeats);
        while (this.played < this.repeats) {
            try {
                if (!(await playOnce(this.ayah.audio))) break;
            } catch {
                break;
            }
            this.played++;
        }
        this.playing = false;
    },
    toRead() {
        player.pause();
        this.playing = false;
        this.step = 'read';
        this.fade = 0;
    },
    moreFade() {
        if (this.fade < 3) this.fade++;
        else this.toRecite();
    },
    toRecite() {
        this.step = 'recite';
        this.revealed = 0;
    },
    reveal() {
        if (this.revealed < this.words.length) this.revealed++;
    },
    revealAll() {
        this.revealed = this.words.length;
    },
    async gotIt() {
        if (!this.memorized.has(this.index)) {
            await post('/hifz/mark', { surah: this.surah, ayah: this.index, action: 'memorized' }).catch(() => {});
            this.memorized.add(this.index);
        }
        this.learnedToday.push(this.index);
        vibrate(40);
        if (this.learnedToday.length > 1) {
            this.linking = true;
        } else {
            this.nextAyah();
        }
    },
    again() {
        this.step = 'listen';
        this.played = 0;
        this.fade = 0;
    },
    async playLink() {
        if (this.playing) {
            player.pause();
            this.playing = false;
            return;
        }
        this.playing = true;
        for (const n of this.learnedToday) {
            try {
                if (!(await playOnce(this.ayahs[n - 1].audio))) break;
            } catch {
                break;
            }
        }
        this.playing = false;
    },
    nextAyah() {
        player.pause();
        this.playing = false;
        this.linking = false;
        if (this.learnedToday.length >= this.target || this.index >= this.total) {
            this.done = true;
            return;
        }
        this.index++;
        this.step = 'listen';
        this.played = 0;
        this.fade = 0;
        this.revealed = 0;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    keepGoing() {
        this.target += cfg.target;
        this.done = false;
        if (this.index < this.total) this.nextAyah();
    },
    ar,
}));

/* ---------------- review session (spaced repetition) ---------------- */

Alpine.data('reviewSession', (items) => ({
    items,
    i: 0,
    shown: false,
    playing: false,
    results: { good: 0, again: 0 },
    get item() {
        return this.items[this.i];
    },
    get finished() {
        return this.i >= this.items.length;
    },
    async play() {
        if (this.playing) {
            player.pause();
            this.playing = false;
            return;
        }
        this.playing = true;
        try {
            await playOnce(this.item.audio);
        } catch {
            /* ignore */
        }
        this.playing = false;
    },
    async rate(action) {
        player.pause();
        this.playing = false;
        post('/hifz/mark', { surah: this.item.s, ayah: this.item.a, action }).catch(() => {});
        this.results[action]++;
        vibrate(20);
        this.i++;
        this.shown = false;
    },
    ar,
}));

/* ---------------- push notifications ---------------- */

function urlBase64ToUint8Array(base64) {
    const padding = '='.repeat((4 - (base64.length % 4)) % 4);
    const raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

Alpine.data('pushSettings', (vapidKey) => ({
    supported: 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window,
    standalone: matchMedia('(display-mode: standalone)').matches || navigator.standalone === true,
    isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent),
    enabled: false,
    busy: false,
    message: '',
    async init() {
        if (!this.supported) return;
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        this.enabled = !!sub && Notification.permission === 'granted';
    },
    async enable() {
        this.busy = true;
        this.message = '';
        try {
            const perm = await Notification.requestPermission();
            if (perm !== 'granted') {
                this.message = 'لم يتم السماح بالإشعارات. يمكن تفعيلها من إعدادات المتصفح.';
                return;
            }
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(vapidKey) });
            await post('/push/subscribe', sub.toJSON());
            this.enabled = true;
            this.message = 'تم تفعيل التذكيرات على هذا الجهاز ✓';
        } catch {
            this.message = 'تعذّر تفعيل الإشعارات على هذا الجهاز.';
        } finally {
            this.busy = false;
        }
    },
    async disable() {
        this.busy = true;
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (sub) {
                await post('/push/unsubscribe', { endpoint: sub.endpoint }).catch(() => {});
                await sub.unsubscribe();
            }
            this.enabled = false;
            this.message = 'تم إيقاف التذكيرات على هذا الجهاز.';
        } finally {
            this.busy = false;
        }
    },
    async test() {
        await post('/push/test').catch(() => {});
        this.message = 'أُرسل إشعار تجريبي، سيصل خلال ثوانٍ.';
    },
}));

/* ---------------- hijri date (Umm al-Qura) ---------------- */

Alpine.data('hijri', () => ({
    text: '',
    init() {
        try {
            this.text = new Intl.DateTimeFormat('ar-SA-u-ca-islamic-umalqura', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
            const weekday = new Intl.DateTimeFormat('ar', { weekday: 'long' }).format(new Date());
            this.text = `${weekday}، ${this.text}`;
        } catch {
            this.text = '';
        }
    },
}));

Alpine.data('hadithSave', (id, saved) => ({
    saved,
    async toggle() {
        const r = await post(`/hadith/${id}/save`).catch(() => null);
        if (r) this.saved = r.saved;
        vibrate(20);
    },
}));

window.Alpine = Alpine;
window.ar = ar;

// The PDF reader is large, so it is only loaded on pages that need it.
if (document.querySelector('[data-needs-pdf]')) {
    import('./pdf-reader.js').then((m) => {
        Alpine.data('pdfReader', m.pdfReader);
        window.generateMissingCovers = m.generateMissingCovers;
        Alpine.start();
        document.dispatchEvent(new Event('pdf-ready'));
    });
} else {
    Alpine.start();
}

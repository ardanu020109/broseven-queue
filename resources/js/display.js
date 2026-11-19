import './echo';

const root = document.getElementById('display');

if (root) {
    const config = JSON.parse(document.getElementById('display-config').textContent);
    const initial = JSON.parse(document.getElementById('display-initial').textContent);
    const endpoint = root.dataset.endpoint;

    const byId = (id) => document.getElementById(id);

    const els = {
        panel: byId('now-panel'),
        number: byId('now-number'),
        service: byId('now-service'),
        counter: byId('now-counter'),
        banner: byId('recall-banner'),
        recent: byId('recent-list'),
        services: byId('service-list'),
        next: byId('next-list'),
        time: byId('display-time'),
        date: byId('display-date'),
        status: byId('connection-status'),
        statusText: byId('connection-text'),
    };

    /* ============================================================
       Jam dan tanggal (zona waktu aplikasi)
       ============================================================ */
    const timeFormat = new Intl.DateTimeFormat('id-ID', {
        timeZone: config.timezone, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
    });
    const shortTimeFormat = new Intl.DateTimeFormat('id-ID', {
        timeZone: config.timezone, hour: '2-digit', minute: '2-digit', hour12: false,
    });
    const dateFormat = new Intl.DateTimeFormat('id-ID', {
        timeZone: config.timezone, weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });

    const tickClock = () => {
        const now = new Date();
        els.time.textContent = timeFormat.format(now).replace(/\./g, ':');
        els.date.textContent = dateFormat.format(now);
    };

    tickClock();
    setInterval(tickClock, 1000);

    /* ============================================================
       Render (hanya textContent: aman dari injeksi HTML)
       ============================================================ */
    const makeRow = (className = 'display-row') => {
        const li = document.createElement('li');
        li.className = className;
        return li;
    };

    const makeSpan = (className, text) => {
        const span = document.createElement('span');
        if (className) {
            span.className = className;
        }
        span.textContent = text;
        return span;
    };

    const makeDot = (color) => {
        const dot = document.createElement('span');
        dot.className = 'service-dot';
        if (color) {
            dot.style.background = color;
        }
        return dot;
    };

    const emptyRow = (text) => {
        const li = makeRow('display-row empty');
        li.textContent = text;
        return li;
    };

    const fitNumber = () => {
        els.number.style.fontSize = '';

        const available = els.panel.clientWidth - 48;
        let size = parseFloat(getComputedStyle(els.number).fontSize);
        let guard = 0;

        while (els.number.scrollWidth > available && size > 24 && guard < 30) {
            size *= 0.93;
            els.number.style.fontSize = `${size}px`;
            guard += 1;
        }
    };

    const restartAnimation = (element, className) => {
        element.classList.remove(className);
        void element.offsetWidth;
        element.classList.add(className);
    };

    let recallTimer = null;

    const showRecall = () => {
        els.banner.hidden = false;
        restartAnimation(els.panel, 'recall');

        clearTimeout(recallTimer);
        recallTimer = setTimeout(() => {
            els.banner.hidden = true;
            els.panel.classList.remove('recall');
        }, 4500);
    };

    let currentKey = null;
    let currentNumber = null;

    const renderNow = (now) => {
        const key = now ? `${now.number}|${now.called_at}|${now.recalled_at ?? ''}` : 'none';

        els.number.textContent = now ? now.number : '-';
        els.number.classList.toggle('is-empty', !now);
        els.service.textContent = now ? (now.service ?? '') : 'Menunggu panggilan';
        els.counter.textContent = now && now.counter ? now.counter : '';
        fitNumber();

        if (currentKey !== null && key !== currentKey && now) {
            restartAnimation(els.number, 'flip');

            // Nomor sama dengan waktu panggil ulang berbeda: tampilkan penanda panggilan ulang.
            if (currentNumber === now.number) {
                showRecall();
            }
        }

        currentKey = key;
        currentNumber = now ? now.number : null;
    };

    const renderServices = (services) => {
        const rows = services.map((service) => {
            const li = makeRow();
            li.append(
                makeDot(service.color),
                makeSpan('', service.name),
                makeSpan('num', service.current ?? '-')
            );
            li.title = `${service.waiting} menunggu${service.is_open ? '' : ' (tutup)'}`;
            return li;
        });

        els.services.replaceChildren(...(rows.length ? rows : [emptyRow('Belum ada layanan')]));
    };

    const renderNext = (queues) => {
        const rows = queues.map((queue) => {
            const li = makeRow();
            li.append(makeDot(queue.color), makeSpan('', queue.service ?? ''), makeSpan('num', queue.number));
            return li;
        });

        els.next.replaceChildren(...(rows.length ? rows : [emptyRow('Tidak ada antrean menunggu')]));
    };

    const renderRecent = (history) => {
        const rows = history.map((entry) => {
            const li = makeRow('display-row recent-item');
            const detail = [entry.service, entry.counter].filter(Boolean).join(' · ');
            const time = shortTimeFormat.format(new Date(entry.at)).replace(/\./g, ':');

            const text = document.createElement('span');
            text.className = 'recent-detail';
            text.append(makeSpan('recent-number', entry.number), makeSpan('recent-meta', `${detail} · ${time}`));

            li.append(text);
            return li;
        });

        els.recent.replaceChildren(...(rows.length ? rows : [emptyRow('Belum ada panggilan')]));
    };

    const render = (snapshot) => {
        renderNow(snapshot.now);
        renderServices(snapshot.services);
        renderNext(snapshot.next);
        renderRecent(snapshot.recent);
    };

    /* ============================================================
       Pengambilan data (penyegaran dan polling cadangan)
       ============================================================ */
    let fetching = false;

    const fetchSnapshot = async () => {
        if (fetching) {
            return;
        }

        fetching = true;

        try {
            const response = await fetch(endpoint, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });

            if (response.ok) {
                render(await response.json());
            }
        } catch (error) {
            // Jaringan terputus: percobaan berikutnya mengikuti jadwal polling.
        } finally {
            fetching = false;
        }
    };

    let refreshTimer = null;

    const refreshSoon = () => {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(fetchSnapshot, 150);
    };

    /* ============================================================
       Suara panggilan (Web Speech API)
       ============================================================ */
    const speechSupported = 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window;
    const STORAGE_KEY = 'bq.display.voice';
    const defaults = {
        enabled: config.sound_enabled,
        volume: config.volume,
        rate: config.rate,
        lang: config.lang,
    };

    const loadVoice = () => {
        try {
            return { ...defaults, ...JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') };
        } catch (error) {
            return { ...defaults };
        }
    };

    const voice = loadVoice();

    const saveVoice = () => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(voice));
        } catch (error) {
            // abaikan
        }
    };

    let unlocked = false;
    const speechQueue = [];
    let speaking = false;

    const normalizeLang = (value) => String(value).replace('_', '-').toLowerCase();

    const pickVoice = () => {
        const voices = window.speechSynthesis.getVoices();
        const wanted = normalizeLang(voice.lang);

        return voices.find((item) => normalizeLang(item.lang) === wanted)
            || voices.find((item) => normalizeLang(item.lang).startsWith(wanted.slice(0, 2)))
            || null;
    };

    const playNext = () => {
        if (speaking || speechQueue.length === 0) {
            return;
        }

        speaking = true;

        const utterance = new SpeechSynthesisUtterance(speechQueue.shift());
        utterance.lang = voice.lang;
        utterance.volume = Number(voice.volume);
        utterance.rate = Number(voice.rate);

        const chosen = pickVoice();
        if (chosen) {
            utterance.voice = chosen;
        }

        const finish = () => {
            speaking = false;
            playNext();
        };

        utterance.onend = finish;
        utterance.onerror = finish;

        window.speechSynthesis.speak(utterance);
    };

    const speak = (text) => {
        if (!speechSupported) {
            return;
        }

        speechQueue.push(text);

        // Hindari antrean suara yang menumpuk jika banyak panggilan beruntun.
        while (speechQueue.length > 5) {
            speechQueue.shift();
        }

        playNext();
    };

    // Nomor dieja per karakter agar jelas: "CV00001" menjadi "C V 0 0 0 0 1".
    const spell = (value) => String(value).toUpperCase().replace(/[^A-Z0-9]/g, '').split('').join(' ');

    const buildSpeech = (payload, isRecall) => {
        const template = config.template || 'Nomor antrean {number}, silakan menuju loket pelayanan.';

        const text = template
            .replaceAll('{number}', spell(payload.number))
            .replaceAll('{service}', payload.service || '')
            .replaceAll('{counter}', payload.counter || 'loket pelayanan');

        if (!isRecall) {
            return text;
        }

        return normalizeLang(voice.lang).startsWith('en') ? `Calling again. ${text}` : `Panggilan ulang. ${text}`;
    };

    const announce = (payload, isRecall) => {
        if (voice.enabled && unlocked) {
            speak(buildSpeech(payload, isRecall));
        }
    };

    if (speechSupported) {
        window.speechSynthesis.onvoiceschanged = () => {};
        window.speechSynthesis.getVoices();
    }

    /* ============================================================
       WebSocket (Reverb) dan polling cadangan
       ============================================================ */
    let pollTimer = null;

    const startPolling = (interval) => {
        clearInterval(pollTimer);
        pollTimer = setInterval(fetchSnapshot, interval);
    };

    const setStatus = (state) => {
        const labels = {
            live: 'Terhubung real-time',
            connecting: 'Menghubungkan...',
            fallback: 'Mode cadangan (polling)',
        };

        els.status.className = `connection is-${state}`;
        els.statusText.textContent = labels[state];
    };

    const applyConnectionState = (state) => {
        if (state === 'connected') {
            setStatus('live');
            startPolling(30000);
            fetchSnapshot();
        } else if (state === 'connecting' || state === 'initialized') {
            setStatus('connecting');
            startPolling(4000);
        } else {
            setStatus('fallback');
            startPolling(4000);
        }
    };

    const relevant = (payload) => !config.service_id || Number(payload.service_id) === Number(config.service_id);

    try {
        const pusher = window.Echo.connector.pusher;

        window.Echo.channel('queue-display')
            .listen('.queue.created', (payload) => {
                if (relevant(payload)) {
                    refreshSoon();
                }
            })
            .listen('.queue.called', (payload) => {
                if (relevant(payload)) {
                    announce(payload, false);
                    refreshSoon();
                }
            })
            .listen('.queue.recalled', (payload) => {
                if (relevant(payload)) {
                    showRecall();
                    announce(payload, true);
                    refreshSoon();
                }
            })
            .listen('.queue.skipped', (payload) => {
                if (relevant(payload)) {
                    refreshSoon();
                }
            })
            .listen('.queue.completed', (payload) => {
                if (relevant(payload)) {
                    refreshSoon();
                }
            });

        applyConnectionState(pusher.connection.state);
        pusher.connection.bind('state_change', ({ current }) => applyConnectionState(current));
    } catch (error) {
        setStatus('fallback');
        startPolling(4000);
    }

    /* ============================================================
       Layar penuh, kontrol, dan pengaturan suara
       ============================================================ */
    const toggleFullscreen = () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen?.().catch(() => {});
        } else {
            document.exitFullscreen?.();
        }
    };

    const fullscreenButton = byId('fullscreen-btn');
    fullscreenButton.addEventListener('click', toggleFullscreen);

    document.addEventListener('fullscreenchange', () => {
        const icon = fullscreenButton.querySelector('i');
        icon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    });

    let hideTimer = null;

    const showControls = () => {
        root.classList.add('show-controls');
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => root.classList.remove('show-controls'), 3500);
    };

    document.addEventListener('mousemove', showControls);
    document.addEventListener('touchstart', showControls, { passive: true });

    const panel = byId('settings-panel');
    const toggleSettings = (force) => {
        panel.hidden = typeof force === 'boolean' ? !force : !panel.hidden;
    };

    byId('settings-btn').addEventListener('click', () => toggleSettings());
    byId('settings-close').addEventListener('click', () => toggleSettings(false));

    document.addEventListener('keydown', (event) => {
        if (event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement) {
            return;
        }

        const key = event.key.toLowerCase();

        if (key === 'f') {
            toggleFullscreen();
        } else if (key === 's') {
            toggleSettings();
        }
    });

    const enabledInput = byId('voice-enabled');
    const volumeInput = byId('voice-volume');
    const rateInput = byId('voice-rate');
    const langSelect = byId('voice-lang');
    const volumeLabel = byId('voice-volume-value');
    const rateLabel = byId('voice-rate-value');

    const syncSettingsUi = () => {
        enabledInput.checked = Boolean(voice.enabled);
        volumeInput.value = voice.volume;
        rateInput.value = voice.rate;
        volumeLabel.textContent = `${Math.round(voice.volume * 100)}%`;
        rateLabel.textContent = `${Number(voice.rate).toFixed(1)}x`;

        if (![...langSelect.options].some((option) => option.value === voice.lang)) {
            langSelect.add(new Option(voice.lang, voice.lang));
        }

        langSelect.value = voice.lang;
    };

    if (!speechSupported) {
        byId('voice-unsupported').hidden = false;
        [enabledInput, volumeInput, rateInput, langSelect, byId('voice-test')].forEach((input) => {
            input.disabled = true;
        });
    }

    enabledInput.addEventListener('change', () => { voice.enabled = enabledInput.checked; saveVoice(); });
    volumeInput.addEventListener('input', () => { voice.volume = Number(volumeInput.value); saveVoice(); syncSettingsUi(); });
    rateInput.addEventListener('input', () => { voice.rate = Number(rateInput.value); saveVoice(); syncSettingsUi(); });
    langSelect.addEventListener('change', () => { voice.lang = langSelect.value; saveVoice(); });

    byId('voice-test').addEventListener('click', () => {
        unlocked = true;
        speak(buildSpeech({ number: 'CV00001', service: 'Contoh Layanan', counter: 'Loket 1' }, false));
    });

    syncSettingsUi();

    /* ============================================================
       Layar mulai: izin suara dan layar penuh dari satu klik
       ============================================================ */
    byId('start-btn').addEventListener('click', () => {
        unlocked = true;
        byId('start-overlay').hidden = true;

        if (speechSupported) {
            window.speechSynthesis.speak(new SpeechSynthesisUtterance(''));
        }

        toggleFullscreen();
        showControls();
    });

    window.addEventListener('resize', fitNumber);

    render(initial);
}

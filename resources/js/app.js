import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

/**
 * Chiffre qui monte de 0 à sa valeur en moins d'une seconde.
 * Le serveur écrit déjà la valeur finale dans la page : sans JavaScript,
 * ou si l'utilisateur a demandé moins d'animations, rien ne change.
 */
Alpine.data('countUp', (target, suffix = '') => ({
    display: '',
    init() {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const end = Number(target) || 0;
        // Espace fine insécable de fr-FR remplacée par une espace normale,
        // pour rester identique au format affiché par le serveur (« 45 200 MRU »).
        const format = (v) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 })
            .format(v)
            .replace(/[\u202f\u00a0]/g, ' ') + suffix;

        if (reduce || end === 0) {
            this.display = format(end);
            return;
        }

        this.display = format(0);

        const duration = 900;
        const start = performance.now();
        const step = (now) => {
            const t = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - t, 3);
            this.display = format(Math.round(end * eased));
            if (t < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    },
}));

/** Horloge de la barre du haut : utile de nuit, quand le service tourne. */
Alpine.data('clock', () => ({
    time: '',
    init() {
        const tick = () => {
            this.time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        };
        tick();
        setInterval(tick, 15000);
    },
}));

/*
 * Application installable (PWA).
 *
 * Chrome, Edge et Android proposent l'installation via l'événement
 * « beforeinstallprompt » : on le garde de côté pour le déclencher quand
 * l'utilisateur clique sur « Installer ». Les autres navigateurs (iPhone,
 * Firefox) ne le proposent pas : le bouton affiche alors la marche à suivre.
 */
let installPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    window.dispatchEvent(new CustomEvent('app-installable'));
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;
    window.dispatchEvent(new CustomEvent('app-installed'));
});

// Le service worker n'est accepté qu'en HTTPS (ou sur localhost).
if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

function detectPlatform() {
    const ua = navigator.userAgent;
    const ios = /iphone|ipad|ipod/i.test(ua) || (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1);
    if (ios) return 'ios';
    const android = /android/i.test(ua);
    if (/firefox|fxios/i.test(ua)) return android ? 'firefox-android' : 'firefox-desktop';
    if (/samsungbrowser/i.test(ua)) return 'samsung';
    return android ? 'android' : 'desktop';
}

Alpine.data('installApp', () => ({
    installed: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
    ready: installPrompt !== null,
    help: false,
    platform: detectPlatform(),

    init() {
        window.addEventListener('app-installable', () => { this.ready = true; });
        window.addEventListener('app-installed', () => { this.installed = true; this.help = false; });
    },

    async install() {
        if (!installPrompt) {
            this.help = true;
            return;
        }
        installPrompt.prompt();
        const { outcome } = await installPrompt.userChoice;
        installPrompt = null;
        this.ready = false;
        if (outcome === 'accepted') this.installed = true;
    },
}));

Alpine.start();

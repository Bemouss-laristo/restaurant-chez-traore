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

Alpine.start();

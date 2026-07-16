/**
 * HERO DROP — countdown discreto nel chip (giorni/ore).
 * Fase effetti (prossimo step): tilt 3D + sheen foil sul ventaglio [data-cr-fan],
 * solo su dispositivi hover-capable e senza prefers-reduced-motion.
 */
const heroDrop = () => {
	const chips = document.querySelectorAll('[data-cr-countdown]');
	if (!chips.length) return;

	const tick = () => {
		chips.forEach((chip) => {
			const target = new Date(chip.dataset.target.replace(' ', 'T')).getTime();
			if (Number.isNaN(target)) return;
			const diff = Math.max(0, target - Date.now());
			const d = chip.querySelector('[data-cd="d"]');
			const h = chip.querySelector('[data-cd="h"]');
			if (d) d.textContent = Math.floor(diff / 864e5);
			if (h) h.textContent = Math.floor(diff / 36e5) % 24;
		});
	};

	tick();
	setInterval(tick, 60000);
};

export default heroDrop;

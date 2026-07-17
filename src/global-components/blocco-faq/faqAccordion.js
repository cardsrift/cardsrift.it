/**
 * FAQ ACCORDION — anima l'apertura/chiusura del <details> (altezza), progressive enhancement:
 * senza JS il <details> resta pienamente funzionante (apre di scatto). Con prefers-reduced-motion
 * si lascia il comportamento nativo (nessuna animazione).
 */
const faqAccordion = () => {
	const items = document.querySelectorAll('.blocco-faq details');
	if (!items.length) return;

	const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	items.forEach((d) => {
		const body = d.querySelector('.cr-faq-body');
		const summary = d.querySelector('summary');
		if (!body || !summary) return;

		summary.addEventListener('click', (e) => {
			if (reduce) return; // niente animazione: lascia il toggle nativo

			e.preventDefault();
			if (body.dataset.animating) return; // ignora i click durante l'animazione

			body.dataset.animating = '1';
			const end = () => {
				body.style.height = d.open ? 'auto' : '';
				delete body.dataset.animating;
			};

			if (d.open) {
				// chiusura: altezza attuale → 0, poi chiudi il <details>
				body.style.height = `${body.scrollHeight}px`;
				window.requestAnimationFrame(() => { body.style.height = '0px'; });
				body.addEventListener('transitionend', function done() {
					d.open = false;
					body.removeEventListener('transitionend', done);
					end();
				}, { once: true });
			} else {
				// apertura: apri il <details>, 0 → altezza contenuto → auto
				d.open = true;
				body.style.height = '0px';
				window.requestAnimationFrame(() => { body.style.height = `${body.scrollHeight}px`; });
				body.addEventListener('transitionend', function done() {
					body.removeEventListener('transitionend', done);
					end();
				}, { once: true });
			}
		});
	});
};

export default faqAccordion;

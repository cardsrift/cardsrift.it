/**
 * FILTRI DEL LISTATO — solo il richiudersi su telefono.
 *
 * La toolbar è un <details open>: senza JavaScript resta aperta, cioè esattamente
 * com'era prima. Qui la chiudiamo su schermo stretto, dove misurava 333px di altezza
 * e spingeva il primo prodotto a 737px dall'alto (tre quarti di schermata di soli
 * filtri). Se un filtro è già attivo resta aperta: chiuderla nasconderebbe il motivo
 * per cui si stanno vedendo quei risultati.
 *
 * Nessuna gestione dell'altezza: <details> apre e chiude da solo, e la riga di
 * riepilogo ("Filtri · 2") dice sempre cosa c'è dentro anche da chiuso.
 */

const ListingFilters = () => {
	const box = document.querySelector('[data-cr-filters]');
	if (!box) return;

	const narrow = window.matchMedia('(max-width: 767px)');
	const hasActive = box.dataset.crFilters === 'active';

	const apply = () => {
		if (hasActive) return; // filtri in uso: si vedono, sempre
		box.open = !narrow.matches;
	};

	apply();
	narrow.addEventListener('change', apply);
};

export default ListingFilters;

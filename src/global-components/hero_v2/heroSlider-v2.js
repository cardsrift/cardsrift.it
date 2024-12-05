/* global Vimeo */

import Swiper, {
	Navigation,
	Pagination,
} from 'swiper';
import 'swiper/swiper-bundle.css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

// Funzione per caricare l'API di Vimeo dinamicamente
const loadVimeoAPI = () => {
	return new Promise((resolve, reject) => {
		if (typeof Vimeo !== 'undefined') {
			resolve(); // Se Vimeo è già definito, risolvi immediatamente la Promise
			return;
		}

		const script = document.createElement('script');
		script.src = 'https://player.vimeo.com/api/player.js';
		script.onload = () => resolve();
		script.onerror = () => reject(new Error('Vimeo API could not be loaded'));
		document.head.appendChild(script);
	});
};

// Inizializza il player Vimeo
const initVimeoPlayer = (iframeId) => {
	const iframe = document.getElementById(iframeId);
	if (iframe) {
		return new Vimeo.Player(iframe);
	}
	return null;
};

const heroSliderV2 = () => {
	const swiper = new Swiper('.heroSliderV2', {
		modules: [Navigation, Pagination],
		navigation: {
			nextEl: '.swiper-button-next',
			prevEl: '.swiper-button-prev',
		},
		pagination: {
			el: '.swiper-pagination',
			clickable: true,
		},
		on: {
			// Usa la sintassi shorthand per la funzione async
			async slideChange() {
				// Carica l'API Vimeo prima di usarla
				await loadVimeoAPI();

				// Usa this.activeIndex per ottenere l'indice corrente
				const currentIndex = this.activeIndex;

				// Itera su tutti i video Vimeo per mettere in pausa quelli non attivi
				document.querySelectorAll('[id^="vimeo-video-"]').forEach((iframe, index) => {
					const player = new Vimeo.Player(iframe);

					// Se siamo sulla slide corrente, riproduci il video
					if (index === currentIndex) {
						player.getPaused().then(paused => {
							if (paused) {
								player.play(); // Riproduci solo se il video è in pausa
							}
						});
					} else {
						player.pause(); // Metti in pausa il video se la slide non è attiva
					}
				});
			},
		},
	});
};

export default heroSliderV2;

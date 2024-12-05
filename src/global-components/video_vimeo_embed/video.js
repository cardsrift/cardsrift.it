/* eslint-env browser */
import Player from '@vimeo/player';
import { gsap } from 'gsap'; // Importa GSAP

const video = () => {
	// Seleziona l'iframe del video
	const iframe = document.querySelector('.embed-container iframe');
	if (!iframe) {
		return;
	}

	// Crea un'istanza del player Vimeo
	const player = new Player(iframe);

	// Seleziona il bottone mute, l'icona e la muteBar
	const muteToggleBtn = document.querySelector('#mute-toggle');
	const muteBar = document.querySelector('.muteBar');

	// Animazione icona mute con GSAP
	gsap.from('#mute-icon', {
		opacity: 0,
		duration: 0.5,
		ease: 'power2.inOut',
	});

	// Imposta il video in mute e in pausa all'inizio
	player.setMuted(true);
	player.pause();

	// Funzione per aggiornare lo stato della muteBar
	const updateMuteBar = (isMuted) => {
		if (isMuted) {
			muteBar.style.display = 'block'; // Mostra la barra se è muto
		} else {
			muteBar.style.display = 'none'; // Nascondi la barra se l'audio è attivo
		}
	};

	// Inizialmente il video è muto, quindi mostra la muteBar
	updateMuteBar(true);

	// Funzione per il toggle dell'audio
	muteToggleBtn.addEventListener('click', async () => {
		const isMuted = await player.getMuted();
		player.setMuted(!isMuted);

		// Aggiorna la barra mute in base allo stato mute
		updateMuteBar(!isMuted);

		// Aggiungi un'animazione ogni volta che l'utente clicca il mute toggle
		gsap.fromTo('#mute-icon', {
			opacity: 0.5,
			scale: 0.8,
		}, {
			opacity: 1,
			scale: 1,
			duration: 0.3,
			ease: 'power2.inOut',
		});
	});

	// Funzione per mettere in pausa o avviare il video
	const handleVideoVisibility = (isVisible) => {
		if (isVisible) {
			player.play(); // Riproduce il video se è visibile
		} else {
			player.pause(); // Mette in pausa se non è visibile
			player.setMuted(true); // Mette il video in muto
			updateMuteBar(true); // Mostra la muteBar
		}
	};

	// Usa IntersectionObserver per rilevare la visibilità dell'elemento video
	const videoSection = document.querySelector('.video-section');
	if (videoSection) {
		const observer = new IntersectionObserver((entries) => {
			entries.forEach(entry => {
				handleVideoVisibility(entry.isIntersecting);
			});
		}, {
			threshold: 0.5, // Riproduci/metti in pausa quando il video è per metà visibile
		});

		observer.observe(videoSection);
	}
};

export default video;

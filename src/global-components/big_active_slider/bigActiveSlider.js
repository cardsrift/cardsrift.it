import Swiper from 'swiper';
import 'swiper/swiper-bundle.css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const bigActiveSlider = () => {
	const swiper = new Swiper('.big-active-slider', {
		slidesPerView: 2,
		spaceBetween: 30,
		centeredSlides: true,
		breakpoints: {
			640: {
				slidesPerView: 2,
				spaceBetween: 20,
			},
			1024: {
				slidesPerView: 4,
				centeredSlides: false,
				spaceBetween: 20,
				navigation: false,
			},
		},
	});

	// Seleziona tutte le slide
	const slides = document.querySelectorAll('.big-active-slider .swiper-slide');

	// Funzione per gestire l'effetto quando il mouse passa sopra
	slides.forEach(slide => {
		slide.addEventListener('mouseover', () => {
			// Aggiungi la classe 'inactive' a tutte le slide
			slides.forEach(s => {
				s.classList.add('inactive');
				s.classList.remove('active');
			});
			// Aggiungi la classe 'active' solo alla slide su cui passa il mouse
			slide.classList.add('active');
			slide.classList.remove('inactive');
		});

		// Ripristina lo stato iniziale quando il mouse esce
		slide.addEventListener('mouseout', () => {
			slides.forEach(s => {
				s.classList.remove('active', 'inactive');
			});
		});
	});
};

export default bigActiveSlider;

import Swiper from 'swiper';
import 'swiper/swiper-bundle.css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const bgImageSlider = () => {
	const swiper = new Swiper('.rimond-slider', {
		// Optional parameters
		direction: 'horizontal',
		loop: true,
		navigation: false,
		slidesPerView: 1,
		breakpoints: {
			640: {
				slidesPerView: 2,
				spaceBetween: 20,
				navigation: {
					nextEl: '.swiper-button-next',
					prevEl: '.swiper-button-prev',
				},
			},
			1024: {
				slidesPerView: 6,
				spaceBetween: 0,
				navigation: false,
			},
		},
	});

	const slides = document.querySelectorAll('.swiper-slide');
	const images = document.querySelectorAll('.sliderImage');

	slides.forEach(slide => {
		const slideIndex = slide.dataset.index;

		slide.addEventListener('mouseenter', () => {
			images.forEach(image => {
				image.style.zIndex = (image.dataset.index === slideIndex) ? 1 : -1;
			});
		});

		slide.addEventListener('mouseleave', () => {
			images.forEach(image => {
				image.style.zIndex = -1;
			});
		});
	});
};

export default bgImageSlider;

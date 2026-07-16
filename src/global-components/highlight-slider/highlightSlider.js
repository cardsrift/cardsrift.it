import Swiper from 'swiper';
import 'swiper/css';

const highlightSlider = () => {
	const sliders = document.querySelectorAll('.highlight-slider-swiper');
	sliders.forEach(slider => {
		let swiper = new Swiper(slider, {
			slidesPerView: 4,
			breakpoints: {
				0: {
					slidesPerView: 1,
				},
				640: {
					slidesPerView: 2,
				},
				768: {
					slidesPerView: 3,
				},
				1024: {
					slidesPerView: 4,
				},
			},
			spaceBetween: 20,

		});
	});
};

export default highlightSlider;

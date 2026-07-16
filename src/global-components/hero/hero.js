import Swiper, { Autoplay } from 'swiper';
import 'swiper/css';

const hero = () => {
	// Register the Autoplay module
	Swiper.use([Autoplay]);

	const swiper = new Swiper('.hero-slider', {
		direction: 'horizontal',
		loop: true,
		slidesPerView: 1,
		autoplay: {
			delay: 14000,
		},
	});
};

export default hero;

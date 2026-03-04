import Swiper, { Autoplay } from 'swiper';
import 'swiper/swiper-bundle.css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

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

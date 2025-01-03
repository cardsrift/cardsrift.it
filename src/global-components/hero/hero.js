import Swiper from 'swiper';
import 'swiper/swiper-bundle.css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const hero = () => {
	const swiper = new Swiper('.hero-slider', {
		// Optional parameters
		direction: 'horizontal',
		loop: true,
		navigation: false,
		slidesPerView: 1,
	});
};

export default hero;

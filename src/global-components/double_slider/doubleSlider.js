import Swiper, { Pagination } from 'swiper';
import 'swiper/swiper-bundle.css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const doubleSlider = () => {
	Swiper.use([Pagination]);
	const eventsSwiper = new Swiper('.double-slider-events', {
		slidesPerView: 2.3,
		spaceBetween: 10,
	});

	const newsSwiper = new Swiper('.double-slider-news', {
		direction: 'horizontal',
		slidesPerView: 2,
		spaceBetween: 30,
		centeredSlides: true,
		breakpoints: {
			640: {
				slidesPerView: 2,
				spaceBetween: 20,
			},
			1024: {
				direction: 'vertical',
				slidesPerView: 2,
				centeredSlides: false,
				spaceBetween: 20,
				navigation: false,
			},
		},
	});
};

export default doubleSlider;

import Swiper from 'swiper';
import 'swiper/swiper-bundle.css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const highlightSlider = () => {
	const sliders = document.querySelectorAll('.highlight-slider-swiper');
	sliders.forEach(slider => {
		let swiper = new Swiper(slider, {
			slidesPerView: 4,
		});
	});
};

export default highlightSlider;

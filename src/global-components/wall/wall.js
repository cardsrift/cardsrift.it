import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Swiper from 'swiper';
import { Scrollbar } from 'swiper/modules';

gsap.registerPlugin(ScrollTrigger);

const wall = () => {
	// console.log('wall::init');
	const wallElements = window.document.querySelectorAll('.wall');
	wallElements.forEach((wallElement) => {
		const elementsContainer = wallElement.querySelector('.wall-elements-container');
		const desktopContainer = wallElement.querySelector('#desktop-container');
		const mobileContainer = wallElement.querySelector('#mobile-container');

		elementsContainer.querySelectorAll('.wall-element').forEach(element => {
			desktopContainer.appendChild(element.cloneNode(true));
			const swiperSlide = document.createElement('div');
			swiperSlide.classList.add('swiper-slide');
			swiperSlide.appendChild(element.cloneNode(true));
			mobileContainer.appendChild(swiperSlide);
		});

		// Swiper Configuration for Mobile
		Swiper.use([Scrollbar]);
		let swiper = new Swiper(mobileContainer.closest('.swiper-container'), {
			pagination: {
				el: '.swiper-pagination',
				clickable: true,
			},
			scrollbar: {
				el: '.swiper-scrollbar-wall',
				draggable: true,
			},
			slidesPerView: 1.2,
			centeredSlides: false,
			spaceBetween: -10,
		});

		// GSAP ScrollTrigger for Horizontal Scroll and Pinning (Desktop)
		// ScrollTrigger.create({
		// 	trigger: wallElement.querySelector('#horizontal-scroll-section'),
		// 	start: 'top 112px',
		// 	end: () => `+=${wallElement.querySelector('.horizontal-scroll-section').offsetWidth}`,
		// 	pin: true,
		// 	scrub: false,
		// 	markers: true,
		// 	onUpdate: (self) => {
		// 		gsap.to(wallElement.querySelector('#horizontal-scroll-section .horizontal-scroll-section'), {
		// 			x: -self.progress * (wallElement.querySelector('.horizontal-scroll-section').scrollWidth - window.innerWidth),
		// 			// ease: 'none',
		// 		});
		// 	},
		// });
		const tl = gsap.timeline({ paused: true });
		const container = wallElement.querySelector('.horizontal-scroll-section');
		const wrapper = wallElement.querySelector('#horizontal-scroll-section');

		tl.to(wrapper, {
			x: (wrapper.clientWidth - container.clientWidth),
			ease: 'none',
		});
		ScrollTrigger.create({
			animation: tl,
			trigger: wrapper,
			start: 'top 120px',
			end: () => `+=${container.offsetWidth}`,
			pin: true,
			scrub: 0.01,
			// markers: true,
			invalidateOnRefresh: true,
			ease: 'none',
			onRefresh: () => {
				tl.seek(0);
				tl.clear();
				tl.to(wrapper, {
					x: () => (wrapper.clientWidth - container.clientWidth),
					ease: 'none',
				});
			},
		});
		elementsContainer.style.display = 'none';
		window.addEventListener('resize', () => {
			ScrollTrigger.refresh();
		});
	});
};

export default wall;

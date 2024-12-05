import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

const quote = () => {
	// console.log('quote::init');
	gsap.registerPlugin(ScrollTrigger);

	const quoteElements = window.document.querySelectorAll('.quote');

	quoteElements.forEach((quoteElement) => {
		const quoteBackground = quoteElement.querySelector('.quote-background');
		if (quoteBackground) {
		//	console.log('quote::init el', quoteBackground);
			gsap.fromTo(
				quoteBackground,
				{
					y: '25%', // Parte da 0% all'ingresso nella viewport
				},
				{
					y: '-50%', // Arriva a -50% quando esce completamente dalla viewport
					ease: 'slow(0.7, 0.7, false)', // Easing molto lento per il "momentum"
					scrollTrigger: {
						trigger: quoteElement,
						start: 'top 25%', // Inizia l'animazione quando l'elemento entra nella viewport
						end: 'bottom 25%', // Termina l'animazione quando l'elemento esce completamente dalla viewport
						scrub: 12,
					},
				},
			);
		}
	});
};

export default quote;

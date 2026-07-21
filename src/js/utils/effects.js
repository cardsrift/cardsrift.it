/**
 * EFFECTS — motion del design system, su GSAP + ScrollTrigger (decisione 21/07/2026).
 *
 * Regole (invariate): GPU-only (transform/opacity/filter), interazioni puntatore solo se
 * `(hover: hover)`, tutto spento con `prefers-reduced-motion`. Attivato da app.js su documentReady.
 *
 * - hero-vetrina: entrance "blur-to-focus + fan" + hover tilt 3D con glare specular
 * - card prodotto [data-cr-tilt]: tilt "peso" + glare; foil ([data-cr-holo]) anche riflesso holo
 * - reveal on scroll: ScrollTrigger, stagger (ricette dalla ricerca motion premium)
 * - orbs [data-cr-orbs]: parallasse via ScrollTrigger scrub sul layer interno
 * - contatore carrello: pop sugli eventi WooCommerce fragments
 */
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const canHover = window.matchMedia('(hover: hover)').matches;
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
// i reveal ANIMANO solo dopo il settle iniziale (post-load): prima mostrano istantaneo, così ciò che è
// già visibile al load — o che entra in vista mentre le immagini caricano — non fa mai fade.
let revealArmed = false;

/* ---- Hero vetrina: entrance "blur-to-focus + fan" ---------------------------- */
const heroEntrance = () => {
	const cards = gsap.utils.toArray('.hero-vetrina__showcase > *');
	if (!cards.length) return;
	// GSAP legge il fan (rotate/scale da Tailwind) e lo conserva; anima solo y/opacity/blur.
	gsap.fromTo(
		cards,
		{ opacity: 0, y: 36, filter: 'blur(10px)' },
		{
			opacity: 1,
			y: 0,
			filter: 'blur(0px)',
			duration: 0.9,
			ease: 'power2.out',
			stagger: 0.12,
			clearProps: 'filter',
			delay: 0.05,
		},
	);
};

/* ---- Tilt 3D + glare specular su hover (hero e card prodotto) ---------------- */
const cardTilt = () => {
	gsap.utils.toArray('[data-cr-tilt]').forEach((card) => {
		const isHero = card.classList.contains('hero-vetrina__card');
		const maxDeg = isHero ? 11 : 7;
		const scaleUp = isHero ? 1.06 : 1.045;

		// GSAP conserva il fan (rotate/scale) e aggiunge la prospettiva per il tilt 3D.
		// IMPORTANTE: il tilt anima SOLO rotationX/rotationY/scale — MAI `y`, perché `y` è di proprietà
		// del reveal/entrance (opacity+y). Se le condividessero, l'hover ucciderebbe il tween del reveal
		// a metà (card "bloccata", movimento desincronizzato dall'opacity). La sensazione di lift la dà
		// lo scale + lo z-index. Proprietà disgiunte = nessun conflitto GSAP.
		gsap.set(card, { transformPerspective: isHero ? 900 : 700, transformOrigin: '50% 50%' });

		const setRX = gsap.quickTo(card, 'rotationX', { duration: 0.5, ease: 'power2.out' });
		const setRY = gsap.quickTo(card, 'rotationY', { duration: 0.5, ease: 'power2.out' });
		const setSc = gsap.quickTo(card, 'scale', { duration: 0.5, ease: 'power2.out' });
		const baseScale = gsap.getProperty(card, 'scale') || 1;

		card.addEventListener('pointerenter', () => {
			setSc(baseScale * scaleUp);
			card.style.setProperty('--cr-active', '1');
			if (isHero) card.style.zIndex = '6';
		});
		card.addEventListener('pointermove', (e) => {
			const r = card.getBoundingClientRect();
			const px = (e.clientX - r.left) / r.width;
			const py = (e.clientY - r.top) / r.height;
			setRX((0.5 - py) * maxDeg);
			setRY((px - 0.5) * maxDeg);
			card.style.setProperty('--cr-mx', `${(px * 100).toFixed(1)}%`);
			card.style.setProperty('--cr-my', `${(py * 100).toFixed(1)}%`);
		});
		card.addEventListener('pointerleave', () => {
			setRX(0);
			setRY(0);
			setSc(baseScale);
			card.style.setProperty('--cr-active', '0');
			if (isHero) card.style.removeProperty('z-index');
		});
	});
};

/* ---- Reveal on scroll (ScrollTrigger, stagger) ------------------------------ */
const sectionReveals = () => {
	// Se un elemento è GIÀ nel viewport al load → niente fade, lo mostro subito (richiesta cliente):
	// solo ciò che sta SOTTO la piega si rivela allo scroll. (L'hero-vetrina ha la sua entrance a parte.)
	const belowFold = (el) => el.getBoundingClientRect().top >= window.innerHeight;

	gsap.utils.toArray('[data-fx="rise"]').forEach((el) => {
		if (!belowFold(el)) {
			gsap.set(el, { opacity: 1, y: 0 });
			return;
		}
		gsap.set(el, { opacity: 0, y: 24 });
		ScrollTrigger.create({
			trigger: el,
			start: 'top 90%',
			once: true,
			onEnter: () => {
				if (!revealArmed) {
					gsap.set(el, { opacity: 1, y: 0 }); // entrato durante il settle iniziale → niente fade
					return;
				}
				gsap.to(el, {
					opacity: 1,
					y: 0,
					duration: 0.7,
					ease: 'power2.out',
					overwrite: 'auto',
				});
			},
		});
	});
	gsap.utils.toArray('[data-fx-stagger]').forEach((grid) => {
		const items = gsap.utils.toArray(grid.children);
		if (!items.length) return;
		if (!belowFold(grid)) {
			gsap.set(items, { opacity: 1, y: 0 });
			return;
		}
		gsap.set(items, { opacity: 0, y: 26 });
		ScrollTrigger.create({
			trigger: grid,
			start: 'top 86%',
			once: true,
			onEnter: () => {
				if (!revealArmed) {
					gsap.set(items, { opacity: 1, y: 0 });
					return;
				}
				gsap.to(items, {
					opacity: 1,
					y: 0,
					duration: 0.6,
					ease: 'power2.out',
					stagger: 0.08,
					overwrite: 'auto',
				});
			},
		});
	});
};

/* ---- Orbs ambientali: parallasse via scrub sul layer interno ---------------- */
const orbsParallax = () => {
	gsap.utils.toArray('[data-cr-orbs]').forEach((wrap) => {
		const layer = wrap.querySelector('.cr-orbs__layer');
		if (!layer) return;
		gsap.to(layer, {
			yPercent: 14,
			ease: 'none',
			scrollTrigger: {
				trigger: wrap.parentElement || wrap,
				start: 'top bottom',
				end: 'bottom top',
				scrub: true,
			},
		});
	});
};

/* ---- Pop del contatore carrello -------------------------------------------- */
const cartPop = () => {
	if (!window.jQuery) return;
	window.jQuery(document.body).on('added_to_cart wc_fragments_refreshed', () => {
		document.querySelectorAll('.cart-contents-count').forEach((b) => {
			gsap.fromTo(
				b,
				{ scale: 1 },
				{
					scale: 1.35,
					duration: 0.16,
					yoyo: true,
					repeat: 1,
					ease: 'power2.inOut',
				},
			);
		});
	});
};

const initEffects = () => {
	if (reduce) return; // reduced-motion: NESSUNA animazione — contenuti visibili, .cr-fx mai aggiunta
	try {
		if (canHover) cardTilt();
		cartPop();
		// il pre-hide CSS (.cr-fx) vale solo quando GSAP è attivo: senza JS/reduced-motion tutto è visibile
		document.documentElement.classList.add('cr-fx');
		heroEntrance();
		sectionReveals();
		orbsParallax();
		// dopo il load: ricalcola le posizioni (immagini/font cambiano il layout) e "arma" i reveal
		window.addEventListener('load', () => {
			ScrollTrigger.refresh();
			window.requestAnimationFrame(() => { revealArmed = true; });
		});
	} catch (err) {
		// fail-safe: se GSAP esplode a metà, non lasciare i contenuti nascosti dietro .cr-fx
		document.documentElement.classList.remove('cr-fx');
		gsap.set('[data-fx="rise"], [data-fx-stagger] > *, .hero-vetrina__showcase > *', { opacity: 1 });
	}
};

export default initEffects;

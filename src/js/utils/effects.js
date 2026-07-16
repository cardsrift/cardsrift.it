/**
 * EFFECTS — modulo unico degli effetti del design system (Fase effetti, §3d di
 * docs/rework-fase-1.md). NON ancora importato in app.js: si attiva quando
 * l'HTML plain è approvato, un tier alla volta.
 *
 * Regole (non negoziabili):
 * - solo transform / opacity / gradient-position (GPU)
 * - interazioni puntatore solo se `matchMedia('(hover: hover)')`
 * - tutto spento con `prefers-reduced-motion`
 * - un solo elemento "vivo" per viewport
 * - niente listener `scroll` diretti: solo IntersectionObserver / requestAnimationFrame
 *
 * Aggancio nei template (già presente):
 * - [data-cr-fan]  → tilt 3D del ventaglio (hero-drop)      — Tier 1
 * - [data-cr-holo] → sheen foil sulle carte del ventaglio   — Tier 1
 * - [data-fx="rise"] (+ data-fx-delay) → reveal on-scroll   — Tier 1
 * - card offerte/top-deal → sheen su hover                  — Tier 2
 * - .cr-patt → parallasse leggero (max 10-15%, desktop)     — Tier 2
 */

const canHover = () => window.matchMedia('(hover: hover)').matches;
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const initEffects = () => {
	if (reducedMotion()) return;

	// TIER 1 — da implementare qui:
	// initFanTilt();   → pointermove su [data-cr-fan]: --rx/--ry (rotate) + --px/--py (sheen)
	// initReveal();    → IntersectionObserver su [data-fx="rise"], stagger 60-80ms
	// initCartBadge(); → pop del contatore carrello sugli eventi cart fragments

	// TIER 2 / 3: vedi docs/rework-fase-1.md §3d
	if (!canHover()) {
		// niente effetti puntatore su touch
	}
};

export default initEffects;

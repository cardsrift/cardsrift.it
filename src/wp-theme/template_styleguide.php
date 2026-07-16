<?php /* Template Name: Design System */

/**
 * DESIGN SYSTEM — pagina vivente dei primitivi, renderizzata col CSS di produzione.
 * Assegna questo template a una pagina locale (es. /design-system) per il QA visivo:
 * ogni nuovo componente si verifica qui prima di andare in pagina.
 * Riferimenti: docs/design-system.md · docs/design-system-tokens.md
 */
get_header();

$temi = ['dark' => 'Dark (base)', 'light' => 'Light', 'lilla' => 'Lilla', 'lilla2' => 'Lilla chiaro'];
$demo_ids = function_exists('wc_get_products') ? wc_get_products(['status' => 'publish', 'limit' => 4, 'return' => 'ids', 'orderby' => 'date', 'order' => 'DESC']) : [];
?>

<!-- intestazione -->
<section class="cr-sec" data-th="dark">
	<div class="tw-container tw-section py-12">
		<div class="cr-eyebrow">CardsRift · Design System</div>
		<h1 class="font-metropolis font-bold !text-3xl lg:!text-5xl mt-3 mb-3">La base di ogni lavorazione</h1>
		<p class="text-th-muted max-w-[70ch]">Primitivi renderizzati col CSS reale del tema. Documentazione: <code>docs/design-system.md</code> · variabili: <code>docs/design-system-tokens.md</code>. Se un componente nuovo non si compone con questi pezzi, prima si estende il sistema, poi il componente.</p>
	</div>
</section>

<!-- 01 · i 4 temi con bottoni e testo -->
<?php foreach ($temi as $th => $label) : ?>
	<section class="cr-sec border-t border-th-line" data-th="<?= $th; ?>">
		<div class="tw-container tw-section py-8">
			<div class="flex items-center gap-6 flex-wrap">
				<span class="cr-eyebrow shrink-0">01 · Tema <?= esc_html($label); ?></span>
				<span class="font-metropolis font-semibold text-th-ink">Testo primario</span>
				<span class="text-th-muted text-sm">secondario</span>
				<span class="text-th-soft text-sm">terziario</span>
				<span class="cr-price">94,90&nbsp;€</span>
				<span class="cr-chip">Pokémon · JP</span>
				<span class="cr-stock cr-stock--ok">Disponibile</span>
				<span class="cr-stock cr-stock--low">Ultimi 2</span>
				<span class="flex gap-3 ml-auto flex-wrap">
					<a class="cr-btn cr-btn-solid" href="#">Solid</a>
					<a class="cr-btn cr-btn-ghost" href="#">Ghost</a>
					<a class="cr-btn cr-btn-glass" href="#">Glass</a>
				</span>
			</div>
		</div>
	</section>
<?php endforeach; ?>

<!-- 02 · tipografia -->
<section class="cr-sec border-t border-th-line" data-th="dark">
	<div class="tw-container tw-section py-12 flex flex-col gap-4">
		<span class="cr-eyebrow">02 · Tipografia</span>
		<div class="cr-eyebrow">Bylon — eyebrow, badge, date (questa riga)</div>
		<div class="font-bylon italic text-md text-th-acc">Bylon Italic — la “grafia” del negozio</div>
		<h2 class="font-metropolis font-bold !text-5xl">Metropolis Bold — titoli</h2>
		<h3 class="font-metropolis font-semibold !text-2xl">Metropolis SemiBold — card e sottosezioni</h3>
		<p class="font-metropolis font-light !text-2xl max-w-[36ch]">Metropolis Light — manifesti e claim, con <b class="font-semibold text-th-acc">accent</b> semantico.</p>
		<p class="text-th-muted max-w-[64ch]">Adelle Sans — corpo testo (applicata al body). Numeri sempre tabulari: <span class="tabular-nums font-metropolis font-semibold text-th-ink">1.234,56 € · 24/48h · 63:88</span></p>
	</div>
</section>

<!-- 03 · card prodotto: stati WooCommerce reali -->
<?php if ($demo_ids) : ?>
	<section class="cr-sec cr-patt border-t border-th-line" data-th="dark">
		<div class="tw-container tw-section py-12">
			<span class="cr-eyebrow">03 · Card prodotto (dati reali — stati automatici da WooCommerce)</span>
			<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mt-5">
				<?php foreach ($demo_ids as $i => $pid) : ?>
					<?php cr_product_card($pid, ['glass' => $i >= 2, 'top_deal' => $i === 2]); ?>
				<?php endforeach; ?>
			</div>
			<p class="text-th-soft text-xs mt-4 mb-0">Le prime due solide, le ultime due glass (sopra il pattern) — la terza marcata top-deal a scopo dimostrativo. Prezzo/scorte/sconti arrivano dal prodotto reale.</p>
		</div>
	</section>
<?php endif; ?>

<!-- 04 · badge, tasca singole, chip drop -->
<section class="cr-sec border-t border-th-line" data-th="lilla2">
	<div class="tw-container tw-section py-12">
		<span class="cr-eyebrow">04 · Badge fissi · Tasca raccoglitore · Drop chip</span>
		<div class="flex items-start gap-8 flex-wrap mt-5">
			<div class="flex flex-col gap-2.5 relative min-w-[130px]">
				<span class="cr-badge cr-badge--sale !static !w-fit">−15%</span>
				<span class="cr-badge cr-badge--pre !static !w-fit">Preordine</span>
				<span class="cr-badge cr-badge--out !static !w-fit">Esaurito</span>
				<span class="cr-badge cr-badge--top !static !w-fit">Top deal</span>
			</div>
			<a class="cr-pocket w-[170px]" href="#">
				<span class="cr-pocket__well">
					<span class="w-full h-full rounded-md bg-gradient-to-br from-purple to-[#332a4d]"></span>
				</span>
				<span class="flex flex-col gap-1 pt-2 px-0.5 pb-1">
					<span class="flex gap-1"><span class="cr-cchip cr-cchip--cond">NM</span><span class="cr-cchip">JP</span></span>
					<span class="font-metropolis font-semibold text-xs text-th-ink">Nome carta — Set</span>
					<span class="cr-price !text-sm">289,00&nbsp;€</span>
				</span>
				<span class="cr-pocket__pick">Scegli condizione</span>
			</a>
			<span class="cr-dropchip">Drop tra <b class="text-th-acc tabular-nums">71g 16h</b></span>
		</div>
	</div>
</section>

<!-- 05 · ticker -->
<div class="cr-sec border-t border-th-line" data-th="dark">
	<div class="cr-ticker" aria-hidden="true">
		<div class="cr-ticker__track">
			<?php for ($k = 0; $k < 2; $k++) : ?>
				<span class="font-metropolis font-semibold text-xs uppercase tracking-[0.12em] text-th-muted px-6 whitespace-nowrap">05 · Ticker — <b class="text-th-acc">pausa su hover</b></span>
				<span class="font-metropolis font-semibold text-xs uppercase tracking-[0.12em] text-th-muted px-6 whitespace-nowrap">Spento con reduced-motion</span>
				<span class="font-metropolis font-semibold text-xs uppercase tracking-[0.12em] text-th-muted px-6 whitespace-nowrap">Contenuto duplicato ×2 per il loop</span>
			<?php endfor; ?>
		</div>
	</div>
</div>

<!-- 06 · form -->
<section class="cr-sec cr-patt border-t border-th-line" data-th="dark">
	<div class="tw-container tw-section py-12">
		<span class="cr-eyebrow">06 · Form</span>
		<form class="cr-glass rounded-[20px] shadow-th max-w-[720px] p-7 mt-5" onsubmit="return false">
			<div class="grid sm:grid-cols-2 gap-4 mb-4">
				<div><label class="cr-label" for="ds-n">Nome</label><input class="cr-input" id="ds-n" type="text" placeholder="Il tuo nome"></div>
				<div><label class="cr-label" for="ds-s">Select</label><select class="cr-select" id="ds-s"><option>Pokémon</option><option>One Piece</option><option>Magic</option></select></div>
			</div>
			<label class="cr-label" for="ds-t">Note</label>
			<textarea class="cr-textarea mb-4" id="ds-t" placeholder="Testo…"></textarea>
			<button class="cr-btn cr-btn-solid" type="submit">Invia</button>
		</form>
	</div>
</section>

<?php get_footer(); ?>

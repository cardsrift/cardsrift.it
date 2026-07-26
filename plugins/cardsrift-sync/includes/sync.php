<?php

/**
 * SYNC — riconciliazione WooCommerce ↔ CardTrader.
 *
 * PEZZO 1 (qui): matching engine — risolve il `blueprint_id` CardTrader di un prodotto WooCommerce
 * importato da Cardmarket. È il perno del push: senza blueprint non si può creare l'inserzione.
 * Strategia (provata su dati reali, ~92% al primo colpo):
 *   1. espansione: slug pa_espansione (= set code Wizards) → CardTrader `code`; fallback nome espansione
 *   2. carta: `card_market_ids` del blueprint contiene `_cardmarket_id`; fallback nome (+version) normalizzati
 *
 * PEZZO 2 (push) e PEZZO 3 (pull + cron notturno) arrivano dopo. Il push SCRIVE sul marketplace live:
 * dry-run sempre, nessuna scrittura senza go-ahead esplicito.
 */

if (!defined('ABSPATH')) {
	exit;
}

/** Normalizza una stringa per confronti tolleranti (minuscolo, solo alfanumerici). */
function crs_nrm($s)
{
	return preg_replace('/[^a-z0-9]/', '', strtolower((string) $s));
}

/** Nostro slug gioco → game_id CardTrader (dalla lista /games: Magic=1, Pokémon=5, One Piece=15). */
function crs_ct_game_id($game_slug)
{
	$map = ['magic' => 1, 'pokemon' => 5, 'one-piece' => 15];
	return $map[$game_slug] ?? 0;
}

/** Slug del gioco di un prodotto (categoria product_cat che è tra i nostri giochi). '' se assente. Cache-aware. */
function crs_product_game_slug($pid)
{
	$games = array_keys(crs_games());
	$terms = get_the_terms((int) $pid, 'product_cat'); // usa la object-term-cache (prime in blocco per l'N+1)
	if (!$terms || is_wp_error($terms)) {
		return '';
	}
	foreach ($terms as $t) {
		if (in_array($t->slug, $games, true)) {
			return $t->slug;
		}
	}
	return '';
}

/** Slug del TIPO di un prodotto (singole|sealed|accessori) tra le categorie product_cat. '' se assente. Cache-aware. */
function crs_product_type_slug($pid)
{
	$types = array_keys(crs_types());
	$terms = get_the_terms((int) $pid, 'product_cat');
	if (!$terms || is_wp_error($terms)) {
		return '';
	}
	foreach ($terms as $t) {
		if (in_array($t->slug, $types, true)) {
			return $t->slug;
		}
	}
	return '';
}


/**
 * Indice inverso delle espansioni di un gioco CardTrader: ['by_code'=>[code=>id], 'by_name'=>[nrm=>id]].
 * Costruito dalla stessa mappa cache di crs_ct_expansion_map() (nessuna chiamata extra).
 */
function crs_ct_expansion_lookup($ct_game_id)
{
	static $memo = [];
	$ct_game_id = (int) $ct_game_id;
	if (isset($memo[$ct_game_id])) {
		return $memo[$ct_game_id];
	}
	$out = ['by_code' => [], 'by_name' => []];
	foreach (crs_ct_expansion_map() as $id => $e) {
		if ((int) ($e['game_id'] ?? 0) !== $ct_game_id) {
			continue;
		}
		$code = strtolower((string) ($e['code'] ?? ''));
		if ($code !== '') {
			$out['by_code'][$code] = (int) $id;
		}
		$nm = crs_nrm($e['name'] ?? '');
		if ($nm !== '') {
			$out['by_name'][$nm] = (int) $id;
		}
	}
	$memo[$ct_game_id] = $out;
	return $out;
}

/**
 * Codici-espansione candidati su CardTrader per un codice set Cardmarket. Copre le differenze note:
 *  - Cardmarket 'x{code}' = "Extras" (carte showcase/special) → CardTrader 'c{code}' = "Collectors"
 *    (confermato sui dati reali: ogni card_market_id delle Extras vive nell'espansione Collectors);
 *  - qualche codice promo con 'p' finale che CardTrader non usa ('babp' → 'bab').
 * Ordine = priorità (prima il codice così com'è). Il chiamante risolve tra quelli ESISTENTI su CardTrader e
 * verifica comunque col card_market_id, quindi un alias errato non aggancia mai la carta sbagliata.
 */
function crs_ct_expansion_code_aliases($code)
{
	$c = strtolower(trim((string) $code));
	if ($c === '') {
		return [];
	}
	$out = [$c];
	if ($c[0] === 'x' && strlen($c) > 1) {
		$base  = substr($c, 1);
		$out[] = 'c' . $base; // Extras → Collectors
		$out[] = $base;       // fallback: set base
	}
	if (substr($c, -1) === 'p' && strlen($c) > 1) {
		$out[] = substr($c, 0, -1); // 'babp' → 'bab'
	}
	return array_values(array_unique($out));
}

/**
 * Risolve il blueprint CardTrader di un prodotto WooCommerce.
 * @param int|WC_Product $product
 * @return array{blueprint_id:int, method:string, ct_expansion_id:int}
 *         method: card_market_id | name | unmatched | no-game | no-expansion | no-product
 */
function crs_ct_match_blueprint($product)
{
	$product = is_numeric($product) ? wc_get_product((int) $product) : $product;
	if (!$product) {
		return ['blueprint_id' => 0, 'method' => 'no-product', 'ct_expansion_id' => 0];
	}
	$pid = $product->get_id();

	$ct_game = crs_ct_game_id(crs_product_game_slug($pid));
	if (!$ct_game) {
		return ['blueprint_id' => 0, 'method' => 'no-game', 'ct_expansion_id' => 0];
	}

	// Espansione CardTrader: candidati per CODICE (con alias Extras→Collectors, ecc.) + per NOME. Si cerca il
	// card_market_id TRA i candidati (match forte) → la carta si aggancia ovunque CardTrader la archivi, anche
	// quando il codice/nome Cardmarket non combacia (nomi in lingue diverse, sotto-espansioni "Extras").
	$eslug = '';
	$terms = wp_get_object_terms($pid, 'pa_espansione', ['fields' => 'slugs']);
	if ($terms && !is_wp_error($terms)) {
		$eslug = strtolower($terms[0]);
	}
	$lookup = crs_ct_expansion_lookup($ct_game);

	$eids = [];
	foreach (crs_ct_expansion_code_aliases($eslug) as $alias) {
		if (isset($lookup['by_code'][$alias])) {
			$eids[] = (int) $lookup['by_code'][$alias];
		}
	}
	$byname = $lookup['by_name'][crs_nrm(get_post_meta($pid, CRS_META_CM_EXP, true))] ?? 0;
	if ($byname) {
		$eids[] = (int) $byname;
	}
	$eids = array_values(array_unique(array_filter($eids)));
	if (!$eids) {
		return ['blueprint_id' => 0, 'method' => 'no-expansion', 'ct_expansion_id' => 0];
	}

	$cm        = (int) get_post_meta($pid, CRS_META_CM_ID, true);
	$pname     = crs_nrm($product->get_name());
	$api_error = false;
	$name_hit  = null; // primo match per nome, usato solo se il card_market_id non aggancia da nessuna parte
	$primary   = 0;

	foreach ($eids as $eid) {
		$bpmap = crs_ct_blueprint_map($eid);
		if ($bpmap === null) {
			$api_error = true; // guasto API su QUESTO candidato: prova gli altri, non concludere
			continue;
		}
		if (!$primary) {
			$primary = $eid;
		}
		// 1) match FORTE via card_market_ids (vince ovunque sia la carta)
		if ($cm > 0) {
			foreach ($bpmap as $bid => $b) {
				if (in_array($cm, array_map('intval', $b['cm_ids']), true)) {
					return ['blueprint_id' => (int) $bid, 'method' => 'card_market_id', 'ct_expansion_id' => $eid, 'image' => $b['image'] ?? '', 'image_full' => $b['image_full'] ?? ''];
				}
			}
		}
		// 2) fallback nome (promo/varianti senza card_market_ids): tieni il primo, deciso dopo aver esaurito il cm_id
		if ($name_hit === null && $pname !== '') {
			foreach ($bpmap as $bid => $b) {
				if (crs_nrm($b['name']) === $pname) {
					$name_hit = ['bid' => (int) $bid, 'eid' => $eid, 'image' => $b['image'] ?? '', 'image_full' => $b['image_full'] ?? ''];
					break;
				}
			}
		}
	}

	if ($name_hit !== null) {
		return ['blueprint_id' => $name_hit['bid'], 'method' => 'name', 'ct_expansion_id' => $name_hit['eid'], 'image' => $name_hit['image'], 'image_full' => $name_hit['image_full']];
	}
	if ($api_error && !$primary) {
		return ['blueprint_id' => 0, 'method' => 'api-error', 'ct_expansion_id' => 0]; // solo guasti API → ritenta
	}
	return ['blueprint_id' => 0, 'method' => 'unmatched', 'ct_expansion_id' => $primary];
}

/**
 * Risolve un prodotto e, se $persist, salva `_ct_blueprint_id` (+ metodo, diagnostico).
 * @return array come crs_ct_match_blueprint(), + 'name'
 */
function crs_ct_match_one($pid, $persist = false)
{
	$res = crs_ct_match_blueprint($pid);
	$res['name'] = get_the_title($pid);
	if ($persist) {
		if ($res['blueprint_id']) {
			update_post_meta($pid, CRS_META_CT_BLUEPRINT, $res['blueprint_id']);
			if (!empty($res['ct_expansion_id'])) {
				// salva l'espansione CardTrader DOVE VIVE il blueprint (già risolta con gli alias Extras→Collectors):
				// l'autopricer la riusa per raggruppare/scaricare il mercato, senza ri-risolvere dal codice Cardmarket.
				update_post_meta($pid, '_ct_expansion_id', (int) $res['ct_expansion_id']);
			}
			// immagini di catalogo CardTrader (CDN, pulite) già al match → il sito le usa subito, senza aspettare
			// il ciclo push+pull. Il pull poi le rinfresca. Precedenza in crs_fallback_image_src: featured > CT > Cardmarket.
			if (!empty($res['image'])) {
				update_post_meta($pid, '_ct_image', $res['image']);
			}
			if (!empty($res['image_full'])) {
				update_post_meta($pid, '_ct_image_full', $res['image_full']);
			}
		}
		// registra il metodo anche sul FALLIMENTO (unmatched/no-expansion/no-game) — così distinguiamo "match provato
		// ma fallito" da "mai pushato" e la lista "Da rivedere" non segnala i prodotti appena importati. L'errore API
		// è transitorio: NON lo persistiamo (il prossimo push ritenta senza lasciare un falso "da rivedere").
		if ($res['method'] !== 'api-error') {
			update_post_meta($pid, '_ct_match_method', $res['method']);
		}
	}
	return $res;
}

/**
 * ID dei prodotti importati da Cardmarket (hanno _cardmarket_id). Include le BOZZE: sono i prodotti
 * in attesa di prezzo definitivo, che il push manda su CardTrader e il pull poi pubblica.
 */
function crs_ct_matchable_ids()
{
	$q = new WP_Query([
		'post_type'      => 'product',
		'post_status'    => ['publish', 'draft'],
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [['key' => CRS_META_CM_ID, 'compare' => 'EXISTS']],
	]);
	return $q->posts;
}

/* ================= PEZZO 2 — PUSH (WooCommerce → CardTrader): pianificazione ================= */

/**
 * Slug lingua nostro → codice lingua CardTrader (per le properties di push e per il filtro dell'autopricer).
 * CardTrader usa `jp`/`kr` (NON `ja`/`ko`) sia per Magic sia per Pokémon — verificato sui mercati reali.
 */
function crs_slug_to_ct_lang($slug)
{
	$m = ['it' => 'it', 'en' => 'en', 'de' => 'de', 'fr' => 'fr', 'es' => 'es', 'jp' => 'jp', 'kr' => 'kr', 'ru' => 'ru'];
	return $m[strtolower((string) $slug)] ?? 'en';
}

/** Primo slug termine di un attributo sul prodotto ('' se assente). Cache-aware (get_the_terms). */
function crs_first_term_slug($pid, $tax)
{
	$terms = get_the_terms((int) $pid, $tax);
	if (!$terms || is_wp_error($terms)) {
		return '';
	}
	$t = reset($terms);
	return $t ? (string) $t->slug : '';
}

/**
 * Chiavi delle property CardTrader (marketplace e inserzioni) per gioco, dai dati reali dell'API:
 *  - 'lang'      = chiave lingua nel properties_hash;
 *  - 'foil'      = chiave del "finish speciale" (Magic: `mtg_foil`; Pokémon: `pokemon_reverse` = reverse holo);
 *  - 'foil_slug' = valore del nostro attributo pa_foil che corrisponde a quel finish.
 * La condizione è SEMPRE la chiave condivisa `condition`. null se il gioco non è mappato (es. One Piece: TBD).
 */
function crs_ct_game_props($game_slug)
{
	$map = [
		'magic'   => ['lang' => 'mtg_language',     'foil' => 'mtg_foil',        'foil_slug' => 'foil'],
		'pokemon' => ['lang' => 'pokemon_language', 'foil' => 'pokemon_reverse', 'foil_slug' => 'reverse-holo'],
	];
	return $map[$game_slug] ?? null;
}

/**
 * Properties CardTrader per il push. Condizione (condivisa) + lingua e "finish" con le chiavi giuste per gioco
 * (crs_ct_game_props). [] se il gioco non è mappato → il push crea senza properties (evitato a monte).
 */
function crs_ct_push_properties($pid, $game)
{
	$gp = crs_ct_game_props($game);
	if (!$gp) {
		return []; // gioco non mappato
	}
	$props = ['condition' => crs_condition_to_ct(strtoupper(crs_first_term_slug($pid, 'pa_condizione')))];
	$props[$gp['lang']] = crs_slug_to_ct_lang(crs_first_term_slug($pid, 'pa_lingua'));
	if (crs_first_term_slug($pid, 'pa_foil') === $gp['foil_slug']) {
		$props[$gp['foil']] = true; // Magic: mtg_foil · Pokémon: pokemon_reverse
	}
	return $props;
}

/**
 * Payload CREATE/UPDATE per CardTrader. Il prezzo iniziale è quello Cardmarket (poi l'autopricer
 * lo gestisce e lo riprendiamo al pull). Quantità dallo stock WooCommerce.
 */
function crs_ct_push_payload($product)
{
	$pid = $product->get_id();
	return [
		'blueprint_id' => (int) get_post_meta($pid, CRS_META_CT_BLUEPRINT, true),
		'price'        => (float) $product->get_regular_price(),
		'quantity'     => max(0, (int) $product->get_stock_quantity()),
		'properties'   => crs_ct_push_properties($pid, crs_product_game_slug($pid)),
	];
}

/**
 * PIANO di push (dry-run, SOLA LETTURA — nessuna scrittura su CardTrader). Idempotente per costruzione:
 * l'azione dipende SOLO dallo stato attuale (blueprint agganciato, `_ct_product_id` salvato, stock),
 * perciò rilanciarlo non somma nulla — al più SETTA la quantità al valore corrente.
 *   create : ha blueprint, stock>0, mai pushato (nessun _ct_product_id)
 *   update : già su CardTrader (_ct_product_id) e stock>0  → SET quantità/prezzo (mai increment)
 *   delete : già su CardTrader ma stock 0 (venduto)        → azzera/rimuove
 *   skip   : nessun blueprint, oppure niente da fare
 * @return array{counts:array, rows:array}
 */
function crs_ct_push_plan($ids)
{
	$counts = ['create' => 0, 'update' => 0, 'delete' => 0, 'skip' => 0];
	$rows = [];
	foreach ($ids as $pid) {
		$product = wc_get_product($pid);
		if (!$product) {
			continue;
		}
		$bp    = (int) get_post_meta($pid, CRS_META_CT_BLUEPRINT, true);
		$ctpid = (int) get_post_meta($pid, CRS_META_CT_PRODUCT, true);
		$qty   = max(0, (int) $product->get_stock_quantity());

		if ($ctpid) {
			$action = $qty > 0 ? 'update' : 'delete';
		} elseif ($bp && $qty > 0) {
			$action = 'create';
		} else {
			$action = 'skip';
		}
		$counts[$action]++;
		$rows[] = [
			'id'            => $pid,
			'name'          => get_the_title($pid),
			'action'        => $action,
			'qty'           => $qty,
			'price'         => (float) $product->get_regular_price(),
			'blueprint_id'  => $bp,
			'ct_product_id' => $ctpid,
		];
	}
	return ['counts' => $counts, 'rows' => $rows];
}

/* ================= PEZZO 2 — PUSH: esecuzione (SCRIVE su CardTrader) ================= */

/** Estrae l'id del prodotto creato dalla risposta di POST /products (shape difensivo). */
function crs_ct_extract_product_id($data)
{
	if (!is_array($data)) {
		return 0;
	}
	foreach ([['id'], ['resource', 'id'], ['product', 'id'], ['data', 'id']] as $path) {
		$v = $data;
		foreach ($path as $k) {
			$v = (is_array($v) && isset($v[$k])) ? $v[$k] : null;
		}
		if ($v) {
			return (int) $v;
		}
	}
	return 0;
}

/**
 * CREATE: crea l'inserzione su CardTrader e salva `_ct_product_id` sul prodotto (idempotenza).
 * @return array{ok:bool,id:int,err:string,raw:mixed,payload:array}
 */
function crs_ct_create_product($product)
{
	$payload = crs_ct_push_payload($product);
	if (!$payload['blueprint_id']) {
		return ['ok' => false, 'id' => 0, 'err' => 'nessun blueprint agganciato', 'raw' => null, 'payload' => $payload];
	}
	list($ok, $data, $err) = crs_ct_send('POST', 'products', $payload);
	$id = $ok ? crs_ct_extract_product_id($data) : 0;
	if ($id) {
		update_post_meta($product->get_id(), CRS_META_CT_PRODUCT, $id);
		update_post_meta($product->get_id(), CRS_META_CT_STOCK, (int) $payload['quantity']); // ancora pull: qty che ABBIAMO messo su CT
		update_post_meta($product->get_id(), '_ct_stock_ts', time());                        // quando: il pull salta finché l'export CT non è fresco
	}
	return [
		'ok'      => $ok && $id > 0,
		'id'      => $id,
		'err'     => $ok ? ($id ? '' : 'risposta senza id prodotto') : $err,
		'raw'     => $data,
		'payload' => $payload,
	];
}

/** UPDATE: SETTA quantità+prezzo dell'inserzione esistente (mai increment). */
function crs_ct_update_product($product)
{
	$ctpid = (int) get_post_meta($product->get_id(), CRS_META_CT_PRODUCT, true);
	if (!$ctpid) {
		return ['ok' => false, 'err' => 'nessun _ct_product_id'];
	}
	// SOLO la quantità: il prezzo su CardTrader lo governa l'autopricer, non lo ri-spingiamo dal sito
	// (altrimenti litigheremmo con l'autopricer ad ogni push). Il prezzo lo prende il pull da CardTrader.
	$body = ['quantity' => max(0, (int) $product->get_stock_quantity())];
	list($ok, $data, $err) = crs_ct_send('PUT', 'products/' . $ctpid, $body);
	if ($ok) {
		update_post_meta($product->get_id(), CRS_META_CT_STOCK, (int) $body['quantity']); // ancora pull: qty che ABBIAMO messo su CT
		update_post_meta($product->get_id(), '_ct_stock_ts', time());                     // quando: il pull salta finché l'export CT non è fresco
	}
	return ['ok' => $ok, 'err' => $err, 'raw' => $data];
}

/** DELETE: rimuove l'inserzione (venduto/sparito) e pulisce `_ct_product_id`. */
function crs_ct_delete_product($product)
{
	$pid   = $product->get_id();
	$ctpid = (int) get_post_meta($pid, CRS_META_CT_PRODUCT, true);
	if (!$ctpid) {
		return ['ok' => false, 'err' => 'nessun _ct_product_id'];
	}
	list($ok, $data, $err) = crs_ct_send('DELETE', 'products/' . $ctpid);
	if ($ok) {
		delete_post_meta($pid, CRS_META_CT_PRODUCT);
		delete_post_meta($pid, '_ct_price_synced'); // niente riferimento prezzo su un'inserzione che non esiste più
		delete_post_meta($pid, CRS_META_CT_STOCK);  // niente ancora pull su un'inserzione che non esiste più
		delete_post_meta($pid, '_ct_stock_ts');
	}
	return ['ok' => $ok, 'err' => $err, 'raw' => $data];
}

/**
 * Prodotto WC cestinato/eliminato: se è live su CardTrader, rimuovi anche l'inserzione. Senza questo,
 * il pull la troverebbe "sconosciuta" e la RICREEREBBE come nuovo prodotto pubblicato ogni notte (fix H-3).
 */
function crs_ct_on_product_removed($post_id)
{
	if (get_post_type($post_id) !== 'product' || !crs_ct_configured()) {
		return;
	}
	// niente cancellazioni di inserzioni vere da un'installazione locale
	if (!crs_ct_writes_allowed()) {
		return;
	}
	if (!get_post_meta($post_id, CRS_META_CT_PRODUCT, true)) {
		return;
	}
	$product = wc_get_product($post_id);
	if ($product) {
		crs_ct_delete_product($product); // DELETE su CardTrader + pulizia meta
	}
}
add_action('wp_trash_post', 'crs_ct_on_product_removed');
add_action('before_delete_post', 'crs_ct_on_product_removed');

/* ---- Push SINGOLO su cambio stock (vendite sul SITO / resi / modifiche a mano) → CardTrader in tempo reale ---- */

/**
 * Interruttore di soppressione del push-su-stock, valido per la durata della richiesta. Le NOSTRE scritture
 * di stock (import, pull, import_listing) NON devono rimbalzare su CardTrader: il push a blocchi le copre.
 * Import/pull lo alzano; le vendite reali del sito girano senza soppressione e fanno scattare il push singolo.
 */
function crs_ct_push_suppress($on = null)
{
	static $suppressed = false;
	if ($on !== null) {
		$suppressed = (bool) $on;
	}
	return $suppressed;
}

/**
 * Hook stock WooCommerce: se lo stock di un nostro prodotto cambia (ordine sul sito, reso, modifica manuale),
 * accoda un push SINGOLO async della nuova quantità a CardTrader. Non blocca il checkout. Idempotente
 * (crs_ct_push_one SETTA la quantità). Soppresso durante import/pull.
 */
function crs_ct_on_stock_change($product)
{
	if (crs_ct_push_suppress() || !crs_ct_configured()) {
		return;
	}
	// Fuori dalla produzione non si accoda nemmeno: la scrittura sarebbe comunque
	// respinta da crs_ct_send(), ma così la coda non si riempie di push destinati a
	// fallire ogni volta che in locale si fa un ordine di prova. Vedi
	// crs_ct_writes_allowed() in includes/cardtrader.php.
	if (!crs_ct_writes_allowed()) {
		return;
	}
	$pid = is_object($product) ? (int) $product->get_id() : (int) $product;
	if (!$pid) {
		return;
	}
	// solo prodotti nostri: già su CardTrader, oppure importati e agganciabili (blueprint o cardmarket_id)
	if (!get_post_meta($pid, CRS_META_CT_PRODUCT, true) && !get_post_meta($pid, CRS_META_CT_BLUEPRINT, true) && !get_post_meta($pid, CRS_META_CM_ID, true)) {
		return;
	}
	if (function_exists('as_enqueue_async_action')) {
		// dedup: se un push per questo prodotto è già in coda, non accodarne un altro (leggerà comunque la qty aggiornata)
		if (function_exists('as_has_scheduled_action') && as_has_scheduled_action('crs_ct_push_single', [$pid], 'cardsrift')) {
			return;
		}
		as_enqueue_async_action('crs_ct_push_single', [$pid], 'cardsrift');
	} else {
		wp_schedule_single_event(time() + 5, 'crs_ct_push_single', [$pid]);
	}
}
add_action('woocommerce_product_set_stock', 'crs_ct_on_stock_change');
add_action('woocommerce_variation_set_stock', 'crs_ct_on_stock_change');

/** Esegue il push singolo accodato: aggancia il blueprint se manca e riconcilia UNA carta con CardTrader. */
function crs_ct_do_push_single($pid)
{
	$pid = (int) $pid;
	if (!$pid || !crs_ct_configured()) {
		return;
	}
	if (!crs_lock('crs_ct_write')) {
		// una scrittura di massa (push/autopricer) tiene il lock: ri-pianifica CON RITARDO (mai un bounce stretto),
		// così l'aggiornamento non si perde ma non satura la coda mentre l'operazione grossa è in corso.
		if (function_exists('as_schedule_single_action')) {
			as_schedule_single_action(time() + 60, 'crs_ct_push_single', [$pid], 'cardsrift');
		} else {
			wp_schedule_single_event(time() + 60, 'crs_ct_push_single', [$pid]);
		}
		return;
	}
	if (!get_post_meta($pid, CRS_META_CT_BLUEPRINT, true)) {
		crs_ct_match_one($pid, true); // serve il blueprint per un eventuale CREATE
	}
	crs_ct_push_one($pid);
	crs_unlock('crs_ct_write');
}
add_action('crs_ct_push_single', 'crs_ct_do_push_single', 10, 1);

/**
 * PUSH sincrono (riconcilia CardTrader con WC: crea/aggiorna/rimuove). Idempotente.
 * @deprecated In produzione si usa il push a BLOCCHI in background (crs_ct_push_start / crs_ct_do_push_batch),
 * che non va in timeout a scala. Questa versione (e crs_ct_push_plan) è tenuta solo per CLI/piccoli cataloghi:
 * NON collegarla ai flussi (import/pulsante) — usa crs_ct_push_start. @return array counts (+ ok)
 */
function crs_ct_push_run($opts = [])
{
	if (!crs_ct_configured()) {
		return ['ok' => false, 'err' => 'token non configurato'];
	}
	// UN SOLO push per volta: evita che l'auto-push di fine import + il pulsante "Push ora" creino
	// inserzioni DOPPIE su CardTrader (entrambi vedrebbero "nessun _ct_product_id" e creerebbero) — C1.
	if (!crs_lock('crs_ct_write')) {
		return ['ok' => false, 'err' => 'push già in corso'];
	}
	$ids = crs_ct_matchable_ids();

	// aggancia i blueprint mancanti (necessari per creare le inserzioni)
	foreach ($ids as $pid) {
		if (!get_post_meta($pid, CRS_META_CT_BLUEPRINT, true)) {
			crs_ct_match_one($pid, true);
		}
	}

	$plan   = crs_ct_push_plan($ids);
	$counts = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'skipped' => (int) $plan['counts']['skip'], 'errors' => 0];
	$errors = [];
	$key    = ['create' => 'created', 'update' => 'updated', 'delete' => 'deleted'];

	foreach ($plan['rows'] as $row) {
		$act = $row['action'];
		if ($act === 'skip') {
			continue;
		}
		$product = wc_get_product($row['id']);
		if (!$product) {
			continue;
		}
		if ($act === 'create') {
			$r = crs_ct_create_product($product);
		} elseif ($act === 'update') {
			$r = crs_ct_update_product($product);
		} else {
			$r = crs_ct_delete_product($product);
		}
		if (!empty($r['ok'])) {
			$counts[$key[$act]]++;
		} else {
			$counts['errors']++;
			$errors[] = $row['id'] . ': ' . ($r['err'] ?? 'errore');
		}
	}

	$counts['ok'] = true;
	update_option('crs_ct_last_push', [
		'time'   => time(),
		'counts' => $counts,
		'errors' => array_slice($errors, 0, 10),
	], false);
	crs_unlock('crs_ct_write');
	return $counts;
}

/** Prefisso della nota `_ct_review` scritta da un push fallito. Deve restare in TESTA alla nota:
 *  è da lì che crs_ct_push_outcome() riconosce le proprie note e non tocca quelle dell'autopricer. */
const CRS_PUSH_FAIL = 'push fallito';

/**
 * Registra l'esito di UNA scrittura verso CardTrader.
 *
 * ⚠️ Perché esiste: prima l'esito veniva scartato. Una PUT/DELETE fallita (timeout, 429, 500
 * momentaneo di CardTrader) non lasciava traccia da nessuna parte — né log, né "Da rivedere",
 * e nemmeno nello storico di Action Scheduler, che segna l'azione "completata" perché nessuna
 * eccezione PHP è stata sollevata. L'inserzione restava viva su CardTrader con la vecchia
 * quantità: si finisce per vendere una carta che non c'è più.
 *
 * ⚠️ `_ct_review` è condiviso con l'autopricer ("nessun dato di mercato", "gioco non mappato"):
 * qui si scrive e si cancella SOLO la nota che inizia con CRS_PUSH_FAIL, mai le sue.
 *
 * @param int    $pid
 * @param array  $r      esito di crs_ct_create/update/delete_product()
 * @param string $label  cosa restituire se è andata (created|updated|deleted)
 * @param string $azione descrizione leggibile: finisce nella colonna "stato" di Da rivedere
 * @param string $err    (out) motivo del guasto, stringa vuota se è andata
 * @return string $label in caso di successo, 'errors' altrimenti
 */
function crs_ct_push_outcome($pid, $r, $label, $azione, &$err)
{
	if (!empty($r['ok'])) {
		// andata: se restava la nota di un guasto precedente si toglie — ma solo la nostra
		$prev = (string) get_post_meta($pid, '_ct_review', true);
		if (strpos($prev, CRS_PUSH_FAIL) === 0) {
			delete_post_meta($pid, '_ct_review');
		}
		return $label;
	}

	$err = (string) ($r['err'] ?? 'errore sconosciuto');
	update_post_meta($pid, '_ct_review', sprintf(
		'%s (%s) %s: %s',
		CRS_PUSH_FAIL,
		$azione,
		current_time('d/m H:i'),
		$err
	));
	error_log(sprintf('[cardsrift-sync] scrittura CardTrader fallita — prodotto %d — %s — %s', $pid, $azione, $err));
	return 'errors';
}

/**
 * Riconcilia UN prodotto con CardTrader in base al suo stato (usato dal push sync e da quello a blocchi):
 *  collegato+stock>0 → UPDATE quantità · collegato+stock0 → DELETE (venduto) · non collegato+blueprint+stock>0 → CREATE · else skip.
 * Ogni fallimento viene loggato e marcato "Da rivedere" da crs_ct_push_outcome().
 * @param int    $pid
 * @param string $err (out, facoltativo) motivo del guasto — i chiamanti esistenti possono ignorarlo
 * @return string created|updated|deleted|skipped|errors  (null se prodotto assente)
 */
function crs_ct_push_one($pid, &$err = null)
{
	$err     = '';
	$product = wc_get_product($pid);
	if (!$product) {
		return null;
	}
	$ctpid = (int) get_post_meta($pid, CRS_META_CT_PRODUCT, true);
	$qty   = max(0, (int) $product->get_stock_quantity());

	if ($ctpid) {
		// il DELETE è il caso che fa più danno se fallisce in silenzio: l'inserzione resta in vendita
		return $qty > 0
			? crs_ct_push_outcome($pid, crs_ct_update_product($product), 'updated', 'aggiornamento quantità', $err)
			: crs_ct_push_outcome($pid, crs_ct_delete_product($product), 'deleted', 'cancellazione inserzione', $err);
	}
	if (get_post_meta($pid, CRS_META_CT_BLUEPRINT, true) && $qty > 0) {
		// include il caso "risposta senza id prodotto": il POST è passato ma non sappiamo quale
		// inserzione abbiamo creato — va guardato a mano, o il push successivo la duplica.
		return crs_ct_push_outcome($pid, crs_ct_create_product($product), 'created', 'creazione inserzione', $err);
	}
	return 'skipped';
}

/* ---- Push a BLOCCHI in background (stesso schema dell'autopricer): tutto il catalogo senza timeout (C2) ---- */

/** Prodotti per blocco di push (filtrabile). */
function crs_ct_push_batch_size()
{
	return max(1, (int) apply_filters('crs_ct_push_batch_size', 50));
}

/** Avvia il push a blocchi. @return int prodotti; -1 se già in corso (job recente); 0 se non parte. */
function crs_ct_push_start()
{
	if (!crs_ct_configured()) {
		return 0;
	}
	if (!crs_ct_writes_allowed()) {
		return 0; // l'avviso lo mostra la pagina impostazioni (admin.php)
	}
	$job = get_option('crs_ct_push_job');
	if ($job && (time() - (int) ($job['started'] ?? 0)) < 6 * HOUR_IN_SECONDS) {
		return -1;
	}
	if ($job) {
		delete_option('crs_ct_push_job');
		delete_option('crs_ct_push_ids');
	}
	$ids = array_values(crs_ct_matchable_ids());
	if (!$ids) {
		return 0;
	}
	update_option('crs_ct_push_ids', $ids, false); // lista immutabile: il job tiene solo il cursore
	update_option('crs_ct_push_job', [
		'total'   => count($ids),
		'offset'  => 0,
		'counts'  => ['created' => 0, 'updated' => 0, 'deleted' => 0, 'skipped' => 0, 'api_error' => 0, 'errors' => 0],
		'started' => time(),
	], false);
	crs_ct_push_enqueue(0);
	return count($ids);
}

/** Accoda un blocco di push (Action Scheduler; fallback WP-Cron). */
function crs_ct_push_enqueue($offset)
{
	$offset = (int) $offset;
	if (function_exists('as_enqueue_async_action')) {
		as_enqueue_async_action('crs_ct_push_batch', [$offset], 'cardsrift');
	} else {
		wp_schedule_single_event(time() + 1, 'crs_ct_push_batch', [$offset]);
	}
}

/** Processa un blocco di prodotti (match + riconcilia) e accoda il successivo, fino a fine catalogo. */
function crs_ct_do_push_batch($offset = 0)
{
	if (!crs_lock('crs_ct_write')) { // stesso lock del push sync → mai due push in parallelo (C1)
		return;
	}
	$job    = get_option('crs_ct_push_job');
	$offset = (int) $offset;
	if (!$job || $offset !== (int) $job['offset']) {
		crs_unlock('crs_ct_write');
		return;
	}
	$ids = get_option('crs_ct_push_ids');
	if (!is_array($ids)) {
		delete_option('crs_ct_push_job');
		delete_option('crs_ct_push_ids'); // cleanup simmetrico
		crs_unlock('crs_ct_write');
		return;
	}

	$block = array_slice($ids, $offset, crs_ct_push_batch_size());
	crs_ct_prime_caches($block); // N+1 → poche query (H3)
	foreach ($block as $pid) {
		if (!get_post_meta($pid, CRS_META_CT_BLUEPRINT, true)) {
			$m = crs_ct_match_one($pid, true); // aggancia il blueprint se manca (necessario per il CREATE)
			if (($m['method'] ?? '') === 'api-error') {
				$job['counts']['api_error'] = ($job['counts']['api_error'] ?? 0) + 1;
				continue; // guasto API sul blueprint: non è uno "skip" definitivo, ritenteremo (fresh-M-3)
			}
		}
		$s = crs_ct_push_one($pid);
		if ($s !== null && isset($job['counts'][$s])) {
			$job['counts'][$s]++;
		}
	}
	$job['offset'] = $offset + count($block);

	if ($job['offset'] < $job['total'] && $block) {
		$job['started'] = time(); // "battito" anti-azzeramento del job su cron lento (H-4)
		update_option('crs_ct_push_job', $job, false);
		crs_ct_push_enqueue($job['offset']);
	} else {
		update_option('crs_ct_last_push', ['time' => time(), 'counts' => $job['counts']], false);
		delete_option('crs_ct_push_job');
		delete_option('crs_ct_push_ids');
	}
	crs_unlock('crs_ct_write');
}
add_action('crs_ct_push_batch', 'crs_ct_do_push_batch', 10, 1);

/* ================= PEZZO 3 — PULL (CardTrader → WooCommerce) + cron notturno ================= */

/**
 * PULL: riprende da CardTrader il PREZZO (dall'autopricer), l'IMMAGINE di catalogo (blueprint CDN)
 * e le info, e li scrive in WooCommerce. Scrive SOLO in locale.
 *  - rispetta `_crs_price_pinned` (non tocca i prezzi fissati a mano);
 *  - ciclo bozza→pubblica: un prodotto in bozza si pubblica quando ha un prezzo definitivo.
 * Idempotente: riscrive gli stessi valori, nessun effetto cumulativo.
 * @return array{ok:bool, err?:string, counts?:array}
 */
function crs_ct_pull($opts = [])
{
	// UN SOLO pull per volta (cron notturno + "Pull ora" manuale): due pull concorrenti applicherebbero due
	// volte lo stesso delta di stock. Lock DEDICATO (non crs_ct_write) così non affama i batch push/autopricer.
	if (!crs_lock('crs_ct_pull')) {
		// il marcatore lo possiede il pull che sta girando: non toccarlo
		return ['ok' => false, 'err' => 'Un pull è già in corso, riprova tra poco.', 'busy' => true];
	}
	crs_ct_pull_mark('export');
	list($ok, $prods, $err) = crs_ct_products();
	if (!$ok) {
		crs_unlock('crs_ct_pull');
		return crs_ct_pull_done(['ok' => false, 'err' => $err]);
	}
	crs_ct_push_suppress(true); // le riduzioni di stock fatte QUI (vendite su CT) non devono rimbalzare su CardTrader
	$byId = [];
	foreach (crs_ct_arr($prods) as $p) {
		if (isset($p['id'])) {
			$byId[(int) $p['id']] = $p;
		}
	}

	$counts = ['price' => 0, 'image' => 0, 'published' => 0, 'pinned' => 0, 'not_on_ct' => 0, 'stock' => 0, 'imported' => 0, 'seen' => 0];
	$known  = [];
	// se l'autopricer CUSTOM è attivo, il prezzo lo possiede lui: il pull NON lo prende da CardTrader
	$autoprice_on = (bool) get_option('crs_ct_autoprice_on');

	// 1) UPDATE — prodotti WC già collegati a CardTrader (prezzo/immagine/bozza→pubblica)
	$synced = crs_ct_synced_ids();
	crs_ct_prime_caches($synced); // N+1 → poche query: pull leggero (col decouple dall'autopricer basta a evitare il buco H-1)
	crs_ct_pull_mark('update', 0, count($synced));
	$i = 0;
	foreach ($synced as $pid) {
		// battito ogni 50: se il processo viene ucciso, `done` dice a che punto del ciclo si è fermato
		if (++$i % 50 === 0) {
			crs_ct_pull_mark('update', $i);
		}
		$ctpid = (int) get_post_meta($pid, CRS_META_CT_PRODUCT, true);
		$known[$ctpid] = true;
		if (!isset($byId[$ctpid])) {
			$counts['not_on_ct']++;
			// Scollego SOLO dopo che l'inserzione manca in DUE pull consecutivi. Un export parziale (risposta 200
			// troncata di un grande venditore) fa mancare tante inserzioni per UN giro, non per due → così NON
			// scollego in massa e NON provoco ri-creazioni duplicate al push successivo (fix regressione M7).
			$absent = (int) get_post_meta($pid, '_ct_absent', true) + 1;
			if ($absent >= 2) {
				delete_post_meta($pid, CRS_META_CT_PRODUCT);
				delete_post_meta($pid, '_ct_price_synced');
				delete_post_meta($pid, '_ct_absent');
				delete_post_meta($pid, CRS_META_CT_STOCK); // inserzione sparita → ancora pull azzerata (ri-baseline se torna)
				delete_post_meta($pid, '_ct_stock_ts');
				// CardTrader è la verità di stock per OGNI tipo, singole comprese. L'export non contiene mai
				// `quantity: 0` (verificato: 808 inserzioni, minimo 1): un'inserzione esaurita SPARISCE, non
				// scende a zero. Quindi "assente per 2 giri" = ESAURITA, e va azzerata anche qui — il ramo
				// delta più sotto non la vede nemmeno, perché senza riga nell'export non c'è nessun delta.
				//
				// ⚠️ Prima le singole restavano intatte (master = Cardmarket) e il danno era doppio: il sito
				// continuava a vendere una carta già venduta su CardTrader, e al push successivo il prodotto
				// (stock > 0 + blueprint, senza più `_ct_product_id`) rientrava nel ramo `create` di
				// crs_ct_push_one() → l'inserzione veniva RI-CREATA su CardTrader. Azzerando lo stock il push
				// la salta da sé ('skipped'), quindi non serve nessun altro guardiano.
				$sp = wc_get_product($pid);
				if ($sp && (int) $sp->get_stock_quantity() !== 0) {
					$sp->set_stock_quantity(0);
					$sp->set_stock_status('outofstock');
					// singola esaurita → fuori catalogo, come nel ramo delta; sealed e accessori restano
					// visibili "Esaurito" (li si ri-ordina, una singola no)
					if (crs_product_type_slug($pid) === 'singole') {
						$sp->set_catalog_visibility('hidden');
					}
					$sp->save();
					$counts['stock']++;
				}
			} else {
				update_post_meta($pid, '_ct_absent', $absent);
			}
			continue;
		}
		$product = wc_get_product($pid);
		if (!$product) {
			continue;
		}
		$counts['seen']++;
		delete_post_meta($pid, '_ct_absent'); // presente nell'export → azzera il contatore "assente"
		$listing = $byId[$ctpid];
		$changed = false;
		// SINGOLE vs resto (sealed/accessori): le singole sono master=Cardmarket e passano dall'autopricer;
		// sealed/accessori sono master=CardTrader → prezzo e stock (su e giù) li governa CardTrader.
		$is_single = crs_product_type_slug($pid) === 'singole';

		// prezzo — non tocca i prezzi fissati a mano; saltato del tutto se l'autopricer custom è attivo
		if (get_post_meta($pid, CRS_META_PRICE_PINNED, true) === 'yes') {
			$counts['pinned']++;
			// prezzo fissato a mano: propagalo su CardTrader se è cambiato rispetto all'ultimo confermato (M8)
			$raw = $product->get_regular_price();
			// stesso formato dell'autopricer ((string)round(...,2)) → niente PUT spurie passando pinned↔autopriced
			$wcprice = $raw === '' ? '' : (string) round((float) $raw, 2);
			if ($wcprice !== '' && (string) get_post_meta($pid, '_ct_price_synced', true) !== $wcprice) {
				list($ppok) = crs_ct_send('PUT', 'products/' . $ctpid, ['price' => (float) $wcprice]);
				if ($ppok) {
					update_post_meta($pid, '_ct_price_synced', $wcprice);
				}
			}
		} elseif ((!$autoprice_on || !$is_single) && isset($listing['price_cents'])) {
			// prezzo da CardTrader: quando l'autopricer è spento, e SEMPRE per sealed/accessori (non si autoprezzano)
			$price = round(((int) $listing['price_cents']) / 100, 2);
			if (abs((float) $product->get_regular_price() - $price) > 0.001) {
				$product->set_regular_price((string) $price);
				$changed = true;
				$counts['price']++;
			}
		}

		// immagini dal blueprint CDN (senza proxy): preview per il listato, piena per la scheda prodotto
		$imgs = crs_ct_listing_images($listing);
		if ($imgs['preview'] && get_post_meta($pid, '_ct_image', true) !== $imgs['preview']) {
			update_post_meta($pid, '_ct_image', $imgs['preview']);
			$counts['image']++;
		}
		if ($imgs['full'] && get_post_meta($pid, '_ct_image_full', true) !== $imgs['full']) {
			update_post_meta($pid, '_ct_image_full', $imgs['full']);
		}

		// STOCK — modello DELTA sull'ancora _ct_stock (ultima qty CT nota: scritta dal push o osservata dal pull
		// precedente). Applichiamo SOLO la VARIAZIONE di CardTrader dall'ultima volta, non il valore assoluto:
		// così una vendita sul sito (che riduce WC nativamente E viene spinta su CT dal push, aggiornando l'ancora)
		// NON viene contata una seconda volta dal pull, e un restock ancora "in volo" verso CT non azzera WC.
		// CardTrader è il master dello stock per OGNI tipo, singole comprese: applico il delta su e giù.
		// ⚠️ Prima le singole prendevano solo le riduzioni (master = Cardmarket) MA l'ancora avanzava comunque
		// (vedi sotto): il rialzo non veniva rinviato, veniva SCARTATO, e la divergenza restava per sempre.
		// Esempio reale: CT 1→3, ancora 3, WC 1; poi una vendita su CT 3→2 dava delta −1 → WC 0 "esaurito"
		// mentre su CardTrader ce n'erano ancora 2. Si perdevano vendite senza che nulla segnalasse niente.
		// L'export /products/export di CardTrader è in CACHE (~pochi secondi di ritardo, misurato): se un push
		// ha appena aggiornato l'ancora, l'export potrebbe ancora mostrare la quantità VECCHIA → un delta spurio.
		// Perciò salto la riconciliazione stock finché il push è recente (< 2 min): la farà il prossimo pull su dati freschi.
		$ct_stock_ts = (int) get_post_meta($pid, '_ct_stock_ts', true);
		$push_fresh  = $ct_stock_ts && (time() - $ct_stock_ts) < 120;
		if (isset($listing['quantity']) && !$push_fresh) {
			$ctq = max(0, (int) $listing['quantity']);
			if (!metadata_exists('post', $pid, CRS_META_CT_STOCK)) {
				update_post_meta($pid, CRS_META_CT_STOCK, $ctq); // baseline: registra l'ancora senza toccare WC
			} else {
				$apply = $ctq - (int) get_post_meta($pid, CRS_META_CT_STOCK, true);
				if ($apply !== 0) {
					$newq = max(0, (int) $product->get_stock_quantity() + $apply);
					if ($newq !== (int) $product->get_stock_quantity()) {
						$product->set_stock_quantity($newq);
						$product->set_stock_status($newq > 0 ? 'instock' : 'outofstock');
						// La visibilità della singola segue lo stock nei DUE versi. Il ritorno a 'visible' non è
						// un extra: ora che il pull può ALZARE una singola, senza di esso una carta nascosta da
						// un esaurimento resterebbe fuori catalogo anche da tornata disponibile — in vendita su
						// CardTrader e invendibile sul sito, senza nessun errore a vista. Il sealed resta sempre
						// visibile ("Esaurito"): lo si ri-ordina, una singola no.
						if ($is_single) {
							$product->set_catalog_visibility($newq > 0 ? 'visible' : 'hidden');
						}
						$changed = true;
						$counts['stock']++;
					}
					update_post_meta($pid, CRS_META_CT_STOCK, $ctq); // l'ancora segue sempre CardTrader
				}
			}
		}

		// bozza → pubblica: solo con un prezzo DEFINITIVO. Con l'autopricer custom attivo la pubblicazione la fa
		// lui DOPO aver scritto il prezzo reale — qui NON pubblico (eviterei di pubblicare al provvisorio, H2).
		// Eccezione: i SEALED non passano dall'autopricer → li pubblico qui appena hanno il prezzo CardTrader.
		if ($product->get_status() === 'draft' && $product->get_regular_price() !== '' && (!$autoprice_on || !$is_single)) {
			$product->set_status('publish');
			$changed = true;
			$counts['published']++;
		}

		if ($changed) {
			$product->save();
		}
	}

	// 2) IMPORT — inserzioni su CardTrader senza prodotto WC (aggiunte a mano su CardTrader)
	crs_ct_pull_mark('import', count($synced));
	foreach ($byId as $ctid => $listing) {
		if (isset($known[$ctid])) {
			continue;
		}
		if (crs_ct_import_listing($listing)) {
			$counts['imported']++;
		}
	}

	$counts['ok'] = true;
	crs_unlock('crs_ct_pull');
	return crs_ct_pull_done(['ok' => true, 'counts' => $counts]);
}

/**
 * TRACCIA DEL PULL — due marcatori, non un log.
 *
 * ⚠️ Perché esiste: il pull era l'unico dei tre giri senza record di esecuzione (push e autopricer
 * scrivono `crs_ct_last_push` / `crs_ct_last_autoprice`). Un pull ucciso a metà non lasciava NIENTE,
 * quindi era indistinguibile da un pull mai partito — successo il 26/07/2026, dove il giro delle 02:00
 * è morto in mezzo e per capirlo è servita mezz'ora di indagine su transient e `post_modified`, senza
 * arrivare a una risposta certa. E il pull è l'UNICO canale in ingresso da CardTrader: se fallisce in
 * silenzio le scorte del sito restano ferme mentre su CardTrader si vende.
 *
 *  · `crs_ct_pull_running` — scritto all'AVVIO, cancellato alla fine. Se lo si trova ancora lì, il pull
 *    è morto in mezzo: `phase` dice in quale fase, `done`/`total` a che punto del ciclo, `beat` a che ora.
 *  · `crs_ct_last_pull`    — esito dell'ultimo giro CONCLUSO, riuscito o fallito.
 *
 * Deliberatamente NON un file di log e NON una riga per prodotto: su hosting condiviso un file che
 * nessuno ruota diventa un problema, e un'option che cresce pure. Qui sono due option riscritte in posto.
 */
function crs_ct_pull_mark($phase, $done = null, $total = null)
{
	$m = get_option('crs_ct_pull_running');
	if (!is_array($m)) {
		$m = ['started' => time()];
	}
	$m['phase'] = (string) $phase;
	if ($done !== null) {
		$m['done'] = (int) $done;
	}
	if ($total !== null) {
		$m['total'] = (int) $total;
	}
	$m['beat'] = time(); // ultimo segno di vita: se il pull muore, dice a che ora si è fermato
	update_option('crs_ct_pull_running', $m, false);
}

/** Chiude la traccia: scrive l'esito e toglie il marcatore d'avvio. @return array il risultato, invariato */
function crs_ct_pull_done($res)
{
	$m = get_option('crs_ct_pull_running');
	$started = is_array($m) ? (int) ($m['started'] ?? 0) : 0;
	update_option('crs_ct_last_pull', [
		'time'    => time(),
		'started' => $started,
		'secondi' => $started ? max(0, time() - $started) : 0,
		'ok'      => !empty($res['ok']),
		'err'     => (string) ($res['err'] ?? ''),
		'counts'  => is_array($res['counts'] ?? null) ? $res['counts'] : [],
	], false);
	delete_option('crs_ct_pull_running');
	return $res;
}

/** Immagini di catalogo (blueprint CDN) di un'inserzione: ['preview'=>miniatura, 'full'=>piena]. */
function crs_ct_listing_images($listing)
{
	$eid = (int) ($listing['expansion_id'] ?? ($listing['expansion']['id'] ?? 0)); // export = oggetto `expansion`
	$bid = (int) ($listing['blueprint_id'] ?? 0);
	if (!$eid || !$bid) {
		return ['preview' => '', 'full' => ''];
	}
	$map = crs_ct_blueprint_map($eid);
	$b   = is_array($map) ? ($map[$bid] ?? []) : []; // null (errore API) → nessuna immagine, riproveremo
	return ['preview' => $b['image'] ?? '', 'full' => $b['image_full'] ?? ''];
}

/** ID dei prodotti WC collegati a CardTrader (hanno `_ct_product_id`), bozze incluse. */
function crs_ct_synced_ids()
{
	$q = new WP_Query([
		'post_type'      => 'product',
		'post_status'    => ['publish', 'draft'],
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [['key' => CRS_META_CT_PRODUCT, 'compare' => 'EXISTS']],
	]);
	return $q->posts;
}

/** game_id CardTrader → nostro slug gioco ('' se il gioco non è gestito dal sito). */
function crs_ct_game_slug_from_id($ct_game_id)
{
	$map = [1 => 'magic', 5 => 'pokemon', 15 => 'one-piece'];
	return $map[(int) $ct_game_id] ?? '';
}

/**
 * Crea un prodotto WooCommerce da un'inserzione CardTrader non ancora presente sul sito
 * (aggiunta a mano direttamente su CardTrader). Pubblicato (ha già un prezzo definitivo).
 * Instrada per TIPO in base alla categoria del blueprint (crs_ct_category_kind):
 *   - SINGOLE → SKU CM-… (identità Cardmarket via card_market_ids), attributi condizione/lingua/foil, autoprezzabile;
 *   - SEALED/ACCESSORI → SKU CTB-{blueprint} (niente idProduct Cardmarket), prezzo da CardTrader, mai autoprezzato.
 * Dedup per SKU: se il prodotto esiste già, gli aggancia soltanto il collegamento CardTrader.
 * @return int product id creato (0 se saltato/dedup)
 */
function crs_ct_import_listing($listing)
{
	$ctid = (int) ($listing['id'] ?? 0);
	$game = crs_ct_game_slug_from_id((int) ($listing['game_id'] ?? 0));
	if (!$ctid || $game === '') {
		return 0; // senza id o gioco non gestito
	}

	$bid   = (int) ($listing['blueprint_id'] ?? 0);
	$eid   = (int) ($listing['expansion_id'] ?? ($listing['expansion']['id'] ?? 0));
	$bpmap = $bid ? crs_ct_blueprint_map($eid) : [];
	if ($bpmap === null) {
		return 0; // errore API sul blueprint: NON classifico né importo ora — ritento al prossimo pull (niente mis-filing)
	}
	$bp = $bpmap[$bid] ?? [];

	// tipo (singole|sealed|accessori) dalla categoria del blueprint
	$kind = crs_ct_category_kind((int) ($bp['category_id'] ?? 0));
	if ($kind === null) {
		return 0; // errore API su /categories: ritento al prossimo pull invece di mis-classificare (fresh review #1)
	}
	if ($kind === '') {
		$kind = 'singole'; // categoria assente/ignota MA fetch riuscito → default prudente
	}

	// proprietà CardTrader → nostri vocabolari (lingua utile a tutti; condizione/foil solo per le singole)
	$props = (isset($listing['properties_hash']) && is_array($listing['properties_hash'])) ? $listing['properties_hash'] : [];
	$lang  = '';
	foreach ($props as $k => $v) {
		if ($k === 'language' || substr((string) $k, -9) === '_language') {
			$lang = crs_ct_lang_to_slug($v);
			break;
		}
	}
	$cm_ids = $bp['cm_ids'] ?? [];
	$cm_id  = !empty($cm_ids[0]) ? (string) $cm_ids[0] : '';
	$name   = !empty($bp['name']) ? $bp['name'] : ($listing['name_en'] ?? ('Prodotto CardTrader ' . $ctid));

	if ($kind === 'singole') {
		$cond     = crs_ct_condition_to_slug($props['condition'] ?? '');
		$foil     = crs_ct_foil_to_slug($props);
		$sku      = $cm_id ? crs_build_sku($cm_id, $cond, $lang, $foil) : ('CT-' . $ctid);
		$attr_map = ['condizione' => $cond, 'lingua' => $lang, 'foil' => $foil];
	} else {
		// SEALED/ACCESSORI: nessuna condizione/foil; identità sul blueprint (non c'è un idProduct Cardmarket)
		$sku      = crs_build_sealed_sku($bid ?: $ctid, $lang);
		$attr_map = $lang ? ['lingua' => $lang] : [];
		$cm_id    = ''; // non agganciare all'identità Cardmarket un prodotto sealed
	}

	// dedup per SKU: se il prodotto esiste già, gli aggancio solo il collegamento CardTrader
	$existing = wc_get_product_id_by_sku($sku);
	if ($existing) {
		$cur = (int) get_post_meta($existing, CRS_META_CT_PRODUCT, true);
		if ($cur && $cur !== $ctid) {
			// il prodotto è GIÀ collegato a un'altra inserzione CardTrader: NON sovrascrivo (orfanerei quella).
			// Questa inserzione manuale è probabilmente un doppione su CardTrader → la mando in "da rivedere" (fresh-M-2).
			update_post_meta($existing, '_ct_review', 'doppia inserzione CardTrader (ctid ' . $ctid . ')');
			return 0;
		}
		update_post_meta($existing, CRS_META_CT_PRODUCT, $ctid);
		if ($bid) {
			update_post_meta($existing, CRS_META_CT_BLUEPRINT, $bid);
		}
		return 0;
	}

	$qty     = max(0, (int) ($listing['quantity'] ?? 0));
	$product = new WC_Product_Simple();
	$product->set_name($name);
	$product->set_sku($sku);
	$product->set_status('publish'); // ha già un prezzo definitivo (autopricer / prezzo CardTrader del sealed)
	// visibilità: il SEALED resta visibile anche esaurito ("Esaurito"); una SINGOLA a 0 esce dal catalogo
	$product->set_catalog_visibility(($kind === 'singole' && $qty <= 0) ? 'hidden' : 'visible');
	$product->set_regular_price((string) round(((int) ($listing['price_cents'] ?? 0)) / 100, 2));
	$product->set_manage_stock(true);
	$product->set_stock_quantity($qty);
	$product->set_stock_status($qty > 0 ? 'instock' : 'outofstock');

	// espansione (comune a tutti i tipi)
	$exp = $eid ? (crs_ct_expansion_map()[$eid] ?? null) : null;
	if ($exp && !empty($exp['code']) && !empty($exp['name'])) {
		$esp_code = sanitize_title($exp['code']);
		crs_ensure_terms(wc_attribute_taxonomy_name('espansione'), [$esp_code => $exp['name']]);
		$attr_map['espansione'] = $esp_code;
	}
	$attrs = crs_build_attributes($attr_map);
	if ($attrs) {
		$product->set_attributes($attrs);
	}

	$product->update_meta_data(CRS_META_CT_PRODUCT, $ctid);
	if ($bid) {
		$product->update_meta_data(CRS_META_CT_BLUEPRINT, $bid);
	}
	if ($cm_id) {
		$product->update_meta_data(CRS_META_CM_ID, $cm_id); // aggancio all'identità Cardmarket, se disponibile
	}
	if (!empty($bp['image'])) {
		$product->update_meta_data('_ct_image', $bp['image']);            // miniatura (listato)
	}
	if (!empty($bp['image_full'])) {
		$product->update_meta_data('_ct_image_full', $bp['image_full']); // piena (scheda prodotto)
	}

	$pid = $product->save();
	if (!$pid) {
		return 0;
	}

	$cats = [];
	foreach ([$game, $kind] as $cslug) {
		$t = get_term_by('slug', $cslug, 'product_cat');
		if ($t) {
			$cats[] = (int) $t->term_id;
		}
	}
	if ($cats) {
		wp_set_object_terms($pid, $cats, 'product_cat');
	}
	crs_assign_attr_terms($pid, $attr_map);

	return $pid;
}

/** Timestamp del prossimo 02:00 nel fuso del sito (per lo scheduling del cron). */
function crs_ct_next_2am()
{
	$tz  = wp_timezone();
	$now = new DateTime('now', $tz);
	$two = new DateTime('today 02:00', $tz);
	if ($two <= $now) {
		$two->modify('+1 day');
	}
	return $two->getTimestamp();
}

/** Pianifica il pull notturno (idempotente: non duplica l'evento). */
function crs_ct_schedule_pull()
{
	if (!wp_next_scheduled('crs_ct_nightly_pull')) {
		wp_schedule_event(crs_ct_next_2am(), 'daily', 'crs_ct_nightly_pull');
	}
}

/** Rimuove lo scheduling del pull notturno. */
function crs_ct_unschedule_pull()
{
	$ts = wp_next_scheduled('crs_ct_nightly_pull');
	if ($ts) {
		wp_unschedule_event($ts, 'crs_ct_nightly_pull');
	}
}

/**
 * Bozze "orfane": prodotti importati (con `_cardmarket_id`) rimasti in bozza e NON su CardTrader
 * (nessun `_ct_product_id`). Il pull li ignora, quindi non verranno mai pubblicati da soli: vanno
 * agganciati+pushati oppure pubblicati a mano. Ogni riga porta lo stato per capire il perché.
 * @return array{total:int, rows:array}
 */
function crs_ct_orphan_drafts($limit = 200)
{
	// DA RIVEDERE = importati (publish o draft) che il flusso NON riesce a gestire in automatico:
	//  - senza blueprint agganciato (push/autopricer li saltano);
	//  - marcati `_ct_review` dall'autopricer (nessun dato di mercato / gioco non mappato).
	// Così gli skip silenziosi NON spariscono: un umano li aggancia/prezza/pubblica.
	$q = new WP_Query([
		'post_type'      => 'product',
		'post_status'    => ['publish', 'draft'],
		'posts_per_page' => (int) $limit,
		'fields'         => 'ids',
		'meta_query'     => [
			'relation' => 'OR',
			// A) match TENTATO durante un push ma FALLITO: ha _ct_match_method ma nessun blueprint. NON include i
			//    prodotti appena importati e mai spinti (senza metodo) → niente falsi allarmi subito dopo l'import.
			[
				'relation' => 'AND',
				['key' => '_ct_match_method', 'compare' => 'EXISTS'],
				['key' => CRS_META_CT_BLUEPRINT, 'compare' => 'NOT EXISTS'],
			],
			// B) marcato "da rivedere" — QUALSIASI tipo, inclusi sealed/accessori che non hanno _cardmarket_id (review #2)
			['key' => '_ct_review', 'compare' => 'EXISTS'],
		],
	]);

	$rows = [];
	foreach ($q->posts as $pid) {
		$p = wc_get_product($pid);
		if (!$p) {
			continue;
		}
		$review = (string) get_post_meta($pid, '_ct_review', true);
		if (strpos($review, CRS_PUSH_FAIL) === 0) {
			// una scrittura fallita ha la precedenza su tutto: è l'unico caso in cui su CardTrader
			// c'è (o manca) un'inserzione che non corrisponde alla realtà del magazzino.
			$stato = $review;
		} elseif (!get_post_meta($pid, CRS_META_CT_BLUEPRINT, true)) {
			$mm = (string) get_post_meta($pid, '_ct_match_method', true); // motivo del mancato aggancio
			$stato = $mm ? ('non agganciata: ' . $mm) : 'no-blueprint'; // es. "unmatched" / "no-expansion" / "no-game"
		} elseif ($review !== '') {
			$stato = $review;        // "nessun dato di mercato…" / "gioco non mappato"
		} else {
			continue;
		}
		$terms = get_the_terms($pid, 'pa_espansione');
		$rows[] = [
			'id'        => $pid,
			'name'      => get_the_title($pid),
			'expansion' => (!is_wp_error($terms) && $terms) ? reset($terms)->name : '',
			'status'    => $p->get_status(),
			'qty'       => (int) $p->get_stock_quantity(),
			'price'     => $p->get_regular_price(),
			'stato'     => $stato,
			'edit'      => get_edit_post_link($pid, ''),
		];
	}
	return ['total' => count($rows), 'rows' => $rows];
}

/* ================= AUTOPRICER CUSTOM (prezzo per condizione+lingua dal mercato CardTrader) ========= */

/** User id del venditore autenticato — per escludere le MIE inserzioni dal riferimento prezzi. */
function crs_ct_user_id()
{
	$cached = get_transient('crs_ct_uid');
	if ($cached) {
		return (int) $cached;
	}
	list($ok, $info) = crs_ct_info();
	$uid = ($ok && is_array($info)) ? (int) ($info['user_id'] ?? 0) : 0;
	if ($uid) {
		set_transient('crs_ct_uid', $uid, DAY_IN_SECONDS);
	}
	return $uid;
}

/** Mediana di una lista di numeri (null se vuota). */
function crs_ct_median($vals)
{
	sort($vals);
	$k = count($vals);
	if ($k === 0) {
		return null;
	}
	$mid = intdiv($k, 2);
	return ($k % 2) ? $vals[$mid] : ($vals[$mid - 1] + $vals[$mid]) / 2;
}

/**
 * Prezzo di riferimento robusto agli outlier, su un campione di prezzi di mercato: mediana + MAD, filtro
 * SIMMETRICO (taglia ENTRAMBE le code, non solo quella bassa).
 *  - mediana M e MAD (median absolute deviation) sull'intero campione;
 *  - scarta come outlier ogni prezzo FUORI da [M − 3σ, M + 3σ] (σ ≈ 1.4826·MAD): via le civette (troppo basse)
 *    E i placeholder-spazzatura (troppo alti, tipo 9.999€ su una carta da 3€); MAD≈0 → banda ±15% da M;
 *  - prezzo = il più basso dei sopravvissuti (il più economico LEGITTIMO → competitivo).
 * Regge a prezzi-civetta e placeholder finché sono una minoranza. Con pochissimi dati (<4) ripiega sulla
 * mediana — ma il chiamante (crs_ct_price_from_market) la valida contro il mercato ampio della carta. null se vuoto.
 */
function crs_ct_reference_price($prices)
{
	$prices = array_values($prices);
	$n = count($prices);
	if ($n === 0) {
		return null;
	}
	if ($n < 4) {
		return crs_ct_median($prices); // troppo pochi dati: la mediana; la sanità la fa l'ancora del chiamante
	}

	$m   = crs_ct_median($prices);
	$mad = crs_ct_median(array_map(function ($p) use ($m) {
		return abs($p - $m);
	}, $prices));
	$sigma = $mad > 0 ? 1.4826 * $mad : max($m * 0.15, 0.01);
	$lo = $m - 3 * $sigma;
	$hi = $m + 3 * $sigma; // ← taglio ANCHE l'alto: i placeholder gonfiati non sopravvivono
	$survivors = array_filter($prices, function ($p) use ($lo, $hi) {
		return $p >= $lo && $p <= $hi;
	});
	return $survivors ? min($survivors) : $m;
}

/**
 * AUTOPRICER CUSTOM: per ogni prodotto collegato a CardTrader interroga il mercato della sua
 * condizione+lingua+foil, esclude le mie inserzioni e i venditori in vacanza, calcola il prezzo
 * (crs_ct_reference_price) col floor, e lo scrive SU WOOCOMMERCE e SU CARDTRADER (sostituisce il
 * loro autopricer). Rispetta `_crs_price_pinned`; se non trova la condizione esatta lascia il prezzo.
 * Feedback in `crs_ct_last_autoprice`. @return array counts (+ ok)
 */
/** Contesto di pricing condiviso (letto una volta per run): mio user, floor, ricarico. */
function crs_ct_autoprice_ctx()
{
	return [
		'myuser' => crs_ct_user_id(),
		'floor'  => (float) get_option('crs_ct_floor', 0.20),
		'markup' => (float) get_option('crs_ct_markup', 5),
	];
}

/** Struttura counts vuota. */
function crs_ct_autoprice_counts()
{
	return ['priced' => 0, 'unchanged' => 0, 'published' => 0, 'floored' => 0, 'skipped_pinned' => 0, 'skipped_nodata' => 0, 'unsupported_game' => 0, 'fetch_error' => 0, 'errors' => 0, 'seen' => 0];
}

/** Somma il risultato per-carta nei counts. */
function crs_ct_tally(&$counts, $r)
{
	if ($r === null) {
		return;
	}
	$counts['seen']++;
	if (isset($counts[$r['status']])) {
		$counts[$r['status']]++;
	}
	if (!empty($r['floored'])) {
		$counts['floored']++;
	}
	if (!empty($r['published'])) {
		$counts['published']++;
	}
}

/** Pubblica un prodotto in bozza se ha già un prezzo definitivo. @return bool true se pubblicato ora. */
function crs_ct_publish_if_priced($pid)
{
	$product = wc_get_product($pid);
	if ($product && $product->get_status() === 'draft' && $product->get_regular_price() !== '') {
		$product->set_status('publish');
		$product->save();
		return true;
	}
	return false;
}

/**
 * Mercato di un'espansione: mappa blueprint_id => inserzioni. UNA chiamata copre TUTTE le carte del set
 * e TUTTE le lingue (l'endpoint ignora il parametro `language`), quindi la lingua la filtra poi
 * crs_ct_price_from_market per-carta. Timeout largo: la risposta di un set può essere corposa.
 * @return array|null  la mappa (anche vuota = mercato vuoto); NULL su errore di rete/auth/429 (≠ vuoto).
 */
function crs_ct_market_for($eid)
{
	list($ok, $data) = crs_ct_get('marketplace/products', ['expansion_id' => (int) $eid], 60);
	usleep(110000); // rate limit marketplace: 10 req/s
	if (!$ok) {
		return null; // errore: NON confondere con "mercato vuoto" → il chiamante ritenta il gruppo dopo
	}
	if (!is_array($data)) {
		return [];
	}
	// COMPATTA ai soli campi utili al pricing e libera il decode grosso: un set può essere ~50MB → OOM su 128MB (H5)
	$out = [];
	foreach ($data as $bid => $listings) {
		if (!is_array($listings)) {
			continue;
		}
		$slim = [];
		foreach ($listings as $m) {
			$slim[] = [
				'cents' => (int) ($m['price_cents'] ?? ($m['price']['cents'] ?? 0)),
				'uid'   => (int) ($m['user']['id'] ?? 0),
				'vac'   => !empty($m['on_vacation']),
				'ph'    => $m['properties_hash'] ?? [],
			];
		}
		$out[(int) $bid] = $slim;
	}
	unset($data);
	return $out;
}

/**
 * Prezza UN prodotto usando il mercato dell'espansione GIÀ scaricato ($market = blueprint_id => inserzioni).
 * Filtra a condizione+lingua+foil esatte, esclude le mie inserzioni e chi è in vacanza; prezzo robusto
 * (crs_ct_reference_price) + ricarico + floor; skip se invariato; scrive su WC e — se il prodotto è già su
 * CardTrader — fa la PUT del prezzo. @return array{status:string,floored?:bool}|null (null = non prezzabile)
 */
function crs_ct_price_from_market($pid, $market, $ctx)
{
	$bid = (int) get_post_meta($pid, CRS_META_CT_BLUEPRINT, true);
	if (!$bid) {
		return null;
	}
	if (crs_product_type_slug($pid) !== 'singole') {
		return null; // sealed/accessori: prezzo da CardTrader (pull), non dal mercato per condizione/lingua
	}
	if (get_post_meta($pid, CRS_META_PRICE_PINNED, true) === 'yes') {
		// prezzo fissato a mano = definitivo → pubblica se è ancora in bozza (H2 pubblica solo a prezzo reale)
		return ['status' => 'skipped_pinned', 'published' => crs_ct_publish_if_priced($pid)];
	}

	// chiavi property per gioco (Magic: mtg_*; Pokémon: pokemon_language + pokemon_reverse). null = non mappato.
	$gp = crs_ct_game_props(crs_product_game_slug($pid));
	if (!$gp) {
		update_post_meta($pid, '_ct_review', 'gioco non mappato'); // finisce nella lista "da rivedere" (sez.6)
		return ['status' => 'unsupported_game'];
	}
	$condCT = crs_condition_to_ct(strtoupper(crs_first_term_slug($pid, 'pa_condizione')));
	$langCT = crs_slug_to_ct_lang(crs_first_term_slug($pid, 'pa_lingua') ?: 'it');
	$foilOn = crs_first_term_slug($pid, 'pa_foil') === $gp['foil_slug']; // Magic 'foil' · Pokémon 'reverse-holo'

	$list  = $market[$bid] ?? []; // forma compatta di crs_ct_market_for: {cents,uid,vac,ph}
	$exact = []; // condizione+lingua+foil esatti: il prezzo per-lingua che vogliamo
	$broad = []; // stessa condizione+foil, QUALSIASI lingua → campione ampio = "valore reale" della carta (ancora di sanità)
	foreach ($list as $m) {
		if ($m['uid'] === $ctx['myuser'] || $m['vac'] || $m['cents'] <= 0) {
			continue; // non le mie · venditore in vacanza · prezzo nullo
		}
		$ph = $m['ph'];
		if (($ph['condition'] ?? '') !== $condCT) {
			continue; // stessa condizione
		}
		if ((!empty($ph[$gp['foil']])) !== $foilOn) {
			continue; // stesso finish (foil / reverse holo)
		}
		$eur = $m['cents'] / 100;
		$broad[] = $eur;
		if (($ph[$gp['lang']] ?? '') === $langCT) {
			$exact[] = $eur; // stessa lingua (l'API ignora il parametro → filtro qui)
		}
	}

	if (!$exact && !$broad) {
		update_post_meta($pid, '_ct_review', 'nessun dato di mercato'); // davvero niente su cui prezzare
		return ['status' => 'skipped_nodata'];
	}

	// Ancora di SANITÀ: valore robusto della carta sul mercato AMPIO (tutte le lingue, stessa cond+foil). Serve a
	// smascherare i placeholder-spazzatura quando il mercato nella lingua esatta è sottile o inquinato.
	$anchor = crs_ct_reference_price($broad); // null solo se $broad è vuoto
	$cap    = (float) apply_filters('crs_ct_price_cap_mult', 4.0); // il prezzo-lingua non può eccedere Nx il mercato generale
	$note   = '';

	if (count($exact) >= 4) {
		$base = crs_ct_reference_price($exact); // abbastanza dati: prezzo per-lingua robusto (filtro simmetrico)
		if ($anchor !== null && $base > $anchor * $cap) {
			$base = $anchor; // sballa in alto vs il mercato generale = dati-lingua inquinati (placeholder)
			$note = 'prezzo lingua anomalo → uso il mercato generale';
		}
	} elseif ($exact) {
		// mercato-lingua SOTTILE (1-3): mi fido solo se coerente col generale, IN ALTO E IN BASSO (simmetrico)
		$tent = crs_ct_median($exact);
		if ($anchor !== null && ($tent > $anchor * $cap || $tent < $anchor / $cap)) {
			$base = $anchor;
			$note = 'poche inserzioni nella lingua e prezzo anomalo → uso il mercato generale';
		} else {
			$base = $tent;
		}
	} else {
		$base = $anchor; // nessuna inserzione nella lingua → prezzo dal mercato generale (fallback normale, non segnalato)
	}

	$price   = round($base * (1 + $ctx['markup'] / 100), 2); // più basso legittimo + ricarico
	$floored = false;
	if ($price < $ctx['floor']) {
		$price   = $ctx['floor'];
		$floored = true;
	}
	// campione minuscolo: il prezzo si scrive comunque (mai lasciare spazzatura), ma si segnala per un controllo umano
	if (count($broad) < 4) {
		$note = ($note ? $note . ' · ' : '') . 'pochi dati di mercato: verifica';
	}
	if ($note !== '') {
		update_post_meta($pid, '_ct_review', $note); // prezzato ma con nota → compare in "Da rivedere"
	} else {
		delete_post_meta($pid, '_ct_review');
	}

	$product = wc_get_product($pid);
	if (!$product) {
		return null;
	}
	$ctpid  = (int) get_post_meta($pid, CRS_META_CT_PRODUCT, true);
	$synced = (string) get_post_meta($pid, '_ct_price_synced', true); // ultimo prezzo CONFERMATO su CardTrader
	$dirty  = false;

	// prezzo sul sito (solo se cambia)
	if (abs((float) $product->get_regular_price() - $price) > 0.001) {
		$product->set_regular_price((string) $price);
		$dirty = true;
	}
	// bozza → pubblica SOLO ora che c'è un prezzo reale (mai al provvisorio dell'import) — H2
	$published = false;
	if ($product->get_status() === 'draft') {
		$product->set_status('publish');
		$dirty     = true;
		$published = true;
	}
	if ($dirty) {
		$product->save();
	}

	// CardTrader: rispingi il prezzo quando l'ULTIMO confermato ≠ prezzo, INDIPENDENTEMENTE dal sito (H1).
	// Se la PUT fallisce non aggiorno `_ct_price_synced` → il giro dopo ritenta (niente divergenza permanente).
	$pok = true;
	$put = false;
	if ($ctpid && $synced !== (string) $price) {
		$put = true;
		list($pok) = crs_ct_send('PUT', 'products/' . $ctpid, ['price' => $price]);
		if ($pok) {
			update_post_meta($pid, '_ct_price_synced', (string) $price);
		}
	}

	if ($put && !$pok) {
		return ['status' => 'errors', 'floored' => $floored, 'published' => $published]; // il save WC è già avvenuto
	}
	if (!$dirty && !$put) {
		return ['status' => 'unchanged', 'floored' => $floored];
	}
	return ['status' => 'priced', 'floored' => $floored, 'published' => $published];
}

/** Prime le cache di meta e termini per un set di prodotti in poche query (evita l'N+1 nei loop). */
function crs_ct_prime_caches($ids)
{
	$ids = array_values(array_unique(array_map('intval', (array) $ids)));
	foreach (array_chunk($ids, 500) as $chunk) {
		update_meta_cache('post', $chunk);
		update_object_term_cache($chunk, 'product');
	}
}

/**
 * Raggruppa i prodotti prezzabili (con `_ct_blueprint_id`) per ESPANSIONE CardTrader: una fetch di mercato
 * per set copre tutte le carte e tutte le lingue di quel set. @return array di ['eid','pids']
 */
function crs_ct_autoprice_groups()
{
	$ids = crs_ct_synced_ids();
	crs_ct_prime_caches($ids); // N+1 → poche query (H3)
	$groups = [];
	foreach ($ids as $pid) {
		if (!get_post_meta($pid, CRS_META_CT_BLUEPRINT, true)) {
			continue;
		}
		if (crs_product_type_slug($pid) !== 'singole') {
			continue; // solo le singole si autoprezzano; sealed/accessori prendono il prezzo da CardTrader (pull)
		}
		// espansione CardTrader dove vive il blueprint: preferisci quella SALVATA al match (già risolta con gli
		// alias Extras→Collectors); fallback per i prodotti agganciati prima di salvarla → codice+alias, poi nome.
		$eid = (int) get_post_meta($pid, '_ct_expansion_id', true);
		if (!$eid) {
			$game = crs_ct_game_id(crs_product_game_slug($pid));
			if (!$game) {
				continue;
			}
			$lk    = crs_ct_expansion_lookup($game);
			$eslug = strtolower(crs_first_term_slug($pid, 'pa_espansione'));
			foreach (crs_ct_expansion_code_aliases($eslug) as $alias) {
				if (isset($lk['by_code'][$alias])) {
					$eid = (int) $lk['by_code'][$alias];
					break;
				}
			}
			if (!$eid) {
				$eid = (int) ($lk['by_name'][crs_nrm(get_post_meta($pid, CRS_META_CM_EXP, true))] ?? 0);
			}
			if (!$eid) {
				continue;
			}
		}
		$groups[$eid]['eid']    = $eid;
		$groups[$eid]['pids'][] = $pid;
	}
	return array_values($groups);
}

/**
 * AUTOPRICER sincrono (catalogo piccolo / CLI): prezza TUTTO in un colpo, per espansione.
 * Per cataloghi grandi si usa la versione a blocchi in background (crs_ct_autoprice_start).
 */
function crs_ct_autoprice($opts = [])
{
	if (!crs_ct_configured()) {
		return ['ok' => false, 'err' => 'token non configurato'];
	}
	$ctx = crs_ct_autoprice_ctx();
	if (!$ctx['myuser']) {
		return ['ok' => false, 'err' => 'user id non risolto (/info fallita) — non prezzo per non escludere male le mie inserzioni'];
	}
	$counts = crs_ct_autoprice_counts();
	foreach (crs_ct_autoprice_groups() as $g) {
		$market = crs_ct_market_for($g['eid']);
		foreach ($g['pids'] as $pid) {
			crs_ct_tally($counts, crs_ct_price_from_market($pid, $market, $ctx));
		}
	}
	update_option('crs_ct_last_autoprice', ['time' => time(), 'counts' => $counts], false);
	return array_merge(['ok' => true], $counts);
}

/* ---- Autopricer a BLOCCHI in background: tutto il catalogo in una notte, un tot di gruppi-espansione
       per blocco, accodando il successivo (Action Scheduler di WooCommerce; fallback WP-Cron). ---- */

/**
 * Budget di CARTE per blocco (filtrabile): tiene il singolo processo entro i limiti di tempo,
 * a prescindere da quante carte ha un set. Almeno un gruppo intero viene sempre processato.
 */
function crs_ct_ap_card_budget()
{
	return max(1, (int) apply_filters('crs_ct_ap_card_budget', 150));
}

/**
 * Avvia l'autopricer a blocchi: fotografa i gruppi, azzera i counts, accoda il blocco 0.
 * @return int numero di gruppi; -1 se già in corso (job recente); 0 se non parte (token/user assenti o niente da fare).
 */
function crs_ct_autoprice_start()
{
	if (!crs_ct_configured() || !crs_ct_user_id()) {
		return 0; // token assente o /info fallita (myuser=0) → non prezzare (M4)
	}
	if (!crs_ct_writes_allowed()) {
		return 0;
	}
	$job = get_option('crs_ct_ap_job');
	if ($job && (time() - (int) ($job['started'] ?? 0)) < 6 * HOUR_IN_SECONDS) {
		return -1; // davvero in corso (non stantìo)
	}
	if ($job) {
		delete_option('crs_ct_ap_job'); // job vecchio bloccato (blocco morto) → lo recupero, niente stallo eterno (C3)
		delete_option('crs_ct_ap_groups');
	}
	$groups = crs_ct_autoprice_groups();
	if (!$groups) {
		return 0;
	}
	// H4: i gruppi (con tutti gli ID) stanno in un'option scritta UNA volta; il "job" tiene solo il cursore
	// leggero (offset/counts), riscritto ad ogni blocco → niente riscrittura di un blob da centinaia di KB.
	update_option('crs_ct_ap_groups', $groups, false);
	update_option('crs_ct_ap_job', [
		'total'   => count($groups),
		'offset'  => 0,
		'counts'  => crs_ct_autoprice_counts(),
		'started' => time(),
	], false);
	crs_ct_autoprice_enqueue(0);
	return count($groups);
}

/** Accoda un blocco (Action Scheduler async se c'è, altrimenti WP-Cron). */
function crs_ct_autoprice_enqueue($offset)
{
	$offset = (int) $offset;
	if (function_exists('as_enqueue_async_action')) {
		as_enqueue_async_action('crs_ct_autoprice_batch', [$offset], 'cardsrift');
	} else {
		wp_schedule_single_event(time() + 1, 'crs_ct_autoprice_batch', [$offset]);
	}
}

/** Processa un blocco (budget-carte) di gruppi-espansione e accoda il successivo, fino a fine catalogo. */
function crs_ct_do_autoprice_batch($offset = 0)
{
	// serializza i blocchi: se Action Scheduler ne lancia due in parallelo il secondo esce subito (niente 429/duplicati)
	if (!crs_lock('crs_ct_write')) {
		return;
	}
	$job = get_option('crs_ct_ap_job');
	$offset = (int) $offset;
	// no-op se il job non c'è, o se questa è un'invocazione duplicata/stantia (offset non allineato) — retry AS (C3)
	if (!$job || $offset !== (int) $job['offset']) {
		crs_unlock('crs_ct_write');
		return;
	}
	$ctx = crs_ct_autoprice_ctx();
	if (!$ctx['myuser']) {
		crs_unlock('crs_ct_write'); // token morto: non prezzare; il TTL recupera il job al prossimo start (M4)
		return;
	}
	$groups = get_option('crs_ct_ap_groups'); // immutabile: letto, mai riscritto (H4)
	if (!is_array($groups)) {
		delete_option('crs_ct_ap_job');
		delete_option('crs_ct_ap_groups'); // cleanup simmetrico
		crs_unlock('crs_ct_write');
		return;
	}

	// seleziona i gruppi del blocco per budget-CARTE (≥1 gruppo intero), così il blocco resta corto nel tempo
	$budget = crs_ct_ap_card_budget();
	$i = $offset;
	$cards = 0;
	$block = [];
	while ($i < $job['total'] && ($cards === 0 || $cards < $budget)) {
		$block[] = $groups[$i];
		$cards  += count($groups[$i]['pids']);
		$i++;
	}

	// prime le cache di tutte le carte del blocco in poche query (N+1 → ~poche) — H3
	$block_pids = [];
	foreach ($block as $g) {
		$block_pids = array_merge($block_pids, $g['pids']);
	}
	crs_ct_prime_caches($block_pids);

	foreach ($block as $g) {
		$market = crs_ct_market_for($g['eid']);
		if ($market === null) {
			// fetch fallita (rete/auth/429): NON prezzo a "no data"; il gruppo tiene il prezzo vecchio e si ritenta domani (M5)
			$job['counts']['fetch_error'] += count($g['pids']);
		} else {
			foreach ($g['pids'] as $pid) {
				crs_ct_tally($job['counts'], crs_ct_price_from_market($pid, $market, $ctx));
			}
			unset($market); // libera subito il decode del set prima del prossimo (H5: picco memoria)
		}
	}
	$job['offset'] = $i;

	if ($job['offset'] < $job['total']) {
		$job['started'] = time(); // "battito": un job che avanza non viene mai giudicato stantìo e azzerato (H-4)
		update_option('crs_ct_ap_job', $job, false); // solo il cursore leggero (H4)
		crs_ct_autoprice_enqueue($job['offset']);
	} else {
		update_option('crs_ct_last_autoprice', ['time' => time(), 'counts' => $job['counts'], 'total' => $job['total']], false);
		delete_option('crs_ct_ap_job');
		delete_option('crs_ct_ap_groups');
	}
	crs_unlock('crs_ct_write');
}
add_action('crs_ct_autoprice_batch', 'crs_ct_do_autoprice_batch', 10, 1);

/** Routine notturna del cron: pull (immagini/import/pubblica) e poi, se attivo, l'autopricer custom. */
function crs_ct_nightly()
{
	// Accodo PRIMA l'autopricer come evento SEPARATO: gira in una propria richiesta (Action Scheduler / WP-Cron),
	// così anche se il pull qui sotto va in timeout su un catalogo grande, l'autopricer parte comunque (fix H-1).
	if (get_option('crs_ct_autoprice_on')) {
		if (function_exists('as_enqueue_async_action')) {
			as_enqueue_async_action('crs_ct_nightly_autoprice', [], 'cardsrift');
		} else {
			wp_schedule_single_event(time() + 120, 'crs_ct_nightly_autoprice');
		}
	}
	// se un pull manuale tiene il lock, ritenta tra poco (senza rifare l'autopricer) invece di saltare la notte
	$r = crs_ct_pull();
	if (is_array($r) && !empty($r['busy']) && !wp_next_scheduled('crs_ct_pull_retry')) {
		wp_schedule_single_event(time() + 15 * MINUTE_IN_SECONDS, 'crs_ct_pull_retry');
	}
}

/** Retry del pull quando il lock era occupato: si ri-pianifica finché non riesce (il lock si libera presto). */
function crs_ct_do_pull_retry()
{
	$r = crs_ct_pull();
	if (is_array($r) && !empty($r['busy']) && !wp_next_scheduled('crs_ct_pull_retry')) {
		wp_schedule_single_event(time() + 15 * MINUTE_IN_SECONDS, 'crs_ct_pull_retry');
	}
}
add_action('crs_ct_pull_retry', 'crs_ct_do_pull_retry');
add_action('crs_ct_nightly_autoprice', 'crs_ct_autoprice_start');

// il cron esegue pull + (se attivo) autopricer; ri-pianifica come rete di sicurezza se l'evento manca
add_action('crs_ct_nightly_pull', 'crs_ct_nightly');
add_action('init', 'crs_ct_schedule_pull');

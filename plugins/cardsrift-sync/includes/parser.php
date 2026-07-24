<?php

/**
 * PARSER CSV Cardmarket (estensione lupzn, delimitatore ";").
 * - normalizza le magagne del formato (ID come formula ="835026", virgola decimale, BOM)
 * - valida l'header (colonne obbligatorie)
 * - AGGREGA per SKU (somma qty dei doppioni) su un file .ndjson leggibile a batch con fseek (O(n))
 */

if (!defined('ABSPATH')) {
	exit;
}

/** Ripulisce gli ID avvolti come formula Excel: ="835026" -> 835026. */
function crs_clean_id($v)
{
	return trim((string) $v, '="');
}

/**
 * Codice set (stabile tra le lingue) di una carta: ricavato dall'ImageUrl Cardmarket
 * (/{n}/{CODE}/{idProduct}/… → CODE, popolato 801/801 nel test), con fallback alle
 * colonne ExpansionCode/SetCode (spesso vuote). '' se non ricavabile.
 * Vedi docs/catalogo-import.md §6.2. È lo slug del termine pa_espansione.
 */
function crs_expansion_code($image_url, $col_code = '')
{
	if ($image_url && preg_match('~/\d+/([A-Za-z0-9]+)/\d+/~', $image_url, $m)) {
		return strtoupper($m[1]);
	}
	$c = trim((string) $col_code, "=\"");
	return $c !== '' ? strtoupper($c) : '';
}

/**
 * Converte un prezzo testuale in float, rilevando il separatore decimale reale.
 * "0,20"->0.20 · "16,00"->16.0 · "0.20"->0.20 · "1.234,56"->1234.56 · "1,234.56"->1234.56
 */
function crs_parse_price($v)
{
	$v = str_replace(' ', '', trim((string) $v));
	if ($v === '') {
		return '';
	}
	$comma = strrpos($v, ',');
	$dot   = strrpos($v, '.');
	if ($comma !== false && $dot !== false) {
		// c'è sia punto che virgola: l'ULTIMO è il separatore decimale, l'altro è migliaia
		if ($comma > $dot) {
			$v = str_replace(',', '.', str_replace('.', '', $v));
		} else {
			$v = str_replace(',', '', $v);
		}
	} elseif ($comma !== false) {
		$v = str_replace(',', '.', $v); // solo virgola = decimale
	}
	// solo punto (o nessun separatore): il punto è già decimale, lascio com'è
	return is_numeric($v) ? (float) $v : '';
}

/** Colonne che DEVONO esistere nell'header (altrimenti l'import corromperebbe il catalogo in silenzio). */
function crs_csv_required()
{
	return ['idProduct', 'Name', 'Condition', 'Price_EUR', 'Amount'];
}

/** Ritorna l'header del CSV (prima riga), gestendo il BOM. */
function crs_csv_header($path)
{
	if (!is_readable($path)) {
		return [];
	}
	$fh = fopen($path, 'r');
	if (!$fh) {
		return [];
	}
	$bom = fread($fh, 3);
	if ($bom !== "\xEF\xBB\xBF") {
		rewind($fh);
	}
	$h = fgetcsv($fh, 0, ';');
	fclose($fh);
	return is_array($h) ? array_map('trim', $h) : [];
}

/** Colonne obbligatorie mancanti nell'header (array vuoto = ok). */
function crs_csv_missing_columns($path)
{
	return array_values(array_diff(crs_csv_required(), crs_csv_header($path)));
}

/**
 * Legge il CSV grezzo, aggrega per SKU (somma qty, prezzo minimo tra i doppioni) e scrive
 * un record JSON per riga in $out. Ritorna il numero di record UNICI.
 */
function crs_aggregate_csv($csv_path, $out_path, $opts, &$stats = null)
{
	$no_lang = 0; // righe senza lingua riconosciuta
	$skipped = 0; // di quelle, quante SALTATE (non importate) perché non forziamo una lingua
	$set_stats = function () use (&$stats, &$no_lang, &$skipped) {
		$stats = ['no_lang' => $no_lang, 'skipped' => $skipped];
	};
	$fh = fopen($csv_path, 'r');
	if (!$fh) {
		$set_stats();
		return 0;
	}
	$bom = fread($fh, 3);
	if ($bom !== "\xEF\xBB\xBF") {
		rewind($fh);
	}
	$header = fgetcsv($fh, 0, ';');
	if (!$header) {
		fclose($fh);
		return 0;
	}
	$idx = array_flip(array_map('trim', $header));
	$get = function ($r, $k) use ($idx) {
		return isset($idx[$k], $r[$idx[$k]]) ? $r[$idx[$k]] : '';
	};

	$agg = [];
	while (($r = fgetcsv($fh, 0, ';')) !== false) {
		$cmid = crs_clean_id($get($r, 'idProduct'));
		$name = trim($get($r, 'Name'));
		if ($cmid === '' || $name === '') {
			continue;
		}
		$cond  = strtoupper(trim($get($r, 'Condition')));
		$price = crs_parse_price($get($r, 'Price_EUR'));
		$qty   = max(0, (int) $get($r, 'Amount'));
		// lingua per-riga dall'export (la colonna Language è popolata solo con UI EN/DE, §8.1).
		// REGOLA: NON si inventa la lingua. Se manca/non riconosciuta, la riga si SALTA — altrimenti
		// si metterebbe in vendita una carta in una lingua che non si possiede. Solo se l'operatore ha
		// scelto esplicitamente "forza lingua" ($opts['lang'] valorizzato) la applichiamo, a suo rischio.
		$lang = crs_cm_langname_to_slug($get($r, 'Language'));
		if ($lang === '') {
			$no_lang++;
			if (($opts['lang'] ?? '') === '') {
				$skipped++;
				continue; // salta: niente lingua inventata
			}
			$lang = $opts['lang']; // forzata esplicitamente dall'operatore
		}
		// foil dalla colonna ReverseHolo: Y → foil. Serve nello SKU altrimenti foil e non-foil si fondono in una
		// sola voce (qty sommata, prezzo al minimo) e il pull — che costruisce lo SKU CON foil — crea un doppione.
		$rh   = in_array(strtoupper(trim($get($r, 'ReverseHolo'))), ['Y', '1', 'TRUE', 'YES'], true);
		$foil = $rh ? (($opts['game'] ?? '') === 'pokemon' ? 'reverse-holo' : 'foil') : 'normale';
		$sku  = crs_build_sku($cmid, $cond, $lang, $foil);

		if (!isset($agg[$sku])) {
			$agg[$sku] = [
				'sku'           => $sku,
				'cardmarket_id' => $cmid,
				'article_id'    => crs_clean_id($get($r, 'ArticleID')),
				'name'          => $name,
				'expansion'      => trim($get($r, 'Expansion')),
				'expansion_code' => crs_expansion_code($get($r, 'ImageUrl'), $get($r, 'ExpansionCode') ?: $get($r, 'SetCode')),
				'condition'     => $cond,
				'lang'          => $lang,
				'foil'          => $foil,
				'rarity'        => trim($get($r, 'Rarity')), // attributo catalogo (Magic: Rare/Mythic…; Pokémon: Holo/Ultra/Secret Rare)
				'price'         => $price,
				'qty'           => $qty,
				'image_url'     => trim($get($r, 'ImageUrl')),
				'product_url'   => trim($get($r, 'ProductUrl')),
			];
		} else {
			$agg[$sku]['qty'] += $qty; // doppioni (stessa carta+condizione): sommo le quantità
			if ($price !== '' && ($agg[$sku]['price'] === '' || $price < $agg[$sku]['price'])) {
				$agg[$sku]['price'] = $price; // tengo il prezzo più basso
			}
		}
	}
	fclose($fh);
	$set_stats(); // conteggi lingua pronti (usati dall'admin per l'avviso righe saltate)

	$w = fopen($out_path, 'w');
	if (!$w) {
		return 0;
	}
	foreach ($agg as $rec) {
		fwrite($w, wp_json_encode($rec) . "\n");
	}
	fclose($w);
	return count($agg);
}

/**
 * Legge un batch di record dal file .ndjson a partire dal byte $offset (fseek → O(1) per batch).
 * @return array{0:array,1:int,2:bool} [records, nuovo_offset, eof]
 */
function crs_ndjson_batch($path, $offset, $limit)
{
	$records = [];
	$fh = fopen($path, 'r');
	if (!$fh) {
		return [[], (int) $offset, true];
	}
	if ($offset > 0) {
		fseek($fh, (int) $offset);
	}
	$eof = false;
	while (count($records) < $limit) {
		$line = fgets($fh);
		if ($line === false) {
			$eof = true;
			break;
		}
		$line = trim($line);
		if ($line === '') {
			continue;
		}
		$rec = json_decode($line, true);
		if (is_array($rec)) {
			$records[] = $rec;
		}
	}
	$new_offset = ftell($fh);
	fclose($fh);
	return [$records, $new_offset, $eof];
}

/** Chiave-variante (condizione|lingua|foil, minuscolo) di un record/prodotto: scoping della finalize "venduti". */
function crs_variant_key($cond, $lang, $foil)
{
	return strtolower(trim((string) $cond)) . '|' . strtolower(trim((string) $lang)) . '|' . strtolower(trim((string) ($foil ?: 'normale')));
}

/**
 * Presenza nell'export: ['skus'=>[sku=>true], 'tuples'=>[cond|lang|foil=>true]].
 * Le TUPLE dicono quali (condizione,lingua,foil) l'export copre davvero → la finalize azzera solo dentro
 * quelle varianti, così un export filtrato (es. solo italiano) NON tocca le altre (fix #2 export parziale).
 */
function crs_ndjson_present($path)
{
	$out = ['skus' => [], 'tuples' => []];
	$fh = fopen($path, 'r');
	if (!$fh) {
		return $out;
	}
	while (($line = fgets($fh)) !== false) {
		$line = trim($line);
		if ($line === '') {
			continue;
		}
		$rec = json_decode($line, true);
		if (isset($rec['sku'])) {
			$out['skus'][$rec['sku']] = true;
			$out['tuples'][crs_variant_key($rec['condition'] ?? '', $rec['lang'] ?? '', $rec['foil'] ?? '')] = true;
		}
	}
	fclose($fh);
	return $out;
}

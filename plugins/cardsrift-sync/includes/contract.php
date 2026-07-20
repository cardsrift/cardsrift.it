<?php

/**
 * CONTRATTO CONDIVISO — unica fonte di verità per import, tema (lettura) e sync CardTrader.
 * Se cambi SKU/meta/vocabolari, si cambia QUI e basta.
 * Doc: docs/catalogo-import.md
 */

if (!defined('ABSPATH')) {
	exit;
}

/* Meta keys sul prodotto/variazione. */
const CRS_META_CM_ID        = '_cardmarket_id';       // idProduct Cardmarket (la carta)
const CRS_META_CM_ARTICLE   = '_cardmarket_article_id'; // ArticleID Cardmarket (il listing)
const CRS_META_CM_EXP       = '_cardmarket_expansion';
const CRS_META_CM_URL       = '_cardmarket_url';
const CRS_META_CM_IMAGE     = '_cardmarket_image';    // URL immagine catalogo Cardmarket (fallback, nessun allegato)
const CRS_META_CT_BLUEPRINT = '_ct_blueprint_id';     // CardTrader blueprint (fase sync)
const CRS_META_CT_PRODUCT   = '_ct_product_id';       // CardTrader listing (fase sync)
const CRS_META_PRICE_PINNED = '_crs_price_pinned';    // il sync non tocca il prezzo se 'yes'

/* Vocabolari: schema, import e sync li leggono TUTTI da qui. */
function crs_games()
{
	return ['pokemon' => 'Pokémon', 'magic' => 'Magic', 'one-piece' => 'One Piece'];
}
function crs_types()
{
	return ['singole' => 'Singole', 'sealed' => 'Sealed', 'accessori' => 'Accessori'];
}
function crs_conditions()
{
	return ['mt' => 'MT', 'nm' => 'NM', 'ex' => 'EX', 'gd' => 'GD', 'lp' => 'LP', 'pl' => 'PL', 'po' => 'PO'];
}
function crs_languages()
{
	return ['it' => 'IT', 'en' => 'EN', 'jp' => 'JP', 'de' => 'DE', 'fr' => 'FR', 'es' => 'ES', 'kr' => 'KR'];
}
function crs_foils()
{
	return ['normale' => 'Normale', 'foil' => 'Foil', 'reverse-holo' => 'Reverse Holo'];
}

/** SKU stabile e chiave di upsert: CM-{idProduct}-{COND}[-LANG][-FOIL]. */
function crs_build_sku($cm_id, $condition, $lang = '', $foil = '')
{
	$parts = ['CM', $cm_id, strtoupper($condition ?: 'NA')];
	if ($lang) {
		$parts[] = strtoupper($lang);
	}
	if ($foil && $foil !== 'normale') {
		$parts[] = strtoupper($foil);
	}
	return implode('-', $parts);
}

/** Mapping condizione Cardmarket (7 gradi) -> CardTrader. Usato dal sync (fase 2). */
function crs_condition_to_ct($cm)
{
	$map = ['MT' => 'Mint', 'NM' => 'Near Mint', 'EX' => 'Slightly Played', 'GD' => 'Moderately Played', 'LP' => 'Played', 'PL' => 'Heavily Played', 'PO' => 'Poor'];
	return $map[strtoupper($cm)] ?? 'Near Mint';
}

/** Mapping idLanguage Cardmarket -> codice lingua CardTrader. Usato dal sync (fase 2). */
function crs_cm_lang_to_ct($id_language)
{
	$map = [1 => 'en', 2 => 'fr', 3 => 'de', 4 => 'es', 5 => 'it', 6 => 'zh-CN', 7 => 'jp', 8 => 'pt', 9 => 'ru', 10 => 'kr', 11 => 'zh-TW'];
	return $map[(int) $id_language] ?? 'en';
}

/**
 * Nome lingua testuale dell'export Cardmarket (colonna "Language", popolata con UI EN o DE) -> nostro slug.
 * Ritorna '' se vuoto o non riconosciuto (cinese/portoghese/russo non hanno slug nel vocabolario attuale):
 * chi chiama fa fallback alla lingua di default del form. Vedi docs/catalogo-import.md §8.1.
 */
function crs_cm_langname_to_slug($name)
{
	$n = strtolower(trim((string) $name));
	if ($n === '') {
		return '';
	}
	$map = [
		// UI inglese
		'english' => 'en', 'french' => 'fr', 'german' => 'de', 'spanish' => 'es',
		'italian' => 'it', 'japanese' => 'jp', 'korean' => 'kr',
		// UI tedesca
		'englisch' => 'en', 'französisch' => 'fr', 'franzosisch' => 'fr', 'deutsch' => 'de',
		'spanisch' => 'es', 'italienisch' => 'it', 'japanisch' => 'jp', 'koreanisch' => 'kr',
	];
	return $map[$n] ?? '';
}

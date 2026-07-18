<?php

/**
 * ADMIN — pagina "Import Cardmarket": upload CSV → aggregazione per SKU → import a batch (AJAX,
 * offset lato server, ripartibile) con barra di avanzamento. (La pagina CardTrader arriverà col sync.)
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('admin_menu', function () {
	add_menu_page('CardsRift Sync', 'CardsRift Sync', 'manage_woocommerce', 'cardsrift-sync', 'crs_page_import', 'dashicons-update', 56);
	add_submenu_page('cardsrift-sync', 'Import Cardmarket', 'Import Cardmarket', 'manage_woocommerce', 'cardsrift-sync', 'crs_page_import');
	add_submenu_page('cardsrift-sync', 'CardTrader', 'CardTrader (in arrivo)', 'manage_woocommerce', 'cardsrift-ct', 'crs_page_ct');
});

/** Cartella uploads dedicata agli import. */
function crs_upload_dir()
{
	$u = wp_upload_dir();
	$dir = $u['basedir'] . '/cardsrift-sync';
	if (!file_exists($dir)) {
		wp_mkdir_p($dir);
	}
	return $dir;
}

/** Pagina placeholder CardTrader (fase sync). */
function crs_page_ct()
{
	echo '<div class="wrap"><h1>CardTrader</h1><p>Sync con CardTrader in arrivo: price-sync (autopricer → sito) e stock a due vie. Vedi <code>docs/catalogo-import.md</code>.</p></div>';
}

/** Pagina Import: form → upload → runner a batch. */
function crs_page_import()
{
	if (!current_user_can('manage_woocommerce')) {
		return;
	}
	crs_gc_jobs(); // pulizia residui di import vecchi

	echo '<div class="wrap"><h1>Import Cardmarket → WooCommerce</h1>';

	if (!function_exists('wc_get_product')) {
		echo '<div class="notice notice-error"><p>WooCommerce non attivo.</p></div></div>';
		return;
	}
	if ((int) get_option('crs_schema_ver') < CRS_SCHEMA_VER) {
		echo '<div class="notice notice-warning"><p>Schema catalogo non ancora creato: riattiva il plugin.</p></div>';
	}

	if (!empty($_POST['crs_upload']) && check_admin_referer('crs_upload')) {
		crs_handle_upload();
		echo '</div>';
		return;
	}

	$games = crs_games();
	$langs = crs_languages();
	?>
	<form method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('crs_upload'); ?>
		<table class="form-table">
			<tr><th scope="row">File CSV Cardmarket</th><td><input type="file" name="crs_file" accept=".csv" required></td></tr>
			<tr><th scope="row">Gioco</th><td>
				<?php foreach ($games as $slug => $name) : ?>
					<label style="margin-right:14px"><input type="radio" name="crs_game" value="<?php echo esc_attr($slug); ?>" <?php checked($slug, 'magic'); ?>> <?php echo esc_html($name); ?></label>
				<?php endforeach; ?>
			</td></tr>
			<tr><th scope="row">Tipo</th><td>
				<label style="margin-right:14px"><input type="radio" name="crs_tipo" value="singole" checked> Singole</label>
				<label style="margin-right:14px"><input type="radio" name="crs_tipo" value="sealed"> Sealed</label>
				<label><input type="radio" name="crs_tipo" value="accessori"> Accessori</label>
			</td></tr>
			<tr><th scope="row">Lingua predefinita</th><td>
				<select name="crs_lang"><?php foreach ($langs as $slug => $name) {
					echo '<option value="' . esc_attr($slug) . '">' . esc_html($name) . '</option>';
				} ?></select>
				<p class="description">Il CSV non porta la lingua: applicata a tutte le righe (correggibile a mano).</p>
			</td></tr>
			<tr><th scope="row">Modalità</th><td>
				<label style="margin-right:14px"><input type="radio" name="crs_mode" value="full" checked> Full — sovrascrive prezzo/stock <strong>e azzera le carte non presenti nel file</strong></label>
				<label><input type="radio" name="crs_mode" value="add"> Additivo — aggiunge solo i nuovi</label>
				<p class="description">⚠️ In Full, il file deve rappresentare lo stock <em>completo</em> di questo gioco+tipo: le carte assenti vengono messe a zero (venduto).</p>
			</td></tr>
			<tr><th scope="row">Opzioni</th><td>
				<label style="display:block;margin-bottom:4px"><input type="checkbox" name="crs_images" value="1"> Scarica le immagini in libreria (opzionale e più lento; di default si usa l'immagine Cardmarket via URL, senza allegati)</label>
				<label><input type="checkbox" name="crs_dry" value="1"> Dry-run (anteprima, non scrive)</label>
			</td></tr>
		</table>
		<p><button class="button button-primary" name="crs_upload" value="1">Carica e prepara</button></p>
	</form>
	<?php
	echo '</div>';
}

/** Valida header, aggrega per SKU e mostra il runner. */
function crs_handle_upload()
{
	if (empty($_FILES['crs_file']['tmp_name'])) {
		echo '<div class="notice notice-error"><p>Nessun file.</p></div>';
		return;
	}
	$token = wp_generate_password(8, false);
	$raw = crs_upload_dir() . '/' . $token . '.csv';
	if (!move_uploaded_file($_FILES['crs_file']['tmp_name'], $raw)) {
		echo '<div class="notice notice-error"><p>Upload fallito.</p></div>';
		return;
	}

	$missing = crs_csv_missing_columns($raw);
	if ($missing) {
		@unlink($raw);
		echo '<div class="notice notice-error"><p>Colonne obbligatorie mancanti nel CSV: <code>' . esc_html(implode(', ', $missing)) . '</code>. Ri-esporta con l\'estensione (formato atteso: colonne Cardmarket standard).</p></div>';
		return;
	}

	$opts = [
		'game'   => sanitize_key($_POST['crs_game'] ?? 'magic'),
		'tipo'   => sanitize_key($_POST['crs_tipo'] ?? 'singole'),
		'lang'   => sanitize_key($_POST['crs_lang'] ?? 'it'),
		'mode'   => (($_POST['crs_mode'] ?? 'full') === 'add') ? 'add' : 'full',
		'images' => !empty($_POST['crs_images']),
		'dry'    => !empty($_POST['crs_dry']),
	];

	// aggregazione per SKU (somma qty dei doppioni) → file .ndjson leggibile a batch
	$ndjson = crs_upload_dir() . '/' . $token . '.ndjson';
	$total = crs_aggregate_csv($raw, $ndjson, $opts);
	@unlink($raw); // il grezzo non serve più

	if ($total < 1) {
		@unlink($ndjson);
		echo '<div class="notice notice-error"><p>Nessuna riga valida nel CSV.</p></div>';
		return;
	}

	$job = array_merge($opts, [
		'path'   => $ndjson,
		'total'  => $total,
		'offset' => 0,
		'done'   => 0,
		'counts' => ['created' => 0, 'updated' => 0, 'skipped' => 0, 'error' => 0],
	]);
	update_option('crs_job_' . $token, $job, false);
	crs_render_runner($token, $job);
}

/** UI di avanzamento + JS che chiama l'AJAX a batch (l'offset lo tiene il server). */
function crs_render_runner($token, $job)
{
	$nonce = wp_create_nonce('crs_batch');
	?>
	<h2>Import pronto</h2>
	<p><strong><?php echo (int) $job['total']; ?></strong> carte uniche · gioco <code><?php echo esc_html($job['game']); ?></code> · tipo <code><?php echo esc_html($job['tipo']); ?></code> · modalità <code><?php echo esc_html($job['mode']); ?></code><?php echo $job['dry'] ? ' · <strong>DRY-RUN</strong>' : ''; ?></p>
	<div style="max-width:560px">
		<div style="background:#dcdcde;border-radius:6px;overflow:hidden;height:22px"><div id="crs-bar" style="background:#2271b1;height:100%;width:0"></div></div>
		<p id="crs-status">Pronto.</p>
		<pre id="crs-log" style="background:#1d2327;color:#7fd77f;padding:10px;max-height:220px;overflow:auto;display:none;white-space:pre-wrap"></pre>
	</div>
	<p><button class="button button-primary" id="crs-start">Avvia import</button></p>
	<script>
	(function () {
		var token = <?php echo wp_json_encode($token); ?>, nonce = <?php echo wp_json_encode($nonce); ?>, total = <?php echo (int) $job['total']; ?>;
		var bar = document.getElementById('crs-bar'), st = document.getElementById('crs-status'), log = document.getElementById('crs-log');
		function line(t) { log.style.display = 'block'; log.textContent += t + "\n"; log.scrollTop = log.scrollHeight; }
		function batch() {
			fetch(ajaxurl, { method: 'POST', body: new URLSearchParams({ action: 'crs_batch', token: token, nonce: nonce }) })
				.then(function (r) { return r.json(); }).then(function (j) {
					if (!j.success) { st.textContent = 'Errore: ' + (j.data || '?'); return; }
					var d = j.data, c = d.counts;
					var pct = total ? Math.min(100, Math.round(d.processed / total * 100)) : 100;
					bar.style.width = pct + '%';
					st.textContent = pct + '% · creati ' + c.created + ' · aggiornati ' + c.updated + ' · saltati ' + c.skipped + ' · errori ' + c.error;
					(d.errors || []).forEach(line);
					if (d.done) { st.textContent = '✔ Fatto — ' + st.textContent + (d.zeroed ? (' · azzerati (venduti): ' + d.zeroed) : ''); }
					else { batch(); }
				}).catch(function (e) { st.textContent = 'Errore rete: ' + e; });
		}
		document.getElementById('crs-start').addEventListener('click', function () { this.disabled = true; batch(); });
	})();
	</script>
	<?php
}

/** Endpoint AJAX: processa un batch di record (offset lato server, ripartibile). */
add_action('wp_ajax_crs_batch', function () {
	if (!current_user_can('manage_woocommerce') || !check_ajax_referer('crs_batch', 'nonce', false)) {
		wp_send_json_error('auth', 403);
	}
	$token = sanitize_text_field($_POST['token'] ?? '');
	$job = get_option('crs_job_' . $token);
	if (!$job) {
		wp_send_json_error('job non trovato (forse già completato)');
	}

	$limit = !empty($job['images']) ? 8 : 40; // batch più piccolo se scarica immagini in libreria
	list($records, $new_offset, $eof) = crs_ndjson_batch($job['path'], (int) $job['offset'], $limit);

	$opts = [
		'game'    => $job['game'],
		'tipo'    => $job['tipo'],
		'lang'    => $job['lang'],
		'mode'    => $job['mode'],
		'images'  => $job['images'],
		'dry_run' => $job['dry'],
	];

	$errors = [];
	foreach ($records as $rec) {
		$res = crs_import_row($rec, $opts);
		$s = isset($job['counts'][$res['status']]) ? $res['status'] : 'error';
		$job['counts'][$s]++;
		if ($s === 'error') {
			$errors[] = ($res['sku'] ?: '?') . ': ' . ($res['msg'] ?? 'errore');
		}
	}
	$job['offset'] = $new_offset;
	$job['done']  += count($records);

	$done = $eof || empty($records);
	$zeroed = 0;
	if ($done) {
		if (!$job['dry']) {
			$zeroed = crs_finalize_sold($job, crs_ndjson_skus($job['path']));
		}
		@unlink($job['path']);
		delete_option('crs_job_' . $token);
	} else {
		update_option('crs_job_' . $token, $job, false);
	}

	wp_send_json_success([
		'counts'    => $job['counts'],
		'processed' => $job['done'],
		'total'     => (int) $job['total'],
		'errors'    => $errors,
		'done'      => $done,
		'zeroed'    => $zeroed,
	]);
});

/** Garbage collection: CSV/ndjson vecchi, residui cache immagini, option di lavoro orfane. */
function crs_gc_jobs()
{
	$dir = crs_upload_dir();
	foreach (array_merge(glob($dir . '/*.csv') ?: [], glob($dir . '/*.ndjson') ?: []) as $f) {
		if (filemtime($f) < time() - DAY_IN_SECONDS) {
			@unlink($f);
		}
	}
	$u = wp_upload_dir();
	$img = $u['basedir'] . '/cardsrift-sync/img-cache';
	foreach (array_merge(glob($img . '/*.tmp') ?: [], glob($img . '/*.lock') ?: [], glob($img . '/*.fail') ?: []) as $f) {
		if (filemtime($f) < time() - HOUR_IN_SECONDS) {
			@unlink($f);
		}
	}
	global $wpdb;
	$keys = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'crs\\_job\\_%'");
	foreach ((array) $keys as $k) {
		$job = get_option($k);
		if (!$job || empty($job['path']) || !file_exists($job['path'])) {
			delete_option($k);
		}
	}
}

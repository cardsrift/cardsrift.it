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
	add_submenu_page('cardsrift-sync', 'CardTrader', 'CardTrader', 'manage_woocommerce', 'cardsrift-ct', 'crs_page_ct');
});

/** Cartella uploads dedicata agli import. */
function crs_upload_dir()
{
	$u = wp_upload_dir();
	$dir = $u['basedir'] . '/cardsrift-sync';
	if (!file_exists($dir)) {
		wp_mkdir_p($dir);
		// blinda la cartella: niente listing né accesso HTTP diretto ai CSV/ndjson (dati catalogo) — L3
		@file_put_contents($dir . '/index.html', '');
		@file_put_contents($dir . '/.htaccess', "Require all denied\n");
	}
	return $dir;
}

/**
 * Pagina CardTrader — Connessione: salva il token API, testa la connessione (/info) e mostra
 * un'anteprima delle inserzioni già mappate sui nostri vocabolari. L'import vero e proprio arriva
 * in fase B (riuso del batch runner + crs_import_row).
 */
function crs_page_ct()
{
	if (!current_user_can('manage_woocommerce')) {
		return;
	}

	// salvataggio token (nel DB, mai nel codice)
	if (!empty($_POST['crs_ct_save']) && check_admin_referer('crs_ct_token')) {
		// solo ASCII stampabile: strippa spazi/CR/LF/controlli → niente header-injection nel Bearer (L1)
		update_option(CRS_CT_TOKEN_OPT, preg_replace('/[^\x21-\x7E]/', '', (string) ($_POST['crs_ct_token'] ?? '')), false);
		delete_transient('crs_ct_exp_v2');
		echo '<div class="notice notice-success is-dismissible"><p>Token salvato.</p></div>';
	}

	// salvataggio impostazioni autopricer custom
	if (!empty($_POST['crs_ct_savecfg']) && check_admin_referer('crs_ct_cfg')) {
		update_option('crs_ct_autoprice_on', !empty($_POST['crs_ct_ap_on']) ? 1 : 0, false);
		$fl = (float) str_replace(',', '.', (string) ($_POST['crs_ct_floor'] ?? '0.20'));
		update_option('crs_ct_floor', max(0, $fl), false);
		$mk = (float) str_replace(',', '.', (string) ($_POST['crs_ct_markup'] ?? '5'));
		update_option('crs_ct_markup', max(0, $mk), false);
		echo '<div class="notice notice-success is-dismissible"><p>Impostazioni autopricer salvate.</p></div>';
	}

	$has   = crs_ct_configured();
	$tok   = crs_ct_token();
	$mask  = $has ? str_repeat('•', max(0, strlen($tok) - 4)) . substr($tok, -4) : '';
	$nonce = wp_create_nonce('crs_ct_ajax');
	?>
	<div class="wrap">
		<h1>CardTrader — Connessione</h1>
		<p>Import delle <strong>tue inserzioni</strong> CardTrader (stock, prezzo, condizione, foil, immagine) come prodotti WooCommerce. Prima serve il token API.</p>

		<h2>1 · Token API</h2>
		<p class="description">Lo trovi nei <em>settings</em> del tuo profilo CardTrader (serve un account con accesso API). Resta salvato nel database del sito, mai nel codice.</p>
		<form method="post">
			<?php wp_nonce_field('crs_ct_token'); ?>
			<table class="form-table"><tr>
				<th scope="row">Bearer token</th>
				<td>
					<input type="password" name="crs_ct_token" value="" autocomplete="off" style="width:420px" placeholder="<?php echo $has ? esc_attr('configurato: ' . $mask) : esc_attr('incolla qui il token'); ?>">
					<p><button class="button button-primary" name="crs_ct_save" value="1">Salva token</button></p>
				</td>
			</tr></table>
		</form>

		<?php if ($has) : ?>
			<h2>2 · Test e anteprima</h2>
			<p>
				<button class="button" id="crs-ct-test">Test connessione</button>
				<button class="button" id="crs-ct-preview">Anteprima inserzioni</button>
			</p>
			<pre id="crs-ct-out" style="background:#1d2327;color:#7fd77f;padding:10px;max-height:360px;overflow:auto;display:none;white-space:pre-wrap"></pre>
			<div id="crs-ct-table"></div>
			<script>
			(function () {
				var nonce = <?php echo wp_json_encode($nonce); ?>;
				var out = document.getElementById('crs-ct-out'), tbl = document.getElementById('crs-ct-table');
				function show(t) { out.style.display = 'block'; out.textContent = t; }
				function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
				function call(action, cb) {
					fetch(ajaxurl, { method: 'POST', body: new URLSearchParams({ action: action, nonce: nonce }) })
						.then(function (r) { return r.json(); })
						.then(function (j) { if (!j.success) { tbl.innerHTML = ''; show('Errore: ' + (j.data || '?')); return; } cb(j.data); })
						.catch(function (e) { show('Errore rete: ' + e); });
				}
				document.getElementById('crs-ct-test').addEventListener('click', function () {
					tbl.innerHTML = ''; show('Connessione…');
					call('crs_ct_test', function (d) { show('✔ Connesso\n' + JSON.stringify(d, null, 2)); });
				});
				document.getElementById('crs-ct-preview').addEventListener('click', function () {
					out.style.display = 'none'; tbl.innerHTML = 'Carico…';
					call('crs_ct_preview', function (d) {
						var h = '<p><strong>' + d.total + '</strong> inserzioni totali · anteprima prime ' + d.rows.length + ' (immagine CDN + card_market_id dal blueprint):</p>';
						h += '<table class="widefat striped"><thead><tr><th>Img</th><th>Nome</th><th>Espansione</th><th>Cond.</th><th>Lingua</th><th>Foil</th><th>Qty</th><th>€</th><th>CM id</th></tr></thead><tbody>';
						d.rows.forEach(function (r) {
							var img = r.image_url ? '<img src="' + esc(r.image_url) + '" alt="" style="width:44px;height:auto;border-radius:3px" loading="lazy">' : '<span style="color:#a00">—</span>';
							var cm = r.cardmarket_id ? '<code>' + esc(r.cardmarket_id) + '</code>' : '<span style="color:#a00">—</span>';
							h += '<tr><td>' + img + '</td><td>' + esc(r.name) + '</td><td>' + esc(r.expansion) + ' <code>' + esc(r.expansion_code) + '</code></td><td>' + esc(r.condition) + '</td><td>' + esc(r.lang) + '</td><td>' + esc(r.foil) + '</td><td>' + esc(r.qty) + '</td><td>' + esc(r.price) + '</td><td>' + cm + '</td></tr>';
						});
						h += '</tbody></table><p class="description">Se una miniatura o un CM id è <span style="color:#a00">—</span>, il blueprint non l\'ha esposto: lo verifichiamo caso per caso.</p>';
						tbl.innerHTML = h;
					});
				});
			})();
			</script>

			<h2>3 · Aggancio blueprint (matching)</h2>
			<p class="description">Risolve il <code>blueprint_id</code> CardTrader di ogni prodotto importato da Cardmarket — è il perno del push. <strong>Dry-run</strong> = solo report copertura; <strong>Aggancia e salva</strong> memorizza <code>_ct_blueprint_id</code> sui prodotti (scrittura solo locale).</p>
			<p>
				<button class="button" id="crs-mt-dry">Report copertura (dry-run)</button>
				<button class="button button-primary" id="crs-mt-save">Aggancia e salva</button>
			</p>
			<div style="max-width:560px"><div style="background:#dcdcde;border-radius:6px;overflow:hidden;height:20px"><div id="crs-mt-bar" style="background:#2271b1;height:100%;width:0"></div></div><p id="crs-mt-status">—</p></div>
			<div id="crs-mt-out"></div>
			<script>
			(function () {
				var nonce = <?php echo wp_json_encode($nonce); ?>;
				var bar = document.getElementById('crs-mt-bar'), st = document.getElementById('crs-mt-status'), out = document.getElementById('crs-mt-out');
				function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
				function post(action, body) { return fetch(ajaxurl, { method: 'POST', body: new URLSearchParams(Object.assign({ action: action, nonce: nonce }, body || {})) }).then(function (r) { return r.json(); }); }
				function run(token, total) {
					post('crs_ct_match_run', { token: token }).then(function (j) {
						if (!j.success) { st.textContent = 'Errore: ' + (j.data || '?'); return; }
						var d = j.data, pct = total ? Math.round(d.processed / total * 100) : 100, parts = [];
						bar.style.width = pct + '%';
						Object.keys(d.counts).forEach(function (k) { parts.push(k + ' ' + d.counts[k]); });
						st.textContent = pct + '% · ' + parts.join(' · ');
						if (!d.done) { run(token, total); return; }
						var cov = total ? Math.round(d.matched / total * 100) : 0;
						var h = '<p><strong>Copertura: ' + d.matched + '/' + total + ' (' + cov + '%)</strong></p>';
						if (d.unmatched.length) {
							h += '<p>Non agganciati (primi ' + d.unmatched.length + '):</p><table class="widefat striped"><thead><tr><th>ID</th><th>Nome</th><th>Motivo</th></tr></thead><tbody>';
							d.unmatched.forEach(function (u) { h += '<tr><td>' + u.id + '</td><td>' + esc(u.name) + '</td><td><code>' + esc(u.method) + '</code></td></tr>'; });
							h += '</tbody></table>';
						}
						out.innerHTML = h;
					}).catch(function (e) { st.textContent = 'Errore rete: ' + e; });
				}
				function start(persist) {
					out.innerHTML = ''; bar.style.width = '0'; st.textContent = 'Avvio…';
					post('crs_ct_match_start', { persist: persist ? '1' : '' }).then(function (j) {
						if (!j.success) { st.textContent = 'Errore: ' + (j.data || '?'); return; }
						if (!j.data.total) { st.textContent = 'Nessun prodotto da agganciare.'; return; }
						run(j.data.token, j.data.total);
					});
				}
				document.getElementById('crs-mt-dry').addEventListener('click', function () { start(false); });
				document.getElementById('crs-mt-save').addEventListener('click', function () { start(true); });
			})();
			</script>

			<?php $next = wp_next_scheduled('crs_ct_nightly_pull'); ?>
			<h2>4 · Pull prezzi &amp; immagini (CardTrader → sito)</h2>
			<p class="description">Riprende da CardTrader il <strong>prezzo</strong> (autopricer) e l'<strong>immagine</strong> di catalogo e li scrive sul sito. Rispetta i prezzi fissati a mano (<code>_crs_price_pinned</code>) e <strong>pubblica i prodotti in bozza</strong> appena hanno un prezzo. Automatico ogni notte alle <strong>02:00</strong>.</p>
			<p><button class="button button-primary" id="crs-pull-now">Esegui pull ora</button> <span id="crs-pull-status"></span></p>
			<p class="description">Prossima esecuzione automatica: <code><?php echo esc_html($next ? wp_date('d/m/Y H:i', $next) : 'non pianificata'); ?></code>. ⚠️ Su hosting a basso traffico (Aruba) WP-Cron può non scattare puntuale: imposta un cron di sistema che chiami <code><?php echo esc_url(site_url('wp-cron.php?doing_wp_cron')); ?></code> attorno alle 02:00.</p>
			<script>
			(function () {
				var nonce = <?php echo wp_json_encode($nonce); ?>;
				document.getElementById('crs-pull-now').addEventListener('click', function () {
					var s = document.getElementById('crs-pull-status');
					s.textContent = ' esecuzione…';
					fetch(ajaxurl, { method: 'POST', body: new URLSearchParams({ action: 'crs_ct_pull_now', nonce: nonce }) })
						.then(function (r) { return r.json(); })
						.then(function (j) { s.textContent = j.success ? (' ✔ ' + JSON.stringify(j.data)) : (' errore: ' + (j.data || '?')); })
						.catch(function (e) { s.textContent = ' errore rete: ' + e; });
				});
			})();
			</script>

			<?php $lp = get_option('crs_ct_last_push'); ?>
			<h2>5 · Push su CardTrader</h2>
			<p class="description">Il push aggancia i blueprint e riconcilia CardTrader: crea le nuove inserzioni pubbliche, aggiorna le quantità, rimuove i venduti. Parte a fine import <strong>solo se spunti l'opzione</strong> nel form (default: no) — così l'import resta locale finché non decidi di pubblicare. Qui puoi comunque lanciarlo a mano.</p>
			<p>
				<button class="button" id="crs-push-now">Push ora</button>
				<span id="crs-push-status"><?php
					if (is_array($lp)) {
						$c = $lp['counts'];
						printf(
							'Ultimo push: <code>%s</code> — creati %d · aggiornati %d · eliminati %d · errori %d',
							esc_html(wp_date('d/m/Y H:i', $lp['time'])),
							(int) ($c['created'] ?? 0),
							(int) ($c['updated'] ?? 0),
							(int) ($c['deleted'] ?? 0),
							(int) ($c['errors'] ?? 0)
						);
					} else {
						echo 'Nessun push ancora eseguito.';
					}
				?></span>
			</p>
			<script>
			(function () {
				var nonce = <?php echo wp_json_encode($nonce); ?>;
				var s = document.getElementById('crs-push-status');
				function fmt(c) { return 'creati ' + (c.created || 0) + ' · aggiornati ' + (c.updated || 0) + ' · eliminati ' + (c.deleted || 0) + ' · saltati ' + (c.skipped || 0) + ' · errori ' + (c.errors || 0); }
				function poll() {
					fetch(ajaxurl, { method: 'POST', body: new URLSearchParams({ action: 'crs_ct_push_progress', nonce: nonce }) })
						.then(function (r) { return r.json(); })
						.then(function (j) {
							if (!j.success) { s.textContent = ' errore'; return; }
							var d = j.data;
							if (d.running) { s.textContent = ' in corso… ' + d.offset + '/' + d.total + ' — ' + fmt(d.counts); setTimeout(poll, 3000); }
							else { s.textContent = ' ✔ fatto — ' + (d.counts ? fmt(d.counts) : ''); }
						})
						.catch(function (e) { s.textContent = ' errore rete: ' + e; });
				}
				document.getElementById('crs-push-now').addEventListener('click', function () {
					s.textContent = ' avvio…';
					fetch(ajaxurl, { method: 'POST', body: new URLSearchParams({ action: 'crs_ct_push_now', nonce: nonce }) })
						.then(function (r) { return r.json(); })
						.then(function (j) {
							if (!j.success) { s.textContent = ' errore: ' + (j.data || '?'); return; }
							if (!j.data.started) { s.textContent = ' ' + (j.data.msg || 'già in corso') + ' — controllo…'; poll(); return; }
							s.textContent = ' avviato: ' + j.data.total + ' prodotti in coda…'; setTimeout(poll, 2000);
						})
						.catch(function (e) { s.textContent = ' errore rete: ' + e; });
				});
			})();
			</script>

			<?php $orph = crs_ct_orphan_drafts(); ?>
			<h2>6 · Da rivedere</h2>
			<p class="description">Prodotti che il flusso <strong>non gestisce in automatico</strong> — non agganciati a un blueprint, o non prezzabili (nessun dato di mercato / gioco non mappato). Non spariscono in silenzio: qui decidi cosa farne (aggancia, prezza a mano, pubblica).</p>
			<?php if (empty($orph['rows'])) : ?>
				<p><strong>Niente da rivedere</strong> — tutto agganciato e prezzabile. 👍</p>
			<?php else : ?>
				<p><strong><?php echo (int) $orph['total']; ?></strong> prodotti da rivedere<?php echo $orph['total'] >= 200 ? ' (mostro i primi 200)' : ''; ?>:</p>
				<table class="widefat striped">
					<thead><tr><th>Prodotto</th><th>Espansione</th><th>Stato WC</th><th>Stock</th><th>Prezzo</th><th>Problema</th><th></th></tr></thead>
					<tbody>
					<?php
					$labels = [
						'no-blueprint' => '<span style="color:#a00">Nessun blueprint</span> — aggancia a mano o pubblica col prezzo',
					];
					foreach ($orph['rows'] as $r) : ?>
						<tr>
							<td><strong><?php echo esc_html($r['name']); ?></strong></td>
							<td><?php echo esc_html($r['expansion']); ?></td>
							<td><?php echo $r['status'] === 'draft' ? '<em>bozza</em>' : 'pubblicato'; ?></td>
							<td><?php echo (int) $r['qty']; ?></td>
							<td><?php echo esc_html($r['price']); ?> €</td>
							<td><?php echo wp_kses_post($labels[$r['stato']] ?? '<span style="color:#996800">' . esc_html($r['stato']) . '</span>'); ?></td>
							<td><?php if ($r['edit']) : ?><a href="<?php echo esc_url($r['edit']); ?>">Modifica</a><?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php
			$ap_on  = (bool) get_option('crs_ct_autoprice_on');
			$floor  = (float) get_option('crs_ct_floor', 0.20);
			$markup = (float) get_option('crs_ct_markup', 5);
			$la     = get_option('crs_ct_last_autoprice');
			?>
			<h2>7 · Autopricer custom (per condizione + lingua)</h2>
			<p class="description">Calcola il prezzo dal <strong>mercato CardTrader</strong> della stessa condizione+lingua+foil — il <strong>più basso legittimo + un ricarico</strong>, escludendo i prezzi-civetta con analisi statistica (mediana + MAD) sull'intero mercato della carta, oltre alle tue inserzioni e ai venditori in vacanza — con un prezzo minimo. Lo scrive sul sito <em>e</em> su CardTrader. Se attivo, il pull non prende più il prezzo da CardTrader: <strong>spegni l'autopricer di CardTrader dal tuo profilo</strong> per non litigare.</p>
			<form method="post">
				<?php wp_nonce_field('crs_ct_cfg'); ?>
				<p><label><input type="checkbox" name="crs_ct_ap_on" value="1" <?php checked($ap_on); ?>> <strong>Attiva l'autopricer custom</strong> (sostituisce quello di CardTrader)</label></p>
				<p>
					Ricarico sul più basso: <input type="text" name="crs_ct_markup" value="<?php echo esc_attr(rtrim(rtrim(number_format($markup, 2, '.', ''), '0'), '.')); ?>" size="4"> % &nbsp;
					Prezzo minimo (floor): <input type="text" name="crs_ct_floor" value="<?php echo esc_attr(number_format($floor, 2, '.', '')); ?>" size="6"> € &nbsp;
					<button class="button" name="crs_ct_savecfg" value="1">Salva impostazioni</button>
				</p>
			</form>
			<p>
				<button class="button button-primary" id="crs-ap-now">Autoprezza ora</button>
				<span id="crs-ap-status"><?php
					if (is_array($la)) {
						$c = $la['counts'];
						printf(
							'Ultimo: <code>%s</code> — prezzate %d · invariate %d · al minimo %d · <strong>no dato %d</strong> · <strong>gioco n/s %d</strong> · <strong>errori fetch %d</strong> · pinned %d · errori %d',
							esc_html(wp_date('d/m/Y H:i', $la['time'])),
							(int) ($c['priced'] ?? 0),
							(int) ($c['unchanged'] ?? 0),
							(int) ($c['floored'] ?? 0),
							(int) ($c['skipped_nodata'] ?? 0),
							(int) ($c['unsupported_game'] ?? 0),
							(int) ($c['fetch_error'] ?? 0),
							(int) ($c['skipped_pinned'] ?? 0),
							(int) ($c['errors'] ?? 0)
						);
					} else {
						echo 'Mai eseguito.';
					}
				?></span>
			</p>
			<script>
			(function () {
				var nonce = <?php echo wp_json_encode($nonce); ?>;
				var s = document.getElementById('crs-ap-status');
				function fmt(c) { return 'prezzate ' + (c.priced || 0) + ' · invariate ' + (c.unchanged || 0) + ' · al minimo ' + (c.floored || 0) + ' · no dato ' + (c.skipped_nodata || 0) + ' · gioco n/s ' + (c.unsupported_game || 0) + ' · errori fetch ' + (c.fetch_error || 0) + ' · pinned ' + (c.skipped_pinned || 0) + ' · errori ' + (c.errors || 0); }
				function poll() {
					fetch(ajaxurl, { method: 'POST', body: new URLSearchParams({ action: 'crs_ct_autoprice_progress', nonce: nonce }) })
						.then(function (r) { return r.json(); })
						.then(function (j) {
							if (!j.success) { s.textContent = ' errore'; return; }
							var d = j.data;
							if (d.running) { s.textContent = ' in corso… ' + d.offset + '/' + d.total + ' set — ' + fmt(d.counts); setTimeout(poll, 3000); }
							else { s.textContent = ' ✔ fatto — ' + (d.counts ? fmt(d.counts) : ''); }
						})
						.catch(function (e) { s.textContent = ' errore rete: ' + e; });
				}
				document.getElementById('crs-ap-now').addEventListener('click', function () {
					s.textContent = ' avvio…';
					fetch(ajaxurl, { method: 'POST', body: new URLSearchParams({ action: 'crs_ct_autoprice_now', nonce: nonce }) })
						.then(function (r) { return r.json(); })
						.then(function (j) {
							if (!j.success) { s.textContent = ' errore: ' + (j.data || '?'); return; }
							if (!j.data.started) { s.textContent = ' ' + (j.data.msg || 'già in corso') + ' — controllo…'; poll(); return; }
							s.textContent = ' avviato: ' + j.data.total + ' set in coda…'; setTimeout(poll, 2000);
						})
						.catch(function (e) { s.textContent = ' errore rete: ' + e; });
				});
			})();
			</script>
		<?php else : ?>
			<p><em>Salva un token per abilitare test e anteprima.</em></p>
		<?php endif; ?>
	</div>
	<?php
}

/** AJAX: test connessione (GET /info). */
add_action('wp_ajax_crs_ct_test', function () {
	if (!current_user_can('manage_woocommerce') || !check_ajax_referer('crs_ct_ajax', 'nonce', false)) {
		wp_send_json_error('auth', 403);
	}
	list($ok, $data, $err) = crs_ct_info();
	if (!$ok) {
		wp_send_json_error($err);
	}
	wp_send_json_success($data);
});

/** AJAX: anteprima delle prime inserzioni, già normalizzate sui nostri vocabolari. */
add_action('wp_ajax_crs_ct_preview', function () {
	if (!current_user_can('manage_woocommerce') || !check_ajax_referer('crs_ct_ajax', 'nonce', false)) {
		wp_send_json_error('auth', 403);
	}
	list($ok, $data, $err) = crs_ct_products();
	if (!$ok) {
		wp_send_json_error($err);
	}
	$list   = is_array($data) ? $data : [];
	$slice  = array_slice($list, 0, 25);
	$expmap = crs_ct_expansion_map();

	// blueprint delle sole espansioni presenti nell'anteprima (cache per-espansione) → immagine CDN + card_market_id
	$bpmap = [];
	foreach (array_unique(array_map(function ($p) {
		return (int) ($p['expansion_id'] ?? 0);
	}, $slice)) as $eid) {
		if ($eid) {
			$bpmap += crs_ct_blueprint_map($eid);
		}
	}

	$rows = [];
	foreach ($slice as $p) {
		$rows[] = crs_ct_enrich_from_blueprint(crs_ct_normalize_product($p, $expmap), $bpmap);
	}
	wp_send_json_success(['total' => count($list), 'rows' => $rows]);
});

/** AJAX: avvia un job di matching (raccoglie gli ID da agganciare). */
add_action('wp_ajax_crs_ct_match_start', function () {
	if (!current_user_can('manage_woocommerce') || !check_ajax_referer('crs_ct_ajax', 'nonce', false)) {
		wp_send_json_error('auth', 403);
	}
	$ids   = crs_ct_matchable_ids();
	$token = wp_generate_password(8, false);
	update_option('crs_match_' . $token, [
		'ids'       => $ids,
		'offset'    => 0,
		'persist'   => !empty($_POST['persist']),
		'counts'    => [],
		'unmatched' => [],
	], false);
	wp_send_json_success(['token' => $token, 'total' => count($ids)]);
});

/** AJAX: processa un batch di matching (offset lato server, ripartibile). */
add_action('wp_ajax_crs_ct_match_run', function () {
	if (!current_user_can('manage_woocommerce') || !check_ajax_referer('crs_ct_ajax', 'nonce', false)) {
		wp_send_json_error('auth', 403);
	}
	$token = sanitize_text_field($_POST['token'] ?? '');
	$job   = get_option('crs_match_' . $token);
	if (!$job) {
		wp_send_json_error('job non trovato (forse già completato)');
	}

	foreach (array_slice($job['ids'], (int) $job['offset'], 40) as $pid) {
		$r = crs_ct_match_one($pid, !empty($job['persist']));
		$m = $r['method'];
		$job['counts'][$m] = ($job['counts'][$m] ?? 0) + 1;
		if ($r['blueprint_id'] === 0 && count($job['unmatched']) < 25) {
			$job['unmatched'][] = ['id' => $pid, 'name' => html_entity_decode(get_the_title($pid)), 'method' => $m];
		}
		$job['offset']++;
	}

	$done = $job['offset'] >= count($job['ids']);
	if ($done) {
		delete_option('crs_match_' . $token);
	} else {
		update_option('crs_match_' . $token, $job, false);
	}

	wp_send_json_success([
		'processed' => (int) $job['offset'],
		'total'     => count($job['ids']),
		'counts'    => $job['counts'],
		'matched'   => ($job['counts']['card_market_id'] ?? 0) + ($job['counts']['name'] ?? 0),
		'unmatched' => $job['unmatched'],
		'done'      => $done,
	]);
});

/** AJAX: esegue il pull (prezzi + immagini) ora, manualmente. */
add_action('wp_ajax_crs_ct_pull_now', function () {
	if (!current_user_can('manage_woocommerce') || !check_ajax_referer('crs_ct_ajax', 'nonce', false)) {
		wp_send_json_error('auth', 403);
	}
	$res = crs_ct_pull();
	if (empty($res['ok'])) {
		wp_send_json_error($res['err'] ?? 'errore pull');
	}
	wp_send_json_success($res['counts']);
});

/** AJAX: avvia il push a blocchi in background. */
add_action('wp_ajax_crs_ct_push_now', function () {
	if (!current_user_can('manage_woocommerce') || !check_ajax_referer('crs_ct_ajax', 'nonce', false)) {
		wp_send_json_error('auth', 403);
	}
	$n = crs_ct_push_start();
	if ($n === -1) {
		wp_send_json_success(['started' => false, 'msg' => 'già in corso']);
	}
	if ($n === 0) {
		wp_send_json_error('niente da pushare o token assente');
	}
	wp_send_json_success(['started' => true, 'total' => $n]);
});

/** AJAX: stato del job di push (polling avanzamento). */
add_action('wp_ajax_crs_ct_push_progress', function () {
	if (!current_user_can('manage_woocommerce') || !check_ajax_referer('crs_ct_ajax', 'nonce', false)) {
		wp_send_json_error('auth', 403);
	}
	$job = get_option('crs_ct_push_job');
	if ($job) {
		wp_send_json_success(['running' => true, 'offset' => (int) $job['offset'], 'total' => (int) $job['total'], 'counts' => $job['counts']]);
	}
	$lp = get_option('crs_ct_last_push');
	wp_send_json_success(['running' => false, 'counts' => is_array($lp) ? $lp['counts'] : null]);
});

/** AJAX: avvia l'autopricer a blocchi in background. */
add_action('wp_ajax_crs_ct_autoprice_now', function () {
	if (!current_user_can('manage_woocommerce') || !check_ajax_referer('crs_ct_ajax', 'nonce', false)) {
		wp_send_json_error('auth', 403);
	}
	$n = crs_ct_autoprice_start();
	if ($n === -1) {
		wp_send_json_success(['started' => false, 'msg' => 'già in corso']);
	}
	if ($n === 0) {
		wp_send_json_error('niente da prezzare (nessun prodotto agganciato) o token assente');
	}
	wp_send_json_success(['started' => true, 'total' => $n]);
});

/** AJAX: stato del job autopricer (per il polling della barra di avanzamento). */
add_action('wp_ajax_crs_ct_autoprice_progress', function () {
	if (!current_user_can('manage_woocommerce') || !check_ajax_referer('crs_ct_ajax', 'nonce', false)) {
		wp_send_json_error('auth', 403);
	}
	$job = get_option('crs_ct_ap_job');
	if ($job) {
		wp_send_json_success(['running' => true, 'offset' => (int) $job['offset'], 'total' => (int) $job['total'], 'counts' => $job['counts']]);
	}
	$la = get_option('crs_ct_last_autoprice');
	wp_send_json_success(['running' => false, 'counts' => is_array($la) ? $la['counts'] : null]);
});

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
			<tr><th scope="row">Righe senza lingua</th><td>
				<select name="crs_lang">
					<option value="">— Salta le righe senza lingua (consigliato) —</option>
					<?php foreach ($langs as $slug => $name) {
						echo '<option value="' . esc_attr($slug) . '">Forza lingua: ' . esc_html($name) . '</option>';
					} ?>
				</select>
				<p class="description">Se una riga del CSV <strong>non ha la lingua</strong> (export dall'interfaccia italiana → colonna «Language» vuota, o lingua non riconosciuta): di default la <strong>salta</strong>, così non si mette mai in vendita una carta con una lingua <em>inventata</em> (che potresti non avere). Scegli «Forza lingua» solo se sei <strong>certo</strong> che TUTTO l'export è di quella lingua. Meglio ancora: <strong>esporta dall'interfaccia inglese</strong> (lingua per carta) → questo campo diventa irrilevante.</p>
			</td></tr>
			<tr><th scope="row">Modalità</th><td>
				<label style="margin-right:14px"><input type="radio" name="crs_mode" value="full" checked> Full — stock a <strong>delta</strong> + azzera i venduti (carte non più nel file)</label>
				<label><input type="radio" name="crs_mode" value="add"> Additivo — aggiunge solo i nuovi (non tocca gli esistenti)</label>
				<p class="description">⚠️ In Full il file deve rappresentare lo stock <em>completo</em> di questo gioco+tipo (per le varianti che contiene): le carte assenti vengono messe a zero (venduto). Lo stock degli esistenti si aggiorna a <strong>delta</strong> — un restock su Cardmarket si somma, le vendite fatte su sito/CardTrader restano; il <strong>prezzo non viene toccato</strong>.</p>
			</td></tr>
			<tr><th scope="row">Opzioni</th><td>
				<label style="display:block;margin-bottom:4px"><input type="checkbox" name="crs_images" value="1"> Scarica le immagini in libreria (opzionale e più lento; di default si usa l'immagine Cardmarket via URL, senza allegati)</label>
				<label style="display:block;margin-bottom:4px"><input type="checkbox" name="crs_push" value="1"> Dopo l'import, <strong>spingi lo stock su CardTrader</strong> (crea/aggiorna le inserzioni pubbliche di <em>tutto</em> il catalogo pronto). Lascia <strong>deselezionato</strong> per importare senza pubblicare nulla.</label>
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
		'lang'   => sanitize_key($_POST['crs_lang'] ?? ''), // '' = salta le righe senza lingua (niente lingua inventata)
		'mode'   => (($_POST['crs_mode'] ?? 'full') === 'add') ? 'add' : 'full',
		'images' => !empty($_POST['crs_images']),
		'push'   => !empty($_POST['crs_push']), // push a CardTrader a fine import SOLO se spuntato (default: no)
		'dry'    => !empty($_POST['crs_dry']),
	];

	// aggregazione per SKU (somma qty dei doppioni) → file .ndjson leggibile a batch
	$ndjson = crs_upload_dir() . '/' . $token . '.ndjson';
	$stats  = [];
	$total  = crs_aggregate_csv($raw, $ndjson, $opts, $stats);
	@unlink($raw); // il grezzo non serve più

	if ($total < 1) {
		@unlink($ndjson);
		$why = !empty($stats['skipped']) ? ' — ' . (int) $stats['skipped'] . ' righe saltate perché senza lingua (ri-esporta dall\'interfaccia inglese di Cardmarket)' : '';
		echo '<div class="notice notice-error"><p>Nessuna riga valida nel CSV' . esc_html($why) . '.</p></div>';
		return;
	}

	// M6: un solo import per volta (due upload concorrenti sullo stesso prodotto creerebbero SKU duplicati)
	$active = get_option('crs_import_active');
	if (is_array($active) && (time() - (int) ($active['time'] ?? 0)) < HOUR_IN_SECONDS) {
		@unlink($ndjson);
		echo '<div class="notice notice-error"><p>Un import è già in corso: attendi che finisca prima di avviarne un altro.</p></div>';
		return;
	}
	update_option('crs_import_active', ['token' => $token, 'time' => time()], false);

	// AVVISO righe senza lingua: di default le SALTIAMO (niente lingua inventata → niente carte fantasma in vendita).
	if (!empty($stats['skipped'])) {
		echo '<div class="notice notice-warning"><p><strong>⚠️ ' . (int) $stats['skipped'] . ' righe saltate:</strong> non hanno la lingua nel CSV → NON importate (non invento la lingua, per non mettere in vendita carte che non hai in quella lingua). Ri-esporta da Cardmarket con l\'interfaccia <strong>inglese</strong>, oppure — se sei certo della lingua — riscegli «Forza lingua» nel form.</p></div>';
	} elseif (!empty($stats['no_lang'])) {
		echo '<div class="notice notice-warning"><p><strong>Nota:</strong> forzata la lingua <code>' . esc_html($opts['lang']) . '</code> su ' . (int) $stats['no_lang'] . ' righe senza lingua nel CSV. Assicurati che TUTTE quelle carte siano davvero in questa lingua.</p></div>';
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
					if (d.done) {
						st.textContent = '✔ Fatto — ' + st.textContent + (d.zeroed ? (' · azzerati (venduti): ' + d.zeroed) : '');
						if (d.push > 0) { line('→ Push CardTrader avviato in background: ' + d.push + ' prodotti in coda (esito in "Ultimo push", sez. 5)'); }
						else if (d.push === -1) { line('→ Push CardTrader: un push è già in corso, salto'); }
						else { line('→ Nessun push: import applicato solo in locale (spunta "spingi lo stock su CardTrader" per pubblicare)'); }
					}
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
	// le scritture di stock dell'import NON devono innescare un push singolo per carta verso CardTrader:
	// ci pensa il push a blocchi di fine import (crs_ct_push_start). Sopprime l'hook woocommerce_product_set_stock.
	crs_ct_push_suppress(true);

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
	$push = null;
	if ($done) {
		if (!$job['dry']) {
			$zeroed = crs_finalize_sold($job, crs_ndjson_present($job['path']));
			// PUSH verso CardTrader SOLO se richiesto esplicitamente (spunta nel form): crea inserzioni PUBBLICHE reali
			// di TUTTO il catalogo pronto. Default off → l'import resta un'operazione locale finché non lo decidi tu.
			if (!empty($job['push']) && crs_ct_configured()) {
				$push = crs_ct_push_start(); // solo enqueue in background (C2): l'import non aspetta migliaia di scritture
			}
		}
		@unlink($job['path']);
		delete_option('crs_job_' . $token);
		delete_option('crs_import_active'); // libera il mutex import (M6)
	} else {
		update_option('crs_job_' . $token, $job, false);
		// heartbeat del mutex import: rinnova il timestamp ad OGNI batch così non scade durante import lunghi
		// (immagini → batch da 8 → un catalogo grande può superare l'ora). Scade solo dopo 1h di INATTIVITÀ (#4).
		$act = get_option('crs_import_active');
		if (is_array($act) && ($act['token'] ?? '') === $token) {
			$act['time'] = time();
			update_option('crs_import_active', $act, false);
		}
	}

	wp_send_json_success([
		'counts'    => $job['counts'],
		'processed' => $job['done'],
		'total'     => (int) $job['total'],
		'errors'    => $errors,
		'done'      => $done,
		'zeroed'    => $zeroed,
		'push'      => $push,
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

/* ---- Spunta "Prezzo fissato a mano" nel pannello prezzo del prodotto (WooCommerce) ----
   Quando attiva: autopricer e pull NON toccano il prezzo (lo gestisci tu). Utile per le carte costose/particolari
   dal mercato sottile. Il prezzo manuale viene comunque propagato su CardTrader dal pull (crs_ct_pull). */
add_action('woocommerce_product_options_pricing', function () {
	woocommerce_wp_checkbox([
		'id'          => CRS_META_PRICE_PINNED,
		'value'       => get_post_meta(get_the_ID(), CRS_META_PRICE_PINNED, true) === 'yes' ? 'yes' : 'no',
		'label'       => __('Prezzo fissato a mano', 'cardsrift-sync'),
		'description' => __('Se attivo, l’autopricer e il pull NON cambiano questo prezzo — lo gestisci tu. Il tuo prezzo manuale viene comunque spinto su CardTrader.', 'cardsrift-sync'),
	]);
});
add_action('woocommerce_admin_process_product_object', function ($product) {
	// la checkbox è presente in $_POST solo se spuntata (il salvataggio prodotto è già protetto dal nonce di WooCommerce)
	$product->update_meta_data(CRS_META_PRICE_PINNED, isset($_POST[CRS_META_PRICE_PINNED]) ? 'yes' : '');
});

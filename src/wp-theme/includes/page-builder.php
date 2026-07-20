<?php

/**
 * PAGE BUILDER — engine "struttura in codice, contenuti da backend".
 * Documentazione: docs/rework-fase-1.md · decisione: 17/07/2026.
 *
 * Una SOLA fonte di verità per pagina: il manifest content/{slug}.php, che elenca
 * le sezioni in ordine con tema + copy (in __()) + la dichiarazione dei pochi campi
 * legati al DB ('edit'). Da qui derivano ENTRAMBI:
 *   - il rendering        → cr_render_page()
 *   - il backend ACF      → cr_register_builder_fields()  (sezioni pre-apparecchiate,
 *                           l'editor riempie solo i campi 'edit', non ristruttura)
 *
 * Il copy NON sta in ACF: vive nei manifest (traducibile via text domain 'cardsrift').
 */

/**
 * Pagine gestite dal builder: slug del manifest → regola di location ACF.
 * Per ora solo la homepage (ha il suo template). Le altre pagine si aggiungono qui.
 */
function cr_builder_pages()
{
	return [
		'home' => [['param' => 'page_template', 'operator' => '==', 'value' => 'template_homepage.php']],
	];
}

/**
 * Carica il manifest di una pagina (array di sezioni) da content/{slug}.php.
 */
function cr_load_manifest($slug)
{
	$file = get_template_directory() . "/content/{$slug}.php";
	if (!is_readable($file)) {
		return [];
	}
	$data = include $file;
	return is_array($data) ? $data : [];
}

/**
 * True se la pagina corrente è composta dal builder (homepage o pagina con manifest):
 * quelle sezioni sono full-bleed e NON vanno avvolte nel .container legacy (vedi header.php).
 */
function cr_is_builder_page()
{
	if (is_page_template('template_homepage.php')) {
		return true;
	}
	// rotte gioco-scoped, accessori e pagina prodotto: sezioni full-bleed come la home
	if (get_query_var('cr_game') || get_query_var('cr_tipo') || is_singular('product')) {
		return true;
	}
	$id = get_queried_object_id();
	if (!$id) {
		return false;
	}
	$slug = get_post_field('post_name', $id);
	return $slug && cr_load_manifest($slug) !== [];
}

/**
 * Renderizza una pagina dal suo manifest: per ogni sezione fonde il copy (dal codice)
 * con i valori DB (dai campi ACF 'edit', letti dalla pagina corrente) e delega al
 * template del componente via la stessa pipeline di sempre (component_data query var).
 */
function cr_render_page($slug)
{
	$sections = cr_load_manifest($slug);
	if (!$sections) {
		return;
	}
	$page_id = get_queried_object_id();

	foreach ($sections as $i => $section) {
		if (empty($section['comp'])) {
			continue;
		}
		$data = $section;

		// merge dei campi legati al DB, letti da ACF sulla pagina corrente
		if (!empty($section['edit']) && is_array($section['edit'])) {
			foreach ($section['edit'] as $name => $def) {
				$data[$name] = get_field("cr_{$slug}_{$i}_{$name}", $page_id);
			}
		}
		unset($data['edit'], $data['comp'], $data['label']); // meta interne: fuori dal template

		set_query_var('component_data', $data);
		get_template_part("global-components/{$section['comp']}/template");
	}
}

/**
 * Registra da codice i campi ACF del builder (hook acf/init):
 *  - un field group per pagina builder, con una tab per sezione che HA campi 'edit'
 *    (le sezioni automatiche non compaiono: non c'è nulla da riempire);
 *  - il campo prodotto "data_uscita" (vetrina "in arrivo").
 * Così il backend è sempre allineato al codice e i campi sono versionati nel repo.
 */
function cr_register_builder_fields()
{
	if (!function_exists('acf_add_local_field_group')) {
		return;
	}

	foreach (cr_builder_pages() as $slug => $location_rule) {
		$sections = cr_load_manifest($slug);
		if (!$sections) {
			continue;
		}

		$fields = [[
			'key'     => "field_cr_{$slug}_intro",
			'type'    => 'message',
			'label'   => '',
			'message' => __('Questa pagina è composta da codice (struttura, ordine e testi). Qui sotto trovi solo i contenuti collegati al database da riempire — il resto è automatico.', 'cardsrift'),
			'new_lines' => 'wpautop',
		]];

		$has_editable = false;
		foreach ($sections as $i => $section) {
			if (empty($section['edit']) || !is_array($section['edit'])) {
				continue;
			}
			$has_editable = true;
			$label = $section['label'] ?? ucwords(str_replace('-', ' ', $section['comp'] ?? ''));

			$fields[] = [
				'key'   => "field_cr_{$slug}_{$i}_tab",
				'type'  => 'tab',
				'label' => ($i + 1) . ' · ' . $label,
			];
			foreach ($section['edit'] as $name => $def) {
				$fields[] = cr_build_edit_field($slug, $i, $name, $def);
			}
		}

		if (!$has_editable) {
			continue;
		}

		acf_add_local_field_group([
			'key'      => "group_cr_builder_{$slug}",
			'title'    => __('Contenuti pagina', 'cardsrift'),
			'fields'   => $fields,
			'location' => [$location_rule],
			'position' => 'normal',
			'style'    => 'default',
			'menu_order' => 0,
		]);
	}

	// Campo prodotto: data di uscita ("in arrivo"). date_picker → return Ymd (ordinamento affidabile).
	acf_add_local_field_group([
		'key'    => 'group_cr_product_rework',
		'title'  => __('Prodotto — Dati rework', 'cardsrift'),
		'fields' => [[
			'key'            => 'field_cr_data_uscita',
			'label'          => __('Data di uscita', 'cardsrift'),
			'name'           => 'data_uscita',
			'type'           => 'date_picker',
			'instructions'   => __('Compila solo per i prodotti "in arrivo": alimenta la vetrina uscite e li esclude dalle griglie in vendita.', 'cardsrift'),
			'display_format' => 'd/m/Y',
			'return_format'  => 'Ymd',
			'first_day'      => 1,
		]],
		'location'   => [[['param' => 'post_type', 'operator' => '==', 'value' => 'product']]],
		'position'   => 'side',
		'menu_order' => 20,
	]);
}
add_action('acf/init', 'cr_register_builder_fields');

/**
 * Costruisce una singola definizione di campo ACF a partire dalla dichiarazione 'edit'
 * del manifest. Tipi supportati: post_object, relationship, image, text.
 */
function cr_build_edit_field($slug, $i, $name, $def)
{
	$def  = is_array($def) ? $def : [];
	$base = [
		'key'          => "field_cr_{$slug}_{$i}_{$name}",
		'name'         => "cr_{$slug}_{$i}_{$name}",
		'label'        => $def['label'] ?? ucfirst($name),
		'instructions' => $def['instructions'] ?? '',
	];

	switch ($def['type'] ?? 'text') {
		case 'post_object':
			$count = (int) ($def['count'] ?? 1);
			return array_merge($base, [
				'type'          => 'post_object',
				'post_type'     => [$def['post_type'] ?? 'product'],
				'return_format' => 'id',
				'ui'            => 1,
				'allow_null'    => 1,
				'multiple'      => $count > 1 ? 1 : 0,
				'max'           => $count > 1 ? $count : 0,
			]);

		case 'relationship':
			return array_merge($base, [
				'type'          => 'relationship',
				'post_type'     => [$def['post_type'] ?? 'product'],
				'return_format' => 'id',
				'max'           => (int) ($def['count'] ?? 0),
			]);

		case 'image':
			return array_merge($base, [
				'type'          => 'image',
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'library'       => 'all',
			]);

		default:
			return array_merge($base, ['type' => 'text']);
	}
}

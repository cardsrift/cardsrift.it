<?php get_header(); ?>
<?php if (get_field('builder_contents')) : ?>
	<?php foreach (get_field('builder_contents') as $key => $content) : ?>
		<a id="<?= $content['acf_fc_layout']; ?>_<?= $key; ?>"></a>
		<?php
		// debug( get_field('builder_contents'));
		set_query_var('content_counter', $key);
		set_query_var('component_data', $content);
		get_template_part('global-components/' . $content['acf_fc_layout'] . '/template');
		?>
	<?php endforeach; ?>
<?php endif; ?>
<?php get_footer(); ?>
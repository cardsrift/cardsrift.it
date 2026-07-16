<?php /* Template Name: Homepage */ ?>
<?php get_header(); ?>

 <?php 
   $sections = get_field('components') ?: [];
   foreach( $sections as $component) {
    $component_name = str_replace( '_', '-', $component['acf_fc_layout'] );
    if ($component) {
                set_query_var('component_data', $component);
                get_template_part("global-components/$component_name/template");
    }
}
?>



<?php get_footer(); ?>
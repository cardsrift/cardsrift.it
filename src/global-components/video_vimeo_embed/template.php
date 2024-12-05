<?php include 'variables.php'; ?>
<?php
// Use preg_match to find iframe src.
preg_match('/src="(.+?)"/', $video, $matches);
$src = $matches[1];

// Add extra parameters to src and replace HTML.
$params = array(
	'controls'  => 0,
	'hd'        => 1,
	'autohide'  => 1,
	'loop'		=> 1,
);
$new_src = add_query_arg($params, $src);
$video = str_replace($src, $new_src, $video);

// Add extra attributes to iframe HTML.
$attributes = 'frameborder="0"';
$video = str_replace('></iframe>', ' ' . $attributes . '></iframe>', $video);

// Display customized HTML.
?>
<section id="video" class="embed-container video-section relative sectionSpied">
	<?php echo $video; ?>
	<button id="mute-toggle" class="max-lg:scale-75 bg-blue/50 hover:opacity-100 opacity-30 transition-all absolute lg:top-4 lg:right-4 top-1 right-1 p-3 bg-gray-900 text-white rounded-full">
		<svg class=" w-8 h-8 ">
			<use xlink:href="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/sprite/sprite.svg#speaker"></use>
		</svg>
		<div class="muteBar absolute top-2 left-2 h-1 w-[52px] bg-white rotate-45 origin-left"></div>
		
	</button>
</section>
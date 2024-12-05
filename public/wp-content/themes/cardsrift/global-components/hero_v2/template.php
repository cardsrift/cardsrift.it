<?php include 'variables.php'; ?>
<section class="hero-slider ">
	<!-- Slider main container -->
	<div class="swiper heroSliderV2">
		<!-- Additional required wrapper -->
		<div class="swiper-wrapper">
			<!-- Slides -->
			<?php foreach ($slides as $slide): ?>
				<div class="swiper-slide  h-[900px] relative">
					<div class="tw-container  text-white gap-8 mx-auto hero_text_w z-10 absolute top-0 left-0 right-0 bottom-0 flex justify-center items-start flex-col">
						<h1 class="text-3xl"><?= $slide['title']; ?></h1>
						<div class="text-lg"><?= $slide['text']; ?></div>
						<a class="btn btn-xs sm:btn-sm md:btn-md lg:btn-lg" target="<?= $slide['cta']['target']; ?>" href="<?= $slide['cta']['url']; ?>"><?= $slide['cta']['title']; ?></a>
					</div>
					<?php if ($slide['imagevideo'] === 'image'): ?>
						<picture>
							<source media="(max-width:768px)" srcset="<?= $slide['image_mobile']['sizes']['mob_full_size']; ?>" type="">
							<img class="" src="<?= $slide['image']['sizes']['full_size']; ?>"
								alt="<?= $slide['image']['alt']; ?>">
						</picture>
					<?php elseif ($slide['imagevideo'] === 'video_file') : ?>
						<video autoplay muted loop class="w-full">
							<source src="<?= $slide['video_url']; ?>" type="video/mp4">
						</video>
					<?php elseif ($slide['imagevideo'] === 'video_vimeo') : ?>
						<?php
						$video = $slide['vimeo_embed'];
						// debug($slide);

						if ($video) {
							// Add autoplay functionality to the video code
							if (preg_match('/src="(.+?)"/', $video, $matches)) {
								$src = $matches[1];
								$params = array(
									'controls'    => 0,
									'hd'        => 1,
									'autoplay' => 1,
									'muted' => 1,
									'loop' => 1
								);

								$new_src = add_query_arg($params, $src);
								$video = str_replace($src, $new_src, $video);

								// Assign an ID for each Vimeo iframe based on the slide index
								$attributes = 'frameborder="0" class="vimeo-iframe" id="vimeo-video-' . $index . '"';

								$video = str_replace('></iframe>', ' ' . $attributes . '></iframe>', $video);
							}

							echo '<div class="video-embed">', $video, '</div>';
						}
						?>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

		</div>
		<!-- If we need pagination -->
		<div class="swiper-pagination"></div>

		<!-- If we need navigation buttons -->
		<div class="swiper-button-prev"></div>
		<div class="swiper-button-next"></div>
	</div>
</section>
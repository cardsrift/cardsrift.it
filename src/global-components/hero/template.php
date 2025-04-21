<?php 
 $hero_slides = $component_data['slides'];
?>

<?php if($hero_slides) : ?>
	<section class="homepage-hero relative z-[0] -mx-6 h-[600px] 2xl:h-[800px]">


			<div class="swiper hero-slider !z-[6] max-lg:h-[600px] lg:h-full">
				<!-- Additional required wrapper -->
				<div class="swiper-wrapper h-full">
					<?php foreach($hero_slides as $key => $slide) : ?>
						<?php 
						//debug($slide['slide']);
							$image_mobile_url = $slide['slide_mobile'] ?? $slide['slide_mobile']['url'];
							$image_title = $slide['slide']['title'];
							$image_alt = $slide['slide']['alt'];
							$image_url = $slide['slide']['url'];
							$type = $slide['type'];
						?>
							<?php if ($type === 'img') : ?>
							
								
								<a class="swiper-slide overflow-hidden block h-full" href="<?= $slide['slide']['url']; ?>">
									<picture class="block h-full w-full relative ">
											<source media="(max-width: 767px)" srcset="<?php echo $image_mobile_url; ?>">
											<img class=" block h-full w-full object-cover transition-all object-center" 
												src="<?php echo $image_url; ?>" 
												alt="<?php echo $image_alt; ?>" 
												title="<?php echo $image_title ?>">
									</picture>
								</a>
							
							<?php elseif ($type === 'video') : ?>
								<?php if (!empty($slide['video']['url'])): ?>
									<div class="swiper-slide overflow-hidden h-full flex items-center justify-center bg-black">

										<video class="heroVideo object-cover object-center pointer-events-none h-full w-full max-lg:hidden lg:block" autoplay muted loop playsinline>
											<source src="<?= $slide['video']['url']; ?>">
											<!-- <source src="path-to-my-video.mp4" type="video/mp4" />
											<source src="path-to-my-video.webm" type="video/webm" /> -->
										</video>

										<video class="heroVideo object-cover object-center pointer-events-none h-full w-full max-lg:block lg:hidden" autoplay muted loop>
											<source src="<?= $slide['video_mobile']['url']; ?>">
										</video>
									</div>
									
								<?php endif; ?>
							<?php endif; ?>
						
					<?php endforeach; ?>
				</div>
			</div>
	</section>
<?php endif; ?>
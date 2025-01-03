<?php 
 $hero_slides = $component_data['slides'];
?>

<?php if($hero_slides) : ?>
<section class="homepage-hero relative z-[0] pt-12 lg:pt-16 ">


		<div class="swiper hero-slider !z-[6] max-lg:h-[400px] xl:max-h-[850px]">
			<!-- Additional required wrapper -->
			<div class="swiper-wrapper">
				<?php foreach($hero_slides as $key => $slide) : ?>
					
				<?php 
                    $image_title = $slide['slide']['title'];
                    $image_alt = $slide['slide']['alt'];
					$image = $slide['slide']['url'];
			    ?>
							
                <a href="#" class="swiper-slide overflow-hidden">
						<picture class="h-full w-full relative aspect-video">
						    <source media="(max-width: 767px)" srcset="<?php echo $image; ?>">
						    <img class="h-full w-full object-cover transition-all aspect-video" 
                                src="<?php echo $image; ?>" 
                                alt="<?php echo $image_alt; ?>" 
                                title="<?php echo $image_title ?>">
						</picture>
				</a>
				<?php endforeach; ?>
			</div>

		</div>
</section>
<?php endif; ?>
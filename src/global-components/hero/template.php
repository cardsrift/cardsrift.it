<?php 
 $hero_slides = $component_data['slides'];
?>

<?php if($hero_slides) : ?>
<section class="homepage-hero relative z-[0]">


		<div class="swiper hero-slider !z-[6] max-lg:h-[400px] lg:h-[450px]">
			<!-- Additional required wrapper -->
			<div class="swiper-wrapper h-full">
				<?php foreach($hero_slides as $key => $slide) : ?>
					
				<?php 
                    $image_title = $slide['slide']['title'];
                    $image_alt = $slide['slide']['alt'];
					$image = $slide['slide']['url'];
			    ?>
							
                <a href="#" class="swiper-slide overflow-hidden block h-full">
						<picture class="block h-full w-full relative ">
						    <source media="(max-width: 767px)" srcset="<?php echo $image; ?>">
						    <img class=" block h-full w-full object-cover transition-all object-center" 
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
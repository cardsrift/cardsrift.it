<?php 
 $hero_slides = $component_data['slides'];
?>

<?php include 'variables.php';

?>

<?php if($hero_slides) : ?>
<section class="single-post-hero tw-section relative z-[0] pt-12 lg:pt-16 ">


		<div class="swiper hero-listing-slider !z-[6] max-lg:h-[400px] xl:max-h-[850px]">
			<!-- Additional required wrapper -->
			<div class="swiper-wrapper">
				<?php foreach($hero_slides as $key => $slide) : ?>
					
				<?php 
					debug($slide);
			    ?>
							
                <a href="#" class="swiper-slide group overflow-hidden">
					<!-- <div class="absolute lg:p-16 p-4 z-[5] text-white lg:bottom-16 bottom-12 lg:max-w-[800px] max-w-4/5">
						
						<div class="lg:text-2xl text-base font-medium leading-5 lg:leading-8 pb-2 lg:pb-4">
							<?php //echo $spotlight_title ?>
						</div>
						<div class="lg:text-md text-sm font-light mb-3 lg:mb-6">
							<?php //echo $spotlight_text ?>
						</div>
						
						
					</div> -->
						<picture class="h-full w-full relative aspect-video">
						    <source media="(max-width: 767px)" srcset="">
						    <?php //debug($spotlight_image_mobile); ?>
						    <img class="h-full w-full object-cover group-hover:scale-105 transition-all aspect-video" 
                                src="" 
                                alt="<?php //echo $image_alt; ?>" 
                                title="<?php //echo $image_title ?>">
						</picture>
				</a>
				<?php endforeach; ?>
			</div>

		</div>
</section>
<?php endif; ?>
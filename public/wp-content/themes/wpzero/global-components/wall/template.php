<?php include 'variables.php'; ?>
<section class="sectionSpied sectionPadding max-lg:!pt-[120px] !pb-0 lg:pt-[240px]  wall" id="<?php echo $id_homepage_scroll; ?>">
	<div class="wall-wrapper">
		<div class="wall-content">
			<h2 class="wall-title tw-h2 fadeIn">
				<?php echo $title; ?>
			</h1>
			<div class="wall-text tw-p fadeIn max-lg:pb-[60px]">
				<?php echo $text; ?>
			</h1>
		</div>
		<div class="wall-elements-container">
			<?php foreach($items as $item) {
				$randomClass = mt_rand(0, 2);
				$className = '';
				if ($randomClass === 1) {
					$className = 'wall-element__aspect16by9';
				} elseif ($randomClass === 2) {
					$className = 'wall-element__aspect1by1';
				}
			?>
				<a
					href="<?php echo $item->guid; ?>"
					class="wall-element <?php echo $className; ?>"
				>
					<div class="wall-element-bg"
						style="background-image: url('<?php echo get_the_post_thumbnail_url($item->ID); ?>');">
					</div>
					<div class="wall-element-content">
						<h4 class="wall-element-title text-2xl font-bold">
							<?php echo $item->post_title; ?>
						</h4>
						<div class="wall-element-badge">
							<?php echo strtoupper($item->post_type); ?>
						</div>
					</div>
				</a>
			<?php } ?>
		</div>
		<div class="wall-items">
			<div id="horizontal-scroll-section" class="hidden md:block w-full">
				<div class="horizontal-scroll-section w-[300vw] md:w-[290vw] lg:w-[280vw] xl:w-[230vw]" id="desktop-container">
				</div>
			</div>
			<div class="md:hidden w-full">
				<div class="swiper-container">
					<div class="swiper-wrapper" id="mobile-container"></div>
					<div class="swiper-pagination flex items-center justify-between">
						<a href="#" class="swiper-insights-link text-white">
							<?php echo __('TUTTI GLI INSIGHTS', 'praxi'); ?> 
							<span class="swiper-insights-link-icon"></span>
						</a>
					</div>
					<div class="swiper-scrollbar swiper-scrollbar-wall"></div>
				</div>
			</div>
		</div>
	</div>
</section>
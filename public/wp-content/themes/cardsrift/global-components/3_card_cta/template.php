<?php include 'variables.php'; ?>
<section class="three-card-cta tw-section py-16 lg:pt-[60px]  max-lg:pt-[120px] pb-[120px] bg-blue-light sectionSpied" >
	<div class="tw-container">
		<div class="tw-h2 text-center text-blue fadeIn">
			<?= $title; ?>
		</div>
		<?php if ($cards) : ?>
			<div class="card-wrapper flex flex-col gap-3 lg:gap-5 pt-8 lg:pt-16 lg:flex-row justify-center fadeIn ">
				<?php foreach ($cards as $card): ?>
					<?php //  debug($card['image'])
					?>
					<a href="<?php echo $card['cta']['url']; ?>" target="<?php echo $card['cta']['target']; ?>" class="card-inner transition-all bg-white p-8 flex items-center lg:justify-center lg:flex-col lg:w-[375px] lg:h-[375px]">
						<picture>
							<source media="(max-width:768px)" srcset="<?= $card['image']['sizes']['thumbnail']; ?>" type="">
							<img class="h-[70px] lg:h-[110px]" src="<?= $card['image']['sizes']['thumbnail']; ?>"
								alt="<?= $card['image']['alt']; ?>">
						</picture>
						<div class="tw-h4 max-lg:pl-8 font-medium text-blue lg:pt-8">
							<?php echo $card['cta']['title']; ?>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
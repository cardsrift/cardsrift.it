<nav class="main-menu max-lg:bg-purple-light container mx-auto rounded-[20px] lg:pt-0 h-full">
	<?php $menu_hamburger = sort_wp_nav('header'); ?>

	<ul class="flex justify-center align-center flex-col lg:flex-row h-full w-full max-lg:border-solid max-lg:border-8 max-lg:rounded-[20px] max-lg:border-black max-lg:pt-24 max-lg:pb-16 lg:gap-10">
		<?php
		foreach ($menu_hamburger as $item_hamburger) :
			$class = ( $item_hamburger['url'] == 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) ? 'current-menu-item' : '';
			if (!isset($item_hamburger['children'])) :
		?>
		
				<li class="menuItemflex text-center tw-pmenu uppercase max-lg:my-4 lg:flex lg:items-center mx-auto h-full max-lg:w-full max-lg:h-fit <?= $class ?'text-purple': 'text-black'; ?>">
					<a class=" " href="<?= $item_hamburger['url']; ?>">
						<?= $item_hamburger['title']; ?>
						
					</a>
				</li>
			<?php else : ?>
				<li class="menuItemWChild tw-pmenu uppercase max-lg:my-4 lg:flex lg:items-center text-center mx-auto maxl-lg:relative max-lg:h-fit lg:h-full max-lg:w-full <?= $class ?'text-purple': 'text-black'; ?> ">
					<a class="toggleMenu" href="<?= $item_hamburger['url']; ?>">
						<?= $item_hamburger['title']; ?>
					</a>
					
					<!-- <span class="toggleMenu block w-full h-7 shadow-md shadow-black rounded-md pt-2 lg:hidden">&#43;</span> -->


					<ul class="main_menu__list hidden max-lg:mt-5 hover_arrow_state flex flex-wrap max-lg:h-fit max-lg:w-full lg:h-[300px] lg:bg-purple lg:fixed lg:right:0 lg:left:0 w-full lg:top-[90px] lg:left-[50%] lg:-translate-x-[50%]">
						<?php foreach ($item_hamburger['children'] as $submenu_hamburger) :
							if (!isset($submenu_hamburger['children'])) :
						?>
								<li class="menuItemflex flex justify-center items-center mx-auto h-full max-lg:w-full <?= $class ?'text-purple': 'text-black'; ?>">

									<a class="" href="<?= $submenu_hamburger['url']; ?>"><?= $submenu_hamburger['title']; ?>
								</a>
								</li>
							<?php else : ?>
								<?php $menu_image = get_field('category_image', $submenu_hamburger['ID'])['url']; ?>
								<li class="menuItemWChild flex flex-col justify-center items-center mx-auto relative max-lg:h-full lg:h-full max-lg:w-1/2 <?= $class ?'text-purple': 'text-black'; ?> px-3">
									<a class="bg-white rounded-[7px] mb-6 py-1 px-2 shadow-black shadow-md w-full flex justify-center items-center" href="<?= $submenu_hamburger['url']; ?>">
									<?php if ($menu_image) : ?>
											<img src="<?php echo $menu_image ?>" class="block !h-[80px] object-contain" alt="<?= $submenu_hamburger['title']; ?>">
										<?php endif; ?>
									</a>
									<!-- <span class="toggleMenu block w-full h-7 border border-b border-black pt-2 lg:hidden">&#43;</span> -->
										
									<!-- <ul class="main_menu__list hidden hover_arrow_state flex-col lg:flex lg:h-[300px] lg:bg-purple lg:fixed lg:right:0 lg:left:0 w-full lg:top-[90px] lg:left-[50%] lg:-translate-x-[50%]">
										<?php foreach ($submenu_hamburger['children'] as $sub_item_hamburger) : ?>
											<li class="menuItemWChild flex-col justify-center items-center mx-auto relative lg:h-full max-lg:w-full <?= $class ?'text-purple': 'text-black'; ?> ">
												<a class="" href="<?= $sub_item_hamburger['url']; ?>"><?= $sub_item_hamburger['title']; ?></a>
											</li>
										<?php endforeach; ?>
									</ul> -->

								</li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</li>
				<li class="max-lg:mt-auto lg:flex lg:items-center">
					<ul class="flex gap-5 justify-center">
						<li class="menuItemflex text-center  h-full max-lg:max-w-1/2 max-lg:h-fit text-black">
							<a class="flex w-full h-full justify-center" href="http://localhost/cardsrift/public/cart/">
								<svg class="h-10 w-10">
									<use xlink:href="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/sprite/sprite.svg#cart"></use>
								</svg>
							
								<?php //echo __('Carrello', 'cardsrift') ?>						
							</a>
						</li>
						<li class="menuItemflex text-center  h-full max-lg:max-w-1/2 max-lg:h-fit text-black">
							<a class="flex justify-center w-full h-full" href="http://localhost/cardsrift/public/my-account/">
								<svg class="h-10 w-10">
									<use xlink:href="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/sprite/sprite.svg#profile"></use>
								</svg>
								<?php //echo __('Profilo', 'cardsrift') ?>					
							</a>
						</li>
					</ul>
				</li>
				
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
</nav>
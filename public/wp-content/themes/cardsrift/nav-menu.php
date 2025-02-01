<nav class="main-menu bg-green container mx-auto  lg:pt-0 h-full">
	<?php $menu_hamburger = sort_wp_nav('header'); ?>

	<ul class="flex justify-center align-center flex-col lg:flex-row h-full max-lg:bg-purple-light w-full max-lg:border-solid max-lg:border-8 max-lg:rounded-[20px] max-lg:border-black py-40">
		<?php
		foreach ($menu_hamburger as $item_hamburger) :
			$class = ( $item_hamburger['url'] == 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) ? 'current-menu-item' : '';
			if (!isset($item_hamburger['children'])) :
		?>
		
				<li class="menuItemflex flex-col justify-center align-center mx-auto h-full max-lg:w-full max-lg:h-fit <?= $class ?'text-purple': 'text-black'; ?>">
					<a class=" " href="<?= $item_hamburger['url']; ?>">
						<?= $item_hamburger['title']; ?>
						
					</a>
				</li>
			<?php else : ?>
				<li class="menuItemWChild flex-col justify-center align-center mx-auto relative max-lg:h-fit lg:h-full max-lg:w-full <?= $class ?'text-purple': 'text-black'; ?> ">
					<a class="" href="<?= $item_hamburger['url']; ?>">
						<?= $item_hamburger['title']; ?>
					</a>
					
					<span class="toggleMenu absolute top-0 right-4 lg:hidden">&#43;</span>

					<ul class="main_menu__list hidden hover_arrow_state flex max-lg:h-fit max-lg:w-full lg:flex lg:h-[300px] lg:bg-purple lg:fixed lg:right:0 lg:left:0 w-screen lg:top-[90px] lg:left-[50%] lg:-translate-x-[50%]">
						<?php foreach ($item_hamburger['children'] as $submenu_hamburger) :
							if (!isset($submenu_hamburger['children'])) :
						?>
								<li class="menuItemflex flex-col justify-center align-center mx-auto h-full max-lg:w-full <?= $class ?'text-purple': 'text-black'; ?>">

									<a class="" href="<?= $submenu_hamburger['url']; ?>"><?= $submenu_hamburger['title']; ?>
								</a>
								</li>
							<?php else : ?>
								<?php $menu_image = get_field('category_image', $submenu_hamburger['ID'])['url']; ?>
								<li class="menuItemWChild flex-col justify-center align-center mx-auto relative max-lg:h-fit lg:h-full max-lg:w-1/2 <?= $class ?'text-purple': 'text-black'; ?>">
									<a class="" href="<?= $submenu_hamburger['url']; ?>">
									<?php if ($menu_image) : ?>
											<img src="<?php echo $menu_image ?>" class="block w-[100px] text-purple">
										<?php endif; ?>
									</a>
									<span class="toggleMenu absolute top-0 right-4 lg:hidden">&#43;</span>
										
									<ul class="main_menu__list hidden hover_arrow_state flex-col lg:flex lg:h-[300px] lg:bg-purple lg:fixed lg:right:0 lg:left:0 w-screen lg:top-[90px] lg:left-[50%] lg:-translate-x-[50%]">

												<?php foreach ($submenu_hamburger['children'] as $sub_item_hamburger) : ?>
													<li class="menuItemWChild flex-col justify-center align-center mx-auto relative lg:h-full max-lg:w-full <?= $class ?'text-purple': 'text-black'; ?> ">

														<a class="" href="<?= $sub_item_hamburger['url']; ?>"><?= $sub_item_hamburger['title']; ?></a>
													</li>
												<?php endforeach; ?>
											</ul>
								</li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
</nav>
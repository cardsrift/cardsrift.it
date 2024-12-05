<nav class="main-menu container mx-auto pt-[var(--header-h-mobile)] lg:pt-0">
	<?php $menu_hamburger = sort_wp_nav('header'); ?>

	<ul class="inline-flex pt-[10%] flex-1 justify-center align-center flex-col lg:flex-row  text-4">
		<?php
		foreach ($menu_hamburger as $item_hamburger) :
			$class = ( $item_hamburger['url'] == 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) ? 'current-menu-item' : '';
			if (!isset($item_hamburger['children'])) :
		?>
				<li class="menuItemflex inline-flex  justify-center align-center flex-col  mx-2 <?= $class ?'text-red': 'text-white'; ?>">
					<a class=" " href="<?= $item_hamburger['url']; ?>"><?= $item_hamburger['title']; ?></a>
				</li>
			<?php else : ?>
				<li class="menuItemWChild flex justify-center align-center flex-col mx-2  relative <?= $class ?'text-red': 'text-white'; ?>">
					<a class="" href="<?= $item_hamburger['url']; ?>"><?= $item_hamburger['title']; ?>
				</a>
				<span class="toggleMenu absolute top-0 -right-4 lg:hidden">&#43;</span>

	
							<ul class="main_menu__list hidden hover_arrow_state mt-2 ml-2 flex-col  border border-white lg:bg-blue-light lg:ml-0 lg:p-1 lg:absolute lg:top-4 lg:left-[50%] lg:-translate-x-[50%]">
								<?php foreach ($item_hamburger['children'] as $submenu_hamburger) :
									if (!isset($submenu_hamburger['children'])) :
								?>
										<li class="menuItem lg:w-full">
											<a class="" href="<?= $submenu_hamburger['url']; ?>"><?= $submenu_hamburger['title']; ?>
										</a>
										</li>
									<?php else : ?>
										<li class="menuItemWChild relative lg:flex">
											<a class="" href="<?= $submenu_hamburger['url']; ?>">
												<?= $submenu_hamburger['title']; ?>
											</a>
											<span class="toggleMenu absolute top-0 -right-4 lg:hidden">&#43;</span>

													<ul class="hidden main_menu__list hover_arrow_state mt-2 ml-2 flex-col lg:bg-blue-light lg:ml-0 lg:p-1 lg:absolute lg:top-4 lg:left-[50%] lg:-translate-x-[50%]">
														<?php foreach ($submenu_hamburger['children'] as $sub_item_hamburger) : ?>
															<li class="menuItem">
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
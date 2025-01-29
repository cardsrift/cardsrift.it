<nav class="main-menu container mx-auto pt-[var(--header-h-mobile)] lg:pt-0 h-full">
	<?php $menu_hamburger = sort_wp_nav('header'); ?>

	<ul class="bg-black lg:bg-white w-full lg:w-auto inline-flex flex-1 justify-center align-center flex-col lg:flex-row text-4 h-full">
		<?php
		foreach ($menu_hamburger as $item_hamburger) :
			$class = ( $item_hamburger['url'] == 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) ? 'current-menu-item' : '';
		?>
				<li class="menuItemWChild flex justify-center align-center flex-col mx-2  relative <?= $class ? 'text-purple': 'text-white lg:text-black'; ?>">
					<a class="" href="<?= $item_hamburger['url']; ?>"><?= $item_hamburger['title']; ?></a>
					<span class="toggleMenu absolute top-0 right-4 lg:hidden">&#43;</span>
				</li>
		<?php endforeach; ?>
	</ul>
</nav>
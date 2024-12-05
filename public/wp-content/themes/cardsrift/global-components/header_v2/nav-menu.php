<?php // if (wp_get_nav_menu_items('header')) { ?>

<nav class="main-menu transition-all mx-auto pt-[var(--header-h-mobile)] lg:pt-0">
	<?php $menu_hamburger = sort_wp_nav('header'); ?>
	<!-- Primo livello -->

	<ul class="inline-flex flex-1 align-center flex-col lg:flex-row text-4 justify-between max-lg:gap-8 max-lg:pt-8 pb">

		<?php
		foreach ($menu_hamburger as $item_hamburger) :
			$class = ($item_hamburger['url'] == 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ? 'current-menu-item' : '';
			if (!isset($item_hamburger['children'])) :
		?>
				<li class="menuItemflex tw-menu-item-1 text-white lg:text-blue firstLevelMenu  inline-flex  justify-center align-center flex-col  lg:mx-4 <?= $class ? 'text-red' : 'text-blue'; ?>">
					<a class="relative custom_underline " href="<?= $item_hamburger['url']; ?>"><?= $item_hamburger['title']; ?></a>
				</li>
			<?php else : ?>
				<li class="menuItemWChild transition-all tw-menu-item-1 text-white lg:text-blue firstLevelMenu  flex justify-center align-center flex-col lg:mx-4   <?= $class ? 'text-red' : 'text-blue'; ?>">
					<a class="relative custom_underline " href="javascript:void(0)"><?= $item_hamburger['title']; ?>
					</a>

					<!-- secondo livello -->
					<div class="wrap-menu max-lg:hidden lg:opacity-0 lg:transition-all max-lg:transition-all lg:invisible max-lg:flex-col lg:py-[60px]  max-lg:pt-[var(--header-h-mobile)] flex gap-4 lg:gap-8 absolute max-lg:right-0 max-lg:left-[100%] max-lg:bg-orange max-lg:top-0 max-lg:bottom-0 lg:bg-white max-w-[1800px] lg:bg-red lg:ml-0 lg:tw-section  lg:top-[99%] lg:mt-0 lg:left-[0] lg:right-[0] lg:min-h-[423px] lg:before:content-[''] lg:before:absolute lg:before:h-full lg:before:left-0 lg:before:top-0 lg:before:bottom-0 lg:before:right-[-100%] lg:before:shadow-custom lg:before:bg-white">
						<!-- Menu Item Description -->
						<div class="flex flex-col basis-1/4 max-lg:hidden z-10">
							<div class="text-lg uppercase text-blue"><?php echo get_field('title_description', $item_hamburger['ID']); ?></div>
							<div class="tw-p text-grey-light "><?php echo get_field('text_description', $item_hamburger['ID']); ?></div>
						</div>
						<!-- BTN BACK -->
						<div class="lg:hidden  mt-4 flex items-center min-w-[200px] backToFirstLevel">
							<button href="" class="btn-circle btn-circle-lime">
								<svg class="btn-circle-arrow rotate-180">
									<use xlink:href="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/sprite/sprite.svg#arrow-right"></use>
								</svg>
							</button>
							<span class="pl-2 uppercase text-xxs font-semibold"><?= __('Back', 'praxi'); ?></span>
						</div>
						<!-- Menu Item List -->
						<ul class=" secondLevel main_menu__list hover_arrow_state relative flex-col max-lg:flex border max-lg:gap-8 max-lg:pt-4">

							<?php foreach ($item_hamburger['children'] as $submenu_hamburger) : ?>
								<?php if (!isset($submenu_hamburger['children'])) : ?>
									<li class="menuItem lg:hover:text-blue lg:border-b-[1px] lg:border-blue-light lg:border-solid lg:py-4 tw-menu-item-1 text-white lg:text-grey-light lg:w-full" data-img="<?= get_field('image', $submenu_hamburger['ID'])['sizes']['menu_image']; ?>">
										<a class="min-w-[290px] lg:w-full flex" href="<?= $submenu_hamburger['url']; ?>"><?= $submenu_hamburger['title']; ?>
										</a>
									</li>
								<?php else : ?>
									<li class="menuItemWChild  lg:hover:text-blue lg:border-b-[1px] lg:border-blue-light lg:border-solid lg:py-4 items-center tw-menu-item-1 text-white lg:text-grey-light max-lg:pr-10 lg:flex" data-img="<?= get_field('image', $submenu_hamburger['ID'])['sizes']['menu_image']; ?>">
										<a class="min-w-[290px] lg:w-full relative flex" href="javascript:void(0)">
											<?= $submenu_hamburger['title']; ?>
											<span class="toggleMenu  plusMenu absolute top-[50%] translate-y-[-50%] -right-0 lg:hidden"></span>
										</a>
										<span class="toggleMenu max-lg:hidden flex lg:-translate-x-2 transition-all w-4 h-4 top-0 -right-4">
											<svg class="icon icon--size_mod fill-grey">
												<use xlink:href="<?php echo get_template_directory_uri(); ?>/assets/images/sprite/sprite.svg#arrow">
												</use>
											</svg>
										</span>
										<!-- Terzo Livello -->
										<div class="wrap-menu lg:pl-3 lg:min-w-[298px] lg:justify-end lg:h-full max-lg:overflow-hidden lg:opacity-0 lg:invisible transition-all lg:transition-opacity flex flex-col lg:flex-col-reverse max-lg:max-h-0  lg:ml-0 lg:absolute lg:top-0 lg:right-[-100%] w-max">
											<div class="menuItem third-level-first-item  lg:w-full flex items-center tracking-[2px] justify-center uppercase bg-white lg:bg-blue py-4 lg:p-4 max-lg:mt-9 text-blue lg:text-white max-lg:mt-8 text-sm font-semibold w-[calc(100vw-1.2rem) ">
												<a class="transition-all" href="<?= $submenu_hamburger['url']; ?>"><?= $submenu_hamburger['title']; ?></a>
												<svg class="icon icon--size_mod w-4 h-4 ml-2 fill-current  transition-all">
													<use xlink:href="<?php echo get_template_directory_uri(); ?>/assets/images/sprite/sprite.svg#arrow">
													</use>
												</svg>
											</div>
											<ul class=" main_menu__list hover_arrow_state flex flex-col max-lg:gap-8 max-lg:pt-8 transition-all">
												<?php foreach ($submenu_hamburger['children'] as $sub_item_hamburger) : ?>
													<li class="menuItem lg:!w-full lg:hover:text-blue lg:transition-all lg:border-b-[1px] lg:border-blue-light lg:border-solid lg:py-4  w-max lg:min-w[350px] tw-menu-item-2 text-white  lg:text-grey-light max-lg:opacity-70 ">
														<a class="" href="<?= $sub_item_hamburger['url']; ?>"><?= $sub_item_hamburger['title']; ?></a>
													</li>
												<?php endforeach; ?>
											</ul>
										</div>
									</li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
						<!-- Immagine con src dinamico in base all'immagine della voce secondo livello -->
						<img id="dynamicImage" class="absolute max-container:hidden right-8 top-[50%] translate-y-[-50%] img-fluid max-w-[450px] h-auto" src="" alt="">
					</div>
				</li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
	<div class="areaCandidati lg:hidden text-blue-light2 font-base font-normal my-8">Area Candidati</div>
	<div class="areaCandidati lg:hidden text-blue-light2 font-base font-normal my-8">  <?php echo do_shortcode('[wpml_language_selector_widget]'); ?>
	</div>
</nav>
<?php // } ?>
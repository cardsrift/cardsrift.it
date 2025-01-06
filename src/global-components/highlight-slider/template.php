<?php
$title =  $component_data['title'];
$slides =  $component_data['highlights'];


?>

<section class="highlight-slider relative py-8 xl:py-11">
    <div class="tw-container">

        <?php if ($title) : ?>
            <div class="highlight-slider-title max-sm:px-5">
                <div class="">
                    <h1 class="tw-h4 font-500">
                        <?php echo $title; ?>
                    </h1>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($slides) : ?>
            <div class="swiper highlight-slider-slides !pt-6 !pb-8">
                <div class="highlight-slider-swiper swiper-container max-sm:!mr-10">
                    <div class="swiper-wrapper !h-[350px]">
                        <?php foreach ($slides as $key => $slide) : ?>

                            <?php 
                                $product_id = $slide['product']->ID;
                                $product_title = $slide['product']->post_title;
                                $product_image = wp_get_attachment_image_url(get_post_thumbnail_id($product_id), 'full');
                                $product = wc_get_product( $product_id );
                                $price = number_format((float)$product->get_regular_price(), 2, ',', '');
                                $terms = get_the_terms($product_id, 'product_cat');
                                //debug($terms)
                            ?>

                            <div class="flex flex-col swiper-slide h-full">
                                
                                <a href="<?php echo get_permalink($product_id); ?>" class="highlight-slider-link">
                                    <picture class=" flex justify-center items-center">
                                        <source srcset="<?php echo $product_image; ?>" media="(max-width: 768px)">
                                        <img class="h-[200px]" src="<?php echo $product_image; ?>">
                                    </picture>
                                </a>
                                <div class="py-10 xl:py-12 px-8 flex flex-col justify-between text-center">
                                        <a href="<?php echo get_permalink($product_id); ?>" class="highlight-slider-link">
                                        <h4 class="text-xxs">
                                            <?php echo $product_title; ?>
                                        </h4>
                                    </a>
                                    <?php echo '€'.$price  ?>
                                </div>
                                <a href="<?php echo get_permalink($product_id); ?>" class="highlight-slider-link text-center block w-full">
                                    <?php echo __('Aggiungi al carrello', 'cardsrift'); ?>
                                </a>
                            </div>


                        <?php endforeach; ?>
                    </div>
                </div>
            </div>       
        <?php endif; ?>

    </div>
</section>
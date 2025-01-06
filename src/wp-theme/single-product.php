<?php get_header(); ?>

<?php 
$post = get_post();
$post_id = $post->ID;
$product = wc_get_product( $post_id );
$product_data = $product->get_data();
$title = $product_data['name'];
$description = $product_data['description'];
$excerpt = $product_data['short_description'];
$price = $product_data['price'];
$image = wp_get_attachment_image_url(get_post_thumbnail_id($post_id), 'full');
//debug($product->get_data());
?>

<section class="product-main-infos">
    <div class="tw-container">
        <div class="product-main-infos-wrapper lg:flex ">
            <div class="product-main-infos-image lg:w-1/3">
                <img src="<?php echo $image ?>" alt="<?php echo $title ?>">
            </div>
            <div class="product-main-infos-content lg:w-2/3">
                <h1 class="tw-h1"><?php echo $title ?></h1>
                <p class="tw-p"><?php echo $description ?></p>
                <p class="tw-p"><?php echo $price ?></p>
                <button class="tw-button">Add to cart</button>
            </div>
        </div>
    </div>
</section>



<?php get_footer(); ?>
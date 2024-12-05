<?php include 'variables.php'; ?>
<section>
    <div>
    
       <!-- Slider main container -->
        <div class="swiper rimond-slider relative h-96">

            <div class="absolute z-[-1] top-0 right-0 bottom-0 left-0 max-lg:hidden">
        
                <?php foreach($slides as $key => $image): ?>
                    <img class="sliderImage h-full w-full object-cover absolute -z-[1]" src="<?php echo $image['image']['url'] ?>" alt="" data-index="<?php echo $key ?>">
                <?php endforeach; ?>

            </div>  
            <!-- Additional required wrapper -->
            <div class="swiper-wrapper" >

                <?php foreach($slides as $key => $slide): ?>

                    <div class="swiper-slide group border-2 border-white border-solid z-[1] bg-gradient-to-t from-blue to-transparent to-50%" data-index="<?php echo $key ?>">
                        <div class="p-2 flex flex-col justify-end h-full w-full">
                        <img class="h-full w-full object-cover absolute -z-[1] lg:hidden" src="<?php echo $slide['image']['url'] ?>" alt="" data-index="<?php echo $key ?>">
                            <h3 class="text-white mb-4">
                            <?php echo $slide['title']; ?>
                            </h3>
                            <div class="slide-text text-white opacity-1 mt-4 translate-y-12 h-0 group-hover:-translate-y-[1.5rem] group-hover:h-auto" >
                                <div class="tw-p mb-3">
                                    <?php echo $slide['text']; ?>
                                </div>
                                <svg class="btn-circle-arrow h-4 w-4 fill-white">
                                    <use xlink:href="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/sprite/sprite.svg#right-arrow"></use>
                                </svg>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

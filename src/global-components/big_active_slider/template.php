<?php include 'variables.php'; ?>
<section class="tw-section">
     <!-- Slider main container -->
     <div class="big-active-slider relative h-96 tw-container mt-14">

<!-- Additional required wrapper -->
        <div class="swiper-wrapper" >

            <?php foreach($slides as $key => $slide): ?>

                <div class="swiper-slide " >
                    <div class="">
                        <h3 class="">
                            <?php echo $slide['title']; ?>
                        </h3>
                        <div class="" >
                                <?php echo $slide['text']; ?>
                        </div>
                        <div class="" >
                            <?php echo $slide['subtitle']; ?>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
    </div>
</section>
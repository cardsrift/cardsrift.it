<?php include 'variables.php'; ?>
<section class="double-slider-section tw-section h-80 my-32">
    <div class="tw-container flex max-lg:flex-col">
        <div class="w-full lg:w-3/5 bg-blue h-80" >
            <div class="bg-blue h-80">
                <div dir="rtl" class="swiper double-slider-events h-full !px-8">
                    <div class="swiper-wrapper h-full">
                        <?php foreach($events_slides as $key => $slide) : ?>
                           
                            <?php
                                debug($events_slides);
                                $event_id = $slide['event_slide']->ID;
                                
                                $event_title = $slide['event_slide']->post_title;
                                $event_image = $slide['event_image'];
                                $event_url = get_permalink($event_id);
                                $event_start_date = $slide['start_event'];
                                $event_end_date = $slide['end_event'];

                                $is_single_day = $event_start_date == $event_end_date;

                                $event_term = ($terms = get_the_terms($event_id, 'event-category')) && count($terms) > 0 ? $terms[0]->name : false;
                            ?>

                            <div class="swiper-slide relative h-full">
                                <a href="<?php echo $event_url ?>">

                                    <?php  //debug($event_image)['sizes'][''] ?>
                                    <div class="absolute w-full h-full object-fit -z-[1]">
                                       
                                        <picture class="h-full w-full">
                                            <img class="h-full w-full" src="<?php echo $event_image['url'] ?>" alt="">
                                        </picture>
                                    </div>
                                    <div class="flex flex-col items-end justify-end h-full p-4">
                                        <div class="text-green">
                                            <?php 
                                                if ($is_single_day) :
                                                    echo __('Dal', 'praxi') . ' ' . $event_start_date . ' ' . __('al', 'praxi') . ' ' . $event_end_date;
                                                else :
                                                    echo $event_start_date;
                                                endif;
                                            ?>
                                        </div>
                                
                                        <h4 class="text-left text-white"><?php echo $event_title ?></h4>
                                        <div class="bg-black text-white rounded-xl py-1 px-2 text-[10px] uppercase">
                                            <?php if($event_term) :?>
                                                <?php echo $event_term; ?>
                                            <?php endif;?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-full lg:w-2/5 bg-green h-80">
                <div class="w-full h-80  pl-8">
                    <div class="swiper double-slider-news h-full w-full">
                        <div class="swiper-wrapper h-full w-full">
                        <?php foreach($news_slides as $key => $slide) : ?>
                            <?php 
                                $news_id = $slide['news_slide']->ID;
                                $news_title = $slide['news_slide']->post_title;
                                $news_url = get_permalink($news_id);
                                $news_date = $slide['news_date'];
                            ?>

                            <div class="swiper-slide p-2 w-full h-full">
                                <div class="border-solid border-black border-2 h-full max-w-full p-2">
                                    <div>
                                        <?php echo $news_date ?>
                                    </div>
                                    <h4>
                                        <?php echo $news_title ?>
                                    </h4>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
        </div>
    </div>
</section>
<!-- 
<footer class="footer bg-base-200 text-base-content p-10 h-[200px]">
    <div>
        <img class="!h-12" src="<?php echo get_field('logo', 'options')['url'] ?>" alt="">
        <address>
          <?php echo get_field('address', 'options') ?>
        </address>
    </div>
  <?php 
  $footer_links = get_field('link_group', 'options')
  ?>
  <?php foreach($footer_links as $link_group): ?>
    <div class="max-md:collapse max-md:collapse-plus bg-base-200">
      <input type="radio" name="my-accordion-3" checked="checked" class="md:hidden"/>
      <div class="max-md:collapse-title text-xl font-medium md:hidden"><?php echo $link_group['link_title'] ?></div>
      <div class="collapse-content md:visible">
      <nav class="flex flex-col">
        <h6 class="footer-title max-md:hidden font-metropolis"><?php echo $link_group['link_title'] ?></h6>
        <?php foreach($link_group['links'] as $link): ?>
        <a href="<?php echo $link['link']['url'] ?>" class="link link-hover"><?php echo $link['link']['title'] ?></a>
        <?php endforeach; ?>
      </nav>
      </div>
    </div>
    <?php endforeach; ?>

</footer> -->

<footer class=" bg-base-200 text-base-content p-10 ">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-center">
           
          <div class="flex flex-col ">

            <!-- Logo -->
            <div class="mb-12">
             <img class="!h-8" src="<?php echo get_field('logo', 'options')['url'] ?>" alt="">
            </div>
            <!-- Informazioni aziendali -->
            <div class="text-center md:text-left">
                <p class="mb-2">P.IVA: 13934020960 - Cod. Fiscale: BRLMRC94E06E951S</p>
                <p class="mb-2">Sede Legale: <br> Via Palestro 10, <br>20823 Lentate sul Seveso (MB), <br>Italia</p>
                <p class="mb-2">REA: MB-2752105</p>
                <p class="mb-2">
                PEC: <a href="mailto:mario.rossi@pec.it" class="text-blue-400 font-bold hover:underline">postmaster@pec.cardsrift.com</a> <br>
                Info: <a href="mailto:cardsrift@gmail.com" class="text-blue-400 font-bold hover:underline">cardsrift@gmail.com</a></p>
            </div>
           

          </div>

           

            <!-- Link utili -->
            <div class="text-center md:text-right mt-4 md:mt-0 h-full">

           <!-- Social e pagamenti -->
           <div class="flex flex-col md:flex-row justify-end items-start mb-10">
              <!-- Social -->
              <div class="flex space-x-4">
                <a href="https://instagram.com" target="_blank" class="inline-block !h-8 !w-8">
                  <img src="<?php echo get_template_directory_uri().'/assets/images/instagram.png' ?>" alt="Instagram" class="!h-full !w-full">
                </a>
                <a href="https://tiktok.com" target="_blank" class="!h-8 !w-8">
                  <img src="<?php echo get_template_directory_uri().'/assets/images/tiktok.svg' ?>" alt="tiktok" class="!h-full !w-full">
                </a>
                <a href="https://telegram.com" target="_blank" class="!h-8 !w-8">
                  <img src="<?php echo get_template_directory_uri().'/assets/images/telegram.svg' ?> " alt="Gruppo Telegram" class="!h-full !w-full">
                </a>
              </div>           
            </div>

                <ul>
                    <li><a href="#" class="hover:text-gray-400">Termini e Condizioni</a></li>
                    <li><a href="#" class="hover:text-gray-400">Privacy Policy</a></li>
                    <li><a href="#" class="hover:text-gray-400">Cookie Policy</a></li>
                </ul>
                  <!-- Metodi di pagamento -->
            <ul class="mt-10 lg::mt-10 flex justify-end gap-4">
              <li class="!w-8 !h-8">
                <img src="<?php echo get_template_directory_uri().'/assets/images/mastercard.svg' ?> " alt="mastercard" class="!h-full !w-full">
              </li>
              <li class="!w-8 !h-8">
                <img src="<?php echo get_template_directory_uri().'/assets/images/visa.svg' ?> " alt="visa" class="!h-full !w-full">
              </li>
              <li class="!w-8 !h-8">
                <img src="<?php echo get_template_directory_uri().'/assets/images/paypal.svg' ?> " alt="paypal" class="!h-full !w-full">
              </li>
              
            </ul>

      
            </div>
        </div>

       

        <div class="text-center mt-6 text-sm text-gray-400">
            &copy; 2025 CardsRift - Tutti i diritti riservati
        </div>
    </div>
</footer>

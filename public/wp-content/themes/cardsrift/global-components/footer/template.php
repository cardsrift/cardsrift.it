<footer class=" bg-base-200 text-base-content tw-section pb-8 pt-12 ">
    <div class="container mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center">
           
          <div class="flex flex-col ">

            <!-- Logo -->
            <div class="mb-12">
             <img class="!h-8 max-lg:mx-auto" src="<?php echo get_field('logo', 'options')['url'] ?>" alt="">
            </div>
            <!-- Informazioni aziendali -->
            <div class="text-center md:text-left">
                <p class="mb-2">P.IVA: 13934020960</p>
                <p class="mb-2">Via Palestro 10 <br>20823 <br> Lentate sul Seveso (MB)</p>
                <p class="mb-2">
                  <a href="mailto:cardsrift@gmail.com" class="text-blue-400 font-bold hover:underline">cardsrift@gmail.com</a>
                </p>
            </div>
           

          </div>

           

            <!-- Link utili -->
            <div class="text-center md:text-right mt-8 md:mt-4 h-full max-lg:flex max-lg:flex-col max-lg:justify-center">

            <!-- Social e pagamenti -->
              <div class="flex flex-col md:flex-row justify-end lg:items-start max-lg:items-center mb-10 ">
                  <!-- Social -->
                  <div class="flex space-x-4 max-lg:justify-center">
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

   <?php
	//base
	?>
   </div>

   <footer class="footer">

   </footer>

   <?php
	//wrapper
	?>
   </div>

   <?php wp_footer(); ?>


   <script>
   	jQuery(document).on('gform_post_render', () => {
   		const inputListGravity = document.querySelectorAll('.gform-body .gfield input');
   		const textAreaGravity = document.querySelectorAll('.gform-body .gfield textarea');
   		const checkIsFilledGravity = (target) => {
   			let inputValue = target.value;
   			if (inputValue === '') {
   				target.closest('.gfield').classList.remove('form_input--active_mod');
   			} else {
   				target.closest('.gfield').classList.add('form_input--active_mod');
   			}
   		};
   		inputListGravity.forEach(item => {
   			checkIsFilledGravity(item);

   			item.addEventListener('focus', (el) => {
   				el.target.closest('.gfield').classList.add('form_input--active_mod');
   			});

   			item.addEventListener('blur', (el) => {
   				checkIsFilledGravity(el.target);
   			});
   		});
   		textAreaGravity.forEach(item => {
   			checkIsFilledGravity(item);

   			item.addEventListener('focus', (el) => {
   				el.target.closest('.gfield').classList.add('form_input--active_mod');
   			});

   			item.addEventListener('blur', (el) => {
   				checkIsFilledGravity(el.target);
   			});
   		});
	});
   </script>
   </body>

   </html>
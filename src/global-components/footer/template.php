
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

</footer>

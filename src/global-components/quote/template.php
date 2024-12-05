<?php include 'variables.php'; ?>
<section
	id="<?php echo $id_homepage_scroll; ?>"
	class=" sectionSpied 
		quote
		flex
		items-center
		justify-center
		py-28
		md:py-[240px]
	"
	>
	<div class="quote-background"></div>
    <div class="
		tw-container
		text-white
		mx-auto
		z-10
		flex
		flex-col
		items-center
	">
        <h1 class="quote-title fadeIn tw-h1 text-center font-bold leading-[40px] xl:leading-[80px] ">
			<?= $title; ?>
			<br>
			<span class="quote-subtitle text-center font-[100] font-overpass">
				<?= $subtitle; ?>
			<span>
		</h1>
        <div class="quote-text fadeIn relative text-center font-light xl:leading-[36px] italic font-overpass mt-14">
			<div class="tw-container mx-auto">
				<?= $text; ?>
			</div>
		</div>
    </div>
</section>

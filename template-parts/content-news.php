<?php

/* ── Content Part: News Item ── */

?>


<div class="news-item">
	<a class="img" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>

		<?php the_post_thumbnail('large', array('class' => 'lozad')); ?>

		<?php else : ?>

		<img class="lozad" src="<?php echo get_template_directory_uri(); ?>/img/1.jpg" alt="<?php the_title(); ?>" />

		<?php endif; ?>
	</a>
	<div class="content">
		<div class="meta">
			<span class="date"><?php echo get_the_date('d.m.Y'); ?></span>
			<span class="category"><?php echo get_the_category()[0]->name; ?></span>
		</div>
		<h3 class="title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<div class="desc"><?php echo wp_trim_words(get_the_excerpt(), 20); ?></div>
	</div>
</div>
<?php

/* ── About Banner ── */

$page_banner = get_field('page_banner');
$banners = array();
$alt_text = get_the_title();

// Handle multiple banners or single banner
if ( $page_banner ) {
	if ( is_array($page_banner) && isset($page_banner[0]) ) {
		// Multiple banners (Gallery field)
		foreach ( $page_banner as $banner ) {
			if ( isset($banner['url']) ) {
				$banners[] = array(
					'url' => $banner['url'],
					'alt' => isset($banner['alt']) ? $banner['alt'] : $alt_text
				);
			}
		}
	} elseif ( isset($page_banner['url']) ) {
		// Single banner (ACF image field)
		$banners[] = array(
			'url' => $page_banner['url'],
			'alt' => isset($page_banner['alt']) ? $page_banner['alt'] : $alt_text
		);
	}
}

// Fallback
if ( empty($banners) ) {
	$banners[] = array(
		'url' => get_template_directory_uri() . '/img/1.jpg',
		'alt' => 'About Banner'
	);
}

?>
<section class="page-banner-main banner-2">
	<div class="banner-slide" data-slide-count="<?php echo count($banners); ?>">
		<div class="swiper">
			<div class="swiper-wrapper">
				<?php foreach ( $banners as $banner ) : ?>
				<div class="swiper-slide">
					<div class="img-ratio pt-[calc(600/1920*100rem)]">
						<img class="lozad" data-src="<?php echo esc_url($banner['url']); ?>"
							alt="<?php echo esc_attr($banner['alt']); ?>">
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="slide-controls">
			<div class="btn-prev"><i class="fa-thin fa-chevron-left"></i></div>
			<div class="btn-next"><i class="fa-thin fa-chevron-right"></i></div>
		</div>
	</div>
</section>
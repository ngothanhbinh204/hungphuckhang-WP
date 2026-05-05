<?php
$queried_object = get_queried_object();
$id = '';

if ( is_category() || is_tax() || is_tag() ) {
	$id = $queried_object->taxonomy . '_' . $queried_object->term_id;
} else {
	$id = get_the_ID();
}

$banners = array();
$alt_text = is_archive() ? post_type_archive_title('', false) : get_the_title();

// Get local banners
$local_banners = get_field('banner_select_page', $id);
if ( $local_banners && is_array($local_banners) ) {
    foreach ( $local_banners as $banner_post ) {
        $banner_url = get_the_post_thumbnail_url($banner_post->ID, 'full');
        if ( $banner_url ) {
            $banners[] = array(
                'url' => $banner_url,
                'alt' => get_the_title($banner_post->ID)
            );
        }
    }
} elseif ( $local_banners && is_object($local_banners) ) {
    $banner_url = get_the_post_thumbnail_url($local_banners->ID, 'full');
    if ( $banner_url ) {
        $banners[] = array(
            'url' => $banner_url,
            'alt' => get_the_title($local_banners->ID)
        );
    }
}

// Fallback to global banner
if ( empty($banners) ) {
    $archive_banner_global = get_field('archive_product_banner', 'options');
    if ( $archive_banner_global ) {
        $banner_url = is_array($archive_banner_global) ? $archive_banner_global['url'] : $archive_banner_global;
        if ( $banner_url ) {
            $banners[] = array(
                'url' => $banner_url,
                'alt' => $alt_text
            );
        }
    }
}

// Final fallback
if ( empty($banners) ) {
    $banners[] = array(
        'url' => get_template_directory_uri() . '/img/1.jpg',
        'alt' => $alt_text
    );
}

$has_multiple = count($banners) > 1;
?>

<section class="page-banner-main banner-2">
	<?php if ( $has_multiple ) : ?>
	<div class="banner-slide">
		<div class="swiper">
			<div class="swiper-wrapper">
				<?php foreach ( $banners as $banner ) : ?>
				<div class="swiper-slide">
					<div class="img img-ratio pt-[calc(664/1920*100rem)]">
						<img class="lozad" data-src="<?php echo esc_url($banner['url']); ?>"
							alt="<?php echo esc_attr($banner['alt']); ?>" />
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
	<?php else : ?>
	<div class="img img-ratio pt-[calc(664/1920*100rem)]">
		<img class="lozad" data-src="<?php echo esc_url($banners[0]['url']); ?>"
			alt="<?php echo esc_attr($banners[0]['alt']); ?>" />
	</div>
	<?php endif; ?>
</section>
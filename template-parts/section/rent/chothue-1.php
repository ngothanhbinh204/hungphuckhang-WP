<?php
/* ── Section 1: Hero Banner (Cho Thuê Page) ── */
// Lấy dữ liệu từ trang sử dụng template-rent.php để dùng chung ở nhiều trang
$rent_page_id = get_page_id_by_template('templates/template-rent.php') ?: get_the_ID();

// Lấy danh sách repeater banner
$rent_hero_banners = get_field('rent_hero_banners', $rent_page_id);
?>
<section class="page-banner-main chothue-1">
	<div class="banner-slide" data-slide-count="<?php echo count($rent_hero_banners); ?>">
		<div class="swiper">
			<div class="swiper-wrapper">
				<?php 
				if ( $rent_hero_banners ) :
					foreach ( $rent_hero_banners as $banner ) : 
						$rent_banner_type = isset($banner['rent_banner_type']) ? $banner['rent_banner_type'] : 'default';
						
						if ( $rent_banner_type === 'normal' ) : 
							$normal_image = isset($banner['rent_normal_image']) ? $banner['rent_normal_image'] : null;
							if ( $normal_image ) :
				?>
								<div class="swiper-slide list-item">
									<div class="slide-item">
										<a class="img-ratio ratio:pt-[800_1920] xl:ratio:pt-[600_1920]" href="javascript:void(0)">
											<img class="lozad" data-src="<?php echo esc_url($normal_image['url']); ?>" alt="<?php echo esc_attr($normal_image['alt'] ?: 'Banner'); ?>">
										</a>
									</div>
								</div>
				<?php 	
							endif;
						else : 
							// Default banner type
							$rent_hero_title = isset($banner['rent_hero_title']) ? $banner['rent_hero_title'] : '';
							$rent_hero_desc  = isset($banner['rent_hero_desc']) ? $banner['rent_hero_desc'] : '';
							$rent_hero_bg    = isset($banner['rent_hero_bg']) ? $banner['rent_hero_bg'] : null;
							$rent_hero_img   = isset($banner['rent_hero_img']) ? $banner['rent_hero_img'] : null;
				?>
								<div class="swiper-slide list-item">
									<div class="slide-item">
										<div class="slide-bg">
											<a class="img-ratio ratio:pt-[800_1920] xl:ratio:pt-[600_1920]" href="javascript:void(0)">
												<?php if ( $rent_hero_bg ) : ?>
													<img class="lozad" data-src="<?php echo esc_url($rent_hero_bg['url']); ?>" alt="<?php echo esc_attr($rent_hero_bg['alt'] ?: 'Background'); ?>" />
												<?php else : ?>
													<img class="lozad" data-src="<?php echo get_template_directory_uri(); ?>/img/bg_banner_2.png" alt="Background" />
												<?php endif; ?>
											</a>
											<div class="slide-overlay"></div>
											<img class="layer-union" src="<?php echo get_template_directory_uri(); ?>/img/Union.svg" alt="Decoration">
										</div>
										<div class="slide-content">
											<div class="slide-decorations">
												<img class="deco-goctren" src="<?php echo get_template_directory_uri(); ?>/img/goctren.svg" alt="Decor">
												<img class="deco-gocduoi" src="<?php echo get_template_directory_uri(); ?>/img/gocduoi.svg" alt="Decor">
											</div>
											<div class="slide-inner">
												<div class="slide-info" data-aos="fade-right">
													<h2 class="slide-title"><?php echo esc_html($rent_hero_title); ?></h2>
													<p class="slide-desc"><?php echo esc_html($rent_hero_desc); ?></p>
												</div>
												<div class="slide-image" data-aos="fade-left">
													<?php if ( $rent_hero_img ) : ?>
														<img class="lozad" data-src="<?php echo esc_url($rent_hero_img['url']); ?>" alt="<?php echo esc_attr($rent_hero_img['alt'] ?: 'Product'); ?>" />
													<?php else : ?>
														<img class="lozad" data-src="<?php echo get_template_directory_uri(); ?>/img/product-banner.png" alt="Product" />
													<?php endif; ?>
												</div>
											</div>
										</div>
									</div>
								</div>
				<?php 
						endif;
					endforeach;
				else : 
					// Fallback if no banner is added yet
				?>
					<div class="swiper-slide list-item">
						<div class="slide-item">
							<div class="slide-bg">
								<a class="img-ratio ratio:pt-[800_1920] xl:ratio:pt-[600_1920]" href="javascript:void(0)">
									<img class="lozad" data-src="<?php echo get_template_directory_uri(); ?>/img/bg_banner_2.png" alt="Background" />
								</a>
								<div class="slide-overlay"></div>
								<img class="layer-union" src="<?php echo get_template_directory_uri(); ?>/img/Union.svg" alt="Decoration">
							</div>
							<div class="slide-content">
								<div class="slide-decorations">
									<img class="deco-goctren" src="<?php echo get_template_directory_uri(); ?>/img/goctren.svg" alt="Decor">
									<img class="deco-gocduoi" src="<?php echo get_template_directory_uri(); ?>/img/gocduoi.svg" alt="Decor">
								</div>
								<div class="slide-inner">
									<div class="slide-info" data-aos="fade-right">
										<h2 class="slide-title">Hưng Phúc Khang</h2>
										<p class="slide-desc">Đối tác phân phối & dịch vụ máy photocopy, máy in chuyên nghiệp</p>
									</div>
									<div class="slide-image" data-aos="fade-left">
										<img class="lozad" data-src="<?php echo get_template_directory_uri(); ?>/img/product-banner.png" alt="Product" />
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<div class="slide-controls">
			<div class="btn-prev"><i class="fa-thin fa-chevron-left"></i></div>
			<div class="btn-next"><i class="fa-thin fa-chevron-right"></i></div>
		</div>
	</div>
</section>

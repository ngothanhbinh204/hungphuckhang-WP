<?php
/**
 * The Template for displaying product archives
 *
 * Sử dụng bởi:
 *   - Page Template: template-product-list.php (Bán Máy)
 *   - Page Template: template-rent.php (Cho Thuê) → include qua chothue-product-list.php
 *   - Taxonomy: taxonomy-product_cat.php (lọc theo danh mục cụ thể)
 *
 * Biến context có thể truyền vào khi include:
 *   $is_included_in_page  (bool)   — đang được include vào một page, bỏ header/footer
 *   $is_load_more         (bool)   — dùng nút Xem thêm thay vì phân trang
 *   $is_ajax_filter       (bool)   — bật lọc AJAX
 *   $filter_base_url      (string) — URL gốc cho form filter & category links
 *   $product_title        (string) — tiêu đề mặc định khi chưa chọn danh mục
 *   $filter_product_type  (string|array) — slug(s) của product_type cần lọc.
 *                                          Truyền vào từ chothue-product-list.php (đọc từ ACF page_product_type).
 *                                          Nếu rỗng/không truyền, archive-product.php tự đọc ACF của page hiện tại.
 *                                          Ví dụ: 'cho-thue', 'ban-may', hoặc array('cho-thue','ban-may')
 */

if ( !isset($is_included_in_page) ) {
	get_header();
}

if ( !isset($is_included_in_page) ) {
	get_template_part('modules/common/banner');
	get_template_part('template-parts/section/global/breadcrumb');
}

// ── Context Flags ──────────────────────────────────────────────────────────────
$is_product_page_template = is_page_template('templates/template-product-list.php');
$is_ajax_filter           = isset($is_ajax_filter) ? $is_ajax_filter : true;
$ajax_class               = $is_ajax_filter ? 'ajax-filter-enabled' : '';

// Loại hình lọc: truyền từ context (chothue-product-list.php hay template-product-list.php)
// Hoặc lấy từ ACF field page_product_type trên page hiện tại
$current_page_id     = get_queried_object_id();
$filter_product_type = isset($filter_product_type) ? $filter_product_type : '';

// Nếu chưa được truyền vào, thử lấy từ ACF của page hiện tại (dùng cho template-product-list.php)
if ( empty($filter_product_type) && !is_tax() ) {
	$acf_type_ids = get_field('page_product_type', $current_page_id);
	if ( !empty($acf_type_ids) ) {
		$_slugs = array();
		foreach ( (array) $acf_type_ids as $_type_id ) {
			$_type_term = get_term( (int) $_type_id, 'product_type' );
			if ( $_type_term && !is_wp_error($_type_term) ) {
				$_slugs[] = $_type_term->slug;
			}
		}
		if ( !empty($_slugs) ) {
			$filter_product_type = count($_slugs) === 1 ? $_slugs[0] : $_slugs;
		}
	}
}

// ── URL Filter Params & Dynamic Min/Max Price (Tối ưu Performance) ───────────────────
// Sử dụng Transient API để cache kết quả (12 tiếng), giảm tải DB thay vì query mỗi lần load trang
$transient_key = 'cc_global_min_max_prices';
$price_bounds = get_transient($transient_key);

if ( false === $price_bounds ) {
	global $wpdb;
	// Tối ưu Query: 
	// 1. Tách 2 meta_key bằng UNION ALL để tối ưu Index lookup thay vì toán tử IN.
	// 2. Dùng REGEXP '^[0-9]+$' để loại rác, tránh lỗi CAST.
	$query = "
		SELECT MIN(price) AS min_p, MAX(price) AS max_p
		FROM (
			SELECT CAST(pm.meta_value AS UNSIGNED) AS price
			FROM {$wpdb->postmeta} pm 
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
			WHERE pm.meta_key = 'product_price' 
			  AND pm.meta_value REGEXP '^[0-9]+$'
			  AND p.post_type = 'product' AND p.post_status = 'publish'
			UNION ALL
			SELECT CAST(pm.meta_value AS UNSIGNED) AS price
			FROM {$wpdb->postmeta} pm 
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
			WHERE pm.meta_key = 'product_rent_price' 
			  AND pm.meta_value REGEXP '^[0-9]+$'
			  AND p.post_type = 'product' AND p.post_status = 'publish'
		) AS combined_prices
	";
	
	$price_query = $wpdb->get_row($query);
	
	$global_min_price = 0;
	$global_max_price = 500000000;
	
	if ( $price_query && $price_query->max_p ) {
		$global_min_price = intval($price_query->min_p);
		$global_max_price = intval($price_query->max_p);
	}
	
	// Lưu cache trong 12 giờ
	$price_bounds = array(
		'min' => $global_min_price,
		'max' => $global_max_price
	);
	set_transient($transient_key, $price_bounds, 12 * HOUR_IN_SECONDS);
} else {
	$global_min_price = $price_bounds['min'];
	$global_max_price = $price_bounds['max'];
}

// Làm tròn min xuống và max lên hàng triệu
$global_min_price = floor($global_min_price / 1000000) * 1000000;
$global_max_price = ceil($global_max_price / 1000000) * 1000000;
if ($global_min_price < 0) $global_min_price = 0;
if ($global_max_price <= 0) $global_max_price = 10000000; // fallback if no prices

$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : $global_min_price;
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : $global_max_price;
$url_cat   = isset($_GET['product_cat']) ? sanitize_text_field($_GET['product_cat']) : '';
$base_url  = isset($filter_base_url) ? $filter_base_url : '';

// ── Active Category ────────────────────────────────────────────────────────────
$current_term  = get_queried_object();
$active_cat_id = 0;
if ( is_tax('product_cat') ) {
	$active_cat_id = $current_term->term_id;
} elseif ( $url_cat ) {
	$term_by_slug = get_term_by('slug', $url_cat, 'product_cat');
	if ( $term_by_slug ) $active_cat_id = $term_by_slug->term_id;
}

// ── Sidebar Categories ─────────────────────────────────────────────────────────
// Lấy danh mục có sản phẩm thuộc loại hình đang active
// Nếu có filter_product_type: chỉ lấy danh mục có ít nhất 1 sản phẩm thuộc loại hình đó
// Nếu không: lấy tất cả danh mục có sản phẩm
if ( !empty($filter_product_type) ) {
	// Query nhanh để lấy post IDs thuộc loại hình này
	$type_posts = get_posts(array(
		'post_type'      => 'product',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $filter_product_type,
			),
		),
	));

	if ( !empty($type_posts) ) {
		$sidebar_cats = wp_get_object_terms($type_posts, 'product_cat', array(
			'orderby' => 'name',
			'order'   => 'ASC',
		));
		// Loại trùng
		$unique_cats = array();
		$seen_ids    = array();
		foreach ( $sidebar_cats as $cat ) {
			if ( !in_array($cat->term_id, $seen_ids) ) {
				$unique_cats[] = $cat;
				$seen_ids[]    = $cat->term_id;
			}
		}
		$terms = $unique_cats;
	} else {
		$terms = array();
	}
} else {
	// Không có filter loại hình → lấy tất cả danh mục có sản phẩm
	$terms = get_terms(array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	));
	if ( is_wp_error($terms) ) $terms = array();
}
?>
<section class="product-list <?php echo $ajax_class; ?>" id="product-list-section"
	data-ajax-filter="<?php echo $is_ajax_filter ? 'true' : 'false'; ?>"
	data-is-load-more="<?php echo (isset($is_load_more) && $is_load_more) ? 'true' : 'false'; ?>"
	data-page-id="<?php echo $current_page_id; ?>"
	data-product-type="<?php echo esc_attr( is_array($filter_product_type) ? implode(',', $filter_product_type) : $filter_product_type ); ?>">
	<div class="container-full">
		<div class="wrapper">
			<aside class="sidebar">
				<div class="side-box sticky-sidebar">
					<div class="box-category">
						<div class="box-title"><?php _e('DANH MỤC SẢN PHẨM', 'canhcamtheme'); ?></div>
						<ul class="category-list">
							<?php
							if ( $terms && !is_wp_error($terms) ) :
								foreach ( $terms as $term ) :
									$active_class = ( $active_cat_id === $term->term_id ) ? 'active' : '';
									$link         = $base_url
										? add_query_arg('product_cat', $term->slug, $base_url)
										: get_term_link($term);
									$link_class   = $is_ajax_filter ? 'ajax-cat-link' : '';
							?>
								<li class="<?php echo $active_class; ?>">
									<a href="<?php echo esc_url($link); ?>" class="<?php echo $link_class; ?>" data-slug="<?php echo $term->slug; ?>">
										<?php echo esc_html($term->name); ?>
									</a>
								</li>
							<?php
								endforeach;
							endif;
							?>
						</ul>
					</div>
					<div class="box-price">
						<div class="box-title"><?php _e('GIÁ SẢN PHẨM', 'canhcamtheme'); ?></div>
						<div class="price-range">
							<div id="price-slider"
								 data-min="<?php echo $global_min_price; ?>"
								 data-max="<?php echo $global_max_price; ?>"
								 data-start-min="<?php echo $min_price; ?>"
								 data-start-max="<?php echo $max_price; ?>">
							</div>
							<div class="range-info">
								<span id="price-min-label"><?php echo number_format($global_min_price, 0, ',', '.'); ?>đ</span>
								<span id="price-max-label"><?php echo number_format($global_max_price, 0, ',', '.'); ?>đ</span>
							</div>
							<form id="price-filter-form" method="GET" action="<?php echo esc_url($base_url); ?>">
								<input type="hidden" name="orderby"     id="input-orderby"      value="<?php echo isset($_GET['orderby']) ? esc_attr($_GET['orderby']) : ''; ?>">
								<input type="hidden" name="product_cat" id="input-product-cat"  value="<?php echo esc_attr($url_cat); ?>">
								<input type="hidden" name="min_price"   id="input-min-price"    value="<?php echo $min_price; ?>">
								<input type="hidden" name="max_price"   id="input-max-price"    value="<?php echo $max_price; ?>">
							</form>
						</div>
					</div>
				</div>
			</aside>
			<main class="main-content">
				<div class="content-header">
					<?php
					$custom_prefix = get_field('page_product_prefix', $current_page_id);
					$prefix        = $custom_prefix ? esc_html($custom_prefix) . ' ' : '';
					?>
					<h1 class="title" id="archive-title"
						data-default-title="<?php echo isset($product_title) ? esc_attr($product_title) : __('Sản phẩm', 'canhcamtheme'); ?>"
						data-prefix="<?php echo esc_attr($prefix); ?>">
						<span class="text"><?php
						if ( is_tax() ) {
							echo $prefix . single_term_title('', false);
						} elseif ( $active_cat_id ) {
							$act_term = get_term($active_cat_id);
							echo $prefix . esc_html($act_term->name);
						} else {
							echo isset($product_title) ? esc_html($product_title) : __('Sản phẩm', 'canhcamtheme');
						}
						?></span>
					</h1>
					<button class="btn btn-primary btn-remove-filter lg:ml-auto">
						<i class="fa-light fa-filter-slash"></i>
						<?php _e('Hủy Lọc', 'canhcamtheme'); ?>
					</button>
					<div class="sort-block">
						<span><?php _e('SẮP XẾP', 'canhcamtheme'); ?></span>
						<?php
						$onchange      = $is_ajax_filter ? '' : 'onchange="location = this.value;"';
						$select_class  = $is_ajax_filter ? 'ajax-sort-select' : '';
						$current_orderby = isset($_GET['orderby']) ? $_GET['orderby'] : '';
						?>
						<select name="sort" class="<?php echo $select_class; ?>" <?php echo $onchange; ?>>
							<option value="all"       <?php selected($current_orderby, '');           ?>><?php _e('Tất cả',           'canhcamtheme'); ?></option>
							<option value="date"      <?php selected($current_orderby, 'date');       ?>><?php _e('Mới nhất',         'canhcamtheme'); ?></option>
							<option value="price"     <?php selected($current_orderby, 'price');      ?>><?php _e('Giá thấp đến cao', 'canhcamtheme'); ?></option>
							<option value="price-desc"<?php selected($current_orderby, 'price-desc'); ?>><?php _e('Giá cao đến thấp', 'canhcamtheme'); ?></option>
						</select>
					</div>
				</div>

				<div class="product-refresh-wrapper" style="position: relative; min-height: 200px;">
					<div id="ajax-loader" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 10; align-items: center; justify-content: center;">
						<i class="fa-light fa-spinner-third fa-spin spinner-i"></i>
					</div>

					<div class="product-grid" id="product-grid-container">
					<?php
					// ── Build Query ──────────────────────────────────────────────────────────
					$meta_query = array('relation' => 'AND');
					if ( isset($_GET['min_price']) || isset($_GET['max_price']) ) {
						$meta_query[] = array(
							'key'     => 'product_price',
							'value'   => array($min_price, $max_price),
							'type'    => 'NUMERIC',
							'compare' => 'BETWEEN',
						);
					}

					$tax_query = array('relation' => 'AND');

					// 1. Loại hình: lọc theo product_type trực tiếp
					if ( !empty($filter_product_type) ) {
						$tax_query[] = array(
							'taxonomy' => 'product_type',
							'field'    => 'slug',
							'terms'    => $filter_product_type,
						);
					}

					// 2. Danh mục: lọc theo URL param hoặc taxonomy archive
					if ( $url_cat ) {
						$tax_query[] = array(
							'taxonomy' => 'product_cat',
							'field'    => 'slug',
							'terms'    => $url_cat,
						);
					} elseif ( is_tax('product_cat') && !isset($is_included_in_page) ) {
						// Trên taxonomy archive: WP đã handle, chỉ dùng khi cần WP_Query riêng
					}

					if ( ($is_product_page_template || isset($is_included_in_page)) && !is_tax() ) {
						// ── Custom Page / Included Query ────────────────────────────────────
						$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
						$args  = array(
							'post_type'      => 'product',
							'posts_per_page' => PerpageProduct,
							'paged'          => $paged,
							'meta_query'     => $meta_query,
						);

						if ( count($tax_query) > 1 ) {
							$args['tax_query'] = $tax_query;
						}

						// Sort
						$orderby = isset($_GET['orderby']) ? $_GET['orderby'] : '';
						if ( $orderby === 'price' ) {
							$args['meta_key'] = 'product_price';
							$args['orderby']  = 'meta_value_num';
							$args['order']    = 'ASC';
						} elseif ( $orderby === 'price-desc' ) {
							$args['meta_key'] = 'product_price';
							$args['orderby']  = 'meta_value_num';
							$args['order']    = 'DESC';
						}

						$temp_query = new WP_Query($args);
						if ( $temp_query->have_posts() ) :
							while ( $temp_query->have_posts() ) : $temp_query->the_post(); ?>
								<div class="product-column">
									<?php get_template_part('template-parts/content', 'product'); ?>
								</div>
							<?php endwhile;
							$max_pages = $temp_query->max_num_pages;
							wp_reset_postdata();
						else : ?>
							<p><?php _e('Không tìm thấy sản phẩm nào trong tầm giá này.', 'canhcamtheme'); ?></p>
						<?php endif;

					} else {
						// ── Standard Archive (taxonomy-product_cat.php) ─────────────────────
						if ( have_posts() ) :
							while ( have_posts() ) : the_post(); ?>
								<div class="product-column">
									<?php get_template_part('template-parts/content', 'product'); ?>
								</div>
							<?php endwhile;
						else : ?>
							<p><?php _e('Không tìm thấy sản phẩm nào.', 'canhcamtheme'); ?></p>
						<?php endif;
					}
					?>
					</div><!-- /#product-grid-container -->

					<div class="pagination-wrapper" id="product-pagination-container">
					<?php
					if ( isset($is_load_more) && $is_load_more ) {
						if ( isset($max_pages) && $max_pages > 1 ) : ?>
						<div class="load-more-container text-center mt-8 flex justify-center gap-4">
							<button id="load-more-products"
									data-current-page="1"
									data-max-pages="<?php echo $max_pages; ?>"
									data-post-type="product"
									data-product-cat="<?php echo esc_attr($url_cat); ?>"
									data-product-type="<?php echo esc_attr($filter_product_type); ?>"
									data-min-price="<?php echo $min_price; ?>"
									data-max-price="<?php echo $max_price; ?>"
									class="btn btn-primary btn-load-more">
								<?php _e('Xem thêm', 'canhcamtheme'); ?>
								<i class="fa-light fa-chevron-down"></i>
							</button>
							<button id="show-less-products" class="btn btn-primary btn-show-less" style="display: none;">
								<?php _e('Thu gọn', 'canhcamtheme'); ?>
								<i class="fa-light fa-chevron-up"></i>
							</button>
						</div>
						<?php endif;

					} elseif ( isset($is_product_page_template) && $is_product_page_template && !is_tax() ) {
						$paginate_args = array(
							'total'     => isset($temp_query) ? $temp_query->max_num_pages : 1,
							'prev_text' => '<i class="fa-light fa-chevron-left"></i>',
							'next_text' => '<i class="fa-light fa-chevron-right"></i>',
							'type'      => 'list',
						);
						if ( isset($_GET['product_cat']) || isset($_GET['min_price']) || isset($_GET['max_price']) || isset($_GET['orderby']) ) {
							$paginate_args['add_args'] = array(
								'product_cat' => isset($_GET['product_cat']) ? $_GET['product_cat'] : '',
								'min_price'   => isset($_GET['min_price'])   ? $_GET['min_price']   : '',
								'max_price'   => isset($_GET['max_price'])   ? $_GET['max_price']   : '',
								'orderby'     => isset($_GET['orderby'])     ? $_GET['orderby']     : '',
							);
						}
						echo paginate_links($paginate_args);

					} else {
						the_posts_pagination(array(
							'prev_text' => '<i class="fa-light fa-chevron-left"></i>',
							'next_text' => '<i class="fa-light fa-chevron-right"></i>',
							'type'      => 'list',
						));
					}
					?>
					</div><!-- /#product-pagination-container -->
				</div><!-- /.product-refresh-wrapper -->
			</main>
		</div>
	</div>
</section>

<?php
if ( !isset($is_included_in_page) ) {
	get_template_part('template-parts/section/home/home-contact');
	get_footer();
}

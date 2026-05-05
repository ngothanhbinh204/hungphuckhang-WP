<?php
/**
 * Section: Danh sách sản phẩm (Cho Thuê Page)
 *
 * Đọc Loại Hình hiển thị từ ACF field `page_product_type` của Page hiện tại.
 * Editor vào sidebar trang "Cho Thuê Máy" → chọn đúng Loại Hình → danh sách
 * sản phẩm sẽ tự động lọc theo lựa chọn đó, không hardcode bất kỳ slug nào.
 *
 * Compatible với WPML: get_field() trả đúng giá trị theo ngôn ngữ hiện tại.
 */

// Cấu hình context cho archive-product.php
$is_included_in_page = true;
$is_load_more        = true;   // Dùng nút "Xem thêm" thay vì phân trang số
$is_ajax_filter      = true;   // AJAX filter — không reload trang
$filter_base_url     = get_permalink();
$product_title       = get_field('rent_list_title') ?: __('Sản phẩm cho thuê', 'canhcamtheme');

/**
 * Đọc Loại Hình từ ACF field của Page hiện tại.
 * Field: page_product_type (taxonomy field, return_format = id, multi_select)
 * → Trả về array of term IDs, hoặc false nếu chưa chọn.
 *
 * Convert sang array of slugs để truyền vào archive-product.php.
 * WP_Query tax_query hỗ trợ cả string lẫn array cho 'terms'.
 */
$current_page_id      = get_queried_object_id();
$acf_product_type_ids = get_field('page_product_type', $current_page_id);
$filter_product_type  = array(); // Mặc định: không lọc loại hình (hiện tất cả)

if ( !empty($acf_product_type_ids) ) {
	foreach ( (array) $acf_product_type_ids as $type_id ) {
		$type_term = get_term( (int) $type_id, 'product_type' );
		if ( $type_term && !is_wp_error($type_term) ) {
			$filter_product_type[] = $type_term->slug;
		}
	}
}

// Nếu chỉ 1 loại hình, dùng string; nhiều loại thì để array
// WP_Query chấp nhận cả 2 dạng, data attribute sẽ dùng comma-separated
if ( count($filter_product_type) === 1 ) {
	$filter_product_type = $filter_product_type[0];
}

include get_template_directory() . '/archive-product.php';

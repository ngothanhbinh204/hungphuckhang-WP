<?php
/**
 * The Template for displaying product category archives
 *
 * Khi duyệt theo danh mục (/danh-muc-san-pham/ricoh/ chẳng hạn),
 * hiển thị tất cả sản phẩm của danh mục đó bất kể loại hình.
 * Không truyền $filter_product_type → archive-product.php hiển thị tất cả loại hình.
 */

// Không set $filter_product_type → hiển thị tất cả sản phẩm trong danh mục này
// (người dùng đã chọn cụ thể 1 danh mục, không cần lọc thêm loại hình)
include get_template_directory() . '/archive-product.php';

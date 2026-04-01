<?php
/**
 * Template Name: Home Page
 */

get_header();

/* ── MAPPING index.html → template-home.php ── */

// Section 1: Hero Slider
if (get_field('show_section_1') !== false) {
	get_template_part('template-parts/section/home/home-1');
}

// Section 2: Về chúng tôi (Sliding Titles)
if (get_field('show_section_2') !== false) {
	get_template_part('template-parts/section/home/home-2');
}

// Section 3: Counter
if (get_field('show_section_3') !== false) {
	get_template_part('template-parts/section/home/home-3');
}

// Section 4: Sản phẩm đang giảm giá (Tabs)
if (get_field('show_section_4') !== false) {
	get_template_part('template-parts/section/home/home-4');
}

// Section 5: Dịch vụ của chúng tôi (Accordion)
if (get_field('show_section_5') !== false) {
	get_template_part('template-parts/section/home/home-5');
}

// Section 6: Cam kết chất lượng (Reusable)
if (get_field('show_section_6') !== false) {
	get_template_part('template-parts/section/home/home-6');
}

// Section 7: Đối tác khách hàng
if (get_field('show_section_7') !== false) {
	get_template_part('template-parts/section/home/home-7');
}

// Section 8: Tin tức nổi bật
if (get_field('show_section_8') !== false) {
	get_template_part('template-parts/section/home/home-8');
}

// Section 9: Liên hệ tư vấn (Reusable)
if (get_field('show_section_9') !== false) {
	get_template_part('template-parts/section/home/home-contact');
}

get_footer();

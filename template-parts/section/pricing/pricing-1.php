<?php

$pricing_page_id = get_page_id_by_template('templates/template-pricing.php') ?: get_the_ID();

$pricing_title = get_field('rent_pricing_title', $pricing_page_id) ?: '';
$pricing_sub_title = get_field('rent_pricing_sub_title', $pricing_page_id) ?: '';

$pricing_plans = get_field('rent_pricing_plans', $pricing_page_id); // Repeater

?>

<section class="chothue-4">

	<div class="container">

		<div class="section-header" data-aos="fade-up">

			<h2 class="section-title"><?php echo esc_html($pricing_title); ?></h2>
			<div class="body-2 my-2"><?php echo wp_kses_post($pricing_sub_title); ?></div>

		</div>

		<div class="pricing-grid">

			<?php if ( $pricing_plans ) : ?>

			<?php $delay = 0; foreach ( $pricing_plans as $plan ) : ?>

			<div class="pricing-col" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">

				<div class="pricing-card">

					<?php if ( $plan['image'] ) : ?>

					<div class="card-image">

						<img src="<?php echo esc_url($plan['image']['url']); ?>"
							alt="<?php echo esc_attr($plan['image']['alt']); ?>">

					</div>

					<?php endif; ?>

					<div class="card-body">

						<div class="body-2"><?php echo wp_kses_post($plan['description']); ?></div>

					</div>

				</div>

			</div>

			<?php $delay += 100; endforeach; ?>

			<?php endif; ?>

		</div>

	</div>

</section>
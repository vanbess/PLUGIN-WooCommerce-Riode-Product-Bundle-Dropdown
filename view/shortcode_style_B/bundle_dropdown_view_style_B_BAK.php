<?php
global $woocommerce;

$cart_items            = $woocommerce->cart->get_cart();
$currency              = get_woocommerce_currency_symbol();
$package_product_ids   = self::$package_product_ids;
$package_number_item_2 = self::$package_number_item_2;

if (!empty($package_product_ids)) { ?>

	<div class="bd_items_div bd_package_items_div i_clearfix theme_color_<?= self::$package_theme_color ?> bd_select_wrap" id="bd_checkout">

		<ul class="list default_option">
			<li>
				Choose your bundle
			</li>
		</ul>

		<ul class="list bd_select_ul">
			<?php

			$product_count = count($package_product_ids);

			// current currency
			$current_curr = get_woocommerce_currency();

			$p_i = 0;

			// create array variations data
			$var_data = BD::$bd_product_variations;

			// create array variation custom price
			$variation_price = [];

			// loop
			foreach ($package_product_ids as $opt_i => $prod) {

				$i_title                = '';
				$cus_bundle_total_price = 0;

				/**
				 * Setup titles, product ids, calculate prices et al
				 */

				// type free
				if ($prod['type'] == 'free') :

					// product id
					$p_id = (int)$prod['id'];

					// bundle title
					if ((int)$prod['qty_free'] == 0) :
						$i_title = sprintf(__('Buy %s', 'woocommerce', 'bd'), (int)$prod['qty']);
					else :
						$i_title = sprintf(__('Buy %s + Get %d FREE', 'bd'), (int)$prod['qty'], (int)$prod['qty_free']);
					endif;

					// pricing
					$i_total_qty    = (int)$prod['qty'] + (int)$prod['qty_free'];
					$i_price        = ($product_price * (int)$prod['qty']) / $i_total_qty;
					$i_price_total  = $i_price * $i_total_qty;
					$price_discount = ($product_price * $i_total_qty) - $i_price_total;
					$i_coupon        = ((int)$prod['qty_free'] * 100) / $i_total_qty;

					// js input data package
					$js_discount_type  = 'free';
					$js_discount_qty   = (int)$prod['qty_free'];
					$js_discount_value = (int)$prod['id_free'];

				endif;

				// type off
				if ($prod['type'] == 'off') :

					// product id
					$p_id = (int)$prod['id'];

					// bundle title
					$i_title = sprintf(__('Buy %s + Get %d&#37;', 'bd'), (int)$prod['qty'], (float)$prod['coupon']) . ' ' . __('Off', 'bd');

					// pricing
					$i_total_qty    = (int)$prod['qty'];
					$i_tt           = $product_price * $prod['qty'];
					$i_coupon        = (float)$prod['coupon'];
					$i_price        = ((float)$product_price - ((float)$product_price * (float)$i_coupon / 100));
					$i_price_total  = $i_price * (int)$prod['qty'];
					$price_discount = $i_tt - $i_price_total;

					// js input data package
					$js_discount_type  = 'percentage';
					$js_discount_qty   = 1;
					$js_discount_value = (float)$prod['coupon'];

				endif;

				// type bun
				if ($prod['type'] == 'bun') :

					// product id
					$p_id = (int)$prod['prod'][0]['id'];

					// bundle title
					$i_title = $prod['title_header'] ?: __('Bundle option', 'bd');

					// pricing
					$i_total_qty    = count($prod['prod']);
					$i_price        = (float)$prod['total_price'];
					$i_out_of_stock = false;

					// js input data package
					$js_discount_type = 'percentage';
					$js_discount_qty = 1;
					$js_discount_value = (float)$prod['discount_percentage'];

					$sum_price_regular = 0;
					$total_price_bun   = 0;

					foreach ($prod['prod'] as $i => $i_prod) {

						$p_bun = wc_get_product($i_prod['id']);

						if ('outofstock' === $p_bun->get_stock_status()) {
							$i_out_of_stock = true;
						}

						if ($p_bun->is_type('variable')) {
							$sum_price_regular += (float)$p_bun->get_variation_regular_price('min') * (int)$i_prod['qty'];
							$total_price_bun   += (float)$p_bun->get_variation_sale_price('min') * (int)$i_prod['qty'];
						} else {
							$sum_price_regular += (float)$p_bun->get_regular_price() * (int)$i_prod['qty'];
							$total_price_bun   += (float)$p_bun->get_sale_price() * (int)$i_prod['qty'];
						}
					}

					// discount percent
					$i_coupon = (float)$prod['discount_percentage'];

					// get price total bundle
					if ($i_price) {
						$sum_price_regular      = $i_price;
						$cus_bundle_total_price = $i_price;
					}

					$subtotal_bundle = $sum_price_regular;

					// apply discount percentage
					if ($prod['discount_percentage'] > 0) {
						$subtotal_bundle -= ($subtotal_bundle * $i_coupon / 100);
					}

					$price_discount = $sum_price_regular - $subtotal_bundle;

				endif;

				$product            = wc_get_product($p_id);
				$product_separate   = 1;
				$product_title      = $prod['title_package'] ?: $product->get_title();
				$product_name       = $prod['product_name'] ?: $product->get_title();
				$product_price_html = $product->get_price_html();

				if ($product->is_type('variable')) {
					$product_price = (float)$product->get_variation_regular_price('min');
				} else {
					$product_price = (float)$product->get_regular_price();
				}

				// bd product option has custom price
				if ($prod['custom_price'] && current($prod['custom_price'])[$current_curr]) {
					$product_price      = (float)current($prod['custom_price'])[$current_curr];
					$product_price_html = wc_price($product_price);
				}

				// get bd price variation
				if ($product->is_type('variable')) {
					foreach ($product->get_available_variations() as $key => $var_data) {

						$variation_price[$prod['bun_id']][$var_data['variation_id']]['variation_id'] = $var_data['variation_id'];

						if ($prod['custom_price'] && isset($prod['custom_price'][$var_data['variation_id']][$current_curr])) {
							$variation_price[$prod['bun_id']][$var_data['variation_id']]['price'] = $prod['custom_price'][$var_data['variation_id']][$current_curr];
						} else {
							$variation_price[$prod['bun_id']][$var_data['variation_id']]['price'] = $var_data['display_regular_price'];
						}
					}
				}

				// get price reg, sale product of package free, off
				if ($product->is_type('variable')) {
					$product_regular_price = (float)$product->get_variation_regular_price('min');
					$product_sale_price    = (float)$product->get_variation_sale_price('min');
				} else {
					$product_regular_price = (float)$product->get_regular_price();
					$product_sale_price    = (float)$product->get_sale_price();
				}

				// free and off product bundles
				if ($prod['type'] == 'free' || $prod['type'] == 'off') { ?>

					<li class="item-selection col-hover-focus bd_item_div bd_item_div_<?php echo ($p_id) ?> bd_c_package_option <?= (self::$package_default_id == $prod['bun_id']) ? 'bd_selected_default_opt' : '' ?>" data-type="<?php echo ($prod['type']) ?>" data-bundle_id="<?php echo ($prod['bun_id']) ?>" data-coupon="<?= round((float)$i_coupon, 0) ?>">
					<?php
				} else { ?>

					<li class="item-selection col-hover-focus bd_item_div bd_item_div_<?php echo ($prod['bun_id']) ?> bd_c_package_option <?= (self::$package_default_id == $prod['bun_id']) ? 'bd_selected_default_opt' : '' ?>" data-type="<?php echo ($prod['type']) ?>" data-bundle_id="<?php echo ($prod['bun_id']) ?>" data-coupon="<?= round((float)$i_coupon, 0) ?>">
					<?php
				} ?>

					<!-- js input hidden data package -->
					<input type="hidden" class="js-input-discount_package" data-type="<?php echo $js_discount_type ?>" data-qty="<?php echo $js_discount_qty ?>" value="<?php echo $js_discount_value ?>">
					<input type="hidden" class="js-input-cus_bundle_total_price" value="<?php echo $cus_bundle_total_price ?>">

					<!-- results -->
					<input type="hidden" class="js-input-price_package" value="">
					<input type="hidden" class="js-input-price_summary" value="">

					<div class="col-inner box-shadow-2 box-shadow-3-hover box-item">
						<div class="bd_item_infos_div bd_collapser_inner i_row i_clearfix">

							<div class="bd_c_package_content">
								<div class="bd_c_package_title d-block">
									<div class="pi-1"><?php echo $i_title ?></div>
									<?php
									if ($prod['type'] == 'free' || $prod['type'] == 'off') {
									?>
										<input type="checkbox" name="bd_selected_package_product" data-product_id="<?php echo ($p_id) ?>" data-index="<?php echo ($opt_i) ?>" value="<?php echo ($p_id) ?>" class="d-none bd_selected_package_product product_id">
									<?php
									} else {
									?>
										<input type="checkbox" name="bd_selected_package_product" data-product_id="<?php echo ($prod['bun_id']) ?>" data-index="<?php echo ($opt_i) ?>" value="<?php echo ($prod['bun_id']) ?>" class="d-none bd_selected_package_product product_id">
									<?php } ?>

									<span class="show_discount_label"><?php echo (sprintf(__('%s&#37; OFF', 'bd'), round((float)$i_coupon, 0))) ?></span>
								</div>

								<div class="bd_c_package_info text-right">
									<?php if ($prod['type'] == 'bun') { // bundle option
									?>
										<div class="pi-price-pricing">
											<div class="pi-price-each pl-lg-1">
												<span class="js-label-price_total"><?php echo wc_price($subtotal_bundle); ?></span>
											</div>
										</div>
										<div class="pi-price-regular-total">
											<span class="js-label-price_total"><?php echo wc_price($sum_price_regular); ?></span>
										</div>
										<!-- get prices bundle -->
										<input type="hidden" class="bd_bundle_price_hidden" data-label="<?= $i_title ?>" value="<?= $subtotal_bundle ?>">
										<input type="hidden" class="bd_bundle_price_regular_hidden" data-label="<?= __('Old Price', 'bd') ?>" value="<?= $sum_price_regular ?>">
										<input type="hidden" class="bd_bundle_price_sale_hidden" data-label="<?= __('Old Price', 'bd') ?>" value="<?= $sum_price_regular ?>">
										<input type="hidden" class="bd_bundle_product_qty_hidden" value="1">
									<?php
									} else { // free and off option
									?>
										<div class="pi-price-pricing">
											<div class="pi-price-each pl-lg-1">
												<span><?php echo wc_price($i_price); ?></span>
												<span class="pi-price-each-txt">/ <?php echo __('each', 'bd'); ?></span>
											</div>

										</div>
										<div class="pi-price-regular-total">
											<span><?php echo wc_price($product_price); ?></span>
										</div>

										<!-- get prices bundle -->
										<input type="hidden" class="mwc_bundle_price_hidden" data-label="<?= $i_title ?>" value="<?= $i_price_total ?>">
										<input type="hidden" class="mwc_bundle_price_regular_hidden" data-label="<?= __('Old Price', 'bd') ?>" value="<?= $product_regular_price ?>">
										<input type="hidden" class="mwc_bundle_price_sale_hidden" data-label="<?= __('Old Price', 'bd') ?>" value="<?= $product_sale_price ?>">
										<input type="hidden" class="mwc_bundle_product_qty_hidden" value="<?= $i_total_qty ?>">
									<?php
									} ?>
								</div> <!-- end bd_c_package_info -->
							</div> <!-- end bd_c_package_content -->

						</div> <!-- end bd_item_infos_div -->
					</div>

					<!-- Product variations form ------------------------------>
					<div class="bd_product_variations d-none info_products_checkout <?= (($prod['type'] == 'free' || $prod['type'] == 'off') && $product->is_type('variable')) ? 'is_variable' : '' ?>">
						<table class="product_variations_table">
							<tbody>
								<?php
								//package selection variations free and off
								if ($prod['type'] == 'free' || $prod['type'] == 'off') {

									// get variation images product
									if (!isset($var_data[$p_id]) && $product->is_type('variable')) {

										$var_arr = [];

										foreach ($product->get_available_variations() as $key => $var_data) {
											array_push($var_arr, [
												'id'         => (int)$var_data['variation_id'],
												'price'      => (float)$prod['custom_price'][$var_data['variation_id']][$current_curr],
												'attributes' => $var_data['attributes'],
												'image'      => $var_data['image']['url']
											]);
										}

										$var_data[$p_id] = $var_arr;
									}
								?>

									<?php
									for ($i = 0; $i < $prod['qty']; $i++) {
									?>
										<tr class="c_prod_item" data-id="<?php echo ($p_id) ?>" <?= (!$product->is_type('variable')) ? 'hidden' : '' ?>>
											<?php if ($product->is_type('variable')) {
											?>
												<td class="variation_img">
													<img class="bd_variation_img" src="<?= wp_get_attachment_image_src($product->get_image_id())[0] ?>">
												</td>
												<td class="variation_selectors">
													<?php

													// show variations linked by variations
													echo BD::return_bd_linked_variations_dropdown([
														'product_id'		=> $p_id,
														'class' 			=> 'var_prod_attr bundle_dropdown_attr',
													], $var_data);

													$prod_variations = $product->get_variation_attributes();

													foreach ($prod_variations as $attribute_name => $options) {
														// $default_opt = $product->get_variation_default_attribute($attribute_name);
														$default_opt = '';
														try {
															$default_opt =  $product->get_default_attributes()[$attribute_name];
														} catch (\Throwable $th) {
														}
													?>

														<div class="variation_item">
															<p class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </p>

															<!-- load dropdown variations -->
															<?php
															echo BD::return_bd_onepage_checkout_variation_dropdown([
																'product_id'		=> $p_id,
																'options' 			=> $options,
																'attribute_name'	=> $attribute_name,
																'default_option'	=> $default_opt,
																'var_data'			=> $var_data[$p_id],
																'class' 			=> 'var_prod_attr bundle_dropdown_attr',
															]);
															?>

														</div>
													<?php
													}
													?>
												</td>
											<?php
											}
											?>
										</tr>
										<?php
									}
								} else { //package selection bundle
									$_index = 1;
									foreach ($prod['prod'] as $i => $i_prod) {

										$p_id = (int)$i_prod['id'];
										$b_product = wc_get_product($p_id);

										// get variation images product
										if (!isset($var_data[$p_id]) && $b_product->is_type('variable')) {

											$var_arr = [];

											foreach ($b_product->get_available_variations() as $key => $var_data) {
												array_push($var_arr, [
													'id'         => (int)$var_data['variation_id'],
													'price'      => (float)$prod['custom_price'][$var_data['variation_id']][$current_curr],
													'attributes' => $var_data['attributes'],
													'image'      => $var_data['image']['url']
												]);
											}
											$var_data[$p_id] = $var_arr;
										}

										for ($i = 1; $i <= $i_prod['qty']; $i++) {
										?>
											<tr class="c_prod_item" data-id="<?php echo ($p_id) ?>" <?= (!$b_product->is_type('variable')) ? 'hidden' : '' ?>>
												<?php if ($b_product->is_type('variable')) {
												?>
													<td class="variation_img">
														<img id="prod_image" class="bd_variation_img" src="<?= wp_get_attachment_image_src($b_product->get_image_id())[0] ?>">
													</td>
													<td class="variation_selectors">
														<?php

														// show variations linked by variations
														echo BD::return_bd_linked_variations_dropdown([
															'product_id'		=> $p_id,
															'class' 			=> 'var_prod_attr bundle_dropdown_attr',
														], $var_data);

														$prod_variations = $b_product->get_variation_attributes();
														foreach ($prod_variations as $attribute_name => $options) {
															// $default_opt = $b_product->get_variation_default_attribute($attribute_name);
															$default_opt = '';
															try {
																$default_opt =  $b_product->get_default_attributes()[$attribute_name];
															} catch (\Throwable $th) {
																$default_opt = '';
															}
														?>
															<div class="variation_item">
																<p class="variation_name mb-0 mr-2"><?= wc_attribute_label($attribute_name) ?>: </p>

																<!-- load dropdown variations -->
																<?php
																echo BD::return_bd_onepage_checkout_variation_dropdown([
																	'product_id'		=> $p_id,
																	'options' 			=> $options,
																	'attribute_name'	=> $attribute_name,
																	'default_option'	=> $default_opt,
																	'var_data'			=> $var_data[$p_id],
																	'class' 			=> 'var_prod_attr bundle_dropdown_attr',
																]);
																?>

															</div>
														<?php
														}
														?>
													</td>
												<?php
												}
												?>
											</tr>
										<?php
										}
										?>
								<?php
									}
								}
								?>
							</tbody>
						</table>

						<!-- variations free products -->
						<?php
						if ($prod['type'] == 'free' && isset($prod['qty_free']) && $prod['qty_free'] > 0) {
						?>
							<h5 class="title_form"><?= __('Select Free Product', 'bundle_dropdown') ?>:</h5>
							<table class="product_variations_table">
								<tbody>
									<?php
									for ($i = 0; $i < $prod['qty_free']; $i++) {
									?>
										<tr class="c_prod_item" data-id="<?php echo ($p_id) ?>" <?= (!$product->is_type('variable')) ? 'hidden' : '' ?>>
											<?php if ($product->is_type('variable')) {
											?>
												<td class="variation_img">
													<img class="bd_variation_img" src="<?= wp_get_attachment_image_src($product->get_image_id())[0] ?>">
												</td>
												<td class="variation_selectors">
													<?php

													// show variations linked by variations
													echo BD::return_bd_linked_variations_dropdown([
														'product_id'		=> $p_id,
														'class' 			=> 'var_prod_attr bundle_dropdown_attr',
													], $var_data);

													$prod_variations = $product->get_variation_attributes();
													foreach ($prod_variations as $attribute_name => $options) {
														// $default_opt = $product->get_variation_default_attribute($attribute_name);
														$default_opt = '';
														try {
															$default_opt =  $product->get_default_attributes()[$attribute_name];
														} catch (\Throwable $th) {
															$default_opt = '';
														}
													?>
														<div class="variation_item">
															<p class="variation_name"><?= wc_attribute_label($attribute_name) ?>: </p>

															<!-- load dropdown variations -->
															<?php
															echo BD::return_bd_onepage_checkout_variation_dropdown([
																'product_id'		=> $p_id,
																'options' 			=> $options,
																'attribute_name'	=> $attribute_name,
																'default_option'	=> $default_opt,
																'var_data'			=> $var_data[$p_id],
																'class' 			=> 'var_prod_attr bundle_dropdown_attr',
															]);
															?>

														</div>
													<?php
													}
													?>
												</td>
											<?php
											}
											?>
										</tr>
									<?php
									}
									?>
								</tbody>
							</table>

						<?php
						}
						?>
					</div>
					<!-- end product variations form -->
					</li>
				<?php
				$p_i++;
			}
				?>

				<!-- </div>
			</div> -->

		</ul>
	</div>

	<script>
		var opc_variation_data = <?= json_encode($var_data) ?>;
		const bd_variation_price = <?= json_encode($variation_price) ?>;

		var bd_products_variations = <?php echo (json_encode(BD::$bd_products_variations)) ?>;
		var bd_products_variations_prices = <?php echo (json_encode(BD::$bd_products_variations_prices)) ?>;
	</script>

<?php
}

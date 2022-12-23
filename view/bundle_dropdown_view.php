<?php
global $woocommerce;

$cart_items            = $woocommerce->cart->get_cart();
$currency              = get_woocommerce_currency_symbol();
$package_product_ids   = self::$package_product_ids;
$package_number_item_2 = self::$package_number_item_2;
// $addon_product_ids = self::$addon_product_ids;

if (!empty($package_product_ids)) {
?>

	<div class="bd_items_div bd_package_items_div i_clearfix theme_color_<?= self::$package_theme_color ?>" id="bd_checkout">

		<?php

		// get product count
		$product_count = count($package_product_ids);

		// current currency
		$current_curr = get_woocommerce_currency();

		$p_i = 0;

		// create array variations data
		$var_data = BD::$bd_product_variations;

		// create array variation custom price
		$variation_price = [];

		foreach ($package_product_ids as $opt_i => $prod) {

			$i_title                = '';
			$cus_bundle_total_price = 0;

			/**
			 * Setup product ids and text to display
			 */

			// type free
			if ($prod['type'] == 'free') :

				$p_id = (int)$prod['id'];

				if ((int)$prod['qty_free'] === 0) :
					$i_title = sprintf(__('Buy %s', 'woocommerce', 'bd'), (int)$prod['qty']);
				else :
					$i_title = sprintf(__('Buy %s + Get %d FREE', 'bd'), (int)$prod['qty'], (int)$prod['qty_free']);
				endif;

			endif;

			// type off
			if ($prod['type'] == 'off') :
				$p_id = (int)$prod['id'];
				$i_title = sprintf(__('Buy %s + Get %d&#37;', 'bd'), (int)$prod['qty'], (int)$prod['coupon']) . ' ' . __('Off', 'bd');
			endif;

			// type bun
			if ($prod['type'] == 'bun') :
				$p_id = (int)$prod['prod'][0]['id'];
				$i_title = $prod['title_header'] ?: __('Bundle option', 'bd');
			endif;

			// set up package name/title
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
				$product_price = current($prod['custom_price'])[$current_curr];
				$product_price_html = wc_price($product_price);
			}

			// get bd price variation
			if ($product->is_type('variable')) {
				foreach ($product->get_available_variations() as $key => $value) {

					$variation_price[trim($prod['bun_id'])][$value['variation_id']]['variation_id'] = $value['variation_id'];

					if ($prod['custom_price'] && isset($prod['custom_price'][$value['variation_id']][$current_curr])) {
						$variation_price[trim($prod['bun_id'])][$value['variation_id']]['price'] = $prod['custom_price'][$value['variation_id']][$current_curr];
					} else {
						$variation_price[trim($prod['bun_id'])][$value['variation_id']]['price'] = $value['display_regular_price'];
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

			/**
			 * CALCULATE BUNDLE PRICES
			 */

			//  free type
			if ($prod['type'] === 'free') :

				$i_total_qty    = (int)$prod['qty'] + (int)$prod['qty_free'];
				$i_price        = ((float)$product_price * (int)$prod['qty']) / $i_total_qty;
				$i_price_total  = $i_price * $i_total_qty;
				$price_discount = ((float)$product_price * $i_total_qty) - $i_price_total;
				$i_coupon        = ((int)$prod['qty_free'] * 100) / $i_total_qty;

				// js input data package
				$js_discount_type  = 'free';
				$js_discount_qty   = (int)$prod['qty_free'];
				$js_discount_value = (int)$prod['id_free'];

			endif;

			// off type
			if ($prod['type'] === 'off') :

				$i_total_qty    = (int)$prod['qty'];
				$i_tt           = (float)$product_price * (int)$prod['qty'];
				$i_coupon        = (float)$prod['coupon'];
				$i_price        = ((float)$product_price - ((float)$product_price * $i_coupon / 100));
				$i_price_total  = $i_price * (int)$prod['qty'];
				$price_discount = $i_tt - $i_price_total;

				// js input data package
				$js_discount_type  = 'percentage';
				$js_discount_qty   = 1;
				$js_discount_value = (float)$prod['coupon'];

			endif;

			// bundle type
			if ($prod['type'] === 'bun') :

				$i_total_qty    = count($prod['prod']);
				$i_price        = (float)$prod['total_price'];
				$i_out_of_stock = false;

				// js input data package
				$js_discount_type  = 'percentage';
				$js_discount_qty   = 1;
				$js_discount_value = (float)$prod['discount_percentage'];

				$sum_price_regular = 0;
				$total_price_bun   = 0;

				foreach ($prod['prod'] as $i => $i_prod) :

					$p_bun = wc_get_product($i_prod['id']);

					if ('outofstock' === $p_bun->get_stock_status()) {
						$i_out_of_stock = true;
					}

					// variable prod
					if ($p_bun->is_type('variable')) {
						$sum_price_regular += (float)$p_bun->get_variation_regular_price('min') * (int)$i_prod['qty'];
						$total_price_bun   += (float)$p_bun->get_variation_sale_price('min') * (int)$i_prod['qty'];

						// simple prod
					} else {
						$sum_price_regular += (float)$p_bun->get_regular_price() * (int)$i_prod['qty'];
						$total_price_bun   += (float)$p_bun->get_sale_price() * (int)$i_prod['qty'];
					}

				endforeach;

				// discount percent
				$i_coupon = (float)$prod['discount_percentage'];

				// get price total bundle
				if ($i_price) {
					$sum_price_regular      = $i_price;
					$cus_bundle_total_price = $i_price;
				}

				$subtotal_bundle = $sum_price_regular;

				// apply discount percentage
				if ((float)$prod['discount_percentage'] > 0) {
					$subtotal_bundle -= ($subtotal_bundle * $i_coupon / 100);
				}

				$price_discount = $sum_price_regular - $subtotal_bundle;

			endif;


			if ($prod['type'] == 'free' || $prod['type'] == 'off') { ?>

				<div class="item-selection col-hover-focus bd_item_div bd_item_div_<?php echo trim($prod['bun_id']) ?> bd_c_package_option <?= (self::$package_default_id == $prod['bun_id']) ? 'bd_selected_default_opt' : '' ?>" data-type="<?php echo trim($prod['type']) ?>" data-bundle_id="<?php echo trim($prod['bun_id']) ?>" data-coupon="<?= round($i_coupon, 0) ?>">
				<?php
			} else { ?>

					<div class="item-selection col-hover-focus bd_item_div bd_item_div_<?php echo trim($prod['bun_id']) ?> bd_c_package_option <?= (self::$package_default_id == $prod['bun_id']) ? 'bd_selected_default_opt' : '' ?>" data-type="<?php echo trim($prod['type']) ?>" data-bundle_id="<?php echo trim($prod['bun_id']) ?>" data-coupon="<?= round($i_coupon, 0) ?>">
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

								<div class="bd_c_package_image">
									<?php
									if (wp_is_mobile() && $prod['image_package_mobile']) {
									?>
										<img src="<?php echo ($prod['image_package_mobile']) ?>" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="">
									<?php
									} elseif ($prod['image_package_desktop']) {
									?>
										<img src="<?php echo ($prod['image_package_desktop']) ?>" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="">
									<?php
									} else {
										echo ($product->get_image("woocommerce_thumbnail"));
									}

									// show discout label
									if ($prod['show_discount_label']) : ?>
										<span class="show_discount_label"><?php echo (sprintf(__('%s&#37; OFF', 'bd'), round($i_coupon, 0))) ?></span>
									<?php
									endif;
									?>
								</div>

								<div class="bd_c_package_info">
									<div class="bd_c_package_title">
										<div class="pi-1 text-dark"><?php echo $i_title ?></div>
										<?php
										if ($prod['type'] == 'free' || $prod['type'] == 'off') {
										?>
											<input type="checkbox" name="bd_selected_package_product" data-product_id="<?php echo ($p_id) ?>" data-index="<?php echo ($opt_i) ?>" value="<?php echo ($p_id) ?>" class="bd_selected_package_product product_id">
										<?php
										} else {
										?>
											<input type="checkbox" name="bd_selected_package_product" data-product_id="<?php echo ($prod['bun_id']) ?>" data-index="<?php echo ($opt_i) ?>" value="<?php echo ($prod['bun_id']) ?>" class="bd_selected_package_product product_id">
										<?php } ?>
									</div>
									<div class="pi-info">
										<?php if ($prod['type'] == 'bun') { // bundle option
										?>
											<div class="pi-price-total">
												<span><?php echo (__('Includes', 'bd')) ?>:</span>
												<span class="js-label-price_total"><?php echo wc_price($subtotal_bundle); ?></span>
											</div>
											<!-- get prices bundle -->
											<input type="hidden" class="bd_bundle_price_hidden" data-label="<?= $i_title ?>" value="<?= $subtotal_bundle ?>">
											<input type="hidden" class="bd_bundle_price_regular_hidden" data-label="<?= __('Old Price', 'bd') ?>" value="<?= $sum_price_regular ?>">
											<input type="hidden" class="bd_bundle_price_sale_hidden" data-label="<?= __('Old Price', 'bd') ?>" value="<?= $sum_price_regular ?>">
											<input type="hidden" class="bd_bundle_product_qty_hidden" value="1">
										<?php
										} else { // free and off option
										?>
											<div class="pi-price-total">
												<strong><?php echo (__('Includes', 'bd')) ?>:</strong>
												<span><?php echo wc_price($i_price_total); ?></span>
											</div>

											<!-- get prices bundle -->
											<input type="hidden" class="mwc_bundle_price_hidden" data-label="<?= $i_title ?>" value="<?= $i_price_total ?>">
											<input type="hidden" class="mwc_bundle_price_regular_hidden" data-label="<?= __('Old Price', 'mwc') ?>" value="<?= $product_regular_price ?>">
											<input type="hidden" class="mwc_bundle_price_sale_hidden" data-label="<?= __('Old Price', 'mwc') ?>" value="<?= $product_sale_price ?>">
											<input type="hidden" class="mwc_bundle_product_qty_hidden" value="<?= $i_total_qty ?>">
										<?php
										} ?>
									</div>
									<div class="bd_c_package_desc">
										<?= $product_title ?>
									</div>
									<?php if ($i_total_qty > 1) : ?>
										<ul class="bd_c_package_product_links mt-0">
											<?php
											foreach ($prod['prod'] as $i_prod) {
												$p_id = $i_prod['id'];
											?>
												<li>
													<a class="bd_c_package_product_link" href="<?php echo get_permalink($p_id); ?>"><?php echo get_the_title($p_id); ?></a>
												</li>
											<?php
											}
											?>
										</ul>
									<?php endif; ?>
									<div class="bd_c_package_status mb-2">
										<?php if ($i_out_of_stock) : ?>
											<span class="badge badge-gray"><?php echo __('Out of Stock', 'bd'); ?></span>
										<?php else : ?>
											<span class="badge badge-success"><?php echo __('In Stock', 'bd'); ?></span>
										<?php endif; ?>
									</div>
								</div> <!-- end bd_c_package_info -->
							</div> <!-- end bd_c_package_content -->

						</div> <!-- end bd_item_infos_div -->
					</div>


					<!-- Product variations form ------------------------------>
					<div class="bd_product_variations info_products_checkout <?= (($prod['type'] == 'free' || $prod['type'] == 'off') && $product->is_type('variable')) ? 'is_variable' : '' ?>" style=" display: none;">
						<table class="product_variations_table">
							<tbody>
								<?php
								//package selection variations free and off
								if ($prod['type'] == 'free' || $prod['type'] == 'off') {

									// get variation images product
									if (!isset($var_data[$p_id]) && $product->is_type('variable')) {
										$var_arr = [];
										foreach ($product->get_available_variations() as $key => $value) {
											array_push($var_arr, [
												'id' => $value['variation_id'],
												'price' => $prod['custom_price'][$value['variation_id']][$current_curr],
												'attributes' => $value['attributes'],
												'image' => $value['image']['url']
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
										$p_id = $i_prod['id'];
										$b_product = wc_get_product($p_id);

										// get variation images product
										if (!isset($var_data[$p_id]) && $b_product->is_type('variable')) {
											$var_arr = [];
											foreach ($b_product->get_available_variations() as $key => $value) {
												array_push($var_arr, [
													'id' => $value['variation_id'],
													'price' => $prod['custom_price'][$value['variation_id']][$current_curr],
													'attributes' => $value['attributes'],
													'image' => $value['image']['url']
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

					</div>
				<?php
				$p_i++;
			}
				?>

				<!-- </div>
			</div> -->
				</div>

				<script>
					var opc_variation_data = <?= json_encode($var_data) ?>;
					const bd_variation_price = <?= json_encode($variation_price) ?>;

					var bd_products_variations = <?php echo (json_encode(BD::$bd_products_variations)) ?>;
					var bd_products_variations_prices = <?php echo (json_encode(BD::$bd_products_variations_prices)) ?>;
				</script>

			<?php
		}

		add_action('wp_footer', function () { ?>

				<script>
					$ = jQuery;

					/**
					 * Update bundle price on variation dropdown change
					 */
					$('.var_prod_attr').change(function(e) {

						e.preventDefault();

						var top_parent = $(this).parents('.bd_item_div');

						var reg_price_total = 0;

						var coupon = $(this).parents('.bd_item_div').data('coupon');

						var bundle_id = $(this).parents('.bd_item_div').data('bundle_id');

						var prod_qty = top_parent.find('.var_prod_attr').length;

						top_parent.find('.var_prod_attr').each(function(ind, elem) {

							var var_data = JSON.parse(atob($(this).data('variations')));
							var this_val = $(this).val();

							$.each(var_data, function(index, value) {
								if (this_val === value.attributes.attribute_pa_size) {
									reg_price_total += parseFloat(value.display_regular_price);
								}
							});
						});

						// calculate and format bundle and per item prices
						var new_bundle_price = parseFloat((reg_price_total * ((100 - parseFloat(coupon)) / 100).toFixed(2)));
						var per_item_price = parseFloat((new_bundle_price / prod_qty).toFixed(2));

						// get currency symbol
						var curr_sym = $('.woocommerce-Price-currencySymbol:first').text();

						// update above prices in DOM
						$('.bd_item_div_' + bundle_id).find('.pi-price-each > span > span > bdi').empty().append(curr_sym + per_item_price.toFixed(2));
						$('.bd_item_div_' + bundle_id).find('.pi-price-total > span > span > bdi').empty().append(curr_sym + new_bundle_price.toFixed(2));

					});
				</script>

			<?php });

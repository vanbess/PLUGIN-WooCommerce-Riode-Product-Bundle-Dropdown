jQuery(document).ready(function ($) {

    /**
     * Show variations dropdowns for default packages (only Template A)
     */

    $('.bd_item_div').each(function (index, element) {

        if ($(element).hasClass('bd_selected_default_opt')) {
            setTimeout(() => {
                $(element).find('.bd_c_package_content').click();
            }, 250);
        }

    });

    //progress bar animation
    var progress = '100%';
    $('.loadingMessageContainerWrapper .bar').animate({
        width: progress,
    }, {
        duration: 6000,
        step: function (now, fx) {
            if (now >= 25) {
                $('.counter .steps1').hide();
                $('.counter .steps2').show();
            }
            if (now >= 50) {
                $('.counter .steps2').hide();
                $('.counter .steps3').show();
            }
            if (now >= 75) {
                $('.counter .steps3').hide();
                $('.counter .steps4').show();
            }
        },
        complete: function () {
            $('.loadingMessageContainerWrapper').hide();
        }
    });
    //end progress bar

    var bd_first_check_ajax = 1;
    var checkout_link = $('#bd_checkout_link').val();
    var price_symbol = $('.woocommerce-Price-currencySymbol').first().text();
    var product_id_cart = null;
    var bd_package_is_empty = 0;

    if ($('#bd_package_is_empty').length) {
        bd_package_is_empty = $('#bd_package_is_empty');
    }

    //load image variation to select form
    if ($('#shortcode_type').val() == 'package_order') {
        $('.sel_product_pa_color').each(function (index, el) {
            var img = $(el).find(':selected').data('img');
            $(el).parents('.option_variation').find('.img-variations img').attr('src', img);
        });
    }

    /**
     * Collapse on click
     */
    $('.bd_collapser').click(do_bd_collapse);

    function do_bd_collapse() {
        if ($(this).hasClass('bd_collapser_disabled'))
            return;
        if ($(this).hasClass('bd_collapser_open')) {
            $(this).removeClass('bd_collapser_open').addClass('bd_collapser_close');
            $(this).next('.bd_collapser_inner').slideUp();
        } else {
            $(this).removeClass('bd_collapser_close').addClass('bd_collapser_open');
            $(this).next('.bd_collapser_inner').slideDown();
        }
    }

    /**
     * Bundle dropdown form on submit
     */
    $('.i_bd_form').submit(i_bd_checkout);

    function i_bd_checkout(e) {
        e.preventDefault();
        var product_id = ''; var qty = 1; var separate = 1;
        var add_to_cart_items_data = {
            'products': {}
        };
        $(this).find('input.bd_product_ids').each(function (index, el) {
            if ($(el).val()) {
                product_id = $(el).val();
                if ($(el).attr('data-qty'))
                    qty = $(el).attr('data-qty');
                if ($(el).attr('data-separate'))
                    separate = $(el).attr('data-separate');

                if (typeof add_to_cart_items_data['products'][product_id] == "undefined") {
                    add_to_cart_items_data['products'][product_id] = {};
                }
                add_to_cart_items_data['products'][product_id] = {
                    product_id: product_id,
                    i_product_attribute: '',
                    qty: qty,
                    separate: separate
                };
            }
        });

        var info = {};
        info['action'] = 'bd_add_to_cart_multiple';
        info['add_to_cart_items_data'] = add_to_cart_items_data;

        $('#bd_loading').show();

        $.post(bd_infos.ajax_url, info).done(function (data) {
            if (data.status) {
                location.href = checkout_link //'?bd_checkout=1';//bd_infos.checkout_url;
            } else {
                alert(data.html);
                $('#bd_loading').hide();
            }
        });
        return false;
    }

    /**
     * Checkbox on check
     */
    $('.bd_selected_package_product').on('checked', bd_selected_package_changed);

    function bd_selected_package_changed() {

        var _parent = $(this).parents('.bd_item_div');

        $('.bd_item_config_div_variation').hide();
        let id_product = $(this).val();
        let i_index = $(this).data('index');

        $('.product_bd_id_' + id_product + '_' + i_index).show();

        $('.bd_item_div').removeClass('bd_active_product');

        if ($(this).is(':checked')) {
            $(this).parents('.bd_item_div').addClass('bd_active_product');
        } else {
            $(this).parents('.bd_item_div').removeClass('bd_active_product');
        }

        if ($('.bd_active_product .bd_product_attribute').length) {
            $('.bd_active_product .bd_product_attribute').first().change();
        } else {
            $('.i_bd_pack_variations_intro_div').hide();
            $('.step').css('padding-bottom', '0px');
        }

        $('.option_item').removeClass('option_active');
        $('#opt_item_' + id_product + '_' + i_index).addClass('option_active');

        // get set price
        if (_parent.find('.js-input-cus_bundle_total_price').val() <= 0) {
            $(this).getPriceTotalAndDiscountBundleOption();
        }

    }

    /**
     * Get package price
     */
    $.fn.getPriceTotalAndDiscountBundleOption = function () {

        _parent = $(this).parents('.bd_item_div');

        if (_parent.data('type') == "bun") {
            // get product ids
            var discoutProductIDs = $(this).getDiscountProductIDs();

            var info = {};
            info['action'] = 'bd_get_price_package';
            info['discount'] = discoutProductIDs.discount;
            info['product_ids'] = discoutProductIDs.products;

            //ajax update cart
            $.get(bd_infos.ajax_url, info).done(function (data) {
                if (data.status) {
                    _parent.find('.js-input-price_package').val(JSON.stringify(data));

                    // change label price
                    _parent.find('.js-label-price_each').empty().append(data.each_price_html);
                    _parent.find('.js-label-price_total').empty().append(data.total_price_html);
                    _parent.find('.js-label-price_old').empty().append(data.old_price_html);

                    // set price summary
                    _parent.find('.bd_bundle_price_hidden').val(data.total_price);
                    _parent.find('.bd_bundle_price_regular_hidden').val(data.old_price);
                }
            });
        }
    }

    /**
     * Set variation image
     * 
     * @param {*} _parent 
     * @returns 
     */
    function bd_set_image_variation(_parent) {

        var prod_id = _parent.attr("data-id");
        var bun_img = _parent.find('.bd_variation_img');

        var var_arr = {};
        _parent.find(".variation_item").each(function (index, el) {

            let _select = $(el).find(".var_prod_attr");
            if (_select.val()) {
                var_arr[_select.data("attribute_name")] = _select.val();
            }
        });

        var variation_id = '';
        $.each(opc_variation_data[prod_id], function (index, val) {
            var img = '';

            $.each(var_arr, function (i, e) {

                if (val['attributes'][i] && val['attributes'][i] == e) {
                    variation_id = val['id'];
                    img = val['image'];
                } else {
                    img = '';
                    return false;
                }
            });

            if (img) {
                bun_img.attr({
                    'src': img,
                    'data-src': img
                });
                return false;
            }
        });

        return variation_id;
    }

    /**
     * Bundle dropdown on change
     */
    $('.bundle_dropdown_attr').change(function (e) {

        var _parent = $(this).closest(".c_prod_item");

        // get variation id, set image variation
        var var_id = bd_set_image_variation(_parent);
        // set variation id
        _parent.attr('data-variation_id', var_id);

        // update variation price product bd
        bd_get_price_variation_product($(this).closest(".bd_item_div"));

        // get set price
        $(this).getPriceTotalAndDiscountBundleOption();
    });

    /**
     * Set variation image on window load
     */
    $(window).on('load', function () {

        $('.product-buy-now').addClass('riode_prod_bundle_bn').removeClass('product-buy-now').attr('type', 'submit').off();

        $(".variation_selectors").each(function (i, e) {

            if ($(e).find(".var_prod_attr").length) {
                var _parent = $(e).parents(".c_prod_item");

                var var_id = bd_set_image_variation(_parent);
                // set variation id
                _parent.attr('data-variation_id', var_id);
            }
        });

        // update variation price product bd
        $('.bd_item_div').each(function (index, element) {

            if ($(element).attr('data-type') == 'bun') {
                $(element).find('.bd_product_variations').getPriceTotalAndDiscountBundleOption();
            } else {
                bd_get_price_variation_product('template A', $(this));
            }

        });
    });

    //scroll bundle option mobile
    $('.option_item').click(function () {

        var id_prod = $(this).data('id');
        var i_index = $(this).data('item');
        $('.bd_item_div_' + id_prod + '_' + i_index).click();
        $('.option_item').removeClass('option_active');
        $(this).addClass('option_active');

        // scroll to option selected
        var width_scroll = $('.card').width();
        var item = $(this).data('item');
        $('.scrolling-wrapper').animate({ scrollLeft: width_scroll * item }, 500);
    });

    /**
     * Woo Thumb on click
     */
    $(document).on('click', '#bd_checkout .label_woothumb', function () {
        $(this).parents(".select_woothumb").find(".label_woothumb").removeClass("selected");
        $(this).addClass("selected");
        $(this).parents(".variation_item").find("select").val($(this).data("option")).trigger("change");
    });

    /**
     * Attribute swatch on click
     */
    $(document).on('click', '#bd_checkout .attribute-swatch > .swatchinput > label:not(.disabled)', function () {
        $(this).closest(".variation_item").find(".swatchinput > label").removeClass("selected");
        $(this).addClass("selected");
        $(this).closest(".variation_item").find("select").val($(this).data("option")).trigger("change");
    });

    /**
     * Linked variations on click
     */
    $(document).on('click', '#bd_checkout .attribute-swatch > .swatchinput .linked_product:not(.disabled)', function (e) {
        var _parent = $(this).closest(".c_prod_item");
        _parent.attr('data-id', $(this).attr('data-linked_id'));

        // get variation id, set image variation
        var var_id = bd_set_image_variation(_parent);
        // set variation id
        _parent.attr('data-variation_id', var_id);

        // bd_update_item_cart_ajax();

        bd_get_price_variation_product($(this).closest(".bd_item_div"));
        $(this).getPriceTotalAndDiscountBundleOption()
    });

    /**
   * Single add to cart button on click
   */
    $('button.single_add_to_cart_button').click(function (e) {
        if ($('#bd_checkout .item-selection.bd_active_product').length) {
            $(document.body).one('added_to_cart', function () {
                bd_update_item_cart_ajax(false, false);
            });
        }
    });

    /**
     * Buy now button on click
     */
    setTimeout(() => {

        // Remove/add disabled class from buy now
        if ($('.single_add_to_cart_button').hasClass('disabled')) {
            $('.riode_prod_bundle_bn').addClass('disabled');
        } else {
            $('.riode_prod_bundle_bn').removeClass('disabled');
        }

        // remove/add disabled class on variation button click
        $('.product-variations').find('button').each(function (index, element) {
            $(element).on('click', function () {
                setTimeout(() => {
                    if ($(element).hasClass('active')) {
                        $('.riode_prod_bundle_bn').removeClass('disabled');
                    } else {
                        $('.riode_prod_bundle_bn').addClass('disabled');
                    }
                }, 250);
            });
        });

        // on click
        $(document).find('.riode_prod_bundle_bn').on('click', function (e) {

            e.preventDefault();

            var main_prod = {
                'prod_id': $(document).find('input[name="product_id"]').val(),
                'var_id': $(document).find('input[name="variation_id"]').length ? $(document).find('input[name="variation_id"]').val() : null,
                'qty': $(document).find('.qty').val()
            }

            bd_update_item_cart_ajax(main_prod, true);
        });

    }, 500);

    /**
     * Package content/container on click
     */
    $('.bd_c_package_content').click(function () {
        var selfOption = $(this).closest('.bd_c_package_option');
        $('.bd_c_package_option').each(function () {
            var option = $(this),
                checkbox = option.find('input.bd_selected_package_product');

            option.toggleClass('expanded');

            if (option[0] == selfOption[0]) {
                option.find('.bd_product_variations').slideToggle(!checkbox.prop('checked'));
                checkbox.prop('checked', !checkbox.prop('checked'));
                checkbox.trigger('checked');
            } else {
                option.find('.bd_product_variations').slideUp();
                checkbox.prop('checked', false);
            }
        });
    });


    // element function get discount and product ids
    $.fn.getDiscountProductIDs = function () {
        var _self = this;
        var el_parent = $(_self).parents('.bd_item_div');

        var arr_discount = {
            'type': el_parent.find('.js-input-discount_package').attr('data-type'),
            'qty': el_parent.find('.js-input-discount_package').attr('data-qty'),
            'value': el_parent.find('.js-input-discount_package').val()
        };

        var arr_prod_ids = [];
        $(el_parent.find('.bd_product_variations .c_prod_item')).each(function (index, element) {
            if ($(element).attr('data-variation_id')) {
                arr_prod_ids.push($(element).attr('data-variation_id'));
            } else {
                arr_prod_ids.push($(element).attr('data-id'));
            }
        });

        return {
            'discount': arr_discount,
            'products': arr_prod_ids
        };
    }

    /**
     * Add to cart function
     * 
     * @param bool redirect 
     */
    function bd_update_item_cart_ajax(main_prod = false, redirect = false) {

        var bundle_id = parseInt($('.bd_active_product').data('bundle_id'));

        var add_to_cart_items_data = {
            'products': {}
        };

        // TEMPLATE A
        $('.bd_active_product').find('.info_products_checkout .c_prod_item').each(function (index, el) {
            let variation_id = $(this).attr('data-variation_id');
            let _prod_id = $(this).data('id');

            if (_prod_id) {
                i_product_attribute = {};

                $(this).find('.bundle_dropdown_attr').each(function (var_i, var_el) {
                    if ($(var_el).val()) {
                        if ($(var_el).data('attribute_name')) {
                            i_product_attribute[$(var_el).data('attribute_name')] = $(var_el).val();
                        }
                    }
                });
            }

            // linked variations
            var linked_product = {
                'id': '',
                'attributes': {}
            };

            if ($(this).find('.linked_product.selected').attr('data-linked_id')) {
                var el_linked = $(this).find('.linked_product.selected');
                linked_product['id'] = el_linked.attr('data-linked_id');
                linked_product['attributes'][el_linked.attr('data-attribute_name')] = el_linked.attr('data-option');
            }

            add_to_cart_items_data['products'][_prod_id + '_' + (index + 1)] = {
                product_id: _prod_id,
                linked_product: linked_product,
                variation_id: variation_id,
                i_product_attribute: i_product_attribute,
                qty: 1,
                separate: 1
            };

        });

        // TEMPLATE B
        if ($(document).find('#bd_product_variations_' + bundle_id + ' .c_prod_item').length) {
            $(document).find('#bd_product_variations_' + bundle_id + ' .c_prod_item').each(function (index, el) {

                let variation_id = $(this).attr('data-variation_id');
                let _prod_id = $(this).data('id');

                if (_prod_id) {
                    i_product_attribute = {};

                    $(this).find('.bundle_dropdown_attr').each(function (var_i, var_el) {
                        if ($(var_el).val()) {
                            if ($(var_el).data('attribute_name')) {
                                i_product_attribute[$(var_el).data('attribute_name')] = $(var_el).val();
                            }
                        }
                    });
                }

                // linked variations
                var linked_product = {
                    'id': '',
                    'attributes': {}
                };

                if ($(this).find('.linked_product.selected').attr('data-linked_id')) {
                    var el_linked = $(this).find('.linked_product.selected');
                    linked_product['id'] = el_linked.attr('data-linked_id');
                    linked_product['attributes'][el_linked.attr('data-attribute_name')] = el_linked.attr('data-option');
                }

                add_to_cart_items_data['products'][_prod_id + '_' + (index + 1)] = {
                    product_id: _prod_id,
                    linked_product: linked_product,
                    variation_id: variation_id,
                    i_product_attribute: i_product_attribute,
                    qty: 1,
                    separate: 1
                };

            });
        }

        var info = {};
        info['action'] = 'bd_add_to_cart_multiple';
        info['bundle_id'] = bundle_id;
        info['add_to_cart_items_data'] = add_to_cart_items_data;
        info['bd_first_check_ajax'] = 0;
        info['bd_dont_empty_cart'] = 1;
        info['main_prod'] = main_prod

        // debug
        console.log(info);

        //ajax update cart
        $.post(bd_infos.ajax_url, info).done(function (data) {

            // debug
            console.log(data);

            if (data.status) {
                if (redirect) {
                    window.location.href = bd_infos.checkout_url;
                } else {
                    $(document.body).trigger('wc_fragment_refresh');
                }
            } else {
                alert(data.html);
                $('#bd_loading').hide();
            }
        });
    }

    // end function ajax add to cart


    // function get price variation BD product
    function bd_get_price_variation_product(template, bd_item_div) {

        // Template A
        if (template === 'template A') {

            var product_prices = [];

            if (bd_item_div.find('.bd_product_variations').hasClass("is_variable")) {

                var bundle_id = bd_item_div.attr('data-bundle_id');

                bd_item_div.find('.c_prod_item').each(function (i, el) {
                    product_prices.push(bd_variation_price[parseInt(bundle_id)][parseInt($(this).attr('data-variation_id'))]);
                });

                var info = {};
                info['action'] = 'bd_get_price_variation_product';
                info['price_list'] = product_prices;
                info['coupon'] = bd_item_div.data('coupon');

                //ajax update cart
                $.get(bd_infos.ajax_url, info).done(function (data) {
                    if (data.status) {
                        bd_item_div.find('.pi-price-pricing > .pi-price-each > span').first().html(data.single_price_html);
                        bd_item_div.find('.pi-price-total > span').first().html(data.total_price_html);
                        bd_item_div.find('.bd_bundle_price_hidden').first().val(data.total_price);
                    }
                });
            }
        }

    }

});

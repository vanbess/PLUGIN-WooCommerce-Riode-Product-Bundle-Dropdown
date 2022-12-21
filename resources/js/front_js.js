jQuery(document).ready( function( $ ) {

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
            if(now >= 50 ) {
                $('.counter .steps2').hide();
                $('.counter .steps3').show();
            }
            if(now >= 75 ) {
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
    // $(".bd_product_attribute").Segment();
    var bd_package_is_empty = 0;
    if( $('#bd_package_is_empty').length )
        bd_package_is_empty = $('#bd_package_is_empty');

    //load image variation to select form
    if($('#shortcode_type').val() == 'package_order') {
        $('.sel_product_pa_color').each(function(index, el){
            var img = $(el).find(':selected').data('img');
            $(el).parents('.option_variation').find('.img-variations img').attr('src', img);
        });
    }
                                                          
    $('.bd_collapser').click( do_bd_collapse );
    function do_bd_collapse(){
        if( $(this).hasClass('bd_collapser_disabled') )
            return;
        if( $(this).hasClass('bd_collapser_open') ){
            $(this).removeClass('bd_collapser_open').addClass('bd_collapser_close');
            $(this).next('.bd_collapser_inner').slideUp();
        } else {
            $(this).removeClass('bd_collapser_close').addClass('bd_collapser_open');
            $(this).next('.bd_collapser_inner').slideDown();
        }
    }

    $('.i_bd_form').submit( i_bd_checkout );
    function i_bd_checkout( e ){
        e.preventDefault();
        var product_id = ''; var qty = 1; var separate = 1;
        var add_to_cart_items_data = {
            'products': {}
        };
        $(this).find( 'input.bd_product_ids' ).each(function(index, el){
            if ( $(el).val() ) {
                product_id = $(el).val();
                if( $(el).attr('data-qty') )
                    qty = $(el).attr('data-qty');
                if( $(el).attr('data-separate') )
                    separate = $(el).attr('data-separate');

                if( typeof add_to_cart_items_data['products'][product_id] == "undefined"  ){
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
            data = JSON.parse(data);
            //console.log( data );
            if( data.status ){
                location.href = checkout_link //'?bd_checkout=1';//bd_infos.checkout_url;
            } else {
                alert( data.html );
                $('#bd_loading').hide();
            }
        });
        return false;
    }

    $('.i_bd_form_pack_selector').submit( change_bd_item_pack );
    function change_bd_item_pack( e ){
        e.preventDefault();

        if( !$(this).parents('.bd_item_pack_selector_div').hasClass('bd_pack_selector_selected')
        || $(this).parents('.bd_item_pack_selector_div').hasClass('bd_pack_select_again')){

            $('.bd_item_div').hide().removeClass('bd_active_product');
            $('.bd_pack_selector_selected').removeClass('bd_pack_selector_selected').removeClass('bd_pack_select_again');
            $(this).parents('.bd_item_pack_selector_div').addClass('bd_pack_selector_selected');
            var product_id = '';
            /*var add_to_cart_items_data = {
                'products': {}
            };*/
            $(this).find( 'input.bd_product_ids' ).each(function(index, el){
                if ( $(el).val() ) {
                    product_id = $(el).val();
                    $('.bd_item_div_'+product_id).show().addClass('bd_active_product');
                }
            });
            update_bd_item_pack();
        }

        return false;
    }

    function update_bd_item_pack(){
        var product_id = ''; 
        var separate = 1;
        var add_to_cart_items_data = {
            'products': {}
        };
        var product_n = 1;
        var bundle_id = '';
        $('.bd_active_product').each( function (index, el) {
            var type = $(el).attr('data-type');
            bundle_id = $(el).data('bundle');
            var i_index = $(el).find('.bd_selected_package_product').data('index');

            if(type == 'free' || type == 'off') {
                product_id = $(el).find('.product_id').val();
                var i_items = $('.product_bd_id_'+product_id+'_'+i_index).find('.i_variations').attr("data-items");
                for (let i = 1; i <= i_items; i++) {
                    if ( product_id ) {
                        separate = '';
                        if( $(el).find('.product_separate').val() )
                            separate = $(el).find('.product_separate').val();
        
                        if( typeof add_to_cart_items_data['products'][product_id] == "undefined"  ){
                            add_to_cart_items_data['products'][product_id] = {};
                        }
        
                        i_product_attribute = {};
                        // $(el).find('.bd_product_attribute').each(function(var_index, var_el){
                        $('.product_bd_id_'+product_id+'_'+i_index).find('.bd_product_attribute').each(function(var_index, var_el) {
                            if( $(var_el).val() ){
                                if($(var_el).attr('data-item') == i) {
                                    i_product_attribute[$(var_el).attr('name').replace('i_variation_','')] = $(var_el).val();
                                }
                            }
                            // console.log(i_product_attribute);
                        });
                        i_product_attribute['attribute_separate'] = separate;
        
                        add_to_cart_items_data['products'][product_id+'_'+product_n] = {
                            product_id: product_id,
                            i_product_attribute: i_product_attribute,
                            qty: 1,
                            separate: 1
                        };              
                    }
                    product_n++;
                }
            }else {
                post_id = $(el).find('.product_id').val();
                var i_index = $(el).find('.bd_selected_package_product').data('index');

                $('.product_bd_id_'+post_id+'_'+i_index).find('.select-variations').each(function(var_index, var_el) {
                    
                    var product_id = $(var_el).attr('data-id');
                    separate = '';
                    if( $(el).find('.product_separate').val() )
                        separate = $(el).find('.product_separate').val();
    
                    if( typeof add_to_cart_items_data['products'][product_id] == "undefined" ){
                        add_to_cart_items_data['products'][product_id] = {};
                    }

                    i_product_attribute = {};
                    $(var_el).find('.bd_product_attribute').each(function(sel_i, sel_el) {
                        if( $(sel_el).val() ){
                            i_product_attribute[$(sel_el).attr('name').replace('i_variation_','')] = $(sel_el).val();
                        }
                    });
                    
                    i_product_attribute['attribute_separate'] = separate;
        
                    add_to_cart_items_data['products'][product_id+'_'+product_n] = {
                        product_id: product_id,
                        i_product_attribute: i_product_attribute,
                        qty: 1,
                        separate: 1
                    };
                    product_n++;
                });
            }
        });

        //// Check also addon products --
        
        var product_o = 1;
        var addon_products = [];

        $('.bd_fbt_item.i_active_product').each( function (index, el) {            
            if ( $(el).find('.bd_selected_fbt_product').data('product_id') ) {
                product_id = $(el).find('.bd_selected_fbt_product').data('product_id');
                
                //separate = 'x';
                //if( $(el).find('.product_separate').val() )
                //    separate = $(el).find('.product_separate').val();

                if( typeof add_to_cart_items_data['products'][product_id] == "undefined"  ){
                    add_to_cart_items_data['products'][product_id] = {};
                }

                i_product_attribute = {};
                $('.product_bd_id_'+product_id).find('.bd_product_attribute').each(function(var_index, var_el){
                    if( $(var_el).val() ){
                        i_product_attribute[$(var_el).attr('name').replace('i_variation_','')] = $(var_el).val();
                    }
                });
                
                i_product_qty = $(el).find('.i_product_qty').val();
                //i_product_attribute['attribute_separate'] = separate;
                
                addon_products.push(product_id);

                add_to_cart_items_data['products'][product_id+'_'+product_o] = {
                    product_id: product_id,
                    i_product_attribute: i_product_attribute,
                    i_product_qty: i_product_qty,
                    qty: 1,
                    //separate: 1
                };
            }
            product_o++;
        });

        
                
        add_to_cart_items_data['addon_products'] = addon_products.join(",");
        ///////// -- Addon products check is done

        var info = {};
        info['action'] = 'bd_add_to_cart_multiple';
        info['bundle_id'] = bundle_id;
        info['add_to_cart_items_data'] = add_to_cart_items_data;
        info['bd_first_check_ajax'] = bd_first_check_ajax;
        if( bd_first_check_ajax )
            bd_first_check_ajax = 0;

        // if(product_id_cart == product_id) {
        //     info['bd_dont_empty_cart'] = true;
        // }else {
        //     product_id_cart = product_id;
        // }

        $('#bd_loading').show();
        //console.log(bd_infos.ajax_url);
        
        $.post(bd_infos.ajax_url, info).done(function (data) {
            data = JSON.parse(data);
            if( data.status ){
                $(document.body).trigger("update_checkout");
            } else {
                alert( data.html );
                $('#bd_loading').hide();
            }
        });

    }
    
    /* 
    $(document.body).on("update_checkout", function(){
        $('.bd_subtotal_price').html( $('.cart-subtotal .woocommerce-Price-amount.amount').html('...') );
    });
    $(document.body).on("updated_checkout", function(){
        $('.bd_subtotal_price').html( $('.cart-subtotal .woocommerce-Price-amount.amount').html() );
    });
    */
    
    $('.bd_product_attribute').change( function( i, e){
        var i_item = $(this).find(':selected').data('item');
        var i_img = $(this).find(':selected').data('img');
        
        $('.bd_item_div.bd_active_product').each( function( index, el ){
            c_product_price = 0;
            var c_product_id = $(el).find('input.product_id').val();

            if(i_img != '') {
                $('.img_'+c_product_id+'_'+i_item).attr('srcset','').attr('src', i_img);
            }
            
            if( $(el).find('.i_variations').length ){
                var i_product_variations = bd_products_variations[c_product_id];

                var i_variations_el = $(el).find('.i_variations');
                var variation_found = false;
                var found_n_max = 0; var found_variation_index = 0;
                var var_span_txt = '';
                var var_price_txt = '';
            
                $('.i_bd_pack_variations_intro_div').show();
                $('.step').css('padding-bottom', '66px');

                $.each(i_product_variations, function(var_index, var_value) {
                    var found_i = 0;
                    var found_n = 0;
                    $.each(var_value, function(opt_index, opt_value) {
                        if( opt_value && opt_index != 'image' ){
                            if ( i_variations_el.find('select[name=i_variation_'+opt_index+']').val() == opt_value ){
                                
                                found_i++;
                                found_n ++;
                            } else {
                                found_i--;
                            }
                        } else {
                            found_i++;
                        }
                    });
                    if( found_i == Object.keys(var_value).length ){
                        if( found_n >= found_n_max ){
                            found_n_max = found_n;
                            variation_found = true;
                            found_variation_index = var_index;
                            c_product_price = Number( bd_products_variations_prices[c_product_id][var_index] );
                        }
                    }

                });
                if( found_variation_index ){
                    variation_image_url = bd_products_variations[c_product_id][found_variation_index]['image'];
                    //console.log( bd_products_variations[c_product_id][found_variation_index] );
                    $(el).find('.bd_item_image_div img').attr('srcset','').attr('src', variation_image_url);
                    
                }
                /*
                var_span_txt = '('+price_symbol+c_product_price+', '; k = 1;
                $(el).find('.i_variations .bd_product_attribute').each(function( var_i, variation_select){
                    var variation_val = $(variation_select).val();
                    if( variation_val ){
                        if( var_i > 0 )
                            var_span_txt+= ' - ';
                        var_span_txt+=variation_val;
                    }
                });
                var_span_txt+= ')';
                */
                var_price_txt = '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">'+price_symbol+'</span>'+c_product_price+'</span>';
                //$(el).find('.i_for_variations').html(var_span_txt);
                $(el).find('.i_for_price ins').html(var_price_txt);
            }

        });

        $('.bd_fbt_item.i_active_product').each( function( index, el ){
            c_product_price = 0;
            var c_product_id = $(el).find('input.bd_selected_fbt_product').val();
            if( $(el).find('.i_variations').length ){
                var i_product_variations = bd_products_variations[c_product_id];

                var i_variations_el = $(el).find('.i_variations');
                var variation_found = false;
                var found_n_max = 0; var found_variation_index = 0;

                $.each(i_product_variations, function(var_index, var_value) {
                    var found_i = 0;
                    var found_n = 0;
                    $.each(var_value, function(opt_index, opt_value) {
                        if( opt_value && opt_index != 'image' ){
                            if ( i_variations_el.find('select[name=i_variation_'+opt_index+']').val() == opt_value ){
                                found_i++;
                                found_n ++;
                            } else {
                                found_i--;
                            }
                        } else {
                            found_i++;
                        }
                    });
                    if( found_i == Object.keys(var_value).length ){
                        if( found_n >= found_n_max ){
                            found_n_max = found_n;
                            variation_found = true;
                            found_variation_index = var_index;
                            c_product_price = Number( bd_products_variations_prices[c_product_id][var_index] );
                        }
                    }

                });
                if( found_variation_index ){
                    variation_image_url = bd_products_variations[c_product_id][found_variation_index]['image'];
                    //console.log( bd_products_variations[c_product_id][found_variation_index] );
                    $(el).find('.bd_image_container img').attr('srcset','').attr('src', variation_image_url);
                }

                var_span_txt = '('+price_symbol+c_product_price+', '; k = 1;
                $(el).find('.i_variations .bd_product_attribute').each(function( var_i, variation_select){
                    var variation_val = $(variation_select).val();
                    if( variation_val ){
                        if( var_i > 0 )
                            var_span_txt+= ' - ';
                        var_span_txt+=variation_val;
                    }
                });
                var_span_txt+= ')';
                $(el).find('.i_product_price').html(var_span_txt);
            }

        });
        update_bd_item_pack();
    } );

    /////////////////////////

    $('.bd_selected_fbt_product').change( bd_selected_fbt_product_changed );
    function bd_selected_fbt_product_changed(){
        if( $(this).is(':checked') ){
            $(this).parents('.bd_fbt_item').addClass('i_active_product');
        } else {
            $(this).parents('.bd_fbt_item').removeClass('i_active_product');
        }
        var bd_fbt_active_products = '';
        $('.bd_fbt_product_ids').remove();
        $('.bd_fbt_item.i_active_product').each(function(index, el){
            if( bd_fbt_active_products != '' )
                bd_fbt_active_products+=', ';
            bd_fbt_active_product_id = $(el).find('.bd_selected_fbt_product').attr('data-product_id');
            bd_fbt_active_products+= bd_fbt_active_product_id;
            /*$('.bd_item_pack_selector form.i_bd_form_pack_selector').append(
                '<input type="hidden" name="product_ids[]" value="'+bd_fbt_active_product_id+'_x" class="bd_product_ids bd_fbt_product_ids">'
            );*/
        });
        //$('.bd_item_pack_selector').attr('data-addon_products', bd_fbt_active_products);
        if( $('.bd_items_div').length ){
            if( $('.bd_package_option').length ){
                $('.bd_active_product input.bd_selected_package_product').trigger('checked');
            } else {
                $('.bd_item_pack_selector.bd_pack_selector_selected').addClass('bd_pack_select_again').find('form.i_bd_form_pack_selector').submit();
            }
        } else {

        }

        //$('html,body').animate({'scrollTop': ($('.bd_items_pack_selector_div').offset().top - 40)}, 500);
    }

    $('.bd_fbt_item').click(function(evt){
        var target = $(evt.target);
        if( !target.hasClass('bd_selected_fbt_product') && !target.is( "select" ) && !target.is( "input" ) && !target.is( ".ui-segment *" ) )
            $(this).find('.bd_selected_fbt_product').click();
    });

    
    //var bd_padding_bottom = 80;
    $('.bd_selected_package_product').on('checked', bd_selected_package_changed );
    function bd_selected_package_changed(){

        var _parent = $(this).parents('.bd_item_div');
        

        $('.bd_item_config_div_variation').hide();
        let id_product = $(this).val();
        let i_index = $(this).data('index');
        $('.product_bd_id_'+id_product+'_'+i_index).show();
        
        $('.bd_item_div').removeClass('bd_active_product');
        if( $(this).is(':checked') ){
            $(this).parents('.bd_item_div').addClass('bd_active_product');            
        } else {
            $(this).parents('.bd_item_div').removeClass('bd_active_product');
        }
        if( $('.bd_active_product .bd_product_attribute').length ){
            $('.bd_active_product .bd_product_attribute').first().change();
        } else {
            
            $('.i_bd_pack_variations_intro_div').hide();
            $('.step').css('padding-bottom', '0px');
                
            // update_bd_item_pack();
        }

        //bd_padding_bottom = $('.bd_item_div.bd_active_product .bd_item_config_div').height()+10;
        //$('.bd_items_div.bd_package_items_div').css('padding-bottom', bd_padding_bottom+'px');

        $('.option_item').removeClass('option_active');
        $('#opt_item_'+id_product+'_'+i_index).addClass('option_active');

        // call func updatae cart
        // bd_update_item_cart_ajax();

        // get set price
        if(_parent.find('.js-input-cus_bundle_total_price').val() <= 0) {
            $(this).getPriceTotalAndDiscountBundleOption();
        }
        
    }


    // get price package

    jQuery.fn.getPriceTotalAndDiscountBundleOption = function() {
        
        _parent = $(this).parents('.bd_item_div');

        if(_parent.data('type') == "bun") {
            // get product ids
            var discoutProductIDs = $(this).getDiscountProductIDs();

            var info = {};
            info['action'] = 'bd_get_price_package';
            info['discount'] = discoutProductIDs.discount;
            info['product_ids'] = discoutProductIDs.products;

            //ajax update cart
            jQuery.get(bd_infos.ajax_url, info).done(function (data) {
                data = JSON.parse(data);
                
                if(data.status) {
                    _parent.find('.js-input-price_package').val(JSON.stringify(data));
                    // change label price
                    _parent.find('.js-label-price_each').empty().append(data.each_price_html);
                    _parent.find('.js-label-price_total').empty().append(data.total_price_html);
                    _parent.find('.js-label-price_old').empty().append(data.old_price_html);

                    // change price package
                    // _parent.find('.pi-price-pricing .pi-price-each span').empty().append(data.total_price_html);
                    // _parent.find('.pi-price-pricing .pi-price-orig span').empty().append(data.old_price_html);

                    // set price summary
                    _parent.find('.bd_bundle_price_hidden').val(data.total_price);
                    _parent.find('.bd_bundle_price_regular_hidden').val(data.old_price);
                }
            });
        }
    }


    // function set image variation
    function bd_set_image_variation(_parent) {

        var prod_id = _parent.attr("data-id");
        var bun_img = _parent.find('.bd_variation_img');

        var var_arr = {};
        _parent.find(".variation_item").each(function (index, el) {
            // let _select = $(el).find(".product-variations button.active");
            // if (_select.length > 0) {
            //     var_arr[$(el).find( '.product-variations' ).data('attr')] = _select.attr('name');
            // }
            let _select = $(el).find(".var_prod_attr");
            if(_select.val()) {
                var_arr[_select.data("attribute_name")] = _select.val();
            }
        });

        var variation_id = '';
        $.each(opc_variation_data[prod_id], function(index, val) {
            var img = '';

            $.each(var_arr, function(i, e) {

                if(val['attributes'][i] && val['attributes'][i] == e) {
                    variation_id = val['id'];
                    img = val['image'];
                }else {
                    img = '';
                    return false;
                }
            });

            if(img){
                bun_img.attr({
                    'src': img,
                    'data-src': img
                });
                return false;
            }
        });

        return variation_id;
    }

    // update cart when select dropdown product variation
    $('.bundle_dropdown_attr').change(function(e) {

        var _parent = $(this).closest(".c_prod_item");

        // get variation id, set image variation
        var var_id = bd_set_image_variation(_parent);
        // set variation id
        _parent.attr('data-variation_id', var_id);
        
        // call func updatae cart
        // bd_update_item_cart_ajax();

        // update variation price product bd
        bd_get_price_variation_product($(this).closest(".bd_item_div"));

        // get set price
        $(this).getPriceTotalAndDiscountBundleOption();
    });    

    // set image variation all option when load page
    $(window).on('load', function() {
        $(".variation_selectors").each(function (i, e) {
            // if ($(this).find(".product-variations button").length) {
            //     var _parent = $(this).parents(".c_prod_item");

            //     var var_id = bd_set_image_variation(_parent);
            //     // set variation id
            //     _parent.attr('data-variation_id', var_id);
            // }
            if ($(this).find(".var_prod_attr").length) {
                var _parent = $(this).parents(".c_prod_item");

                var var_id = bd_set_image_variation(_parent);
                // set variation id
                _parent.attr('data-variation_id', var_id);
            }
        });

        // update variation price product bd
        $('.bd_item_div').each(function (index, element) {
            if(element.attr('data-type') == 'bun') {
                element.find('.bd_product_variations').getPriceTotalAndDiscountBundleOption();

            } else {
                bd_get_price_variation_product($(this));
            }
        });
    });

    //scroll bundle option mobile
    $('.option_item').click(function() {
        var id_prod = $(this).data('id');
        var i_index = $(this).data('item');
        $('.bd_item_div_'+id_prod+'_'+i_index).click();
        $('.option_item').removeClass('option_active');
        $(this).addClass('option_active');
        
        // scroll to option selected
        var width_scroll = $('.card').width();
        var item = $(this).data('item');
        $('.scrolling-wrapper').animate( { scrollLeft: width_scroll*item }, 500);
    });

    // change color label
    $(document).on('click', '#bd_checkout .label_woothumb', function () {
        $(this).parents(".select_woothumb").find(".label_woothumb").removeClass("selected");

        $(this).addClass("selected");
        $(this).parents(".variation_item").find("select").val($(this).data("option")).trigger("change"); 
    });
    $(document).on('click', '#bd_checkout .attribute-swatch > .swatchinput > label:not(.disabled)', function () {
        $(this).closest(".variation_item").find(".swatchinput > label").removeClass("selected");

        $(this).addClass("selected");
        $(this).closest(".variation_item").find("select").val($(this).data("option")).trigger("change"); 
    });



    // linked variations select
    $(document).on('click', '#bd_checkout .attribute-swatch > .swatchinput .linked_product:not(.disabled)', function(e) {
        var _parent = $(this).closest(".c_prod_item");
        _parent.attr( 'data-id', $(this).attr( 'data-linked_id' ) );

        // get variation id, set image variation
        var var_id = bd_set_image_variation(_parent);
        // set variation id
        _parent.attr('data-variation_id', var_id);

        // bd_update_item_cart_ajax();

        bd_get_price_variation_product($(this).closest(".bd_item_div"));
        $(this).getPriceTotalAndDiscountBundleOption()
    });

    $('button.single_add_to_cart_button').click(function (e) {
        if ( $('#bd_checkout .item-selection.bd_active_product').length ) {
            $( document.body ).one('added_to_cart', () => bd_update_item_cart_ajax());
        }
    });

    $('.bd-buy-now').click(function (e) {
        bd_update_item_cart_ajax(true);
    });

    $('.bd_c_package_content').click(function () {
        var $selfOption = $(this).closest('.bd_c_package_option');
        $( '.bd_c_package_option' ).each(function() {
            var $option = $(this),
            $chekcbox = $option.find('input.bd_selected_package_product');
        $option.toggleClass('expanded');

            if ( $option[0] == $selfOption[0] ) {
                $option.find('.bd_product_variations').slideToggle(!$chekcbox.prop('checked'));
                $chekcbox.prop('checked', !$chekcbox.prop('checked'));
                $chekcbox.trigger('checked');
            } else {
                $option.find('.bd_product_variations').slideUp();
                $chekcbox.prop('checked', false);
            }
        });
    });
});


// element function get discount and product ids
jQuery.fn.getDiscountProductIDs = function() {
    var _self = this;
    var el_parent = jQuery(_self).parents('.bd_item_div');

    var arr_discount = {
        'type': el_parent.find('.js-input-discount_package').attr('data-type'),
        'qty': el_parent.find('.js-input-discount_package').attr('data-qty'),
        'value': el_parent.find('.js-input-discount_package').val()
    };
     
    var arr_prod_ids = [];
    jQuery(el_parent.find('.bd_product_variations .c_prod_item')).each(function (index, element) {
        if( jQuery(element).attr('data-variation_id') ) {
            arr_prod_ids.push(jQuery(element).attr('data-variation_id'));
        } else {
            arr_prod_ids.push(jQuery(element).attr('data-id'));
        }
    });

    return {
        'discount': arr_discount,
        'products': arr_prod_ids
    };
}




// function ajax add to cart when select option onepage checkout
function bd_update_item_cart_ajax(redirect = false) {
    var bundle_id = jQuery('.bd_active_product').data('bundle_id');

    var add_to_cart_items_data = {
        'products': {}
    };

    jQuery('.bd_active_product').find('.info_products_checkout .c_prod_item').each(function(index, el) {
        let variation_id = jQuery(this).attr('data-variation_id');
        let _prod_id = jQuery(this).data('id');
        
        if( _prod_id ) {
            i_product_attribute = {};
            // jQuery(this).find('.product-variations').each(function(var_i, var_el) {
            //     if( jQuery(var_el).find('button.active').length > 0 ){
            //         if( jQuery(var_el).data('attr') ) {
            //             i_product_attribute[ jQuery(var_el).data('attr') ] = jQuery(var_el).find('button.active').attr('name');
            //         }
            //     }
            // });
            jQuery(this).find('.bundle_dropdown_attr').each(function(var_i, var_el) {
                if( jQuery(var_el).val() ){
                    if( jQuery(var_el).data('attribute_name') ) {
                        i_product_attribute[ jQuery(var_el).data('attribute_name') ] = jQuery(var_el).val();
                    }
                }
            });
        }

        // linked variations
        var linked_product = {
            'id': '',
            'attributes': {}
        };
        if (jQuery(this).find('.linked_product.selected').attr('data-linked_id')) {
            var el_linked = jQuery(this).find('.linked_product.selected');
            linked_product['id'] = el_linked.attr('data-linked_id');
            linked_product['attributes'][el_linked.attr('data-attribute_name')] = el_linked.attr('data-option');
        }


        add_to_cart_items_data['products'][_prod_id+'_' + (index + 1)] = {
            product_id: _prod_id,
            linked_product: linked_product,
            variation_id: variation_id,
            i_product_attribute: i_product_attribute,
            qty: 1,
            separate: 1
        };
        
    });

    // add addon products
    if(jQuery('.bd_item_addons_div').length) {
        var addon_products = {
            'products': {}
        };

        jQuery('.bd_item_addons_div .bd_item_addon.i_selected').each(function(index, el) {
            // get addon id
            let _addon_id = jQuery(this).data('addon_id');
            // get product id
            let _prod_id = jQuery(this).data('id');
            addon_attr = {};

            jQuery(el).find('.info_variations .variation_item .addon_var_select').each(function(var_i, var_el) {
                if( jQuery(var_el).val() ){
                    if( jQuery(var_el).data('attribute_name') ) {
                        addon_attr[ jQuery(var_el).data('attribute_name') ] = jQuery(var_el).val();
                    }
                }
            });

            addon_products['products'][_prod_id+'_' + (index + 1)] = {
                product_id: _prod_id,
                bd_addon_id: _addon_id,
                i_product_attribute: addon_attr,
                qty: jQuery(el).find('.cao_qty .addon_prod_qty').val(),
            };
        });
    }

    var info = {};
    info['action'] = 'bd_add_to_cart_multiple';
    info['bundle_id'] = bundle_id;
    info['add_to_cart_items_data'] = add_to_cart_items_data;
    info['addon_products'] = addon_products;
    info['bd_first_check_ajax'] = 0;
    info['bd_dont_empty_cart'] = 1;

    //ajax update cart
    jQuery.post(bd_infos.ajax_url, info).done(function (data) {
        data = JSON.parse(data);
        if( data.status ){
            if ( redirect ) {
                window.location.href = bd_infos.checkout_url;
            } else {
            jQuery(document.body).trigger('wc_fragment_refresh');
            }
        } else {
            alert( data.html );
            jQuery('#bd_loading').hide();
        }
    });
}
// end function ajax add to cart


// function get price variation BD product
function bd_get_price_variation_product(bd_item_div) {

    var product_prices = [];
    if(bd_item_div.find('.bd_product_variations').hasClass("is_variable")) {
        bd_item_div.find('.c_prod_item').each(function (i, el) {
            product_prices.push( bd_variation_price[bd_item_div.attr('data-bundle_id')][jQuery(this).attr('data-variation_id')] );
        });

        var info = {};
        info['action'] = 'bd_get_price_variation_product';
        info['price_list'] = product_prices;
        info['cupon'] = bd_item_div.data('cupon');

        //ajax update cart
        jQuery.get(bd_infos.ajax_url, info).done(function (data) {
            data = JSON.parse(data);
            if( data.status ) {
                bd_item_div.find('.pi-price-pricing > .pi-price-each > span').first().html(data.single_price_html);
                bd_item_div.find('.pi-price-total > span').first().html(data.total_price_html);
                // set total price hidden input
                bd_item_div.find('.bd_bundle_price_hidden').first().val(data.total_price);
            }
        });
    }
    
}
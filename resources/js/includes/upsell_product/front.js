// addon product id selected
var bd_addon_selected = [];

jQuery(document).ready(function ($) {
  // add fancybox to bundle options
  $(".bd_fancybox_open").fancybox({
    width: "90%",
    height: "90%",
    type: "inline",
    touch: false,
    autoFocus: false,
  });
  // load lazy images fancybox popup
  $(".bd_fancybox_open").click(function(e) {
    $($(this).attr('href')).find('img').trigger('click');
  });

  // selected option product addon
  $(".bd_checkbox_addon").change(function (e) {
    if ($(this).is(":checked")) {
      $(this).parents(".bd_item_addon").addClass("i_selected");

      let addon_id = $(this).parents(".bd_item_addon").data("addon_id");
      if (!bd_addon_selected.includes(addon_id)) {
        // ajax update statistics addon product
        bd_update_addon_product_statistics(addon_id, "click");

        bd_addon_selected.push(addon_id);
      }
    } else {
      $(this).parents(".bd_item_addon").removeClass("i_selected");
    }

    // call ajax add to cart
    bd_update_item_cart_ajax();
  });

  // set image popup
  $(".bd_product_intro_container .intro_img_preview").click(function (e) {
    set_img = $(this).find("img").attr("src");
    $(this)
      .parents(".bd_product_intro_container .left_inner_div")
      .find(".i_wadc_full_image_div img")
      .attr("src", set_img);
  });

  $(".bd_product_additem_btn").click(function (e) {
    id = $(this).data("add_item");
    if (!$("#input_selected_product_" + id).is(":checked")) {
      $("#input_selected_product_" + id).trigger("click");
    }
    $.fancybox.close();
  });

  // update cart when select dropdown product variation
  $(".bd_item_addon .addon_var_select").change(function (e) {
    var _parent = $(this).parents(".bd_item_addon");
    var prod_id = _parent.data("id");
    var bun_img = _parent.find(".img_option img");
    var bun_price_html = _parent.find(".cao_title_options_cont .cao_price .price_change");
    var input_price_hidden = _parent.find(".bd_addon_price_hidden");

    var var_arr = new Array();
    $(this)
      .parents(".info_variations")
      .find(".variation_item")
      .each(function (index, el) {
        let _select = $(el).find(".addon_var_select");
        var_arr[_select.data("attribute_name")] = _select.val();
      });

    $.each(bd_addon_variation_data[prod_id], function (index, val) {
      var img = "";
      var price = "";
      var price_html = "";

      $.each(val["attributes"], function (i, e) {
        if (var_arr[i] && var_arr[i] == e) {
          img = val["image"];
          price_html = val["price_html"];
          price = val["price"];
        } else {
          img = "";
          price = "";
          return false;
        }
      });

      if (price && img) {
        // update variation img, price html to addon item
        bun_img.attr("src", img);
        bun_price_html.html(price_html);

        // change price value input hidden
        input_price_hidden.val(price);

        return false;
      }
    });

    // call func updatae cart
    if (_parent.hasClass("i_selected")) {
      bd_update_item_cart_ajax();
    }
  });

  // update cart when change qty
  $(".bd_item_addon .addon_prod_qty").change(function (e) {
    if ($(this).parents(".bd_item_addon").hasClass("i_selected")) {
      bd_update_item_cart_ajax();
    }
  });

  // function update statistics click addon product
  function bd_update_addon_product_statistics(addon_ids, type) {
    var info = {};
    info["action"] = "bd_update_addon_product_statistics";
    info["type"] = type;
    info["addon_ids"] = addon_ids;

    // call ajax
    jQuery.post(bd_infos.ajax_url, info).done();
  }

  // show more addons
  $('.bd_item_addons_div.see_more .bd_see_more button').click(function (e) {
    e.preventDefault();
    $(this).parents('.bd_item_addons_div').find('.bd_addon_div').show(200);
    $(this).hide();
  });

});

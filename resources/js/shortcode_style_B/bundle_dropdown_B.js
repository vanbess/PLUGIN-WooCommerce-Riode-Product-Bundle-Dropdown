'use strict';

(function ($) {

	$(document).ready(function () {
		$(".bd_select_wrap .default_option").click(function(){
			$(this).parent().toggleClass("active");
		});
		  
		$(".bd_select_wrap .bd_select_ul li").click(function(){
			var $this = $(this),
			currentele = $this.find('.col-inner').html(),
			// $chekcbox = $this.find('.bd_selected_package_product'),
			$wrap = $this.parents(".bd_select_wrap");

			$wrap.find(".default_option li").html(currentele);
			$wrap.removeClass("active");

			$wrap.find(".default_option li").find('.bd_c_package_info').removeClass('text-right');

			// $chekcbox.prop('checked', true);
			// $chekcbox.trigger('checked');
		});
	});
})(window.jQuery);
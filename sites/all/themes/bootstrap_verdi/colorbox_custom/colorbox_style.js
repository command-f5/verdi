isrc = window.isrc || false;
(function ($) {

Drupal.behaviors.initColorboxDefaultStyle = {
  attach: function (context, settings) {

    //фигня для добавления класса. Костыль то какой...
    if(
      context[0] &&
      context[0]['offsetParent'] &&
      context[0]['offsetParent']['ownerDocument'] &&
      context[0]['offsetParent']['ownerDocument']['defaultView'] &&
      context[0]['offsetParent']['ownerDocument']['defaultView']['$this'] &&
      context[0]['offsetParent']['ownerDocument']['defaultView']['$this']['context']
    ){
		var rel = jQuery(context[0]['offsetParent']['ownerDocument']['defaultView']['$this']['context']).attr('rel');
	} else {
		var rel = 'product_card';
	}
	
    var img = jQuery('#colorbox .nomaximg img');
	var src = img.attr('src');
	if( src == undefined ) return false;

	window.isrc = src;
	console.log('init cb '+src);
      var colorbox_div = jQuery('#colorbox');

        colorbox_div.addClass(rel);

        colorbox_div.find('.product_cell_2').hide();

        colorbox_div.css('overflow', 'visible').css('border-radius', '8px').css('width', 'auto').css('height', 'auto').css('max-height', '100%').css('max-width', '100%').css('min-width', '700px');

        function colorbox_resize(img, colorbox_div) {

          var resized = false;
          //не помещается по высоте
          if (img.attr('height') > jQuery(window).height()-135) {
            var xy = img.attr('height')/img.attr('width');
            img.height(jQuery(window).height()-135);
            img.width((jQuery(window).height()-135)/xy);
            resized = true;
          }

          //не помещается по ширине
          if (img.attr('width') > jQuery(window).width()-330) {
            var width = img.attr('width');
            var height = img.attr('height');
            if (resized) {
              width = img.width();
              height = img.height();
            }
            var xy = height / width;

            var new_width = Math.max(jQuery(window).width()-330, 670);
            var new_height = (jQuery(window).width()-330) * xy;

            if (new_height > height && new_width != 670) {
              new_height = height;
              new_width = height/xy;
            }

            img.width(new_width);
			 img.attr({width:new_width});
            img.height(new_height);
			 img.attr({height:new_height});
          }

          colorbox_div.css('left', Math.max(0, (jQuery(window).width()-colorbox_div.width())/2));
          colorbox_div.css('top', Math.max(0, (jQuery(window).height()-colorbox_div.height())/3));

          colorbox_div.find('.product_table').width( (colorbox_div.width()-30) +'px');
          colorbox_div.find('.product_cell_2').show();
        }

        var cboxWrapper_div = jQuery('#cboxWrapper');
        cboxWrapper_div.css('width', 'auto').css('height', 'auto').css('overflow', 'visible').css('position', 'relative');

        jQuery('#cboxTopLeft').remove();
        jQuery('#cboxTopCenter').remove();
        jQuery('#cboxTopRight').remove();
        jQuery('#cboxMiddleLeft').remove();
        jQuery('#cboxMiddleRight').remove();
        jQuery('#cboxBottomLeft').remove();
        jQuery('#cboxBottomCenter').remove();
        jQuery('#cboxBottomRight').remove();

        var cboxContent_div = jQuery('#cboxContent');
        cboxContent_div.css('width', 'auto').css('height', 'auto').css('padding', '15px').css('float', 'none');

        var cboxLoadedContent_div = jQuery('#cboxLoadedContent');
        cboxLoadedContent_div.css('width', 'auto').css('height', 'auto').css('margin', '10px 0px 0px').css('overflow', 'visible');

		colorbox_resize(img, colorbox_div);
		
        // TODO: исправить ресайз. Стоит ли удалять стандартный?
        jQuery(window).unbind('resize');
        jQuery(window).resize(function()  {
          colorbox_resize(img, colorbox_div);
        });
		
		$('#cboxCurrent').hide();
		$('#cboxTitle').hide();
		
	$('#cboxClose,#cboxOverlay').on('click', function(){
		if( window.isrc ) window.isrc = '';
	});

    $(document).bind('cbox_complete', function () {
      // Only run if there is a title.
      if ($('#cboxTitle:empty').length == false) {
        $('#cboxLoadedContent img').bind('mouseover', function () {
          $('#cboxTitle').slideDown();
        });
        $('#cboxOverlay').bind('mouseover', function () {
          $('#cboxTitle').slideUp();
        });
      } else {
        $('#cboxTitle').hide();
      }
    });
  }
};

})(jQuery);

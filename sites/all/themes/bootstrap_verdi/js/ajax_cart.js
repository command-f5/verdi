jQuery(document).ready(function($){
	ajaxCartInit( false );
});

jQuery(document).ajaxSuccess(function(evt, request, settings){
	jQuery('.commerce-add-to-cart').each(function(){
		jQuery(this).attr({ action: window.location.href });
	});
	var href = false;
	ajaxCartInit( href );
});

function ajaxCartInit( href ){

	jQuery('form.commerce-add-to-cart').on('submit',function(){
	if(jQuery(this).find('button').attr('class').indexOf('loade')>=0) return false;
		var product_id = jQuery(this).find('input[name="product_id"]').val(),
			form_build_id = jQuery(this).find('input[name="form_build_id"]').val(),
			form_token = jQuery(this).find('input[name="form_token"]').val() || 0,
			form_id = jQuery(this).find('input[name="form_id"]').val(),
			quantity = jQuery(this).find('input[name="quantity"]').val(),
			action = href || jQuery(this).attr('action'), a = this, $b = jQuery(this).find('button'), cart;
		jQuery.ajax({
			url: action,
			type: "POST",
			data: 'product_id='+product_id+'&form_build_id='+form_build_id+'&form_token='+form_token+'&form_id='+form_id+'&quantity='+quantity,
	  beforeSend: function(){
					jQuery(a).find('button').addClass('loade');
				},
		success: function(data){
					if(data.indexOf('добавлен <a href="/cart">в корзину</a>.')>0){
						alert('Товар добавлен в корзину.');
						$b.html('Товар в корзине');
						cart = jQuery(data).find('.line-item-summary').html() || false;
						if( cart ) jQuery('.line-item-summary').html(cart);
					}
				},
		complete: function(){
					jQuery(a).find('button').removeClass('loade');
				}
		});
		return false;
	});
}
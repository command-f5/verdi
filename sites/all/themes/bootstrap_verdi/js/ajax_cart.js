jQuery(document).ready(function($){
	ajaxCartInit( false );
});

jQuery(document).ajaxSuccess(function(evt, request, settings){
	/*
	var is_catalog = jQuery('#views-exposed-form-catalog-block').attr('action') || false,
		href = false;
	if(is_catalog){
		var p = parseInt(jQuery('.pagination').find('li.active > a').html()),
			next_href = jQuery('.pagination').find('li.next > a').attr('href') || false,
			prev_href = jQuery('.pagination').find('li.prev > a').attr('href') || false,
			razn = 0, pag = 0;
			
		if(next_href){
			razn = -1;
			href = next_href;
		} else if(prev_href){
			razn = 1;
			href = prev_href;
		}
		if( href ){
			var matches = href.match(/(.+)&page=([0-9]+)/i);
			pag = parseInt(matches[2])+razn;
			
			href = matches[1]+'&page='+pag;
		} else {
			var prid = jQuery('#edit-field-product-kind-tid-1').find('input.bef-select-as-radios.form-radio:checked').val() || false,
				sku = jQuery('#edit-sku').val() || '',
				pr_min = jQuery('#edit-commerce-price-amount-min').val() || '',
				pr_max = jQuery('#edit-commerce-price-amount-max').val() || '',
				hot = jQuery('#edit-field-hot-offer-value-1:checked').val() || '',
				newst = jQuery('#edit-field-new-value-1:checked').val() || '';
			if( prid ){
				if( hot != '' ) hot = 'field_hot_offer_value[0]='+hot;
				if( newst != '' ) newst = 'field_new_value[0]='+newst;
				href = '/catalog?field_product_kind_tid_1='+prid+'&sku='+sku+'&commerce_price_amount[min]='+pr_min+'&commerce_price_amount[max]='+pr_max+'&'+hot+'&'+newst+'&page=0';
			}
		}
	}
	*/
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
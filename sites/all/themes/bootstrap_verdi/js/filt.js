jQuery(document).ready(function($){

	hideEmptyMenus();

	initFilt();

});



jQuery(document).ajaxSuccess(function(){

	hideEmptyMenus();

	initFilt();

});



function hideEmptyMenus(){

	var $menu = jQuery('#block-menu-menu-katalog').find('ul.menu.nav') || false,

		$filt = jQuery('.bef-tree.main') || false,

		 menu_items = [], i = 0, parents = {};

	if( $menu && $filt ){

		$menu.find('li a').each(function(){

			menu_items[i] = jQuery(this).attr('title');

			i++;

		});

		$filt.find('li').each(function(){

			var nam = jQuery(this).find('label').html() || false,

				childs = jQuery(this).find('ul').html() || false,

				key = 0;

				if( nam ){

					nam = nam.trim();

					key = menu_items.indexOf(nam);

				}

			if( !childs && key < 0 ){

				jQuery(this).children().remove();

				console.log(nam+' removed');

			}

		});

	}

}



function initFilt(){



	if(jQuery('#edit-field-product-kind-tid-1').find('#chlds1').html()==null){

		jQuery('#edit-field-product-kind-tid-1').append('<div id="chlds1"><ul></ul></div><div id="chlds2"><ul></ul></div>');

	}

	

	jQuery('#edit-field-product-kind-tid-1-wrapper .bef-select-as-radios > ul.bef-tree > li > div > label').on('click',function(){

		var id = jQuery(this).attr('for'), ul = jQuery(this).parent().parent().parent(),

			$childUL1 = jQuery(this).parent().parent().find('ul'),

				 cls = jQuery(this).attr('class') || 'no';

		if(jQuery('#'+id).prop('checked')==false && cls.indexOf('activ')==-1){

			jQuery(ul).find('.activ').removeClass('activ');

			jQuery(ul).find('li').find('input').attr('checked',false);

			jQuery('#'+id).attr('checked',true);

			jQuery(this).addClass('activ');

			

			jQuery(this).parent().find('ul').show(100);

			// 2 уровень

			if($childUL1.html()!=null){

				jQuery('#chlds2 > ul').html('');

				jQuery('#chlds2').hide(50);	

				jQuery('#chlds1 > ul').html($childUL1.html());

					jQuery('#chlds1 > ul > li > div > label').on('click',function(){

						var $inp = jQuery(this).parent().find('input'),

							inpid = $inp.attr('id'),

							ul = jQuery(this).parent().parent().parent(),

							cls = jQuery(this).attr('class') || 'no',

							$childUL2 = jQuery('#'+inpid).parent().parent().find('ul');

						if($inp.prop('checked')==false && cls.indexOf('activ')==-1){

							jQuery(ul).find('.activ').removeClass('activ');

							jQuery(ul).find('li').find('input').attr('checked',false);

							$inp.attr('checked',true);

							jQuery(this).addClass('activ');

						}

						// 3 уровень

						if($childUL2.html()!=null){

							jQuery('#chlds2 > ul').html($childUL2.html());

								jQuery('#chlds2 > ul > li > div > label').on('click',function(){

									var $inp = jQuery(this).parent().find('input'),

										  ul = jQuery(this).parent().parent().parent(),

										 cls = jQuery(this).attr('class') || 'no'

									if($inp.prop('checked')==false && cls.indexOf('activ')==-1){

										jQuery(ul).find('.activ').removeClass('activ');

										jQuery(ul).find('li').find('input').attr('checked',false);

										$inp.attr('checked',true);

										jQuery(this).addClass('activ');

									}

									// отправка если 3 уровень

									jQuery('#edit-submit-catalog').click();

								});

							jQuery('#chlds2').show(150);

						} else {

							jQuery('#views-exposed-form-catalog-block').find('ul.bef-tree-depth-2 label.activ').each(function(){

								var inp_id = jQuery(this).attr('for');

								jQuery('input#'+inp_id).attr('checked',false);

								jQuery(this).removeClass('activ');

							});

							jQuery('#chlds2 > ul').html('');

							jQuery('#chlds2').hide(50);

							// отправка если нет 3 уровня

							jQuery('#edit-submit-catalog').click();

						}

					});

				jQuery('#chlds1').show(150);

			} else {

				jQuery('#views-exposed-form-catalog-block').find('ul.bef-tree-depth-2 label.activ').each(function(){

					var inp_id = jQuery(this).attr('for');

						jQuery('input#'+inp_id).attr('checked',false);

						jQuery(this).removeClass('activ');

				});

				jQuery('#chlds2 > ul').html('');

				jQuery('#chlds2').hide(50);

				jQuery('#chlds1 > ul').html('');

				jQuery('#chlds1').hide(50);

				// отправка если нет 2 уровня

				jQuery('#edit-submit-catalog').click();

			}

		}

	});

	

	jQuery('#edit-field-product-kind-tid-1-wrapper ul li > div > input').each(function(){

		if(jQuery(this).prop('checked')==true){

			var $childUL1 = jQuery(this).parent().parent().find('ul'),

				pULclass = jQuery(this).parent().parent().parent().attr('class');

			if( pULclass != undefined && pULclass.indexOf('main') >= 0 ){

				jQuery('#chlds1 > ul').html($childUL1.html());

				jQuery(this).parent().find('label').addClass('activ');

				// 2 уровень

				if($childUL1.html()!=null){

					jQuery('#chlds1 > ul').html($childUL1.html());

						jQuery('#chlds1 > ul > li > div > label').on('click',function(e){

							var $inp = jQuery(this).parent().find('input'),

							   inpid = $inp.attr('id'),

								  ul = jQuery(this).parent().parent().parent(),

								 cls = jQuery(this).attr('class') || 'no',

						   $childUL2 = jQuery('#'+inpid).parent().parent().find('ul');

							if($inp.prop('checked')==false && cls.indexOf('activ')==-1){

								jQuery(ul).find('.activ').removeClass('activ');

								jQuery(ul).find('li').find('input').attr('checked',false);

								$inp.attr('checked',true);

								jQuery(this).addClass('activ');

							}

							// 3 уровень

							if($childUL2.html()!=null){

								jQuery('#chlds2 > ul').html($childUL2.html());

									jQuery('#chlds2 > ul > li > div > label').on('click',function(){

										var $inp = jQuery(this).parent().find('input'),

											  ul = jQuery(this).parent().parent().parent(),

											 cls = jQuery(this).attr('class') || 'no';

										if($inp.prop('checked')==false && cls.indexOf('activ')==-1){

											jQuery(ul).find('.activ').removeClass('activ');

											jQuery(ul).find('li').find('input').attr('checked',false);

											$inp.attr('checked',true);

											jQuery(this).addClass('activ');

										}

									});

								jQuery('#chlds2').show(150);

							} else {

								jQuery('#chlds2 > ul').html('');

								jQuery('#chlds2').hide(50);

							}

						});

					jQuery('#chlds1').show(150);

				} else {

					jQuery('#chlds1 > ul').html('');

					jQuery('#chlds1').hide(50);

				}

			} else {

				var this_ul = jQuery(this).parent().parent(), ul = jQuery(this).parent().parent().parent(), deph = jQuery(ul).attr('class');

				if( deph != undefined ){

					deph = parseInt(deph.replace('bef-tree child bef-tree-depth-',''));

				} else {

					deph = false;

				}

				if( deph == 2 ){

					var $pul = jQuery(ul).parent(), $mul = $pul.parent().parent();

					$pul.find('label').addClass('activ');

					jQuery('#chlds1 > ul').html($pul.parent().parent().find('ul.bef-tree-depth-1').html());

					jQuery('#chlds1').show(150);

					$mul.children('div').find('label').addClass('activ');

						jQuery('#chlds1 > ul > li > div > label').on('click',function(){

							var $inp = jQuery(this).parent().find('input'),

							   inpid = $inp.attr('id'),

								  ul = jQuery(this).parent().parent().parent(),

								 cls = jQuery(this).attr('class') || 'no',

						   $childUL2 = jQuery('#'+inpid).parent().parent().find('ul');

							if($inp.prop('checked')==false && cls.indexOf('activ')==-1){

								jQuery(ul).find('.activ').removeClass('activ');

								jQuery(ul).find('li').find('input').attr('checked',false);

								$inp.attr('checked',true);

								jQuery(this).addClass('activ');

							}

							// 3 уровень

							if($childUL2.html()!=null){

								jQuery('#chlds2 > ul').html($childUL2.html());

									jQuery('#chlds2 > ul > li > div > label').on('click',function(){

										var $inp = jQuery(this).parent().find('input'),

											  ul = jQuery(this).parent().parent().parent(),

											 cls = jQuery(this).attr('class') || 'no';

										if($inp.prop('checked')==false && cls.indexOf('activ')==-1){

											jQuery(ul).find('.activ').removeClass('activ');

											jQuery(ul).find('li').find('input').attr('checked',false);

											$inp.attr('checked',true);

											jQuery(this).addClass('activ');

										}

										// отправка если 3 уровень

										setTimeout(function(){

											jQuery('#edit-submit-catalog').click();

										}, 150 );

									});

								jQuery('#chlds2').show(150);

							} else {

								jQuery('#views-exposed-form-catalog-block').find('ul.bef-tree-depth-2 label.activ').each(function(){

									var inp_id = jQuery(this).attr('for');

									jQuery('input#'+inp_id).attr('checked',false);

									jQuery(this).removeClass('activ');

								});

								jQuery('#chlds2 > ul').html('');

								jQuery('#chlds2').hide(50);

								// отправка если нет 3 уровня

								setTimeout(function(){

									jQuery('#edit-submit-catalog').click();

								}, 150 );

							}

						});

					jQuery(ul).parent().find('label').addClass('activ');

					jQuery(ul).find('.activ').removeClass('activ');

					jQuery(this).parent().find('label').addClass('activ');

					jQuery('#chlds2 > ul').html(ul.html());

						jQuery('#chlds2 > ul > li > div > label').on('click',function(){

							var $inp = jQuery(this).parent().find('input'),

								 ul = jQuery(this).parent().parent().parent(),

								 cls = jQuery(this).attr('class') || 'no';

							if($inp.prop('checked')==false && cls.indexOf('activ')==-1){

								jQuery(ul).find('.activ').removeClass('activ');

								jQuery(ul).find('li').find('input').attr('checked',false);

								$inp.attr('checked',true);

								jQuery(this).addClass('activ');

							}

							// отправка если 3 уровень

							jQuery('#edit-submit-catalog').click();

						});

					jQuery('#chlds2').show(150);

				} else {

					jQuery(ul).parent().find('label').addClass('activ');

					jQuery(ul).find('.activ').removeClass('activ');

					jQuery(this).parent().find('label').addClass('activ');

					jQuery('#chlds1 > ul').html(ul.html());

						jQuery('#chlds1 > ul > li > div > label').on('click',function(){

							 var $inp = jQuery(this).parent().find('input'),

								inpid = $inp.attr('id'),

								   ul = jQuery(this).parent().parent().parent(),

								  cls = jQuery(this).attr('class') || 'no',

							$childUL2 = jQuery('#'+inpid).parent().parent().find('ul');

							if($inp.prop('checked')==false && cls.indexOf('activ')==-1){

								jQuery(ul).find('.activ').removeClass('activ');

								jQuery(ul).find('li').find('input').attr('checked',false);

								$inp.attr('checked',true);

								jQuery(this).addClass('activ');

							}

							// 3 уровень

							if($childUL2.html()!=null){



								jQuery('#chlds2 > ul').html($childUL2.html());

									jQuery('#chlds2 > ul > li > div > label').on('click',function(){

										var $inp = jQuery(this).parent().find('input'),

											  ul = jQuery(this).parent().parent().parent(),

											 cls = jQuery(this).attr('class') || 'no';

										if($inp.prop('checked')==false && cls.indexOf('activ')==-1){

											jQuery(ul).find('.activ').removeClass('activ');

											jQuery(ul).find('li').find('input').attr('checked',false);

											$inp.attr('checked',true);

											jQuery(this).addClass('activ');

										}

										// отправка если 3 уровень

										jQuery('#edit-submit-catalog').click();

									});

								jQuery('#chlds2').show(150);

							} else {

								jQuery('#chlds2 > ul').html('');

								jQuery('#chlds2').hide(50);

								// отправка если нет 3 уровня

								jQuery('#edit-submit-catalog').click();

							}

						});

					jQuery('#chlds1').show(150);

					var $childUL = jQuery(this_ul).find('ul.bef-tree-depth-2');

					if( $childUL.html()!=null ){

						jQuery('#chlds2 > ul').html($childUL.html());

						jQuery('#chlds2 > ul > li > div > label').on('click',function(){

						var $inp = jQuery(this).parent().find('input'),

							  ul = jQuery(this).parent().parent().parent(),

							 cls = jQuery(this).attr('class') || 'no';

							if($inp.prop('checked')==false && cls.indexOf('activ')==-1){

								jQuery(ul).find('.activ').removeClass('activ');

								jQuery(ul).find('li').find('input').attr('checked',false);

								$inp.attr('checked',true);

								jQuery(this).addClass('activ');

							}

							// отправка если 3 уровень

							jQuery('#edit-submit-catalog').click();

						});

						jQuery('#chlds2').show(150);

						

					}

				}

			}

		}

	});

}
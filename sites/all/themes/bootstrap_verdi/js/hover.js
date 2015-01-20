
jQuery( document ).ready(function() {
	
	Drupal.behaviors.mybehavior = {
		
 		attach: function (context, settings)
		{
			set_active();
		}
	}	
	
  function set_active() {
    jQuery('.views-view-grid td').each(function(){
      if (jQuery(this).children().length != 0) {
        jQuery(this).hover(function(){
          jQuery(this).addClass('active');
        },
        function(){
          jQuery(this).removeClass('active');
        });
      }
    });
  }
  set_active();
 
});

(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

    var fc_plug = true;
	
	jQuery( document ).on('click', '.fc_deactivate_link', function(){
		event.preventDefault();
		var urlRedirect = jQuery( this ).attr('href');
		jQuery(".fc-deactivate-popup-header").addClass("show_pop");
		jQuery(".fc-deactivate-action").attr('href', urlRedirect);
	});
	
	jQuery( document ).on('click', '.fc-close-popup', function(){
        jQuery(".fc-deactivate-popup-header").removeClass("show_pop");
        jQuery(".fc-deactivate-action").attr('href', 'javascript:;');
        jQuery(".fc-deactivate-action").removeClass("fc-bulk-action");
	});
	
	jQuery( document ).on('click', '.fc_delete_user_link', function(){
		event.preventDefault();
		alert("It is not possible to delete this admin user as it is required for FastPress");
    });

    jQuery( document ).on('click', '.fc-bulk-action', function(){
        fc_plug = false;
        jQuery("#bulk-action-form").trigger("submit");
    });
    
    jQuery( document ).on('submit', '#bulk-action-form', function(){
		
        var action = jQuery("#bulk-action-selector-top").val();
        var checked = [];

        if(action == 'deactivate-selected'){
            jQuery('input[type="checkbox"]:checked').each(function () {
                checked.push($(this).val());
            });

            if(jQuery.inArray("fresh-connect/init.php", checked) !== -1 && fc_plug){
                jQuery(".fc-deactivate-popup-header").addClass("show_pop");
                jQuery(".fc-deactivate-action").addClass("fc-bulk-action");
                return false;
            }
        }
	});

})( jQuery );

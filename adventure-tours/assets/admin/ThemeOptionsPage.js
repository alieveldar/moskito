/**
 * Controller object for Theme Options section.
 *
 * @author    Themedelight
 * @package   Themedelight/AdventureTours
 * @version   3.0.1
 */

var ThemeOptionsPage = {
	init:function(){
		this._initTourPageValidation();
	},

	_initTourPageValidation:function(){
		var page_selector = jQuery('select[name=tours_page]'),
			_show_tours_page_error = function(msg){
				var errors_container = page_selector.parents('.vp-field').find('.field');
				if ( errors_container.length < 1 ) return;
				if ( ! msg ) {
					errors_container.find('.at-val-errors').remove();
				} else {
					errors_container.append('<div class="at-val-errors" style="color:#EE0000;margin-top:3px;">' + msg +'</div>');
				}
			};

		if ( ! ajaxurl || page_selector.length < 1 ) {
			return;
		}

		page_selector.on('change',function(){
			var cur_value = jQuery(this).val();
			if ( ! cur_value ) {
				_show_tours_page_error();
				return;
			}

			jQuery.ajax({
				url: ajaxurl,
				method:'POST',
				data:{
					action: 'validate_tours_page',
					page_id: cur_value
				},
				dataType: 'json',
				success: function(r){
					if ( r.is_success ) {
						_show_tours_page_error( r.is_valid ? '' : r.error || 'Unknown validation error. Please contact support.' );
					} else {
						_show_tours_page_error( 'Unknow request error. Please contact support.' );
					}
				}
			});
		}).trigger('change');
	}
};

jQuery(function(){
	ThemeOptionsPage.init();
});

var WooCommerceTourReports = {
	init:function($){
		this._initPurgeCacheButton($);
		this._initTourItemFilter($);
		this._initTourFilterDateMode($);
	},

	getConfig:function( option, default_value ){
		var cfg = window._WooCommerceTourReportsCfg ? window._WooCommerceTourReportsCfg : {};
		if ( option ) {
			return cfg && typeof cfg[option] != 'undefined' ? cfg[option] : (
				typeof default_value != 'undefined' ? default_value : null
			);
		}
		return cfg;
	},

	_initPurgeCacheButton:function($){
		var csv_button = $('.postbox .export_csv');

		if ( csv_button.length < 1 ) {
			return;
		}

		$('<a href="#"></a>')
			.css({
				'float': csv_button.css('float'),
				'line-height': csv_button.css('line-height'),
				'border-left': csv_button.css('border-left'),
				'text-decoration':'none',
				'padding': csv_button.css('padding')
			})
			.text( this.getConfig( 'purge_cache_btn_text', 'Purge Cache') )
			.insertAfter(csv_button)
			.click(function(){
				$.ajax({
					url:document.location + '&nocache=1',
					complete:function(){
						document.location.reload();
					}
				});
				return false;
			});
	},

	_initTourItemFilter:function($){
		var selectFilter = $('#tourReportsItemFilter'),
			originalRows = $('.widefat tr[data-tourspeckey]');

		if ( selectFilter.length < 1 || originalRows.length < 1 ) {
			return;
		}

		var rows = originalRows.clone(),
			cont = originalRows.first().parent();

		selectFilter.on('change',function(){
			var filter = $(this).val();
			cont.html('')
				.append( filter ? rows.filter('[data-tourspeckey="'+filter+'"]').clone() : rows.clone() );
		});
		if ( selectFilter.val() != '' ) {
			selectFilter.trigger('change');
		};
	},

	_initTourFilterDateMode:function($){
		var cont = $('.stats_range'),
			select = cont.find('[name="date_filter_mode"]'),
			active_tab = cont.find('li.active');

		if ( 'tour_date' == select.val() ) {
			jQuery('.range_datepicker.to').datepicker('option', {'maxDate':'+1Y'}); // expands max date for custom dates range filter
		}

		if ( select.length < 1 || active_tab.hasClass('custom') ) {
			return;
		}

		select.on('change',function(){
			var el = $(this);
			document.location += '&' + el.attr('name') + '=' + el.val();
		});
	}
};

jQuery(function($){
	WooCommerceTourReports.init($);
});
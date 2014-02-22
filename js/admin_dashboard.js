jQuery(document).ready(function ($) {

	/**** GLOBAL VARS INIT ****/

	window.dashboard_summary_widget = $('#dashboard_summary');

	var dashboard_summary_period = localStorage.getItem('dashboard_summary_period') || "total"; // load visualization period from browser cache

	var are_results_being_called = false;

	/**** FUNCTIONS ****/

	opbg.set_datepickers = function(target) {

		var from = moment(dashboard_summary_widget.find('.status-data-range .datepicker-box[data-box = from]').val());
		var to = moment(dashboard_summary_widget.find('.status-data-range .datepicker-box[data-box = to]').val());
		var changed = $(target).data("box");

		console.log(from.format());
		console.log(to.format());

		if (from.format() === "Invalid date" || to.format() === "Invalid date"){
			return false;
			/*from = to = moment();
			dashboard_summary_widget.find('.status-data-range .datepicker-box[data-box = from]').val(from.format('YYYY-MM-DD'));
			dashboard_summary_widget.find('.status-data-range .datepicker-box[data-box = to]').val(to.format('YYYY-MM-DD'));*/
		}

		if (changed === "from" ){
			if (from.isAfter(to)){
				from = moment(to);
				dashboard_summary_widget.find('.status-data-range .datepicker-box[data-box = from]').val(from.format('YYYY-MM-DD'));
			}

		}
		if (changed === "to"){
			if (to.isBefore(from)){
				to = moment(from);
			}
			else if (to.isAfter(moment())){
				to = moment();
			}

			dashboard_summary_widget.find('.status-data-range .datepicker-box[data-box = to]').val(to.format('YYYY-MM-DD'));
		}

		localStorage.setItem( 'dashboard_summary_period', from.format('YYYY-MM-DD')+" "+to.format('YYYY-MM-DD') );

		dashboard_summary_period = from.format('YYYY-MM-DD')+" "+to.add('days', 1).format('YYYY-MM-DD');

		opbg.get_dashboard_summary();
	};

	opbg.dashboard_summary_get_statistics = function() {

        var results     = {};
        var classes     = [];

		window.dashboard_summary_widget.find('.status-table .key').each(function(){
			classes.push($(this).attr('class').replace(/ key/g, ''));
		});

		$(classes).each(function(){
			var value = window.dashboard_summary_widget.find('.status-table .'+this+'.value').data('status-value');

			//results[this] = !isNaN(window.parseInt(value)) ? window.parseInt(value) : value;

			results[this.replace(/(status-)/, '')] = value;
		});

		console.log(results);

		return results;
	};

	// Print resources status for summary dashboard widget
	opbg.dashboard_summary_print_status_values = function (target) {

		if (target !== undefined) target.parent().find('.btn').toggleClass('active');

		var mode = dashboard_summary_widget.find('.status-view-toggle .btn.active').data('toggle-option');

		dashboard_summary_widget.find('.status-table .value').each(function(){
			var $this = $(this);
			var new_val;
			if (mode == '%' && !$(this).hasClass('status-total')){
				if (dashboard_summary_widget.find('.status-table .status-total.value').data('status-value') !== 0) {
					new_val = Math.round( ($this.data('status-value') / dashboard_summary_widget.find('.status-table .status-total.value').data('status-value') * 1000) ) / 10;
				}
				else {
					new_val = 0;
				}
				new_val += '%';
			}
			else {
				new_val = $this.data('status-value');
			}

			$this.text(new_val);
		});
	};

	// Upgrade resources status for summary dashboard widget
	opbg.dashboard_summary_upgrade_status_values = function(data){
		console.log(data);

		for (var status in data){
			dashboard_summary_widget.find('.status-table .value.status-' + status).attr('data-status-value', data[status]).data('status-value', data[status]);
		}
		opbg.dashboard_summary_print_status_values();
	};

	opbg.dashboard_summary_change_visualization_period = function(target){

		var pressed_button	= target.data('toggle-option');

		console.log(pressed_button);

		if (dashboard_summary_widget.find('.status-period-toggle .active').data('toggle-option') === pressed_button ) return false;

		if (pressed_button === "range"){
			dashboard_summary_widget.find('.status-data-range').addClass('show');
			dashboard_summary_widget.find('.status-period-toggle button').toggleClass('active');
		}
		else {
			dashboard_summary_widget.find('.status-data-range').removeClass('show');

			dashboard_summary_period = "total";

			opbg.get_dashboard_summary();

			dashboard_summary_widget.one('dashboard-summary', function(){
				dashboard_summary_widget.find('.status-period-toggle button').toggleClass('active');
			});
		}

		return false;
	};

	opbg.get_dashboard_summary = function() {
		var nonce					= dashboard_summary_widget.find('#dashboard-nonce').val();
        var buttons                 = dashboard_summary_widget.find('.status-period-toggle .btn');
		var spinner					= dashboard_summary_widget.find('.status-period-toggle .spinner');
        var datepickers             = dashboard_summary_widget.find('.status-data-range .datepicker-box');
		var success					= false;
        are_results_being_called	= true;

		spinner.removeClass('hide');

		buttons.addClass('disabled');
		datepickers.attr('disabled', true);

		console.log('asking for results with period: '+dashboard_summary_period);

		$.ajax({
			type:	"post",
			url:	ajaxurl,
			data:	{
				'action':       "dashboard_widget_control",
				'nonce':        nonce,
				'button_id':    'get-dashboard-summary',
				'message':      dashboard_summary_period
			},
			success: function(response) {
				if (response.success === true){
					opbg.dashboard_summary_upgrade_status_values(response.status);
					dashboard_summary_widget.trigger('dashboard-summary');
				}
				else {
					alert('Some server error occurred...');
				}
			},
			error: function(response){
				alert('Some connection error occurred...');
			},
			complete: function(response){
				spinner.addClass('hide');
				buttons.removeClass('disabled');
				datepickers.attr('disabled', false);
				are_results_being_called = false;
			}
		});
	};

	opbg.dashboard_summary_set_button_state = function(button){

		console.log(button[0]);

		var button_id = button.attr('id');

		var p = button.closest('.toggle-filters').find('p[data-button-reference = ' + button_id +']');

		var state = button.data('status');

		console.log(state);

		if (state === 1) {
			p.find('span').text(p.data('threshold'));
			button.addClass('active').find('span').text('Deactivate ');
		}
		else if (state === 0) {
			p.find('span').text('off');
			button.removeClass('active').find('span').text('Activate ');
		}
		button.find('i').show();
	};

	/**** MAIN ****/

	if (dashboard_summary_period !== 'total') {

		var periods = dashboard_summary_period.split(' ');
		dashboard_summary_widget.find('.status-data-range .datepicker-box[data-box = from]').val(moment(periods[0]).format('YYYY-MM-DD'));
		dashboard_summary_widget.find('.status-data-range .datepicker-box[data-box = to]').val(moment(periods[1]).format('YYYY-MM-DD'));

		opbg.dashboard_summary_change_visualization_period(dashboard_summary_widget.find('button[data-toggle-option = range]'));

		opbg.set_datepickers(dashboard_summary_widget.find('.status-data-range .datepicker-box[data-box=from]'));
	}
	else {
		dashboard_summary_widget.find('.status-data-range .datepicker-box[data-box = from]').val(moment().subtract('days', 1).format('YYYY-MM-DD'));
		dashboard_summary_widget.find('.status-data-range .datepicker-box[data-box = to]').val(moment().format('YYYY-MM-DD'));

		opbg.get_dashboard_summary();
	}

	/**** EVENTS HANDLERS ****/

	$(document).on('heartbeat-send', function(e, data) {

		if (are_results_being_called === true) return; //ajax call on the run

		console.log('heartbeat-send');
		//if (are_first_results_received === false) return;
		data.dashboard_heartbeat = 'upgrade_dashboard_summary';
		data.dashboard_actual_status = opbg.dashboard_summary_get_statistics();
		data.dashboard_actual_period = dashboard_summary_period;
	});

	$(document).on( 'heartbeat-tick', function(e, data) {

		if (are_results_being_called === true) return; //ajax call on the run

		console.log('heartbeat-tick');

		if ( data.is_database_changed !== true ) return; //Only proceed if our data is present

		opbg.get_dashboard_summary();
	});

	dashboard_summary_widget.find('.resources-control').on('click.resources_control', 'button:not(.disabled)', function(){
		console.log('toggling fetching');
		var $this       = $(this);
		var nonce       = dashboard_summary_widget.find('#dashboard-nonce').val();
		var button_id   = $this.attr('id');
		var message     = $this.data('status') ? "off" : "on";

		console.log({
			'action':       "dashboard_widget_control",
			'nonce':        nonce,
			'button_id':    button_id,
			'message':      message

		});

		$this.addClass('disabled').find('span').text('wait...');
		$this.find('i').hide();

		$.ajax({
			type:	"post",
			url:	ajaxurl,
			data:	{
				'action':       "dashboard_widget_control",
				'nonce':        nonce,
				'button_id':    button_id,
				'message':      message
			},
			success: function(response) {
				if (response.success === true){
					if (response.status === "active"){
						$this.data('status', 1);
					}
					else{
						$this.data('status', 0);
					}
				}
				else {
					console.log(response);
					alert('Some server error occurred...');
				}
			},
			error: function(response){
				console.log(response);
				alert('Some connection error occurred...');
				console.success = false;
			},
			complete: function(response){
				$this.removeClass('disabled');
				opbg.dashboard_summary_set_button_state($this);
			}
		});

		return false;

	});

	dashboard_summary_widget.find('.status-data-range').on('change.datepicker', '.datepicker-box:not(.disabled)', function(e){
		console.log(e.target);

		opbg.set_datepickers(e.target);
	});
});
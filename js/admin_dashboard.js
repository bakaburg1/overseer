jQuery(document).ready(function ($) {

	//console.log('admin_dashboard.js called');

	window.dashboard_summary_widget = $('#dashboard_summary');

	// Print resources status for summary dashboard widget
	opbg.dashboard_summary_print_status_values = function () {

		var mode = dashboard_summary_widget.find('.status-view-toggle button.active').data('toggle-option');

		dashboard_summary_widget.find('.status-table .value').each(function(){
			var $this = $(this);
			var new_val;
			if (mode == '%'){
				new_val = Math.round( ($this.data('status-value') / dashboard_summary_widget.find('.status-table .status-total.value').data('status-value') * 1000) ) / 10;
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
			dashboard_summary_widget.find('.status-table .value.status-' + status).data('status-value', data[status]);
		}
		opbg.dashboard_summary_print_status_values();
	};

	$(document).on('heartbeat-send', function(e, data) {
		data.dashboard_heartbeat = 'upgrade_dashboard_summary';
	});

	$(document).on( 'heartbeat-tick', function(e, data) {
		// Only proceed if our data is present
		if ( ! data.dashboard_summary_data )
			return;

		opbg.dashboard_summary_upgrade_status_values(data.dashboard_summary_data);
	});

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

	opbg.dashboard_summary_print_status_values();

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

	opbg.dashboard_summary_change_visualization_period = function(target){

		var buttons = dashboard_summary_widget.find('.status-period-toggle');

		var period	= target.data('toggle-option');
		var nonce	= dashboard_summary_widget.find('#dashboard-nonce').val();
		var cog		= buttons.find('.icon-cog');
		var success	= false;

		console.log(period);

		cog.removeClass('hide');

		buttons.find(' button').addClass('disabled');

		$.ajax({
			type:	"post",
			url:	ajaxurl,
			data:	{
				'action':       "dashboard_widget_control",
				'nonce':        nonce,
				'button_id':    'status-period-toggle',
				'message':      period
			},
			success: function(response) {
				if (response.success === true){
					opbg.dashboard_summary_upgrade_status_values(response.status);
					success = true;
				}
				else {
					alert('Some server error occurred...');
				}
			},
			error: function(response){
				alert('Some connection error occurred...');
			},
			complete: function(response){
				cog.addClass('hide');
				buttons.find('button').removeClass('disabled');

				if (success) {
					buttons.find('button').toggleClass('active');
				}
			}
		});

		return false;
	};
});
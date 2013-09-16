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

		var p = button.siblings('.status-text');

		var state = button.data('status');
		console.log(state);
		if (state === true) {
			p.html(p.data('status-active'));
			button.html(button.data('status-active'));
		}
		else {
			p.html(p.data('status-inactive'));
			button.html(button.data('status-inactive'));
		}
	};

	opbg.dashboard_summary_print_status_values();

	dashboard_summary_widget.find('.resources-control button:not(.disabled)').on('click.resources_control', function(){
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

		$this.addClass('disabled').text('wait...');

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
						$this.data('status', true);

					}
					else{
						$this.data('status', false);
					}
				}
				else {
					console.log(response);
					alert('Some server error occurred...');
					//$this.html(old_html);
				}
			},
			error: function(response){
				console.log(response);
				alert('Some connection error occurred...');
			},
			complete: function(response){
				$this.removeClass('disabled');
				opbg.dashboard_summary_set_button_state($this);
			}
		});

	});
});
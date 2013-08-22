jQuery(document).ready(function ($) {

	console.log('admin_dashboard.js called');

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

        dashboard_summary_upgrade_status_values(data.dashboard_summary_data.summary);
    });

	opbg.dashboard_summary_print_status_values();

	dashboard_summary_widget.find('#remote-resource-fetching:not(.disabled)').on('click.resources_fetch', function(){
		console.log('toggling fetching');
		var $this = $(this);
		var fetch_status = $this.parent('.fetch-button').find('.fetching-status');
		var nonce = $this.data('nonce');
		var message = $this.hasClass('active') ? "off" : "on";

		$this.addClass('disabled').text('wait...');

		$.ajax({
			type:	"post",
			url:	ajaxurl,
			data:	{
				action: "remote_resources_fetching_toggle",
				nonce: nonce,
				remote_resources_fetching_status: message
			},
			success: function(response) {
				if (response.success === true){
					if (response.status === "active"){
						$this.addClass('active');
						$this.empty().text($this.data('status-active-text'));
						fetch_status.empty().text(fetch_status.data('status-active-text')).append($('<i>').addClass('icon-spin icon-refresh'));
					}
					else{
						$this.removeClass('active');
						$this.empty().text($this.data('status-inactive-text'));
						fetch_status.empty().text(fetch_status.data('status-inactive-text'));
					}
				}
				else {
					alert('Some server error occurred...');
					$this.html(old_html);
				}
			},
			error: function(response){
				alert('Some connection error occurred...');
				$this.html(old_html);
			},
			complete: function(response){
				$this.removeClass('disabled');
			}
		});

	});
});

/* Resource fetching routine (deprecated)
	dashboard_summary_widget.find('#resources-fetch:not(.disabled)').on('click.resources_fetch', function(){
		var $this = $(this);
		var nonce = $this.data('nonce');
		var old_html = $this.html();
		var loading_text = $this.data('loading-text');
		var fetched_results = dashboard_summary_widget.find('.fetched-results');

		$this.addClass('disabled');

		$this.text($this.data('loading-text') + ' ');
		$this.append($('<i>').addClass('icon-spin icon-refresh'));
		fetched_results.fadeOut(200);

		$.ajax({
			type:	"post",
			url:	ajaxurl,
			data:	{action: "fetch_new_resources", nonce: nonce},
			success: function(response) {
				window.ajaxRes = response;
				if (response.success === true) {
					if (response.new_results > 0){
						var recatd_msg = '';

						if (response.recatd > 0)
							recatd_msg = 'and <span class="old">' + response.recatd + '</span> were found under more topics';

						fetched_results.html('<span class="new">' + response.new_results + '</span> new resource' + (response.new_results > 1 ? 's' : '') + ' were found' + recatd_msg + ' in ' + response.duration + '!');

						opbg.dashboard_summary_upgrade_status_values(response.summary);
					}
					else {
						var recatd_msg = '.';
						console.log(response.recatd);
						if (response.recatd > 0)
							recatd_msg = ', but <span class="old">' + response.recatd + '</span> of the old ones were found under new topics!';

						fetched_results.html('Whoops! There are no new resources' + recatd_msg);
					}
				}
				else {
					fetched_results.text('Some server error occurred...');
				}
			},
			error:		function(response){
				window.ajaxRes = response;
				fetched_results.text('Some connection error occurred...');
			},
			complete:	function(response){
				fetched_results.append($('<i>').addClass('icon-remove-sign')).fadeIn(200);
				$this.removeClass('disabled').html(old_html);
			}
		});
	});
	*/
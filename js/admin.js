function opbg_dashboard_summary_print_status_values(){
	$ = jQuery;

	mode = dashboard_summary_widget.find('.status-view-toggle button.active').data('toggle-option');

	dashboard_summary_widget.find('.status-table .value').each(function(){
		var $this = $(this);
		var new_val;
		if (mode == '%'){
			new_val = Math.round( ($this.data('status-value') / dashboard_summary_widget.find('.status-table .status-total.value').data('status-value') * 1000) ) * 10;
			new_val += '%';
		}
		else {
			new_val = $this.data('status-value');
		}

		$this.text(new_val);
	})
}

function opbg_dashboard_summary_upgrade_status_values(data){
	for (var status in data){
		dashboard_summary_widget.find('.status-table .value.status-' + status).data('status-value', data[status])
	}
	opbg_dashboard_summary_print_status_values();
}

jQuery(document).ready(function( $ ) {

	window.dashboard_summary_widget = $('#dashboard_summary');

	opbg_dashboard_summary_print_status_values();

	$(".bootstrap-wpadmin .btn-group[data-toggle='buttons-radio'] button").on('click.button-toggle-radio', function(){
		console.log(this);
		var $this  = $(this);
		if (!$this.hasClass('.active')){
			$this.siblings().addBack().toggleClass('active');

			var $callback = $this.parent().data('toggle-function');

			window[$callback]();
		}
	})

	$('.closable-message-wrapper').on('click.message_remove', '.icon-remove-sign', function(){
		var parent = $(this).parent()
		parent.fadeOut(200, function(){
			parent.empty();
		})
	})

	dashboard_summary_widget.find('#resources-fetch:not(.disabled)').on('click.resources_fetch', function(){
		var $this 				= $(this);
		var nonce 				= $this.data('nonce');
		var old_html 			= $this.html();
		var loading_text 		= $this.data('loading-text');
		var loading_dots_count 	= 1;
		var loading_dots 		= '.';
		var fetched_results 	= dashboard_summary_widget.find('.fetched-results');

		$this.addClass('disabled');

		$this.text($this.data('loading-text') + ' ');
		$this.append($('<i>').addClass('icon-spin icon-refresh'));

		$.ajax({
			type: 		"post",
			url: 		ajaxurl,
			data: 		{action: "fetch_new_resources", nonce: nonce},
			success: 	function(response) {
				window.ajaxRes = response;
				if (response.success == true) {
					if (response.new_results > 0){
						fetched_results.html('<span>' + response.new_results + '</span> new resource' + (response.new_results > 1 ? 's' : '') + ' were found!');

						opbg_dashboard_summary_upgrade_status_values(response.summary);
					}
					else {
						fetched_results.text('Sorry! There were no new resources.');
					}
				}
				else {
					fetched_results.text('Some server error occurred...');
				}
			},
			error:		function(){
				fetched_results.text('Some connection error occurred...');
			},
			complete:	function(response){
				fetched_results.append($('<i>').addClass('icon-remove-sign')).fadeIn(200);
				$this.removeClass('disabled').html(old_html);
			}
		})
	})
});

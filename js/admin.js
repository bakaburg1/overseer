jQuery(document).ready(function( $ ) {

	window.opbg = {};

	opbg.dashboard_summary_print_status_values = function(){

		mode = dashboard_summary_widget.find('.status-view-toggle button.active').data('toggle-option');

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
		})
	}

	opbg.dashboard_summary_upgrade_status_values = function(data){
		for (var status in data){
			dashboard_summary_widget.find('.status-table .value.status-' + status).data('status-value', data[status])
		}
		opbg.dashboard_summary_print_status_values();
	}

	opbg.set_admin_menu_icons = function (){
		var adminmenu	= $('#adminmenu');
		var pods_icons = {
			'authors':		'icon-user',
			'feeds':		'icon-rss',
			'resources':	'icon-compass',
			'sources':		'icon-globe',
			'topics':		'icon-folder-open'
		};
		for (var pods in pods_icons){
			adminmenu.find('.menu-icon-generic.toplevel_page_pods-manage-' + pods + ' .wp-menu-image').addClass(pods_icons[pods] + ' overridden');
			$('body.toplevel_page_pods-manage-' + pods + ' #icon-edit-pages').addClass(pods_icons[pods]);
		}

		adminmenu.find('.menu-icon-dashboard .wp-menu-image').addClass('icon-home overridden');

		$('.wrap #icon-index').addClass('icon-home');
	}


	window.dashboard_summary_widget = $('#dashboard_summary');

	opbg.dashboard_summary_print_status_values();

	opbg.set_admin_menu_icons();

	$(".bootstrap-wpadmin .btn-group[data-toggle='buttons-radio'] button").on('click.button-toggle-radio', function(){
		console.log(this);
		var $this  = $(this);
		if (!$this.hasClass('.active')){
			$this.siblings().addBack().toggleClass('active');

			var $callback = $this.parent().data('toggle-function');

			opbg[$callback]();
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
		var fetched_results 	= dashboard_summary_widget.find('.fetched-results');

		$this.addClass('disabled');

		$this.text($this.data('loading-text') + ' ');
		$this.append($('<i>').addClass('icon-spin icon-refresh'));
		fetched_results.fadeOut(200);

		$.ajax({
			type: 		"post",
			url: 		ajaxurl,
			data: 		{action: "fetch_new_resources", nonce: nonce},
			success: 	function(response) {
				window.ajaxRes = response;
				if (response.success == true) {
					if (response.new_results > 0){
						var recatd_msg = '';

						if (response.recatd > 0)
							recatd_msg = ' and <span class="old">' + response.recatd + '</span> were found under more topics';

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
		})
	})
});

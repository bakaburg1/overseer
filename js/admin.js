jQuery(document).ready(function ($) {

	// Instantiate main object
	window.opbg = {};

	// Add classes to admin menu
	opbg.set_admin_menu_icons = function(){
		var adminmenu	= $('#adminmenu');

		adminmenu.find('.wp-menu-image img[src ~= font-class]').each(function(){
			$(this).hide();
			var font_class= $(this).attr('src').replace('font-class', '');
			$(this).parent().addClass(font_class);

			var pod_type = $(this).closest('li').attr('id');
			console.log(pod_type);

			$('body.' + pod_type + ' #icon-edit-pages').addClass(font_class);

			$(this).remove();
		});

		adminmenu.find('.menu-icon-dashboard .wp-menu-image').addClass('icon-home');

		$('.wrap #icon-index').addClass('icon-home');
	};

	opbg.manage_pods_list_page = function() {
		if ($('#the-list').length !== 0){

			$('#the-list tr').each(function(){
				$(this).find('td').each(function(index){
					$(this).attr('data-pods-field', pods_list_page_data.list_fields_manage[index]).addClass('pods-fields-' + pods_list_page_data.list_fields_manage[index]);
				});
			});

			console.log('Resources list page');

			var table_rows = $('#the-list .row-actions');

			var current_pods;

			var fields_to_change;

			var new_value;

			table_rows.append(' | <span class="pods-quick-edit"><a href="#"></a></span>');

			if (pods_list_page_data.current_pods === 'resources'){
				table_rows.find('.pods-quick-edit a').text('Set as not pertinent');
				fields_to_change = 'status';
				new_value = 0;

			}

			if (pods_list_page_data.current_pods === 'sources'){
				table_rows.find('.pods-quick-edit a').text('Blacklist whole site');
				fields_to_change = 'blacklisted';
				new_value = '*';
			}

			table_rows.on('click.pods_item_quick_edit', '.pods-quick-edit a:not(.disabled)', function() {
				$this = $(this);

				//if ($this.hasClass('disabled')) return false;

				$this.addClass('disabled');

				$this.parent().append(' <i class="icon-spin icon-cog"></i>');

				var loading = $this.siblings('i');

				var pods_item_id = $(this).closest('tr').attr('id');
				pods_item_id = window.parseInt(pods_item_id.replace('item-', ''));

				var el_to_change = $this.closest('tr').find('td[data-pods-field = ' + fields_to_change + ']');

				console.log("quick edit:");
				console.log({
					action: 'pods-quick-edit',
					nonce: pods_list_page_data.pods_list_page_data_nonce,
					pods_item_id: pods_item_id,
					pods_name: pods_list_page_data.current_pods,
					field: fields_to_change,
					value: new_value
				});

				$.ajax({
					type: 'post',
					url: window.ajaxurl,
					data: {
						action: 'pods-quick-edit',
						nonce: pods_list_page_data.pods_list_page_data_nonce,
						pods_item_id: pods_item_id,
						pods_name: pods_list_page_data.current_pods,
						field: fields_to_change,
						value: new_value
					},
					success: function(response){
						console.log(response);
						if (response.success !== false){
							el_to_change.text(response.value);
						}
					},
					complete: function(){
						loading.remove();
						$this.removeClass('disabled');
					}
				});

				return false;

			});
		}
	};

	opbg.set_admin_menu_icons();

	if (typeof pods_list_page_data !== 'undefined'  && pods_list_page_data.pods_manage_page_type === 'list'){
		opbg.manage_pods_list_page();
	}

	// Convert urls in pods list pages table into links
    if ( $('.wp-list-table').length ){
        $(".wp-list-table tbody tr td:contains('http')").each(function(){
            url = $(this).text();
            $(this).html('<a href="' + url + '" target="_blank">' + url + '</a>');
        });
    }

    // Implement automatic function calling on radio button toggle
	$(".bootstrap-wpadmin .btn-group[data-toggle='buttons-radio']").on('click.button-toggle-radio', 'button:not(.disabled):not(.active)', function(){
        var $this       = $(this);
        var $callback   = $this.parent().data('toggle-function');

		if (opbg[$callback]($this)){
			$this.siblings().addBack().toggleClass('active');
		}

		return false;
	});

	// Implement message remove on x button push
	$('.closable-message-wrapper').on('click.message_remove', '.icon-remove-sign', function(){
		var parent = $(this).parent();
		parent.fadeOut(200, function(){
			parent.empty();
		});
	});
});

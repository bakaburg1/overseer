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

	opbg.quickedit_resource_pertinency = function() {
		if ($('#the-list').length !== 0){
			console.log('Resources list page');

			$('#the-list .row-actions').append(' | <span class="pertinency-toggle"><a href="#" title="change pertinency" alt="change pertinency">Set as not pertinent</a></span>');
		
			$('#the-list').on('click.quickedit_resource_pertinency', '.pertinency-toggle a', function() {
				$this = $(this);

				if ($this.hasClass('disabled')) return false;

				$this.addClass('disabled');

				$this.parent().append(' <i class="icon-spin icon-refresh"></i>');

				var loading = $this.siblings('i');

				var status_item = $this.closest('tr').find('td:contains("New"), td:contains("Categorized"), td:contains("Not pertinent")');

				var resource_id = $(this).closest('tr').attr('id');
				resource_id = window.parseInt(resource_id.replace('item-', ''));

				$.ajax({
					type: 'post',
					url: window.ajaxurl,
					data: {
						action: 'quickedit_resource_pertinency',
						nonce: resource_list_page_js_objects.pertinency_quickedit_nonce,
						resource_id: resource_id
					},
					success: function(response){
						if (response !== false){
							status_item.text('Not pertinent');
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

	if (pagenow === "toplevel_page_pods-manage-resources"){
		opbg.quickedit_resource_pertinency();
	}

	// Convert urls in pods list pages table into links
    if ( $('.wp-list-table').length ){
        $(".wp-list-table tbody tr td:contains('http')").each(function(){
            url = $(this).text();
            $(this).html('<a href="' + url + '" target="_blank">' + url + '</a>');
        });
    }

    // Implement automatic function calling on radio button toggle
	$(".bootstrap-wpadmin .btn-group[data-toggle='buttons-radio'] button").on('click.button-toggle-radio', function(){
		console.log(this);
		var $this  = $(this);
		if (!$this.hasClass('.active')){
			$this.siblings().addBack().toggleClass('active');

			var $callback = $this.parent().data('toggle-function');

			opbg[$callback]();
		}
	});

	// Implement message remove on x button push
	$('.closable-message-wrapper').on('click.message_remove', '.icon-remove-sign', function(){
		var parent = $(this).parent();
		parent.fadeOut(200, function(){
			parent.empty();
		});
	});
});

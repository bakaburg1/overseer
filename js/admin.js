jQuery(document).ready(function ($) {

	// Instantiate main object
	window.opbg = {};

	// Add classes to admin menu
	opbg.set_admin_menu_icons = function(){
		var adminmenu	= $('#adminmenu');
		var pods_icons = {
			'authors':		'icon-user',
			'feeds':		'icon-rss',
			'resources':	'icon-compass',
			'sources':		'icon-globe',
			'topics':		'icon-folder-open'
		};

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

	opbg.set_admin_menu_icons();

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

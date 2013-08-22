<?php

function build_dashboard_summary(){

wp_add_dashboard_widget('dashboard_summary', 'Summary', function(){

	$resources = (object)opbg_get_resource_summary();

	$fetch_status = get_option( 'remote_resources_fetching_status', false );

	$button_state = $fetch_status ? 'active' : '';

	$button_text = $fetch_status ? 'Deactivate!' : 'Activate!';

	$fetch_status_text = $fetch_status ? 'Resource fetching is active!<i class="icon-spin icon-refresh"></i>' : 'Resource fetching is inactive!';

	bk1_debug::log($fetch_status);

	?>
	<div class="bootstrap-wpadmin">
		<section class="fetch row-fluid">
			<div class="fetch-button span6">
				<p data-status-active-text="Resource fetching is active!" data-status-inactive-text="Resource fetching is inactive!" class="fetching-status"><?php echo $fetch_status_text ?></p>
				<button data-status-active-text="Deactivate!" data-status-inactive-text="Activate!" data-nonce="<?php echo wp_create_nonce('remote_resource_fetching_toggle_nonce') ?>" id="remote-resource-fetching" class="btn btn-primary btn-block <?php echo $button_state ?>"><?php echo $button_text ?></button>
			</div>
			<!-- <div class="fetched-results span6 closable-message-wrapper">Push Fetch to scan the feeds for new resources!</div> -->
		</section>
		<hr>
		<section class="status row-fluid">
			<div class="status-table span6">
				<div class="row-fluid">
					<div class="grid-dummy-first span12"></div>
					<div class="status-header span6">Status:</div>
					<div class="status-view-toggle span6">
						<div class="btn-group" data-toggle="buttons-radio" data-toggle-function="dashboard_summary_print_status_values">
							<button data-toggle-option="#" class="btn btn-mini active">#</button>
							<button data-toggle-option="%" class="btn btn-mini">%</button>
						</div>
					</div>
					<div class="separator span12"></div>
					<div class="status-new key span6">New:</div><div class="status-new value span6" data-status-value="<?php echo $resources->new ?>"></div>
					<div class="status-categorized key span6">Categorized:</div><div class="status-categorized value span6" data-status-value="<?php echo $resources->categorized ?>"></div>
					<div class="status-excluded key span6">Excluded:</div><div class="status-excluded value span6" data-status-value="<?php echo $resources->excluded ?>"></div>
					<div class="separator span12"></div>
					<div class="status-total key span6">Total:</div><div class="status-total value span6" data-status-value="<?php echo $resources->total ?>"></div>
				</div>
			</div>
			<div class="status-graph span6">

			</div>
		</section>
	</div>
	<?php });
}

?>
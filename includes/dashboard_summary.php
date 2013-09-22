<?php

function build_dashboard_summary(){

	wp_add_dashboard_widget('dashboard_summary', 'Summary', function(){

		$resources = (object)opbg_get_resource_summary();

		$fetch_status = get_option( 'resources_fetching_status', false );

		$fetch_text = $fetch_status ? "Deactivate Fetching!" : "Activate Fetching!";

		$fetch_p_text = $fetch_status ? "Resource fetching is active!<i class=\"icon-refresh icon-spin\"></i>" : "Resource fetching is inactive!";

		$filtering_status = get_option( 'resource_filtering_status', false );

		//$filtering_status = $sampling_value !== false ? true : false;

		$filtering_text = $filtering_status ? "Deactivate Filter!" : "Activate Filter!";

		$filtering_p_text = $filtering_status ? "Sampling is on at the ".get_option( 'sampling_threshold')."%!" : "Sampling is off!";

		bk1_debug::log('fetch status: '.$fetch_status);

		?>
		<div class="bootstrap-wpadmin">
			<input type="hidden" id="dashboard-nonce" value="<?php echo wp_create_nonce('dashboard_widget_control_nonce') ?>"></input>
			<section class="resources-control row-fluid">
				<div class="toggle-fetch span6">
					<p data-status-active="Resource fetching is active!<i class='icon-refresh icon-spin'></i>"
						data-status-inactive="Resource fetching is inactive!"
						class="status-text"><?php echo $fetch_p_text ?></p>
						<button data-status-active="Deactivate Fetching!"
						data-status-inactive="Activate Fetching!"
						data-status="<?php echo $fetch_status ?>"
						id="remote-fetching-toggle"
						class="btn btn-primary btn-block"><?php echo $fetch_text ?></button>
					</div>
					<div class="toggle-filter span6">
						<p data-status-active="Sampling is on at the <?php echo get_option( 'sampling_threshold') ?>%!"
							data-status-inactive="Sampling is off!"
							class="status-text"><?php echo $filtering_p_text ?></p>
							<button data-status-active="Deactivate Filter!"
							data-status-inactive="Activate Filter!"
							data-status="<?php echo $filtering_status ?>"
							id="resource-filtering-toggle"
							class="btn btn-primary btn-block"><?php echo $filtering_text ?></button>
						</div>
					</section>
					<hr>
					<section class="status row-fluid">
						<div class="status-table span6 row-fluid">
							<div class="grid-dummy-first span12"></div>
							<div class="status-header span5">Status:</div>
							<div class="status-view-toggle span6">
								<div class="btn-group" data-toggle="buttons-radio" data-toggle-function="dashboard_summary_print_status_values">
									<button data-toggle-option="#" class="btn btn-mini active">#</button>
									<button data-toggle-option="%" class="btn btn-mini">%</button>
								</div>
							</div>
							<div class="separator span12"></div>
							<div class="status-new key span5">New:</div><div class="status-new value span6" data-status-value="<?php echo $resources->new ?>"></div>
							<div class="status-categorized key span5">Categorized:</div><div class="status-categorized value span6" data-status-value="<?php echo $resources->categorized ?>"></div>
							<div class="status-excluded key span5">Excluded:</div><div class="status-excluded value span6" data-status-value="<?php echo $resources->excluded ?>"></div>
							<div class="separator span12"></div>
							<div class="status-total key span5">Total:</div><div class="status-total value span6" data-status-value="<?php echo $resources->total ?>"></div>
						</div>
						<div class="status-graph span6">

						</div>
					</section>
				</div>
				<?php });
}
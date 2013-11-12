<?php

function build_dashboard_summary(){

	wp_add_dashboard_widget('dashboard_summary', 'Summary', function(){

		$resources = (object)opbg_get_resource_summary(false, true);

		$fetch_status = get_option( 'resources_fetching_status', false );

		$fetch_text = $fetch_status ? "Deactivate Fetching!" : "Activate Fetching!";

		$fetch_p_text = $fetch_status ? "Resource fetching is active!<i class=\"icon-refresh icon-spin\"></i>" : "Resource fetching is inactive!";

		$sampling_threshold = pods('opbg_database_settings')->field('sampling_threshold');
		$sampling_status = get_option( 'sampling_filtering_status', false );

		$alexa_threshold = pods('opbg_database_settings')->field('alexa_threshold');
		$alexa_status = get_option( 'alexa_filtering_status', false );
		if ($alexa_status == false) $alexa_threshold = 'off';

		bk1_debug::log($sampling_status);
		bk1_debug::log($alexa_status);

		?>
		<div class="bootstrap-wpadmin">
			<input type="hidden" id="dashboard-nonce" value="<?php echo wp_create_nonce('dashboard_widget_control_nonce') ?>"></input>
			<section class="resources-control row-fluid">
				<div class="toggle-fetch span6">
					<p data-status-active="Resource fetching is active!<i class='icon-refresh icon-spin'></i>"
						data-status-inactive="Resource fetching is inactive!"
						class="status-text"><?php echo $fetch_p_text ?>
					</p>
					<button data-status-active="Deactivate Fetching!"
						data-status-inactive="Activate Fetching!"
						data-status="<?php echo $fetch_status ?>"
						id="remote-fetching-toggle"
						class="btn btn-primary btn-block"><?php echo $fetch_text ?>
					</button>
				</div>
				<div class="toggle-filters span6 row-fluid">
					<div class="row-fluid">
						<p data-button-reference="sampling-filtering-toggle" data-threshold="<?php echo $sampling_threshold ?>" class="status-text span6">Sampling level: <span><?php echo ($sampling_status == false) ? 'off' : $sampling_threshold; ?></span></p>
						<p data-button-reference="alexa-filtering-toggle" data-threshold="<?php echo $alexa_threshold ?>" class="status-text span6">Alexa limit: <span><?php echo ($alexa_status == false) ? 'off' : $alexa_threshold; ?></span></p>
					</div>
					<div class="row-fluid">
						<button
							data-status="<?php echo $sampling_status ?>"
							id="sampling-filtering-toggle"
							class="btn span6<?php echo $sampling_status ? ' active' : '' ?>"><span><?php echo $sampling_status ? 'Deactivate ' : 'Activate ' ?></span><i class='icon-filter'></i>
						</button>
						<button
							data-status="<?php echo $alexa_status ?>"
							id="alexa-filtering-toggle"
							class="btn span6<?php echo $alexa_status ? ' active' : '' ?>"><span><?php echo $alexa_status ? 'Deactivate ' : 'Activate ' ?></span><i><strong>a</strong></i>
						</button>
					</div>
				</div>
			</section>
			<hr>
			<section class="status row-fluid">
				<div class="status-table span12">
					<div class="grid-dummy-first span12"></div>
					<div class="row-fluid">
						<div class="status-header span2">Status:</div>
						<div class="status-period-toggle span8">
							<div class="btn-group" data-toggle="buttons-radio" data-toggle-function="dashboard_summary_change_visualization_period">
								<button data-toggle-option="month" class="btn btn-mini active">Month</button>
								<button data-toggle-option="total" class="btn btn-mini">All Time</button>
							</div>
							<i class="icon-cog icon-spin hide	"></i>
						</div>
						<div class="status-view-toggle span2">
							<div class="btn-group" data-toggle="buttons-radio" data-toggle-function="dashboard_summary_print_status_values">
								<button data-toggle-option="#" class="btn btn-mini active">#</button>
								<button data-toggle-option="%" class="btn btn-mini">%</button>
							</div>
						</div>
					</div>
					<div class="row-fluid">
						<div class="separator span12"></div>
					</div>
					<div class="status-new key span5">New:</div><div class="status-new value span6" data-status-value="<?php echo $resources->new ?>"></div>
					<div class="status-categorized key span5">Categorized:</div><div class="status-categorized value span6" data-status-value="<?php echo $resources->categorized ?>"></div>
					<div class="status-excluded key span5">Excluded:</div><div class="status-excluded value span6" data-status-value="<?php echo $resources->excluded ?>"></div>
					<div class="row-fluid">
						<div class="separator span12"></div>
					</div>
					<div class="status-total key span5">Total:</div><div class="status-total value span6" data-status-value="<?php echo $resources->total ?>"></div>
				</div>

			</section>
		</div>
				<?php });
}
<?php

function build_dashboard_summary(){

	wp_add_dashboard_widget('dashboard_summary', 'Summary', function(){

		//$resources = (object)opbg_get_resource_summary(false, true);

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
		<div class="bootstrap-wpadmin container-fluid">
			<input type="hidden" id="dashboard-nonce" value="<?php echo wp_create_nonce('dashboard_widget_control_nonce') ?>"></input>
			<section class="resources-control row">
				<div class="toggle-fetch">
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
				<div class="toggle-filters">
					<p data-button-reference="sampling-filtering-toggle" data-threshold="<?php echo $sampling_threshold ?>" class="status-text">Sampling level: <span><?php echo ($sampling_status == false) ? 'off' : $sampling_threshold; ?></span></p>
					<p data-button-reference="alexa-filtering-toggle" data-threshold="<?php echo $alexa_threshold ?>" class="status-text">Alexa limit: <span><?php echo ($alexa_status == false) ? 'off' : $alexa_threshold; ?></span></p>
					<button
						data-status="<?php echo $sampling_status ?>"
						id="sampling-filtering-toggle"
						class="btn btn-default col-xs-offset-1<?php echo $sampling_status ? ' active' : '' ?>"><span><?php echo $sampling_status ? 'Deactivate ' : 'Activate ' ?></span><i class='fa fa-filter'></i>
					</button>
					<button
						data-status="<?php echo $alexa_status ?>"
						id="alexa-filtering-toggle"
						class="btn btn-default col-xs-offset-2<?php echo $alexa_status ? ' active' : '' ?>"><span><?php echo $alexa_status ? 'Deactivate ' : 'Activate ' ?></span><i><strong>a</strong></i>
					</button>
				</div>
			</section>
			<hr>
			<section class="status row">
				<div class="status-table">
					<div class="container row">
						<div class="status-header">Status:</div>
						<div class="status-period-toggle">
							<div class="btn-group" data-toggle="buttons-radio" data-toggle-function="dashboard_summary_change_visualization_period">
								<button data-toggle-option="total" class="btn btn-default btn-xs active">Total</button>
								<button data-toggle-option="range" class="btn btn-default btn-xs">Range</button>
							</div>
							<i class="spinner"></i>
						</div>
						<div class="status-data-range">
							<input type="date" class="form-control datepicker-box" data-box="from">
							<input type="date" class="form-control datepicker-box" data-box="to">
						</div>
						<div class="status-view-toggle">
							<div class="btn-group" data-toggle="buttons-radio" data-toggle-function="dashboard_summary_print_status_values">
								<button data-toggle-option="#" class="btn btn-default btn-xs active">#</button>
								<button data-toggle-option="%" class="btn btn-default btn-xs">%</button>
							</div>
						</div>
					</div>
					<div class="separator"></div>
					<div class="container">
						<div class="status-new key">New:</div><div class="status-new value" data-status-value="">--</div>
						<div class="status-categorized key">Categorized:</div><div class="status-categorized value" data-status-value="">--</div>
						<div class="status-excluded key">Excluded:</div><div class="status-excluded value" data-status-value="">--</div>
						<div class="separator"></div>
						<div class="status-total key">Total:</div><div class="status-total value" data-status-value="">--</div>
					</div>
				</div>

			</section>
		</div>
				<?php });
}
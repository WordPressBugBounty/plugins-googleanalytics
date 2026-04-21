<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ga_stats = new Ga_Stats;

$ga4_demo_enabled     = get_option( 'googleanalytics-ga4-demo' );
$ga4_demo_enabled     = 'on' === $ga4_demo_enabled || '1' === $ga4_demo_enabled;
$page_list_count_data = array();
$gender_count_data    = array();
$age_count_data       = array();
$page_session_count_data = array();
$user_count_data      = array();
$ga4_demo_device_data = array();

$from = false === empty( $date_range['from'] ) ? $date_range['from'] : '8daysAgo';
$to   = false === empty( $date_range['to'] ) ? $date_range['to'] : 'today';

$response = $ga_stats->ga4_run_report_rest(
	$ga4_property,
	array(
		'dateRanges' => array(
			array(
				'startDate' => $from,
				'endDate'   => $to,
			),
		),
		'dimensions' => array(
			array( 'name' => 'date' ),
		),
		'metrics' => array(
			array( 'name' => 'screenPageViews' ),
		),
		'orderBys' => array(
			array(
				'dimension' => array(
					'dimensionName' => 'date',
					'orderType'     => 'ALPHANUMERIC',
				),
				'desc' => false,
			),
		),
	)
);

$page_list_response = $ga_stats->ga4_run_report_rest(
	$ga4_property,
	array(
		'dateRanges' => array(
			array(
				'startDate' => $from,
				'endDate'   => $to,
			),
		),
		'dimensions' => array(
			array( 'name' => 'landingPage' ),
		),
		'metrics' => array(
			array( 'name' => 'screenPageViews' ),
		),
	)
);

foreach ( $page_list_response['rows'] ?? array() as $row ) {
	$page   = isset( $row['dimensionValues'][0]['value'] ) ? $row['dimensionValues'][0]['value'] : '';
	$metric = isset( $row['metricValues'][0]['value'] ) ? (int) $row['metricValues'][0]['value'] : 0;

	if ( '' !== $page ) {
		$page_list_count_data[ $page ] = $metric;
	}
}

$pageViewCount = array_sum( array_values( $page_list_count_data ) );

$user_response = $ga_stats->ga4_run_report_rest(
	$ga4_property,
	array(
		'dateRanges' => array(
			array(
				'startDate' => $from,
				'endDate'   => $to,
			),
		),
		'dimensions' => array(
			array( 'name' => 'date' ),
		),
		'metrics' => array(
			array( 'name' => 'newUsers' ),
		),
		'orderBys' => array(
			array(
				'dimension' => array(
					'dimensionName' => 'date',
					'orderType'     => 'ALPHANUMERIC',
				),
				'desc' => false,
			),
		),
	)
);

$gender_chart_response = $ga_stats->ga4_run_report_rest(
	$ga4_property,
	array(
		'dateRanges' => array(
			array(
				'startDate' => $from,
				'endDate'   => $to,
			),
		),
		'dimensions' => array(
			array( 'name' => 'userGender' ),
		),
		'metrics' => array(
			array( 'name' => 'newUsers' ),
		),
	)
);

$age_chart_response = $ga_stats->ga4_run_report_rest(
	$ga4_property,
	array(
		'dateRanges' => array(
			array(
				'startDate' => $from,
				'endDate'   => $to,
			),
		),
		'dimensions' => array(
			array( 'name' => 'userAgeBracket' ),
		),
		'metrics' => array(
			array( 'name' => 'newUsers' ),
		),
	)
);

foreach ( $gender_chart_response['rows'] ?? array() as $row ) {
	$label  = isset( $row['dimensionValues'][0]['value'] ) ? $row['dimensionValues'][0]['value'] : '';
	$metric = isset( $row['metricValues'][0]['value'] ) ? (int) $row['metricValues'][0]['value'] : 0;

	if ( '' !== $label && 'unknown' !== $label ) {
		$gender_count_data[ $label ] = $metric;
	}
}

$gender_count_data = array_reverse( $gender_count_data, true );

foreach ( $age_chart_response['rows'] ?? array() as $row ) {
	$label  = isset( $row['dimensionValues'][0]['value'] ) ? $row['dimensionValues'][0]['value'] : '';
	$metric = isset( $row['metricValues'][0]['value'] ) ? (int) $row['metricValues'][0]['value'] : 0;

	if ( '' !== $label && 'unknown' !== $label ) {
		$age_count_data[ $label ] = $metric;
	}
}

$ga4_device_chart_response = $ga_stats->ga4_run_report_rest(
	$ga4_property,
	array(
		'dateRanges' => array(
			array(
				'startDate' => $from,
				'endDate'   => $to,
			),
		),
		'dimensions' => array(
			array( 'name' => 'deviceCategory' ),
		),
		'metrics' => array(
			array( 'name' => 'newUsers' ),
		),
	)
);

foreach ( $ga4_device_chart_response['rows'] ?? array() as $row ) {
	$label  = isset( $row['dimensionValues'][0]['value'] ) ? $row['dimensionValues'][0]['value'] : '';
	$metric = isset( $row['metricValues'][0]['value'] ) ? (int) $row['metricValues'][0]['value'] : 0;

	if ( '' !== $label ) {
		$ga4_demo_device_data[ $label ] = $metric;
	}
}

foreach ( $response['rows'] ?? array() as $row ) {
	$date_value = isset( $row['dimensionValues'][0]['value'] ) ? $row['dimensionValues'][0]['value'] : '';
	$metric     = isset( $row['metricValues'][0]['value'] ) ? (int) $row['metricValues'][0]['value'] : 0;

	if ( '' !== $date_value ) {
		$date = gmdate( 'M d', strtotime( $date_value ) );
		$page_session_count_data[ $date ] = $metric;
	}
}

foreach ( $user_response['rows'] ?? array() as $row ) {
	$date_value = isset( $row['dimensionValues'][0]['value'] ) ? $row['dimensionValues'][0]['value'] : '';
	$metric     = isset( $row['metricValues'][0]['value'] ) ? (int) $row['metricValues'][0]['value'] : 0;

	if ( '' !== $date_value ) {
		$date = gmdate( 'M d', strtotime( $date_value ) );
		$user_count_data[ $date ] = $metric;
	}
}
?>
<script type="text/javascript">
	ga_charts.init( function() {
		const pageSessionData = new google.visualization.DataTable();
		const userData = new google.visualization.DataTable();

		pageSessionData.addColumn( 'string', '<?php echo esc_js( __( 'Day', 'googleanalytics' ) ); ?>' );
		pageSessionData.addColumn( 'number', '<?php echo esc_js( __( 'Page Views', 'googleanalytics' ) ); ?>' );
		pageSessionData.addColumn( { type: 'string', role: 'tooltip', 'p': { 'html': true } } );

		userData.addColumn( 'string', '<?php echo esc_js( __( 'Day', 'googleanalytics' ) ); ?>' );
		userData.addColumn( 'number', '<?php echo esc_js( __( 'New Users', 'googleanalytics' ) ); ?>' );
		userData.addColumn( { type: 'string', role: 'tooltip', 'p': { 'html': true } } );

		// Page Sessions.
		<?php foreach ( $page_session_count_data as $date => $value ) : ?>
		pageSessionData.addRow( [
			'<?php echo esc_js( $date ); ?>',
			<?php echo esc_js( $value ); ?>,
			ga_charts.createPageTooltip(
				'<?php echo esc_js( $date ); ?>',
				'<?php echo esc_js( $value ); ?>'
			)
		] );
		<?php endforeach; ?>

		// User data.
		<?php foreach ( $user_count_data as $date => $value ) : ?>
		userData.addRow( [
			'<?php echo esc_js( $date ); ?>',
			<?php echo esc_js( $value ); ?>,
			ga_charts.createUserTooltip(
				'<?php echo esc_js( $date ); ?>',
				'<?php echo esc_js( $value ); ?>'
			)
		] );
		<?php endforeach; ?>

		ga_charts.events( pageSessionData );
		ga_charts.drawPageSessionChart( pageSessionData );
		ga_charts.drawUserChart( userData );

		// GA4 Demographic gender chart.
		<?php
		$demo_gender_data    = array();
		$demo_gender_data[0] = array( 'Gender', 'The gender of visitors' );

		$x = 1;
		foreach ( $gender_count_data as $gender_type => $amount ) {
			$demo_gender_data[ $x ] = array( ucfirst( $gender_type ), intval( $amount ) );
			$x++;
		}
		?>
		ga_charts.drawDemoGenderGa4Chart(<?php echo wp_json_encode( $demo_gender_data ); ?>);
		ga_loader.hide();

		// Demographic age chart.
		<?php
		$demo_ga4_age_data    = array();
		$demo_ga4_age_data[0] = array( 'Age', 'Average age range of visitors' );

		$x = 1;
		foreach ( $age_count_data as $age_type => $amount ) {
			$demo_ga4_age_data[ $x ] = array( $age_type, intval( $amount ) );
			$x++;
		}
		?>
		ga_charts.drawDemoAgeGa4Chart(<?php echo wp_json_encode( $demo_ga4_age_data ); ?>);

		// Device chart.
		<?php
		$ga4_demo_count_data    = array();
		$ga4_demo_count_data[0] = array(
			__( 'Device', 'googleanalytics' ),
			__( 'Device Breakdown', 'googleanalytics' ),
		);

		$x = 1;
		foreach ( $ga4_demo_device_data as $device_type => $amount ) {
			$ga4_demo_count_data[ $x ] = array( $device_type, intval( $amount ) );
			$x++;
		}
		?>
		ga_charts.drawGa4DemoDeviceChart(<?php echo wp_json_encode( $ga4_demo_count_data ); ?>);

		ga_loader.hide();
	} );
</script>

<div class="dashboard-title">GA4 Dashboard</div>

<?php if ( true === empty( $page_list_count_data ) ) : ?>
	<?php
	echo wp_kses(
		Ga_Helper::ga_wp_notice(
			__( 'You don\'t appear to have enough page view data. Please come back at a later date once you do.', 'googleanalytics' ),
			'warning',
			false,
			array()
		),
		array(
			'button' => array(
				'class'   => array(),
				'onclick' => array(),
			),
			'div'    => array(
				'class' => array(),
			),
			'p'      => array(),
		)
	);
	?>
<?php else : ?>
    <div id="page_session_chart_div"></div>

	<?php require plugin_dir_path( __FILE__ ) . 'ga4-demographic-chart.php'; ?>

    <div class="ga-panel ga-panel-default" style="width:100%; max-width:1210px; margin-top: 2rem;">
        <div class="ga-panel-heading">
            <strong><?php echo esc_html( 'Top 10 Pages/Posts by page views' ); ?></strong>
        </div>
        <div class="ga-panel-body">
            <div id="table-container">
                <table class="ga-table">
                    <tr>
                        <th style="text-align: right;">
							<?php echo esc_html( 'Url' ); ?>
                        </th>
                        <th style="text-align: right;">
							<?php echo esc_html( 'Pageviews' ); ?>
                        </th>
                        <th style="text-align: right;">
							<?php echo '%'; ?>
                        </th>
                    </tr>
					<?php foreach ( array_slice( $page_list_count_data, 0, 10 ) as $page => $metric ) : ?>
						<?php $percentage = $pageViewCount > 0 ? round( ( (float) $metric / $pageViewCount ) * 100 ) : 0; ?>
                        <tr>
                            <td class="ga-col-name">
								<?php if ( '(direct) / (none)' !== $page ) : ?>
									<?php
									$single_breakdown = false === empty( $ts ) ?
										'/explorer-table.plotKeys=%5B%5D&_r.drilldown=analytics.sourceMedium:' :
										'/explorer-table.plotKeys=%5B%5D&_r.drilldown=analytics.pagePath:';
									?>
                                    <a class="ga-source-name"
                                       href="<?php echo esc_url(
										   $page . $single_breakdown . str_replace(
											   '+',
											   '%20',
											   str_replace(
												   '2F',
												   '~2F',
												   str_replace( '%', '', rawurlencode( $page ) )
											   )
										   )
									   ); ?>/"
                                       target="_blank"><?php echo esc_html( $page ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $page ); ?>
								<?php endif; ?>
                            </td>
                            <td style="text-align: right"><?php echo esc_html( $metric ); ?></td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar"
                                         aria-valuenow="<?php echo esc_attr( $percentage ); ?>" aria-valuemin="0"
                                         aria-valuemax="100"
                                         style="width: <?php echo esc_attr( $percentage ); ?>%;"></div>
                                    <span style="margin-left: 10px;">
										<?php echo esc_html( Ga_Helper::format_percent( $percentage ) ); ?>
									</span>
                                </div>
                            </td>
                        </tr>
					<?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<div id="user_chart_div"></div>
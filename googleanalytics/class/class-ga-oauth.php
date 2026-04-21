<?php
if (!defined('ABSPATH')) exit;

class GA_OAuth {
	const OPTION_CLIENT_ID     = Ga_Admin::GA_SHARETHIS_PROPERTY_ID;

	public function hooks() {
		add_action( 'admin_post_sharethis_ga_connect', array( $this, 'start_broker_oauth' ) );
		add_action( 'admin_post_sharethis_ga_oauth_callback', array( $this, 'handle_broker_oauth_callback' ) );
	}

	public function start_broker_oauth() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized request.', 'googleanalytics' ) );
		}

		check_admin_referer( 'sharethis_ga_connect' );

		$client_id = get_option( self::OPTION_CLIENT_ID );

		if ( ! is_string( $client_id ) || '' === $client_id ) {
			wp_die( esc_html__( 'Missing ShareThis client ID.', 'googleanalytics' ) );
		}

		$redirect_url = admin_url( 'admin-post.php?action=sharethis_ga_oauth_callback' );
		$nonce        = wp_generate_password( 32, false, false );

		set_transient(
			'sharethis_ga_oauth_state_' . get_current_user_id(),
			array(
				'state' => $nonce,
				'exp'   => time() + 10 * MINUTE_IN_SECONDS,
			),
			10 * MINUTE_IN_SECONDS
		);

		$broker_url = add_query_arg(
			array(
				'client_id'     => rawurlencode( $client_id ),
				'redirect_url'  => rawurlencode( $redirect_url ),
				'state'         => rawurlencode( $nonce ),
				'response_type' => 'code',
			),
			trailingslashit( SHARETHIS_GA_BROKER_BASE ). 'authorize'
		);

		wp_safe_redirect( $broker_url );
		exit;
	}

	public function handle_broker_oauth_callback() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized request.', 'googleanalytics' ) );
		}

		$code           = filter_input( INPUT_GET, 'code', FILTER_UNSAFE_RAW );
		$returned_state = filter_input( INPUT_GET, 'state', FILTER_UNSAFE_RAW );

		$code           = is_string( $code ) ? sanitize_text_field( wp_unslash( $code ) ) : '';
		$returned_state = is_string( $returned_state ) ? sanitize_text_field( wp_unslash( $returned_state ) ) : '';

		if ( '' === $code || '' === $returned_state ) {
			wp_die( esc_html__( 'Missing OAuth callback parameters.', 'googleanalytics' ) );
		}

		$stored = get_transient( 'sharethis_ga_oauth_state_' . get_current_user_id() );

		if ( ! is_array( $stored ) || empty( $stored['state'] ) || ! hash_equals( $stored['state'], $returned_state ) ) {
			wp_die( esc_html__( 'Invalid OAuth state.', 'googleanalytics' ) );
		}

		delete_transient( 'sharethis_ga_oauth_state_' . get_current_user_id() );

		$client_id    = (string) get_option( self::OPTION_CLIENT_ID, '' );

		if ( '' === $client_id ) {
			wp_die( esc_html__( 'Missing installation ID.', 'googleanalytics' ) );
		}

		$callback_url = admin_url( 'admin-post.php?action=sharethis_ga_oauth_callback' );

		$response = wp_remote_post(
			trailingslashit( SHARETHIS_GA_BROKER_BASE ) . 'token',
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
				'body' => array(
					'grant_type'   => 'authorization_code',
					'code'         => $code,
					'client_id'    => $client_id,
					'redirect_uri' => $callback_url,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_die( esc_html( $response->get_error_message() ) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( 200 !== $status_code || ! is_array( $data ) ) {
			wp_die( esc_html__( 'OAuth redemption failed.', 'googleanalytics' ) );
		}

		if ( ! empty( $data['refresh_token'] ) ) {
			update_option(
				'sharethis_ga_refresh_token',
				sanitize_text_field( (string) $data['refresh_token'] )
			);
		}

		if ( ! empty( $data['access_token'] ) ) {
			update_option(
				'ga4-token',
				wp_json_encode(
					array(
						'access_token' => sanitize_text_field( (string) $data['access_token'] ),
						'expires_in'   => ! empty( $data['expires_in'] ) ? absint( $data['expires_in'] ) : 3600,
						'created'      => time(),
						'token_type'   => 'Bearer',
					)
				)
			);
		}

		wp_safe_redirect(
			admin_url( 'admin.php?page=googleanalytics/settings&ga_connected=1' )
		);
		exit;
	}

	/**
	 * Helper for rendering the "Connect" button URL in admin.
	 */
	public function get_connect_url() {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=sharethis_ga_connect' ),
			'sharethis_ga_connect'
		);
	}
}
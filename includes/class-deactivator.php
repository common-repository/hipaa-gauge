<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package    HIPAA_Gauge
 * @subpackage HIPAA_Gauge/includes
 */
class HIPAA_Gauge_Deactivator {

	/**
	 * Fired during plugin deactivation.
	 *
	 * This class defines all code necessary to run during the plugin's deactivation.
	 */
	public static function deactivate() {

		// Clears cron job.
		wp_clear_scheduled_hook( 'hipaa_gauge_push_site_info' );
		// Clears options.
		delete_option( '_hipaa_gauge_api_token' );
		delete_option( '_hipaa_gauge_api_user_data' );
		delete_option( 'hipaa_gauge_plugin_do_activation_redirect' );
		delete_option( 'hipaa_gauge_plugin_do_registered' );

		// Get Site ID.
		$site_url = sanitize_url( $_SERVER['HTTP_HOST'] );
		if ( ! filter_var( $site_url, FILTER_VALIDATE_URL ) ) {
			return;
		}
		$site_host = parse_url( $site_url, PHP_URL_HOST );
		$site_id = base64_encode( $site_host );
		$api_url = sanitize_url( HIPAA_GAUGE_API_URL . 'api/sites/' . $site_id );
		if ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
			return;
		}
		// Build arguments
		$args = array( 'site_id' => $site_id );

		// Calls API to delete site.
		$response = wp_remote_request(
			$api_url,
			array(
				'method' => 'PUT',
					'headers' => array(
						'Content-Type' => 'application/json'
					),
				'body' => json_encode( $args ),
			)
		);

		// Gets API response.
		$response_body = wp_remote_retrieve_body( $response );

		// Checks if response body exists.
		if ( ! is_wp_error( $response ) && ! empty( $response_body ) ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_data = json_decode( $response_body, true );

			// Checks if success.
			if ( $response_code == 200 ) {
				// TO DO on success API
			}
		}
	}
}
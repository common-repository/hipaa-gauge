<?php
// If this file is called directly, busted!
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin APIs specific functionality of the plugin.
 *
 * Defines the plugin name, version
 *
 * @package    HIPAA_Gauge
 * @subpackage HIPAA_Gauge/includes
 */
class HIPAA_Gauge_APIs {

	/**
	 * The ID of this plugin.
	 *
	 * @access private
	 * @var string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @access private
	 * @var string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Other important variables.
	 *
	 * @access private
	 */
	private $api_url = HIPAA_GAUGE_API_URL;
	private $dashboard_key = 'hipaa-gauge-dashboard';
	private $register_key = 'hipaa-gauge-register';
	private $login_key = 'hipaa-gauge-login';

	/**
	 * Initializes the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Gets site domain.
		$this->site_domain = parse_url( sanitize_url( $_SERVER['HTTP_HOST'] ), PHP_URL_HOST );
		// Sets standard api error message.
		$this->standard_api_error = array(
			'error' => __( 'Something went wrong with API request.', 'hipaa-gauge' ),
			'code' => 400,
		);

		// Adds callback for handling form submissions.
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );

		// Adds callback for footer.
		add_action( 'wp_footer', array( $this, 'hipaavault_trustmark' ), 99999 );

		// Adds callback for pushing site info.
		add_action( 'hipaa_gauge_push_site_info', array( $this, 'push_site_info' ) );
	}

	/**
	 * Cron Job to push site info.
	 */
	public function push_site_info() {

		// Gets site details.
		$version = get_bloginfo( 'version' );
		$siteinfo = array( 'wp_core' => sanitize_text_field( $version ) ) ;

		// Gets all installed themes details.
		$all_themes = wp_get_themes();

		if ( ! empty( $all_themes ) ) {
			foreach ( $all_themes as $theme_slug => $theme ) {
				$theme_slug = sanitize_text_field( $theme_slug );
				$siteinfo['themes'][ $theme_slug ] = array(
					'name' => sanitize_text_field( $theme->get( 'Name' ) ),
					'author' => sanitize_text_field( $theme->get( 'Author' ) ),
					'version' => sanitize_text_field( $theme->get( 'Version' ) ),
					'themeuri' => sanitize_text_field( $theme->get( 'ThemeURI' ) ),
					'slug' => $theme_slug,
				);
			}
		}

		// Loads file if not loaded.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		// Gets all installed plugins details.
		$plugins = get_plugins();
		if ( ! empty( $plugins ) ) {
			foreach ( $plugins as $key => $plugin ) {
				$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . sanitize_text_field( $key ) );
				$siteinfo['plugins'][sanitize_text_field( $plugin_data['Name'] )] = array(
					'name' => sanitize_text_field( $plugin_data['Name'] ),
					'version' => sanitize_text_field( $plugin_data['Version'] ),
					'pluginuri' => sanitize_text_field( $plugin_data['PluginURI'] ),
					'author' => sanitize_text_field( $plugin_data['AuthorName'] ),
					'slug' => sanitize_text_field( strtok( $key, '/' ) ),
				);
			}
		}

		// Gets Site ID.
		$site_id = base64_encode( $this->site_domain );

		// Calls API to send site info.
		$response = wp_remote_request(
			$this->api_url . 'api/sites/' . $site_id . '/site-info',
			array(
				'method' => 'PUT',
				'headers' => array(
					'Content-Type' => 'application/json'
			    ),
			    'body' => json_encode( $siteinfo ),
		    )
		);

		// Error in API response.
		if ( is_wp_error( $response ) ) {
			return $this->standard_api_error;
		}

		// Gets API response body.
		$response_body = wp_remote_retrieve_body( $response );

		return json_decode( $response_body, true );
	}

	/**
	 * Handles HIPAA Vault Trust Mark.
	 */
	public function hipaavault_trustmark() {

		$site_data = $this->get_site_data();

		if ( is_array( $site_data ) && ! empty( $site_data['premium'] ) && true == $site_data['premium'] ) {
			// Builds Trust Mark badge.
			$home_page = get_home_url();
			$current_page = rtrim( esc_url( home_url( $_SERVER['REQUEST_URI'] ) ), '/' );
			if ( $home_page != $current_page ) {
				echo '<div class="hipaa-gauge-trustmark" style="position:relative;border:1px solid #000000;padding:5px;background:#000000;text-align:center;width:100%;"><a href="https://www.hipaavault.com" target="_blank" style="color:#ffffff;font-weight:500;text-decoration:none;line-height:26px;font-size:19px;">' . esc_html__( 'HIPAA Vault', 'hipaa-gauge' ) . '</a></div>';
			}
		}
	}

	/**
	 * Clears API session.
	 *
	 * @param string @redirect_to Optional. Redirects to a page. Default empty.
	 */
	public function clear_api_session( $redirect_to = '' ) {

		// Clears session.
		delete_option( '_hipaa_gauge_api_user_data' );

		// Checks if redirection required.
		if ( ! empty( $redirect_to ) ) {
			$redirect_to = sanitize_url( $redirect_to );
			if ( filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
				// Redirect page.
				wp_redirect( $redirect_to );
				exit;
			}
		}
	}

	/**
	 * Gets user info.
	 *
	 * @global object $apis
	 *
	 * @return array Error or user info.
	 */
	public function get_user_info() {
		global $apis;

		// Gets user data.
		$api_data = get_option( '_hipaa_gauge_api_user_data' );
		// Validates email and/or site are not empty or in error.
		$api_data_email = '';
		$api_data_site = '';
		if ( ! empty( $api_data['email'] ) ) {
			$api_data_email = sanitize_email( $api_data['email'] );
			if ( ! is_email( $api_data_email ) ) {
				return $this->standard_api_error;
			}
		}
		if ( ! empty( $api_data['site'] ) ) {
			$api_data_site = parse_url( sanitize_url( $api_data['site'] ), PHP_URL_HOST );
			if ( ! esc_url_raw( $api_data_site ) === $api_data_site ) {
				return $this->standard_api_error;
			}
		}
		if ( empty( $api_data_email ) || empty( $api_data_site ) ) {
			return array(
				'error' => true,
				'message' => __( 'Account not connected, please verify your email first.', 'hipaa-gauge' ),
				'code' => 400,
			);
		}

		// Gets base64encoded email id and site id.
		$user_id = base64_encode( $api_data_email );
		$site_id = base64_encode( $api_data_site );
		$args = array( 'site' => $api_data_site	);

		// Calls API for user info.
		$api_url = sanitize_url( $this->api_url . 'api/users/' . $user_id . '/' . $site_id );
		if ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
			return $this->standard_api_error;
		}
		$response = wp_remote_get(
			$api_url,
			array(
			    'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $api_data_email . ':' . $api_data_site ),
					'Content-Type' 	=> 'application/json',
			    ),
			    'body' => $args,
		    )
		);

		// Error in API call.
        if ( is_wp_error( $response ) ) {
            return $this->standard_api_error;
        }

		// Gets API response body.
		$response_body = wp_remote_retrieve_body( $response );
		$response_array = json_decode( $response_body, true );

		// Returns standard error if response body is empty.
		if ( ! is_array ( $response_array ) ) {
			return $this->standard_api_error;
		}
		if ( ! empty( $response_array['status'] ) && $response_array['status'] == 'Please verify email' ) {
			// Email from option is not verified.
			// Check if email from option is the same as current user's email.
			$current_user = wp_get_current_user();
			$current_user_email = sanitize_email( $current_user->user_email );
			if ( $api_data_email != $current_user_email ) {
				// Email from option does not match current user email.
				// Delete old user data and token.
				// Get access for current user and re-call get_user_info().
				delete_option( '_hipaa_gauge_api_user_data' );
				delete_option( '_hipaa_gauge_api_token' );
				$this->get_access();
				$response_array = $this->get_user_info();
			}
		}

		return $response_array;
	}

	/**
	 * Gets reports.
	 *
	 * @return array Error or reports data.
	 */
	public function get_reports() {
		// Gets base64encoded site id.
		$site_id = base64_encode( $this->site_domain );

		// Calls API for reports.
		$api_url = sanitize_url( $this->api_url . 'api/sites/' . $site_id . '/reports' );
		if ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
			return $this->standard_api_error;
		}

		$response = wp_remote_get(
			$api_url,
			array(
			    'headers' => array(
					'Authorization' => 'Basic ' . $site_id,
					'Content-Type' 	=> 'application/json',
			    ),
		    )
		);

		// Error in API call.
		if ( is_wp_error( $response ) ) {
			return $this->standard_api_error;
		}

		// Gets API response body.
		$response_body = wp_remote_retrieve_body( $response );
		$response_body_decoded = json_decode( $response_body, true );

		// Returns standard error if response body is empty.
		if ( ! is_array ( $response_body_decoded ) ) {
			return $this->standard_api_error;
		}

		return $response_body_decoded;
	}

	/**
	 * Gets report by id.
	 *
	 * @param int $id Report id.
	 *
	 * @return array Error or report data.
	 */
	public function get_report_by_id( $id ) {
		// Returns error if invalid report id.
		if ( ! preg_match( '/^[0-9]+$/', $id ) ) {
			return array(
				'error' => true,
				'message' => __( 'Please specify report ID.', 'hipaa-gauge' ),
				'code' => 400,
			);
		}
		// Gets base64encoded site id.
		$site_id = base64_encode( $this->site_domain );

		// Calls API for report by id.
		$api_url = sanitize_url( $this->api_url . 'api/sites/' . $site_id . '/reports/' . $id );
		if ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
			return $this->standard_api_error;
		}

		$response = wp_remote_get(
			$api_url,
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . $site_id,
					'Content-Type' 	=> 'application/json',
			    ),
		    )
		);
		// Error in API response.
		if ( is_wp_error( $response ) ) {
 			return $this->standard_api_error;
		}

		// Gets API response body.
		$response_body = wp_remote_retrieve_body( $response );
		$response_body_decoded = json_decode( $response_body, true );
		// Returns error if response body is empty.
		if ( ! is_array ( $response_body_decoded ) ) {
			return $this->standard_api_error;
		}

		return $response_body_decoded;
	}

	/**
	 * Registers site.
	 *
	 * @global $api_responses array
	 *
	 * @return array Error or user info.
	 */
	public function register_site() {
		global $api_responses;

		$site_domain = parse_url( sanitize_url( $_SERVER['HTTP_HOST'] ), PHP_URL_HOST );
		$api_responses = ! empty( $api_responses ) ? $api_responses : array();

		$args = array(
			'site' => $site_domain,
		);

		$api_url = sanitize_url( $this->api_url . 'api/sites' );
		if ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
			return $this->standard_api_error;
		}
		$response = wp_remote_post(
			$api_url,
			array(
				'method' => 'POST',
				'headers' => array(
					'Content-Type' => 'application/json'
				),
				'body' => json_encode( $args ),
			)
		);

		// Error in API response.
		if ( is_wp_error( $response ) ) {
			return $this->standard_api_error;
		}

		// Gets API response.
		$response_body = wp_remote_retrieve_body( $response );
		$response_body_decoded = json_decode( $response_body, true );
		if ( ! is_array ( $response_body_decoded ) ) {
			return $this->standard_api_error;
		}

		return $response_body_decoded;
	}

	/**
	 * Plugin Get/Set Site.
	 * Check if the site is new or registered.
	 *
	 * @return array Create user response.
	 */
	public function plugin_get_set_site() {

		global $api_responses;

		$response = $this->register_site();
		// Problem registering site.
		if ( ! isset ( $response['status'] ) || $response['code'] == 400 || $response['code'] == 503 ) {
			return $this->standard_api_error;
		}
		$status = '';
		if ( ! empty( $response['status'] ) ) {
			$status = sanitize_text_field( $response['status'] );
		} else {
			$status = 'No issues registering site';;
		}
		$site_responses = array(
			'message' => $status,
			'class' => 'alert-info',
			'code' => 200,
		);
		$this->set_installation_scan();

		return $site_responses;
	}

	/**
	 * Handles form submissions.
	 *
	 * @global array $api_responses
	 */
	public function handle_form_submission() {

		global $api_responses;

		$api_responses = ! empty( $api_responses ) ? $api_responses : array();

		// Checks if Upgrade/Downgrade submitted.
		if ( ! empty( $_REQUEST['_wpnonce_hipaa_gauge'] ) && wp_verify_nonce( $_REQUEST['_wpnonce_hipaa_gauge'], 'upgrade_downgrade' ) && ! wp_doing_ajax() ) {
			// Gets base64encoded email ID.
			$site_id = base64_encode( $this->site_domain );
			// Calls API to update user premium status.
			$api_url = sanitize_url( $this->api_url . 'api/sites/' . $site_id . '/premium' );
			if ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
				return $this->standard_api_error;
			}
			$response = wp_remote_request(
				$api_url,
				array(
					'method' => 'PUT',
					'headers' => array(
						'Content-Type' 	=> 'application/json',
						'Authorization' => 'Basic ' . $site_id,
					),
				)
			);

			// Gets API response body.
			$response_body = wp_remote_retrieve_body( $response );
			// Error in API response.
			if ( is_wp_error( $response ) ) {
				return $this->standard_api_error;
			}
			$response_body_decoded = json_decode( $response_body, true );
			$response_code =  wp_remote_retrieve_response_code( $response );
			if ( $response_code != 200 ) {
				// Error in response.
				return $response_body_decoded;
			}

			// Validated resonse body is not empty.
			if ( is_array( $response_body ) ) {
				$api_responses['updowngrade'] = array(
					'response' => $response_body_decoded,
					'code' => $response_code,
				);

				// Validates status is in response.
				if ( ! empty( $res_data['status'] ) ) {
					// Premium status has been changed, which changes the cron schedule.
					// Removes any scheduled site info push and re-schedules.
					wp_clear_scheduled_hook( 'hipaa_gauge_push_site_info' );
					$this->set_scheduled_events();
					$args = array(
						'code' => $response_code,
						'status' => sanitize_text_field( $response_body_decoded['status'] ),
					);
					// Redirects to dashboard.
					$redirect_url = add_query_arg( $args, admin_url( 'admin.php?page='. $this->dashboard_key ) );
					wp_redirect( $redirect_url );
					exit;
				}
			}
		}
	}

	/**
	 * Gets plugins by report id.
	 *
	 * @param int $id Report id.
	 *
	 * @return array Error or plugins data.
	 */
	public function get_plugins_by_report_id( $id ) {
		// Returns error if invalid report id.
		if ( ! preg_match( '/^[0-9]+$/', $id ) ) {
			return array(
                'error' => __( 'Please specify report ID.', 'hipaa-gauge' ),
                'code' => 400,
			);
		}

		// Gets base64encoded site id.
		$site_id = base64_encode( $this->site_domain );

		// Calls API for report plugins.
		$api_url = sanitize_url( $this->api_url . 'api/sites/' . $site_id . '/reports/' . $id . '/plugins' );
		if ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
			return $this->standard_api_error;
		}
		$response = wp_remote_get(
			$api_url,
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . $site_id,
					'Content-Type'  => 'application/json',
				),
			)
		);

		// Error in API response.
		if ( is_wp_error( $response ) ) {
			return $this->standard_api_error;
		}

		// Gets API response body.
		$response_body = wp_remote_retrieve_body( $response );
		$response_body_decoded = json_decode( $response_body, true );
		// Returns error if response body is empty.
		if ( ! is_array ( $response_body_decoded ) ) {
			return $this->standard_api_error;
		}

		return $response_body_decoded;
	}

	/**
	 * Gets themes data by report id.
	 *
	 * @param int $id Report id.
	 *
	 * @return array Error or theme data.
	 */
	public function get_themes_by_report_id( $id ) {
		// Returns error if invalid report id.
		if ( ! preg_match( '/^[0-9]+$/', $id ) ) {
			return array(
				'error' => __( 'Please specify report ID.', 'hipaa-gauge' ),
				'code' => 400,
			);
		}

		// Gets base64encoded site id.
		$site_id = base64_encode( $this->site_domain );

		// Calls API for report theme.
		$api_url = sanitize_url( $this->api_url . 'api/sites/' . $site_id . '/reports/' . $id . '/themes' );
		if ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
			return $this->standard_api_error;
		}
		$response = wp_remote_get(
			$api_url,
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . $site_id,
					'Content-Type'  => 'application/json',
				),
			)
		);

		// Error in API response.
		if ( is_wp_error( $response ) ) {
			return $this->standard_api_error;
		}

		// Gets API response body.
		$response_body = wp_remote_retrieve_body( $response );
		$response_body_decoded = json_decode( $response_body, true );
		// Returns error if response body is empty.
		if ( ! is_array ( $response_body_decoded ) ) {
			return $this->standard_api_error;
		}

		return $response_body_decoded;
	}

	/**
	 * Gets wordpress core data by report id.
	 *
	 * @param int $id Report id.
	 *
	 * @return array Error or theme data.
	 */
	public function get_wordpress_core_by_report_id( $id ) {
		// Returns error if invalid report id.
		if ( ! preg_match( '/^[0-9]+$/', $id ) ) {
			return array(
				'error' => __( 'Please specify report ID.', 'hipaa-gauge' ),
				'code' => 400,
			);
		}

		// Gets base64encoded site id.
		$site_id = base64_encode( $this->site_domain );

		// Calls API for report wordpress core.
		$api_url = sanitize_url( $this->api_url . 'api/sites/' . $site_id . '/reports/' . $id . '/wordpress-core' );
		if ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
			return $this->standard_api_error;
		}
		$response = wp_remote_get(
			$api_url,
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . $site_id,
					'Content-Type'  => 'application/json',
				),
			)
		);
		// Error in API response.
		if ( is_wp_error( $response ) ) {
			return $this->standard_api_error;
		}

		// Gets API response body.
		$response_body = wp_remote_retrieve_body( $response );
		$response_body_decoded = json_decode( $response_body, true );
		// Returns error if response body is empty.
		if ( ! is_array ( $response_body_decoded ) ) {
			return $this->standard_api_error;
		}

		return $response_body_decoded;
	}

	/**
	 * Gets web server data by report id.
	 *
	 * @param int $id Report id.
	 *
	 * @return array Error or web server data.
	 */
	public function get_web_server_by_report_id( $id ) {
		// Returns error if invalid report id.
		if ( ! preg_match( '/^[0-9]+$/', $id ) ) {
			return array(
				'error' => __( 'Please specify report ID.', 'hipaa-gauge' ),
				'code' => 400,
			);
		}

		// Gets base64encoded site id.
		$site_id = base64_encode( $this->site_domain );

		// Calls API for report web server.
		$api_url = sanitize_url( $this->api_url . 'api/sites/' . $site_id . '/reports/' . $id . '/web-server' );
		if ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
			return $this->standard_api_error;
		}
		$response = wp_remote_get(
			$api_url,
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . $site_id,
					'Content-Type'  => 'application/json',
				),
			)
		);

		// Error in API response.
		if ( is_wp_error( $response ) ) {
			return $this->standard_api_error;
		}

		// Gets API response body.
		$response_body = wp_remote_retrieve_body( $response );
		$response_body_decoded = json_decode( $response_body, true );
		// Returns error if response body is empty.
		if ( ! is_array ( $response_body_decoded ) ) {
			return $this->standard_api_error;
		}

		return $response_body_decoded;
	}

	/**
	 * Sets any scheduled events.
	 */
	public function set_scheduled_events() {
		$site_data = $this->get_site_data();
		$interval = 'weekly'; // Free user interval.
		if ( ! empty( $site_data['premium'] ) && true === $site_data['premium'] ) {
			$interval = 'daily'; // Premium user interval.
		}

		// Schedules event if not already scheduled.
		if ( ! wp_next_scheduled( 'hipaa_gauge_push_site_info' ) ) {
			wp_schedule_event( time(), $interval, 'hipaa_gauge_push_site_info' );
		}
	}

	/**
	 * Sets installation scan.
	 */
	public function set_installation_scan() {
		$push_site_info = $this->push_site_info();
		if ( ! array_key_exists( 'status', $push_site_info) || $push_site_info['status'] != 'Site info received' ) {
			// Error in pushing site info.
			return;
		}

		// Gets base64encoded site id.
		$site_id = base64_encode( $this->site_domain );

		// Calls API to run scan.
		$api_url = sanitize_url( $this->api_url . 'api/sites/' . $site_id . '/new-site' );
		if ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
			return $this->standard_api_error;
		}

		wp_remote_get(
			$api_url,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
				),
			)
		);
	}

	/**
	 * Gets site data.
	 *
	 * @return array Error or site data.
	 */
	public function get_site_data() {
		// Gets base64encoded site id.
		$site_id = base64_encode( $this->site_domain );

		// Calls API for site data.
		$api_url = sanitize_url( $this->api_url . 'api/sites/' . $site_id . '/status' );
		if ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
			return $this->standard_api_error;
		}

		$response = wp_remote_get(
			$api_url,
			array(
			    'headers' => array(
					'Authorization' => 'Basic ' . $site_id,
					'Content-Type' 	=> 'application/json',
			    ),
		    )
		);

		// Error in API call.
		if ( is_wp_error( $response ) ) {
			return $this->standard_api_error;
		}

		// Gets API response body.
		$response_body = wp_remote_retrieve_body( $response );
		$response_body_decoded = json_decode( $response_body, true );
		// Returns standard error if response body is empty.
		if ( ! is_array ( $response_body_decoded ) ) {
			return $this->standard_api_error;
		}
		$response_body_decoded['code'] = wp_remote_retrieve_response_code( $response );

		return $response_body_decoded;
	}
}
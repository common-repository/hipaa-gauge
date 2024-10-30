<?php
// If this file is called directly, busted!
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    HIPAA_Gauge
 * @subpackage HIPAA_Gauge/admin
 */
class HIPAA_Gauge_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @access private
	 *
	 * @var string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @access private
	 *
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
	private $myinfo_key = 'hipaa-gauge-myinfo';
	private $allreports_key = 'hipaa-gauge-allreports';
	private $report_key = 'hipaa-gauge-report';
	private $resetpassword_key = 'hipaa-gauge-resetpassword';
	private $update_phone_key = 'hipaa-gauge-phone';
	private $update_name_key = 'hipaa-gauge-names';
	private $update_email_key = 'hipaa-gauge-email';

	/**
	 * Initializes the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->site_domain = parse_url( sanitize_url( $_SERVER['HTTP_HOST'] ), PHP_URL_HOST );

		add_action( 'admin_init', array( $this, 'access_restriction' ), 10 );
	}

	/**
	 * Handles role restrictions.
	 * Fires as an admin screen is being initialized.
	 *
	 */
	function access_restriction() {
		// Redirects to dashboard if accessing report page without an id.
		if ( ! empty( $_GET['page'] ) && $_GET['page'] == $this->report_key && empty( $_GET['id'] ) ) {
			wp_redirect( admin_url( 'admin.php?page=' . $this->dashboard_key ) );
			exit;
		}
	}

	/**
	 * Registers the stylesheets for the admin area.
	 *
	 * @param string $hook Hook to check.
	 */
	public function enqueue_styles( $hook ) {
		// Defined allowed screens.
		$allowed_screens = array( 'toplevel_page_hipaa-gauge-dashboard', 'hipaa-gauge_page_hipaa-gauge-allreports', 'hipaa-gauge_page_hipaa-gauge-report', 'toplevel_page_register', 'hipaa-gauge_page_login', 'hipaa-gauge_page-myinfo', 'hipaa-gauge_page_names', 'hipaa-gauge_page_resetpassword', 'hipaa-gauge_page_phone', 'hipaa-gauge_page_email' );

		// Checks if allowed screens.
		if ( in_array( $hook, $allowed_screens ) ) {
			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in Loader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The Loader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */

			wp_enqueue_style( $this->plugin_name, HIPAA_GAUGE_PLUGIN_URL . 'admin/css/admin-style.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '-bootstrap', HIPAA_GAUGE_PLUGIN_URL . 'admin/css/bootstrap.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '-app', HIPAA_GAUGE_PLUGIN_URL . 'admin/css/app.css', array(), $this->version, 'all' );

			wp_enqueue_script( $this->plugin_name .'-gauge', HIPAA_GAUGE_PLUGIN_URL . 'admin/js/gauge.min.js', array(), $this->version );
		}
	}

	/**
	 * Registers the menu pages for the admin area.
	 *
	 */
	public function add_admin_menus() {

		// Adds Dashboard menu page.
		add_menu_page(
			__( 'HIPAA Gauge - Dashboard', 'hipaa-gauge' ),
			__( 'HIPAA Gauge', 'hipaa-gauge' ),
			'manage_options',
			$this->dashboard_key,
			array (
				&$this,
				'plugin_dashboard_page'
			),
			null,
			3
		);

		// Adds Dashboard submenu page.
		add_submenu_page(
			$this->dashboard_key,
			__( 'HIPAA Gauge - Dashboard', 'hipaa-gauge' ),
			__( 'Dashboard', 'hipaa-gauge' ),
			'manage_options',
			$this->dashboard_key,
			array (
				&$this,
				'plugin_dashboard_page'
			)
		);

		// Adds All Reports menu page.
		add_submenu_page(
			$this->dashboard_key,
			__( 'HIPAA Gauge - Dashboard', 'hipaa-gauge' ),
			__( 'Reports', 'hipaa-gauge' ),
			'manage_options',
			$this->allreports_key,
			array (
				&$this,
				'plugin_myreports_page'
			)
		);

		// Adds Report Details menu page.
		add_submenu_page(
			$this->dashboard_key,
			null,
			null,
			'manage_options',
			$this->report_key,
			array (
				&$this,
				'plugin_myreport_by_id_page'
			)
		);
	}

	/**
	 * Plugin Register page callback.
	 *
	 * @global array $api_responses
	 */
	public function plugin_register_page() {

		global $api_responses;

		?>
		<div id="hipaa-gauge-dashboard" class="wrap">
			<h2><?php esc_html_e( 'HIPAA Vault Registration', 'hipaa-gauge' ); ?></h2>
			<p><?php esc_html_e( 'If you are already registered with us, then click ', 'hipaa-gauge' ); ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=hipaa-gauge-login' ) ); ?>"><?php esc_html_e( 'here', 'hipaa-gauge' ); ?></a></p>
			<?php
			// Checks and displays API response.
			$response_code = ! empty( $api_responses['register']['code'] ) ? $api_responses['register']['code'] : 0;
			if ( ( $response_code == 201 || $response_code == 200 ) && ! empty( $api_responses['register']['response']['status'] ) ) {
				echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'API Response:', 'hipaa-gauge' ) . '</strong>' . esc_html__( $api_responses['register']['response']['status'] ) . '</p></div>';
			} elseif ( ! empty( $api_responses['register']['response']['status'] ) ) {
				$this->display_error_message( $api_responses['register']['response']['status'] );
			} ?>
			<form method="post">
				<?php wp_nonce_field( 'registration', '_wpnonce_hipaa_gauge' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="email"><?php esc_html_e( 'Email', 'hipaa-gauge' ); ?></label>
							</th>
							<td>
								<?php
								$email = '';
								if ( ! empty( $_POST['email'] ) ) {
									$email = sanitize_email( $_POST['email'] );
								}
								?>
								<input id="email" type="email" name="email" value="<?php echo esc_attr( $email ); ?>" required pattern="[a-zA-Z0-9._%+-]+@[a-z0-9.-]+\.[a-zA-Z]{2,4}" title="<?php esc_attr_e( 'Please enter valid email address.', 'hipaa-gauge' ); ?>">
								<?php
								if ( isset( $_POST['email'] ) && ! is_email( $email ) ) {
									echo '<div class="hipaa-gauge-error">' . esc_html__( 'Please enter valid email address.', 'hipaa-gauge' ) . '</div>';
								}
								?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="password"><?php esc_html_e( 'Password', 'hipaa-gauge' ); ?></label>
							</th>
							<td>
								<input type="password" name="password" value="" required>
								<?php
								if ( isset( $_POST['password'] ) && empty( $_POST['password'] ) ) {
									echo '<div class="hipaa-gauge-error">' . esc_html_e( 'Please enter your password.', 'hipaa-gauge' ) . '</div>';
								}
								?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="confirm_password"><?php esc_html_e( 'Confirm Password', 'hipaa-gauge' ); ?></label>
							</th>
							<td>
								<input type="password" name="confirm_password" value="" required>
								<?php
								if ( isset( $_POST['password'] ) && isset( $_POST['confirm_password'] )
									&& $_POST['password'] != $_POST['confirm_password'] ) {
									echo '<div class="hipaa-gauge-error">' . esc_html__( 'Passwords do not match.', 'hipaa-gauge' ) . '</div>';
								}
								?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="site_url"><?php esc_html_e( 'Site URL', 'hipaa-gauge' ); ?></label>
							</th>
							<td>
								<input type="text" name="site_url" value="<?php echo esc_url( $this->site_domain ); ?>" readonly="readonly" required>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="first_name"><?php esc_html_e( 'First Name', 'hipaa-gauge' ); ?></label>
							</th>
							<td>
								<?php
								$first_name = '';
								if ( ! empty( $_POST['first_name'] ) ) {
									$first_name = sanitize_text_field( $_POST['first_name'] );
								}
								?>
								<input type="text" name="first_name" value="<?php echo esc_attr( $first_name ); ?>" required>
								<?php
								if ( isset( $_POST['first_name'] ) && empty( $first_name ) ) {
									echo '<div class="hipaa-gauge-error">' . esc_html__( 'Please enter a valid name.', 'hipaa-gauge' ) . '</div>';
								}
								?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="last_name"><?php esc_html_e( 'Last Name', 'hipaa-gauge' ); ?></label>
							</th>
							<td>
								<?php
								$last_name = '';
								if ( ! empty( $_POST['last_name'] ) ) {
									$last_name = sanitize_text_field( $_POST['last_name'] );
								}
								?>
								<input type="text" name="last_name" value="<?php echo esc_attr( $last_name ); ?>" required>
								<?php
								if ( isset( $_POST['last_name'] ) && empty( $last_name ) ) {
									echo '<div class="hipaa-gauge-error">' . esc_html__( 'Please enter a valid name.', 'hipaa-gauge' ) . '</div>';
								}
								?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="phone"><?php esc_html_e( 'Phone Number', 'hipaa-gauge' ); ?></label>
							</th>
							<td>
								<?php
								$phone = '';
								if ( ! empty( $_POST['phone'] ) ) {
									$phone = sanitize_text_field( $_POST['phone'] );
								}
								?>
								<input type="text" name="phone" value="<?php echo esc_attr( $phone ); ?>" required>
								<?php
								if ( isset( $_POST['phone'] ) && ! preg_match('/^[0-9]{10}+$/', $phone ) ) {
									echo '<div class="hipaa-gauge-error">' . esc_html__( 'Please enter a valid phone number.', 'hipaa-gauge' ) . '</div>';
								}
								?>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Submit', 'hipaa-gauge' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Plugin Login page callback.
	 *
	 * @global array $api_responses
	 */
	public function plugin_login_page() {

		global $api_responses;

		?>
		<div id="hipaa-gauge-dashboard" class="wrap">
			<h2><?php esc_html_e( 'HIPAA Vault Login', 'hipaa-gauge' ); ?></h2>
			<p><?php esc_html_e( 'If you are a new User click ', 'hipaa-gauge' ); ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this->register_key ) ); ?>"><?php esc_html_e( 'here', 'hipaa-gauge' ); ?></a></p>
			<?php
			// Checks and displays API response.
			$response_code = ! empty( $api_responses['login']['code'] ) ? $api_responses['login']['code'] : 0;

			if ( ( $response_code == 201 || $response_code == 200 ) && ! empty( $api_responses['login']['response']['token'] ) ) {
				echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'API Response:', 'hipaa-gauge' ) . '</strong> ' . esc_html__( 'Successfully logged in and generated token.', 'hipaa-gauge' ) . '</p></div>';
			} elseif ( ! empty( $api_responses['login'] ) ) {
				$this->display_error_message( 'Username and Password not accepted for token generation.' );
			} elseif ( ! empty( $_GET['requiredlogin'] ) && $_GET['requiredlogin'] == 'resetpass' ) {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Please confirm your password reset confirmation email. You can login with your new password.', 'hipaa-gauge' ) . '</p></div>';
			} elseif ( ! empty( $_GET['requiredlogin'] ) && $_GET['requiredlogin'] == 'resetemail' ) {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Please confirm your email change confirmation. You can login with your new email.', 'hipaa-gauge' ) . '</p></div>';
			} elseif ( ! empty( $_GET['requiredlogin'] ) && $_GET['requiredlogin'] == 'emptyinfo' ) {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Please check your email inbox if you requested something OR Something went wrong with API.', 'hipaa-gauge' ) . '</p></div>';
			}
			?>
			<form method="post">
				<?php wp_nonce_field( 'login', '_wpnonce_hipaa_gauge' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="email"><?php esc_html_e( 'Email', 'hipaa-gauge' ); ?></label>
							</th>
							<td>
								<?php
								$email = '';
								if ( ! empty( $_POST['email'] ) ) {
									$email = sanitize_email( $_POST['email'] );
								}
								?>
								<input id="email" type="email" name="email" value="<?php echo esc_attr( $email ); ?>" required pattern="[a-zA-Z0-9._%+-]+@[a-z0-9.-]+\.[a-zA-Z]{2,4}" title="<?php esc_attr_e( 'Please enter valid email address.', 'hipaa-gauge' ); ?>">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="password"><?php esc_html_e( 'Password', 'hipaa-gauge' ); ?></label>
							</th>
							<td>
								<input type="password" name="password" value="" required>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Login', 'hipaa-gauge' ); ?>">
					&nbsp;&nbsp;<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this->resetpassword_key ) ); ?>"><?php esc_html_e( 'Lost your password?', 'hipaa-gauge' ); ?></a>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Plugin My Information page callback.
	 *
	 * @global object $apis
	 */
	public function plugin_myinfo_page() {

		global $apis;

		// Gets User Info using API.
		$user_info = $apis->get_user_info();

		?>
		<div id="hipaa-gauge-dashboard" class="wrap">
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=hipaa-gauge-dashboard' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'hipaa-gauge' ); ?></a>
			<h2><?php esc_html_e( 'HIPAA Vault User Information', 'hipaa-gauge' ); ?></h2>
			<?php
			// Checks and display API response.
			$response_code = ! empty( $user_info['code'] ) ? $user_info['code'] : 0;
			if ( $response_code != 200 && ! empty( $user_info['message'] ) ) {
				$this->display_error_message( $user_info['message'] );
			} elseif ( $response_code === 0 && ! empty( $user_info['status'] ) ) {
				$this->display_error_message( $user_info['status'] );
			} else {
			?>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Email', 'hipaa-gauge' ); ?></th>
						<td><?php echo esc_html( sanitize_email( $user_info['email'] ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Site', 'hipaa-gauge' ); ?></th>
						<td><?php echo esc_url( $user_info['site'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'First Name', 'hipaa-gauge' ); ?></th>
						<td><?php esc_html( sanitize_text_field( $user_info['first'] ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Last Name', 'hipaa-gauge' ); ?></th>
						<td><?php esc_html( sanitize_text_field( $user_info['last'] ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Phone Number', 'hipaa-gauge' ); ?></th>
						<td><?php esc_html( sanitize_text_field( $user_info['phone'] ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Premium?', 'hipaa-gauge' ); ?></th>
						<td>
							<?php
							if ( array_key_exists( 'premium', $user_info ) && true === $user_info['premium'] ) {
								esc_html_e( 'Yes', 'hipaa-gauge' );
							} else {
								esc_html_e( 'No', 'hipaa-gauge' );
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Plugin My Reports page callback.
	 *
	 * @global object $apis
	 */
	public function plugin_myreports_page() {

		global $apis;

		?>
		<div id="hipaa-gauge-dashboard" class="wrap">
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=hipaa-gauge-dashboard' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'hipaa-gauge' ); ?></a><br /><br />
			<nav class="navbar page-header mb-4">
				<div class="me-auto">
					<span class="navbar-brand"><?php esc_html_e( 'HIPAA Gauge - Reports', 'hipaa-gauge' ); ?></span>
				</div>
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'images/hipaa-vault-logo.jpg' ); ?>" alt="hipaa-vault" class="d-inline-block align-text-top">
			</nav>
			<?php
			$site_data = $apis->get_site_data();
			if ( ! is_array( $site_data ) ) {
				// Issue getting site data.
				// Display error message.
				$this->display_error_message();
				return;
			}
			if ( array_key_exists( 'status', $site_data ) ) {
				// Issue getting site data.
				// Display error message.
				$this->display_error_message( $site_data['status'] );
				return;
			}
			if ( array_key_exists( 'error', $site_data ) ) {
				// Issue getting site data.
				// Display error message.
				$this->display_error_message( $site_data['error'] );
				return;
			}

			if ( ! array_key_exists( 'premium', $site_data ) || ! true === $site_data['premium'] ) {
				// User is not premium. Display error message.
				$message = 'This page is only available to Premium users.';
				$this->display_error_message( $message );
				return;
			}

			// Gets Reports using API.
			$reports = $apis->get_reports();

			// Checks and displays API response.
			if ( ! is_array( $reports) ) {
				$this->display_error_message();
				return;
			}
			if ( ! empty( $reports['error'] ) && ! empty( $reports['message'] ) ) {
				$this->display_error_message( $reports['message'] );
				return;
			}
			if ( ! empty( $reports['status'] ) ) {
				$this->display_error_message( $reports['status'] );
				return;
			}
			// If no reports yet, display info message.
			if ( empty( $reports['scan_dates'] ) ) {
 				echo '<div class="alert alert-info mt-4">' . esc_html__( 'We are preparing your site report. Please check later.', 'hipaa-gauge' ) . '</div>';
				return;
			}
			?>
			<div class="hipaa-gauge-reports-wrap">
				<div class="hipaa-gauge-recent-reports-wrap">
					<table class="table table-bordered table-striped text-nowrap" style="width: 1px">
						<thead>
							<tr>
								<th><?php esc_html_e( 'DateTime', 'hipaa-gauge' ); ?></th>
								<th><?php esc_html_e( 'Action', 'hipaa-gauge' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $reports['scan_dates'] as $date => $id ):
							// Displays report dates with links to individual reports.
							$report_link = admin_url( 'admin.php?page=' . $this->report_key . '&id=' . $id );
							?>
							<tr>
								<td><?php echo esc_html( $date ); ?></td>
								<td><a href="<?php echo esc_url( $report_link ); ?>"><?php esc_html_e( 'View Report', 'hipaa-gauge' ); ?></a></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Plugin Report by ID page callback.
	 *
	 * @global object $apis API.
	 */
	public function plugin_myreport_by_id_page() {

		global $apis;

		?>
		<div id="hipaa-gauge-dashboard" class="wrap">
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=hipaa-gauge-dashboard' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'hipaa-gauge' ); ?></a><br /><br />
			<nav class="navbar page-header mb-4">
				<div class="me-auto">
					<span class="navbar-brand"><?php esc_html_e( 'HIPAA Gauge - Report Details', 'hipaa-gauge' ); ?></span>
				</div>
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'images/hipaa-vault-logo.jpg' ); ?>" alt="hipaa-vault" class="d-inline-block align-text-top">
			</nav>
			<?php
			$site_data = $apis->get_site_data();
			if ( ! is_array( $site_data ) ) {
				// Issue getting site data.
				// Display error message.
				$this->display_error_message();
				return;
			}
			if ( array_key_exists( 'status', $site_data ) ) {
				// Issue getting site data.
				// Display error message.
				$this->display_error_message( $site_data['status'] );
				return;
			}
			if ( array_key_exists( 'error', $site_data ) ) {
				// Issue getting site data.
				// Display error message.
				$this->display_error_message( $site_data['error'] );
				return;
			}

			if ( ! array_key_exists( 'premium', $site_data ) || ! true === $site_data['premium'] ) {
				// User is not premium. Display error message.
				$message = 'This page is only available to Premium users.';
				$this->display_error_message( $message );
				return;
			}

			// Gets report using API.
			if ( empty( $_GET['id'] ) || ! preg_match( '/^[0-9]+$/', $_GET['id'] ) ) {
				$message = 'Report ID invalid.';
				$this->display_error_message( $message );
                return;
			}
			$report_id = intval( $_GET['id'] );
			$report = $apis->get_report_by_id( $report_id );

			// Checks and displays API response.
			if ( ! is_array( $report ) ) {
				$this->display_error_message();
				return;
			}
			if ( ! empty( $report['error'] ) && ! empty( $report['message'] ) ) {
				$this->display_error_message( $report['message'] );
			}
			if ( ! empty( $report['status'] ) ) {
				$this->display_error_message( $report['status'] );
			}

			// Gets detailed report data using API.
			$plugins = $apis->get_plugins_by_report_id( $report_id );
			$plugins_sorted = array();
			foreach ( $plugins['plugins'] as $plugin ) {
				$plugins_sorted[strtoupper( $plugin['name'] )] = $plugin;
			}
			ksort( $plugins_sorted, SORT_NATURAL );
			$themes = $apis->get_themes_by_report_id( $report_id );
			$themes_sorted = array();
			foreach ( $themes['themes'] as $theme ) {
				$themes_sorted[strtoupper( $theme['name'] )] = $theme;
			}
			ksort( $themes_sorted, SORT_NATURAL );
			$wordpress_core = $apis->get_wordpress_core_by_report_id( $report_id );
			$web_server = $apis->get_web_server_by_report_id( $report_id );

			$count_plugins_not_up_to_date = 0;
			$count_themes_not_up_to_date = 0;

			if ( ! empty( $report['plugins_score'] ) && $report['plugins_score'] != '100%' ) {
				foreach( $plugins_sorted as $plugin ) {
					if (! empty ( $plugin['vulnerabilities'] ) ) {
						foreach ( $plugin['vulnerabilities'] as $vulnerability ) {
							if ( $vulnerability == 'Installed version is not latest version' ) {
								++$count_plugins_not_up_to_date;
							}
						}
					}
				}
			}
			if ( ! empty( $report['themes_score'] ) && $report['themes_score'] != '100%' ) {
				foreach( $themes_sorted as $theme ) {
					if ( ! empty ( $theme['vulnerabilities'] ) ) {
						foreach ( $theme['vulnerabilities'] as $vulnerability ) {
							if ( $vulnerability == 'Installed version is not latest version' ) {
								++$count_themes_not_up_to_date;
							}
						}
					}
				}
			}
			?>
			<?php
			$datetime = '';
			$site = '';
			if ( ! empty( $report['datetime'] ) ) {
				$datetime = sanitize_text_field( $report['datetime'] );
			}
			if ( ! empty( $report['site'] ) ) {
				$site = sanitize_url( $report['site'] );
			}
			if ( ! empty ( $datetime ) && ! empty( $site) ) {
				echo esc_html( $datetime .  ' ' . esc_url( $site ) );
			}
			?>
			<br /><br />
			<table class="table table-sm table-bordered table-striped text-nowrap" style="width: 1px">
				<thead class="table-dark">
					<tr>
						<td>Security Check</td>
						<td>Current Results</td>
					</tr>
				</thead>
				<tr>
					<td>WordPress secret keys</td>
					<?php
						// Security check for wp secret keys.
						$keys = array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' );
						$keys_with_default_values = array();
						foreach ( $keys as $key ) {
							if ( defined( $key ) && constant( $key ) === 'put your unique phrase here' ) {
								// Secret key has not been changed from default value.
								array_push( $keys_with_default_values, $key );
							}
						}
						if ( empty ( $keys_with_default_values ) ) {
							// All wp secret keys have been changed from default values.
							echo '<td class="table-success">' . esc_html__( 'All have been changed from the default values.', 'hipaa-gauge' );
						} else {
							// Not all wp secret keys have been changed from default values.
							echo '<td class="table-danger">' . esc_html__( 'Not all have been changed from the default values. They should be changed.', 'hipaa-gauge' );
							foreach ( $keys_with_default_values as $key_default ) {
								echo '<br />' . esc_html__( $key_default );							}
						}
					?>
					</td>
				</tr>
				<tr>
					<td>XML-RPC</td>
					<?php
						// Security check for XML-RPC.
						$xml_url = get_site_url() . '/xmlrpc.php';
						$xml_is_available = wp_remote_get( $xml_url, array( 'timeout' => 5 ) );
						$xml_is_available_code = wp_remote_retrieve_response_code( $xml_is_available );
						if ( $xml_is_available_code !== 405 ) {
							// XML-RPC is disabled.
							echo '<td class="table-success">' . esc_html__( 'Interface is disabled. This closes some security holes.', 'hipaa-gauge' );
						} else {
							// XML-RPC is not disabled. Try an authenticated request.
							$authenticated_body = '<?xml version="1.0" encoding="iso-8859-1"?><methodCall><methodName>wp.getUsers</methodName><params><param><value>1</value></param><param><value>username</value></param><param><value>password</value></param></params></methodCall>';
							$authenticated_response = wp_remote_post( $xml_url, array( 'body' => $authenticated_body ) );
							if ( is_wp_error( $authenticated_response ) ) {
								// The authenticated_response returned a WP_Error.
								// Should we do anything here?
								echo '<td class="table-danger">' . esc_html__( 'Interface is enabled. This poses some security risks.', 'hipaa-gauge' );
							} else {
								echo '<td class="table-danger">';
								// No error in response.
								if ( preg_match( '/<string>Incorrect username or password.<\/string>/', $authenticated_response['body'] ) ) {
									// XML-RPC is enabled.
									esc_html_e( 'Interface is enabled. This poses some security risks.', 'hipaa-gauge' );
								} else {
									// Try an unauthenticated request.
									$unauthenticated_body     = '<?xml version="1.0" encoding="iso-8859-1"?><methodCall><methodName>demo.sayHello</methodName><params><param></param></params></methodCall>';
									$unauthenticated_response = wp_remote_post( $xml_url, array( 'body' => $unauthenticated_body ) );
									if ( preg_match( '/<string>Hello!<\/string>/', $unauthenticated_response['body'] ) ) {
										// XML-RPC is enabled for unauthenticated requests.
										esc_html_e( 'Iinterface is partly disabled, but still allows unauthenticated requests. This poses some security risks.', 'hipaa-gauge' );
									}
								}
							}
						}
					?>
					</td>
				</tr>
				<tr>
					<td>Passwords</td>
					<?php
						// Security check for weak passwords.
						// This is a very simple brute force password attempt.  Maybe we should remove this feature?
						// WordPress has own password security features.
						// Password list is from https://github.com/danielmiessler/SecLists/blob/master/Passwords/Common-Credentials/10k-most-common.txt.
						$users = get_users( array( 'role__in' => array( 'super_admin', 'administrator', 'editor', 'author', 'contributor' ) ) );
						$passwords = file( HIPAA_GAUGE_PLUGIN_PATH . '/admin/passwords.txt', FILE_IGNORE_NEW_LINES );
						$usernames_with_weak_password = array();
						if ( $passwords !== false ) {
							foreach ( $users as $user ) {
								$username = $user->user_login;
								foreach ( $passwords as $password ) {
									if ( wp_check_password( $password, $user->data->user_pass, $user->ID ) ) {
										// User has a weak password.
										array_push( $usernames_with_weak_password, $username );
										break;
									}
								}
							}
						}
						if ( empty ( $usernames_with_weak_password ) ) {
							// Did not find any users with weak passwords from the password list.
							echo '<td class="table-success">' . esc_html__( 'Not able to brute force the password of any privileged users.', 'hipaa-gauge' );
						} else {
							// At least 1 user has a weak password from the password list.
							echo '<td class="table-danger">' . esc_html__( 'These users were found to have weak passwords that should be updated immediately.', 'hipaa-gauge' ) . '<br />';
							foreach ( $usernames_with_weak_password as $username_with_weak_password ) {
								echo esc_html__( "- {$username_with_weak_password}" ) . '<br />';
							}
						}
					?>
					</td>
				</tr>
				<tr>
					<td>Login page</td>
					<?php
						// Security check for default home page.
						$login_url = wp_login_url();
						$default_login_url = get_site_url() . '/' . 'wp-login.php';
						if ( $login_url != $default_login_url ) {
							echo '<td class="table-success">' . esc_html__( 'Has been changed from default.', 'hipaa-gauge' );
						} else {
							echo '<td class="table-danger">' . esc_html__( 'Has not been changed from default. This poses some security risks.', 'hipaa-gauge' );
						}
					?>
					</td>
				</tr>
				<tr>
					<td>Plugins Installed</td>
					<?php
						// Security check for installed plugins that are not up-to-date.
						if ( $count_plugins_not_up_to_date == 0 ) {
							echo '<td class="table-success">' . esc_html__( 'All are up-to-date.', 'hipaa-gauge' );
						} else {
							echo '<td class="table-danger">' . esc_html__( "{$count_plugins_not_up_to_date} not up-to-date. This may pose some security risks.", 'hipaa-gauge' );
						}
					?>
					</td>
				</tr>
				<tr>
					<td>Themes Installed</td>
					<?php
						// Security check for installed themes that are not up-to-date.
						if ( $count_themes_not_up_to_date == 0 ) {
							echo '<td class="table-success">' . esc_html__( 'All are up-to-date.', 'hipaa-gauge' );
						} else {
							echo '<td class="table-danger">' . esc_html__( "{$count_themes_not_up_to_date} not up-to-date. This may pose some security risks.", 'hipaa-gauge' );
						}
					?>
					</td>
				</tr>
			</table>
			<table>
				<tbody>
					<tr>
						<td class="align-top fw-bold"><?php esc_html_e( 'WordPress Core Score', 'hipaa-gauge' ); ?></td>
						<td>
							<details>
								<summary>
								<?php
								if ( ! empty ( $report['wp_core_score'] ) ) {
									echo esc_html( $report['wp_core_score'] );
								}
								?>
								</summary>
									<table class="table table-sm table-bordered table-striped text-nowrap" style="width: 1px">
									<tr>
										<td>Latest Version</td>
										<td>
										<?php
										if ( ! empty ( $wordpress_core['wordpress_core']['latest_version'] ) )  {
											echo esc_html( $wordpress_core['wordpress_core']['latest_version'] );
										}
										?>
										</td>
									</tr>
									<tr>
										<td>Installed Version</td>
										<td>
										<?php
										if ( ! empty ( $wordpress_core['wordpress_core']['installed_version'] ) ) {
											echo esc_html( $wordpress_core['wordpress_core']['installed_version'] );
										}
										?>
										</td>
									</tr>
									<tr>
										<td style="vertical-align: top">Vulnerabilities</td>
										<?php
										if ( empty( $wordpress_core['wordpress_core']['vulnerabilities'] ) ) {
											echo '<td>' . esc_html__( 'None known for this version', 'hipaa-gauge' ) . '</td>';
										} else {
											echo '<td class="table-danger">';
											foreach ( $wordpress_core['wordpress_core']['vulnerabilities'] as $vulnerability ) {
												echo esc_html( $vulnerability ) . '<br />';
											}
											echo '</td>';
										}
										?>
										</td>
									</tr>
									<?php if ( ! empty ($wordpress_core['wordpress_core']['vulnerabilities'] ) ): ?>
									<tr>
										<td>Suggestions</td>
										<td>
										<?php
										$vulnerability_not_latest_version = false;
										if ( in_array( 'Installed version is not latest version', $wordpress_core['wordpress_core']['vulnerabilities'] ) ) {
											$vulnerability_not_latest_version = true;
											echo esc_html__( 'We recommend you update your core to the latest version', 'hipaa-gauge' ) . '<br />';
										}
										if ( ! $vulnerability_not_latest_version || count( $wordpress_core['wordpress_core']['vulnerabilities'] ) > 1 ) {
											esc_html_e( 'Contact WordPress support and request a patched version which resolves the issue(s)', 'hipaa-gauge' );
										}
										?>
										<td/>
									</tr>
									<?php endif; ?>
								</table>
							</details>
						</td>
					</tr>
					<tr>
						<td class="align-top fw-bold"><?php esc_html_e( 'Plugins Score', 'hipaa-gauge' ); ?></td>
						<td>
							<details>
								<summary>
								<?php
								if ( ! empty( $report['plugins_score'] ) ) {
									echo esc_html( $report['plugins_score'] );
								}
								?>
								</summary>
								<?php foreach( $plugins_sorted as $plugin ):  ?>
									<table class="table table-sm table-bordered table-striped text-nowrap" style="width: 1px">
										<tr>
											<td>Name</td>
											<td>
											<?php
											if ( ! empty( $plugin['name'] ) ) {
												echo esc_html( $plugin['name'] );
											}
											?>
											</td>
										</tr>
										<tr>
											<td>Latest Version</td>
											<td>
											<?php
											if ( ! empty( $plugin['latest_version'] ) ) {
												echo esc_html( $plugin['latest_version'] );
											}
											?>
											</td>
										</tr>
										<tr>
											<td>Installed Version</td><td><?php echo esc_html( $plugin['installed_version'] ); ?></td>
										</tr>
										<tr>
											<td style="vertical-align: top">Vulnerabilities</td>
											<?php
											if ( empty( $plugin['vulnerabilities'] ) ) {
												echo '<td>' . esc_html__( 'None known for this version', 'hipaa-gauge' ) . '</td>';
											} else {
												echo '<td class="table-danger">';
												foreach ( $plugin['vulnerabilities'] as $vulnerability ) {
													echo esc_html( $vulnerability ) . '<br />';
												}
												echo '</td>';
											}
											?>
										</tr>
										<?php if ( ! empty( $plugin['vulnerabilities'] ) ): ?>
										<tr>
											<td>Suggestions</td>
											<td>
											<?php
											$vulnerability_not_latest_version = false;
											if ( in_array( 'Installed version is not latest version', $plugin['vulnerabilities'] ) ) {
												$vulnerability_not_latest_version = true;
												echo esc_html__( 'We recommend you update your plugin to the latest version', 'hipaa-gauge' ) . '<br />';
											}
											if ( ! $vulnerability_not_latest_version || count( $plugin['vulnerabilities'] ) > 1 ) {
												esc_html_e( 'Contact the author or publisher of the plugin and request a patched version which resolves the issue(s)', 'hipaa-gauge' );
											}
											?>
											<td/>
										</tr>
										<?php endif; ?>
									</table>
									<br />
								<?php endforeach; ?>
							</details>
						</td>
					</tr>
					<tr>
						<td class="align-top fw-bold"><?php esc_html_e( 'Themes Score', 'hipaa-gauge' ); ?></td>
						<td>
							<details>
								<summary>
								<?php
								if ( ! empty( $report['themes_score'] ) ) {
									echo esc_html( $report['themes_score'] );
								}
								?>
								</summary>
								<?php foreach( $themes_sorted as $theme ): ?>
									<table class="table table-sm table-bordered table-striped text-nowrap" style="width: 1px">
										<tr>
											<td>Name</td>
											<td>
											<?php
											if ( ! empty( $theme['name'] ) ) {
												echo esc_html( $theme['name'] );
											}
											?>
											</td>
										</tr>
										<tr>
											<td>Latest Version</td>
											<td>
											<?php
											if ( ! empty( $theme['latest_version'] ) ) {
												echo esc_html( $theme['latest_version'] );
											}
											?>
											</td>
										</tr>
										<tr>
											<td>Installed Version</td>
											<td>
											<?php
											if ( ! empty( $theme['installed_version'] ) ) {
												echo esc_html( $theme['installed_version'] );
											}
											?>
											</td>
										</tr>
										<tr>
											<td style="vertical-align: top">Vulnerabilities</td>
											<?php
											if ( empty( $theme['vulnerabilities'] ) ) {
												echo '<td>' . esc_html__( 'None known for this version', 'hipaa-gauge' ) . '</td>';
											} else {
												echo '<td class="table-danger">';
												foreach ( $theme['vulnerabilities'] as $vulnerability ) { 
													echo esc_html( $vulnerability ) . '<br />';
												}
												echo '</td>';
											}
											?>
										</tr>
										<?php if ( ! empty( $theme['vulnerabilities'] ) ): ?>
										<tr>
											<td>Suggestions</td>
											<td>
											<?php
											$vulnerability_not_latest_version = false;
											if ( in_array( 'Installed version is not latest version', $theme['vulnerabilities'] ) ) {
												$vulnerability_not_latest_version = true;
												echo esc_html__( 'We recommend you update your theme to the latest version', 'hipaa-gauge' ) . '<br />';
											}
											if ( ! $vulnerability_not_latest_version || count( $theme['vulnerabilities'] ) > 1 ) {
												esc_html_e( 'Contact the author or publisher of the theme and request a patched version which resolves the issue(s)', 'hipaa-gauge' );
											}
											?>
											<td/>
										</tr>
										<?php endif; ?>
									</table>
									<br />
								<?php endforeach; ?>
							</details>
						</td>
					</tr>
					<tr>
						<td class="align-top fw-bold"><?php esc_html_e( 'Web Server Score', 'hipaa-gauge' ); ?></td>
						<td>
							<details>
								<summary>
								<?php
								if ( ! empty( $report['web_server_score'] ) ) {
									echo esc_html( $report['web_server_score'] );
								}
								?>
								</summary>
								<?php if ( ! empty( $report['web_server_score'] ) &&  $report['web_server_score'] != '100%' ): ?>
								<table class="table table-sm table-bordered table-striped text-nowrap" style="width: 1px">
									<tr>
										<td>Suggestions</td>
										<td><?php esc_html_e( 'Contact your web hosting provider to fix these issues', 'hipaa-gauge' );	?></td>
									</tr>
								</table>
								<?php endif; ?>
								<table class="table table-sm table-bordered table-striped text-nowrap" style="width: 1px">
									<tr>
										<td>Port</td>
										<td>Status</td>
									</tr>
									<?php
									if ( empty( $web_server['web_server']['ports'] ) ) {
									?>
									<tr>
										<td colspan="2"><?php esc_html_e( 'None', 'hipaa-gauge' ); ?></td>
									</tr>
									<?php
									} else {
										foreach ( $web_server['web_server']['ports'] as $port => $status ) {
									?>
									<tr>
										<td><?php echo esc_html( $port ); ?><td><?php echo esc_html( $status ); ?></td>
									</tr>
									<?php
										}
									}
									?>
								</table>
								<br />
								<table class="table table-sm table-bordered table-striped text-nowrap" style="width: 1px">
									<tr>
                                        <td>Cipher</td>
										<td>Strength</td>
                                    </tr>
									<?php
									if ( empty( $web_server['web_server']['ciphers'] ) ) {
									?>
                                    <tr>
                                        <td colspan="2"><?php esc_html_e( 'None', 'hipaa-gauge' ); ?></td>
                                    </tr>
									<?php
									} else {
										foreach ( $web_server['web_server']['ciphers'] as $cipher => $strength ) {
									?>
									<tr>
										<td><?php echo esc_html( $cipher ); ?></td><td><?php echo esc_html( $strength ); ?></td>
									</tr>
									<?php
										}
									}
									?>
								</table>
								<br />
							</details>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Plugin Update Names page callback.
	 *
	 * @global array $api_responses
	 * @global object $apis
	 */
	public function plugin_update_names_page() {

		global $api_responses, $apis;

		// Gets User Info using API.
		$user_info = $apis->get_user_info();
		?>
		<div id="hipaa-gauge-dashboard" class="wrap">
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=hipaa-gauge-dashboard' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'hipaa-gauge' ); ?></a>
			<h2><?php esc_html_e( 'HIPAA Vault Update Contact Name', 'hipaa-gauge' ); ?></h2>
			<?php
			// Checks and display API status.
			$response_code = ! empty( $api_responses['update_names']['code'] ) ? $api_responses['update_names']['code'] : 0;
			if ( ($response_code == 201 || $response_code == 200 ) && ! empty( $api_responses['update_names']['response']['status'] ) ) {
				echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'API Response:', 'hipaa-gauge' ) . '</strong> ' . esc_html( $api_responses['update_names']['response']['status'] ) . '</p></div>';
			} elseif ( ! empty( $api_responses['update_names']['response']['status'] ) ) {
				$this->display_error_message( $api_responses['update_names']['response']['status'] );
			}

			// Gets prefill values for first and last name.
			$prefill_first = '';
			$prefill_last = '';
			if ( ! empty( $user_info['first'] ) ) {
				$prefill_first = sanitize_text_field( $user_info['first'] );
			}
			if ( ! empty( $user_info['last'] ) ) {
				$prefill_last = sanitize_text_field( $user_info['last'] );
			}
			?>
			<form method="post">
				<?php wp_nonce_field( 'update_names', '_wpnonce_hipaa_gauge' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="first_name"><?php esc_html_e( 'First Name', 'hipaa-gauge' ); ?></label>
							</th>
							<td>
								<?php
								$first_name = $prefill_first;
								if ( ! empty( $_POST['first_name'] ) ) {
									$first_name = sanitize_text_field( $_POST['first_name'] );
								}
								?>
								<input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $first_name ); ?>" required>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="last_name"><?php esc_html_e( 'Last Name', 'hipaa-gauge' ); ?></label>
							</th>
							<td>
								<?php
								$last_name = $prefill_last;
								if ( ! empty( $_POST['last_name'] ) ) {
									$last_name = sanitize_text_field( $_POST['last_name'] );
								}
								?>
								<input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $last_name ); ?>" required>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Update', 'hipaa-gauge' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Plugin Update Email page callback.
	 *
	 * @global array $api_responses
	 * @global object $apis
	 */
	public function plugin_update_email_page() {

		global $api_responses, $apis;

		?>
		<div id="hipaa-gauge-dashboard" class="wrap">
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=hipaa-gauge-dashboard' ) ); ?>"><?php esc_html_e( 'Back to Dashboard', 'hipaa-gauge' ); ?></a>
			<h2><?php esc_html_e( 'HIPAA Vault Update New Email Address', 'hipaa-gauge' ); ?></h2>
			<?php
			// Checks and display API response.
			$response_code = ! empty( $api_responses['update_emailid']['code'] ) ? $api_responses['update_emailid']['code'] : 0;
			if ( ( $response_code == 201 || $response_code == 200 ) && ! empty( $api_responses['update_emailid']['response']['status'] ) ) {
				echo '<div class="notice notice-success"><p><strong>' . esc_html__( 'API Response:', 'hipaa-gauge' ) . '</strong> ' . esc_html( $api_responses['update_emailid']['response']['status'] ) . '</p></div>';
			} elseif ( ! empty( $api_responses['update_emailid']['response']['status'] ) ) {
				$this->display_error_message( $api_responses['update_emailid']['response']['status'] );
			}
			?>
			<form method="post">
				<?php wp_nonce_field( 'update_emailid', '_wpnonce_hipaa_gauge' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="new_email"><?php esc_html_e( 'New Email Address', 'hipaa-gauge' ); ?></label>
							</th>
							<td>
								<?php
								$new_email = '';
								if ( ! empty( $_POST['new_email'] ) ) {
									$new_email = sanitize_email( $_POST['new_email'] );
								}
								?>
								<input id="new_email" type="email" name="new_email" value="<?php echo esc_attr( $new_email ); ?>" required pattern="[a-zA-Z0-9._%+-]+@[a-z0-9.-]+\.[a-zA-Z]{2,4}" title="<?php esc_attr_e( 'Please enter valid email address.', 'hipaa-gauge' ); ?>">
								<?php
								if ( isset( $_POST['new_email'] ) && ! is_email( sanitize_email( $_POST['new_email'] ) ) ) {
									echo '<div class="hipaa-gauge-error">' . esc_html__( 'Please enter valid email ID.', 'hipaa-gauge' ) . '</div>';
								}
								?>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Update', 'hipaa-gauge' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Plugin Dashboard page callback.
	 *
	 * @global object $apis
	 */
	public function plugin_dashboard_page() {

		global $apis;

		?>
		<div id="hipaa-gauge-dashboard" class="wrap">
			<nav class="navbar page-header mb-4">
				<div class="me-auto">
					<span class="navbar-brand"><?php esc_html_e( 'HIPAA Gauge - Dashboard', 'hipaa-gauge' ); ?></span>
				</div>
				<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'images/hipaa-vault-logo.jpg' ); ?>" alt="hipaa-vault" class="d-inline-block align-text-top">
			</nav>
			<?php
			// Get site data using API
			$site_data = $apis->get_site_data();
			if ( array_key_exists( 'status', $site_data ) ) {
				// Issue getting site data.
				$this->display_error_message( $site_data['status'] );
				return;
			}
			if ( array_key_exists( 'error', $site_data ) ) {
				// Issue getting site data.
				$this->display_error_message( $site_data['error'] );
				return;
			}

			$apis->set_scheduled_events();
			$cached_reports = $apis->get_reports();

			// No reports yet.
			if ( array_key_exists( 'status', $cached_reports ) && $cached_reports['status'] == 'No scan log for site' ) {
				echo '<div class="alert alert-info mt-4">';
				esc_html_e( 'We are preparing your site report. Please check later.', 'hipaa-gauge' );
				echo '</div>';
				return;
			}
			// Error exists with a status.
			if ( array_key_exists( 'status', $cached_reports ) ) {
				$this->display_error_message( $cached_reports['status'] );
				return;
			}
			// Error getting reports.
			if ( array_key_exists( 'error', $cached_reports ) && array_key_exists( 'message', $cached_reports ) ) {
				$this->display_error_message( $cached_reports['message'] );
				return;
			}
			// Error of no scan dates.
			if ( ! array_key_exists( 'scan_dates', $cached_reports ) ) {
				$this->display_error_message( 'Something went wrong with API request.' );
				return;
			}

			// No errors getting reports. Gets latest report date and id.
			$report_latest = array_slice( $cached_reports['scan_dates'], 0, 1, true );
			$date = key( $report_latest );
			$id = intval( $report_latest[ $date ] );
			if ( ! preg_match( '/^[0-9]+$/', $id ) ) {
				$this->display_error_message( 'Something went wrong with API request.' );
				return;
			}
			$report_link = admin_url( 'admin.php?page='. $this->report_key . '&id=' . $id );
			$report = $apis->get_report_by_id( $id );
			if ( array_key_exists( 'error', $report ) ) {
				// Error getting report.
				$this->display_error_message( $report['error'] );
				return;
			}

			// URL for upgrading/downgrading plugin.
			$updowngrade_url = wp_nonce_url( admin_url( 'admin.php?page='. $this->dashboard_key ), 'upgrade_downgrade', '_wpnonce_hipaa_gauge' );
			?>
			<main>
				<h2 class="text-center"><?php esc_html_e( 'Latest Report', 'hipaa-gauge' ); ?></h2>
				<p class="text-center"><span class="bg-light text-dark">Site was evaluated on <?php echo esc_html( $date ); ?> </span></p>
				<div class="row row-eq-height">
					<div class="col-xl-3 col-lg-6 mb-4">
						<div class="bg-white rounded-lg p-5 shadow">
							<h2 class="h6 font-weight-bold text-center mb-4"><?php esc_html_e( 'WordPress Core', 'hipaa-gauge' ); ?></h2>
							<div class="d-flex justify-content-center">
								<canvas id="gauge-core"></canvas>
							</div>
							<div class="row text-center mt-4">
								<div class="col-6 border-right">
									<div class="h4 font-weight-bold mb-0">
									<?php
									if ( ! empty( $report['wp_core']['latest_version'] ) ) {
										echo esc_html( $report['wp_core']['latest_version'] );
									}
									?>
									</div>
									<span class="small text-gray"><?php esc_html_e( 'Latest Release', 'hipaa-gauge' ); ?></span>
								</div>
								<div class="col-6">
									<div class="h4 font-weight-bold mb-0">
									<?php
									if ( ! empty( $report['wp_core']['installed_version'] ) ) {
										echo esc_html( $report['wp_core']['installed_version'] );
									}
									?>
									</div>
									<span class="small text-gray"><?php esc_html_e( 'Installed', 'hipaa-gauge' ); ?></span>
								</div>
								<div class="col-12">
									<hr />
									<p>
										<?php if ( ! empty( $report['wp_core_score'] ) && $report['wp_core_score'] == '100%' ):
											esc_html_e( 'WordPress is up-to-date & no vulnerabilities detected', 'hipaa-gauge' );
										else:
											if ( $report['wp_core']['installed_version'] !== $report['wp_core']['latest_version'] ) {
												esc_html_e( 'WordPress is not up-to-date', 'hipaa-gauge' );
											} else {
												esc_html_e( 'Vulnerabilities were detected', 'hipaa-gauge' );
											}
										endif;
										?>
									</p>
								</div>
							</div>
							<!-- END -->
						</div>
					</div>
					<div class="col-xl-3 col-lg-6 mb-4">
						<div class="bg-white rounded-lg p-5 shadow">
							<h2 class="h6 font-weight-bold text-center mb-4"><?php esc_html_e( 'WordPress Plugins', 'hipaa-gauge' ); ?></h2>
							<div class="d-flex justify-content-center">
								<canvas id="gauge-plugins" data-width="200" data-height="200"></canvas>
							</div>
							<div class="row text-center mt-4">
								<div class="col-6 border-right">
									<div class="h4 font-weight-bold mb-0">
									<?php
									$plugins_total = 0;
									if ( isset( $report['plugins']['total'] ) ) {
										$plugins_total = $report['plugins']['total'];
									}
									echo esc_html( $report['plugins']['total'] );
									?>
									</div>
									<span class="small text-gray"><?php esc_html_e( 'Plugins Installed', 'hipaa-gauge' ); ?></span>
								</div>
								<div class="col-6">
									<div class="h4 font-weight-bold mb-0">
									<?php
									$plugins_vulnerable = 0;
									if ( isset( $report['plugins']['vulnerable'] ) ) {
										$plugins_vulnerable = $report['plugins']['vulnerable'];
									}
									echo esc_html( $report['plugins']['vulnerable'] );
									?>
									</div>
                                    <span class="small text-gray"><?php esc_html_e( 'Plugins Vulnerable', 'hipaa-gauge' ); ?></span>
								</div>
								<div class="col-12">
									<hr />
									<p>
										<?php if ( ! empty( $report['plugins_score'] ) && $report['plugins_score'] == '100%' ):
											esc_html_e( 'No vulnerabilities were detected', 'hipaa-gauge' );
										else:
											esc_html_e( 'Vulnerabilities were detected', 'hipaa-gauge' );
										endif;
										?>
									</p>
								</div>
							</div>
							<!-- END -->
						</div>
					</div>
					<div class="col-xl-3 col-lg-6 mb-4">
						<div class="bg-white rounded-lg p-5 shadow">
							<h2 class="h6 font-weight-bold text-center mb-4"><?php esc_html_e( 'WordPress Themes', 'hipaa-gauge' ); ?></h2>
							<div class="d-flex justify-content-center">
								<canvas id="gauge-themes"></canvas>
							</div>
							<!-- END -->
							<div class="row text-center mt-4">
								<div class="col-6 border-right">
									<div class="h4 font-weight-bold mb-0">
									<?php
									$themes_total = 0;
									if ( isset( $report['themes']['total'] ) ) {
										$themes_total = $report['themes']['total'];
									}
									echo esc_html( $report['themes']['total'] );
									?>
									</div>
									<span class="small text-gray"><?php esc_html_e( 'Themes Installed', 'hipaa-gauge' ); ?></span>
								</div>
								<div class="col-6">
									<div class="h4 font-weight-bold mb-0">
									<?php
									$themes_vulnerable = 0;
									if ( isset( $report['themes']['vulnerable'] ) ) {
										$themes_vulnerable = $report['themes']['vulnerable'];
									}
									echo esc_html( $report['themes']['vulnerable'] );
									?>
									</div>
									<span class="small text-gray"><?php esc_html_e( 'Themes Vulnerable', 'hipaa-gauge' ); ?></span>
								</div>
								<div class="col-12">
									<hr />
									<p>
										<?php if ( ! empty( $report['themes_score'] ) && $report['themes_score'] == '100%' ):
											esc_html_e( 'No vulnerabilities were detected', 'hipaa-gauge' );
										else:
											esc_html_e( 'Vulnerabilities were detected', 'hipaa-gauge' );
										endif;
										?>
									</p>
								</div>
							</div>
							<!-- END -->
						</div>
					</div>
					<div class="col-xl-3 col-lg-6 mb-4">
						<div class="bg-white rounded-lg p-5 shadow">
							<h2 class="h6 font-weight-bold text-center mb-4"><?php esc_html_e( 'Web Server', 'hipaa-gauge' ); ?></h2>
							<div class="d-flex justify-content-center">
								<canvas id="gauge-server"></canvas>
							</div>
							<div class="row text-center mt-4">
								<div class="col-12 border-right">
									<div class="h4 font-weight-bold mb-0">
									<?php
										if ( ! empty( $report['web_server_score'] ) && $report['web_server_score'] == '100%' ) {
											echo '0';
										}
									?>
									</div>
									<span class="small text-gray"><?php esc_html_e( 'Vulnerabilities Detected', 'hipaa-gauge' ); ?></span>
								</div>
								<div class="col-12">
									<hr />
									<p>
										<?php if ( ! empty( $report['web_server_score'] ) && $report['web_server_score'] == '100%' ):
											esc_html_e( 'No vulnerabilities were detected', 'hipaa-gauge' );
										else:
											esc_html_e( 'Vulnerabilities were detected', 'hipaa-gauge' );
										endif; ?>
									</p>
								</div>
							</div>
							<!-- END -->
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12 text-center mt-2" >
						<ul class="hipaa-gauge-buttons">
							<?php if ( array_key_exists( 'premium', $site_data ) && true === $site_data['premium'] ): ?>
                            <li>
								<a class="btn btn-primary" href="<?php echo esc_url( $report_link ); ?>"><?php esc_html_e( 'Get detailed report', 'hipaa-gauge' ); ?></a>
								<p class="mt-5">
									<a class="btn btn-warning" href="<?php echo esc_url( $updowngrade_url ); ?>" onclick="return confirm('<?php esc_html_e( 'Are you sure you want to downgrade?', 'hipaa-gauge' ); ?>');"><?php esc_html_e( 'Downgrade from Premium Version', 'hipaa-gauge' ); ?></a>
								</p>
							</li>
							<?php else:?>
							<li>
								<h4><?php esc_html_e( 'Need More?', 'hipaa-gauge' ); ?></h4>
								<p class="h6"><?php esc_html_e( 'The premium feature provides a detailed report of potential vulnerabilities. In addition, by enabling the premium feature, you also consent to a back link to HIPAA Gauge. The back link will appear on interior pages (not the home page).', 'hipaa-gauge' ); ?></p>
								<a class="btn btn-warning" href="<?php echo esc_url( $updowngrade_url ); ?>" onclick="return confirm('<?php esc_html_e( 'Are you sure you want to upgrade?', 'hipaa-gauge' ); ?>');"><?php esc_html_e( 'Upgrade to Premium Version', 'hipaa-gauge' ); ?></a>
							</li>
							<?php endif; ?>
						</ul>
					</div>
				</div>
			</main>
		</div>

		<script>
		<?php
		$wp_core_score = 0;
		$plugins_score = 0;
		$themes_score = 0;
		$web_server_score = 0;
		if ( ! empty ( $report['wp_core_score'] ) && preg_match( "/[0-9]+%/", $report['wp_core_score'] ) ) {
			$wp_core_score = $report['wp_core_score'];
		}
		if ( ! empty ( $report['plugins_score'] ) && preg_match( "/[0-9]+%/", $report['plugins_score'] ) ) {
			$plugins_score = $report['plugins_score'];
		}
		if ( ! empty ( $report['themes_score'] ) && preg_match( "/[0-9]+%/", $report['themes_score'] ) ) {
			$themes_score = $report['themes_score'];
		}
		if ( ! empty ( $report['web_server_score'] ) && preg_match( "/[0-9]+%/", $report['web_server_score'] ) ) {
			$web_server_score = $report['web_server_score'];
		}
		?>
		let gauge_core = get_gauge('gauge-core', "<?php echo esc_html( $wp_core_score ); ?>");
		let gauge_plugins = get_gauge('gauge-plugins', "<?php echo esc_html( $plugins_score ); ?>");
		let gauge_themes = get_gauge('gauge-themes', "<?php echo esc_html( $themes_score ); ?>");
		let gauge_server = get_gauge('gauge-server', "<?php echo esc_html( $web_server_score ); ?>");

		function get_gauge(render, score) {
			gauge = new RadialGauge({
				renderTo: render,
				width: 225,
				height: 225,
				units: '%',
				minValue: 0,
				maxValue: 100,
				majorTicks: ['0', '10', '20', '30', '40', '50', '60', '70', '80', '90', '100'],
				minorTicks: 5,
				highlights: [
					{
						"from": 0,
						"to": 50,
						"color": "rgba(255,0, 0, .8)"
					},
					{
						"from": 50,
						"to": 90,
						"color": "rgba(255, 255, 0, .8)"
					},
					{
						"from": 90,
						"to": 100,
						"color": "rgba(0, 128, 0, .9)"
					}
				],
				valueInt: 3,
				valueDec: 0,
				colorMajorTicks: "#ddd",
				colorMinorTicks: "#ddd",
				colorTitle: "#eee",
				colorUnits: "#ccc",
				colorNumbers: "#eee",
				colorPlate: "#222",
				animation: false,
				value: 1,
			});
			gauge.value = score;
			return gauge;
		}
		</script>
		<?php
	}

	/**
	 * Displays error message.
	 *
	 * @param string $message Optional message to display.
	 */
	public function display_error_message( $message = '' ) {
		$default_error_message = sanitize_text_field( 'Something went wrong with API request.' );
		$message = sanitize_text_field( $message );
		if ( empty( $message ) ) {
			$message = $default_error_message;
		}
		echo '<div class="notice notice-error"><p>' . esc_html__( $message, 'hipaa-gauge' ) . '</p></div>';
	}
}
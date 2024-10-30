<?php
// If this file is called directly, busted!
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @package    HIPAA_Gauge
 * @subpackage HIPAA_Gauge/includes
 */
class HIPAA_Gauge {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 */
	public function __construct() {

		$this->plugin_name = 'hipaa-gauge';
		$this->version = HIPAA_GAUGE_VERSION;

		$this->load_dependencies();
		$this->set_locale();

		// Checks if is admin.
		if ( is_admin() ) {
			$this->define_admin_hooks();
		} else {
			$this->define_public_hooks(); //Load public hooks
		}
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - HIPAA_Gauge_Loader. Orchestrates the hooks of the plugin.
	 * - HIPAA_Gauge_i18n. Defines internationalization functionality.
	 * - HIPAA_Gauge_Admin. Defines all hooks for the admin area.
	 * - HIPAA_Gauge_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @access private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin APIs.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-handle-apis.php';

		// Checks if is admin.
		if ( is_admin() ) {

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-admin.php';
		}

		$this->loader = new HIPAA_Gauge_Loader();
	}

	/**
	 * Defines the locale for this plugin for internationalization.
	 *
	 * Uses the HIPAA_Gauge__i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @access private
	 */
	private function set_locale() {

		$i18n = new HIPAA_Gauge_i18n();

		$this->loader->add_action( 'plugins_loaded', $i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Registers all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @access private
	 * @global object $apis
	 */
	private function define_admin_hooks() {

		global $apis;

		$apis = new HIPAA_Gauge_APIs( $this->get_plugin_name(), $this->get_version() );
		$admin = new HIPAA_Gauge_Admin( $this->get_plugin_name(), $this->get_version() );

		// Adds preload hooks.
		$this->loader->add_action( 'admin_menu', $admin, 'add_admin_menus' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
	}

	/**
	 * Registers all of the hooks related to the public area functionality
	 * of the plugin.
	 *
	 * @access private
	 * @global object $apis
	 */
	private function define_public_hooks() {

		global $apis;

		$apis = new HIPAA_Gauge_APIs( $this->get_plugin_name(), $this->get_version() );
	}

	/**
	 * Runs the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return HIPAA_Gauge_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieves the version number of the plugin.
	 *
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Stores plugin version in options.
	 *
	 * @access private
	 *
	 * @param string $version Optional. Version number. Default is blank.
	 */
	private function update_version( $version = '' ) {
		if ( $version == '' ) {
			$version = HIPAA_GAUGE_VERSION;
		}

		update_option( HIPAA_GAUGE_OPTION_NAME, $version );
	}
}
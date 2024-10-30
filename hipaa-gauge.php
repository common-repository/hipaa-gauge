<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.hipaavault.com
 * @package           HIPAA_Gauge
 *
 * @wordpress-plugin
 * Plugin Name:       HIPAA Gauge
 * Plugin URI:        https://www.hipaavault.com/hipaa-gauge-wordpress-website-checkup-tool/
 * Description:       Checks for HIPAA vulnerabilities
 * Version:           1.0.3
 * Author:            HIPAA Vault
 * Author URI:        https://www.hipaavault.com/
 * Text Domain:       hipaa-gauge
 * Domain Path:       /languages
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define constants.
 */

define ( 'HIPAA_GAUGE_VERSION', '1.0.3' );
define ( 'HIPAA_GAUGE_OPTION_NAME', 'HIPAA_Gauge_Settings' );
define ( 'HIPAA_GAUGE_PLUGIN_FILE', basename ( __FILE__ ) );
define ( 'HIPAA_GAUGE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define ( 'HIPAA_GAUGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define ( 'HIPAA_GAUGE_API_URL', 'https://api.hipaavault.com/' );

/**
 * Fires after plugin activation.
 *
 * @see includes/class-activator.php
 */
function hipaa_gauge_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
	HIPAA_Gauge_Activator::activate();
}

/**
 * Fires after plugin activation.
 * Redirects to dashboard page after plugin activation.
 * 
 * @global object $apis
 * @global array $user_responses
 */
function hipaa_gauge_plugin_redirect() {
	if ( get_option( 'hipaa_gauge_plugin_do_activation_redirect ', false ) ) {
		delete_option( 'hipaa_gauge_plugin_do_activation_redirect' );
		if ( ! isset( $_GET['activate-multi'] ) ) {
			global $apis, $user_responses;
			$user_responses = $apis->plugin_get_set_site();
			wp_redirect( admin_url( 'admin.php?page=hipaa-gauge-dashboard' ) );
		}
	}
}

add_action( 'admin_init', 'hipaa_gauge_plugin_redirect' );

/**
 * Fires after plugin deactivation.
 * 
 * @see includes/class-deactivator.php
 * 
 */
function hipaa_gauge_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-deactivator.php';
	HIPAA_Gauge_Deactivator::deactivate();
}

/**
 * Fires after plugin uninstallation.
 * 
 * @see includes/class-uninstaller.php
 */
function hipaa_gauge_uninstall() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-uninstaller.php';
    HIPAA_Gauge_Uninstaller::uninstall();
}

register_activation_hook( __FILE__, 'hipaa_gauge_activate' );

register_deactivation_hook( __FILE__, 'hipaa_gauge_deactivate' );

register_uninstall_hook( __FILE__, 'hipaa_gauge_uninstall' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-hipaa-gauge.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
function hipaa_gauge_run() {

	$plugin = new HIPAA_Gauge();
	$plugin->run();
}

hipaa_gauge_run();
<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    HIPAA_Gauge
 * @subpackage HIPAA_Gauge/includes
 */
class HIPAA_Gauge_Activator {

	/**
	 * Inserts pre-built template on plugin activation.
	 */
	public static function activate() {
		add_option( 'hipaa_gauge_plugin_do_activation_redirect', true );
	}
}
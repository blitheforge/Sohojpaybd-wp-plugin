<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://https://github.com/blitheforge
 * @since             1.0.0
 * @package           Sohojpaybd
 *
 * @wordpress-plugin
 * Plugin Name:       SohojpayBD
 * Plugin URI:        https://sohojpaybd.com
 * Description:       This plugin allows your customers to pay with Bkash, Nagad, Rocket, and all BD gateways
 * Version:           1.0.0
 * Author:            Blithe Forge
 * Author URI:        https://https://github.com/blitheforge/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sohojpaybd
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('SOHOJPAYBD_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sohojpaybd-activator.php
 */
function activate_sohojpaybd()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-sohojpaybd-activator.php';
	Sohojpaybd_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sohojpaybd-deactivator.php
 */
function deactivate_sohojpaybd()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-sohojpaybd-deactivator.php';
	Sohojpaybd_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_sohojpaybd');
register_deactivation_hook(__FILE__, 'deactivate_sohojpaybd');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-sohojpaybd.php';

/**
 * Check if WooCommerce is active
 */
function sohojpaybd_is_woocommerce_active()
{
	include_once(ABSPATH . 'wp-admin/includes/plugin.php');
	return is_plugin_active('woocommerce/woocommerce.php');
}

/**
 * Add settings link to the plugin actions
 */
function sohojpaybd_settings_link($links_array, $plugin_file_name)
{
	if (strpos($plugin_file_name, plugin_basename(__FILE__)) !== false) {
		if (!sohojpaybd_is_woocommerce_active()) {
			$links_array[] = '<a href="' . esc_url(admin_url('plugin-install.php?tab=plugin-information&plugin=woocommerce')) . '">' . __('Please activate WooCommerce first', 'sohojpaybd') . '</a>';
		} else {
			$links_array[] = '<a href="' . esc_url(admin_url('admin.php?page=sohojpaybd')) . '">' . __('Settings', 'sohojpaybd') . '</a>';
			$links_array[] = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=sohojpaybd')) . '">' . __('WooCommerce Settings', 'sohojpaybd') . '</a>';
		}
	}
	return $links_array;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sohojpaybd_settings_link', 10, 2);

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_sohojpaybd()
{
	$plugin = new Sohojpaybd();
	$plugin->run();
}
add_action('plugins_loaded', 'run_sohojpaybd');

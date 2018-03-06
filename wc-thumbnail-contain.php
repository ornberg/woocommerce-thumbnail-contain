<?php
/*
* @wordpress-plugin
* Plugin Name:       WooCommerce Thumbnail Contain
* Description:       Adds 'contain' as WooCommerce product thumbnails cropping option
* Version:           1.0
* Author:            Alexander Örnberg
* Author URI:        http://github.com/ornberg/
* Requires at least: 4.7.9
* Tested up to: 4.9.4
* WC requires at least: 3.3.0
* WC tested up to: 3.3.3
* Text Domain: wc_thumbnail_contain
* Domain Path: /i18n
*/

// If called directly, abort
if (!defined('ABSPATH')) exit;
if (!class_exists('WC_Thumbnail_Contain')):

/**
 * Main class plugin, adds hooks for the customizer and thumbnail creation classes,
 * checks dependencies and loads the plugin text domain
 *
 */
class WC_Thumbnail_Contain {

	/**
	 * The plugins directory path
	 *
	 * @var string
	 */
	static $plugin_dir = null;

	/**
	 * Add hooks and filters
	 *
	 */
	public static function init() {
		// Add install and uninstall procedures
		register_activation_hook(__FILE__, __CLASS__.'::install');
		register_deactivation_hook(__FILE__, __CLASS__.'::uninstall');

		// Check dependencies
		add_action('plugins_loaded', __CLASS__.'::load_plugin', 10);
		add_action('plugins_loaded', __CLASS__.'::load_textdomain', 20);

		require_once(static::plugin_dir('includes/class-wc-thumbnail-contain-customizer.php'));
		add_action('init', 'WC_Thumbnail_Contain_Customizer::init');

		require_once(static::plugin_dir('includes/class-wc-thumbnail-contain-thumbnail-create.php'));
		add_action('init', 'WC_Thumbnail_Contain_Thumbnail_Create::init');
	}

	/**
	 * Responsive thumbnails should be set to 'true' by default
	 *
	 */
	public static function install() {
		update_option('woocommerce_thumbnail_responsive', true);
	}

	/**
	 * Cleanup options on plugin deactivation, and regenerate thumbnails if necessary
	 *
	 */
	public static function uninstall() {
		// Set selected thumbnail option to default value '1:1', and trigger thumbnail regeneration
		if ('contain' == get_option('woocommerce_thumbnail_cropping')) {
			update_option('woocommerce_thumbnail_cropping', '1:1');
			if (method_exists('WC_Regenerate_Images', 'maybe_regenerate_images')) {
				update_option('woocommerce_maybe_regenerate_images_hash', '');
				WC_Regenerate_Images::maybe_regenerate_images();
			}
		}

		// Delete plugin specific options
		delete_option('woocommerce_thumbnail_contain_upscale');
		delete_option('woocommerce_thumbnail_responsive');
		delete_option('woocommerce_maybe_regenerate_images_contain_hash');
	}

	/**
	 * Get plugin directory path, with $path appended
	 *
	 * @param string $path (Default: '').
	 * @return string
	 */
	public static function plugin_dir($path = '') {
		if (is_null(static::$plugin_dir))
			static::$plugin_dir = plugin_dir_path(__FILE__);
		return static::$plugin_dir.$path;
	}

	/**
	 * Check plugin dependencies
	 *
	 */
	public static function load_plugin() {
		$deactivate = false;

		// Check if WooCommerce is activated
		if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			add_action('admin_notices', function() {
				printf(
					'<div class="notice notice-warning"><p>%s</p></div>',
					__('<b>WooCommerce Thumbnail Contain</b> plugin requires <b>WooCommerce</b> to work!', 'wc_thumbnail_contain')
				);
				$deactivate = true;
			});
		}

		// Check PHP version
		if (phpversion() < 5.6) {
			add_action('admin_notices', function() {
				printf(
					'<div class="notice notice-warning"><p>%s</p></div>',
					__('<b>WooCommerce Thumbnail Contain</b> requires at least PHP version 5.6 to work!', 'wc_thumbnail_contain')
				);
				$deactivate = true;
			});
		}

		// Check that image editing is support by the system
		if (!wp_image_editor_supports(array('methods' => array('resize')))) {
			add_action('admin_notices', function() {
				printf(
					'<div class="notice notice-warning"><p>%s</p></div>',
					__('<b>WooCommerce Thumbnail Contain</b> requires the PHP GD library to work!', 'wc_thumbnail_contain')
				);
				$deactivate = true;
			});
		}

		// Deactivate the plugin
		if ($deactivate)
			deactivate_plugins(plugin_basename(__FILE__));
	}

	/**
	 * Load text domain
	 *
	 */
	public static function load_textdomain() {
		$plugin_path = plugin_basename(dirname(__FILE__) . '/i18n');
		load_plugin_textdomain('wc_thumbnail_contain', '', $plugin_path) ? 'true' : 'false';
	}
}
endif;

WC_Thumbnail_Contain::init();

?>

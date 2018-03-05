<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;
if (!class_exists('WC_Thumbnail_Contain_Customizer')):

/**
 * Handles addng of settings and controls to the WP Customizer
 *
 */
class WC_Thumbnail_Contain_Customizer {

	/**
	 * Add hooks and filters
	 *
	 */
	public static function init() {
		add_action('customize_register', __CLASS__.'::extend_thumbnail_settings', 99, 1);
		add_action('customize_controls_print_scripts', __CLASS__.'::add_scripts', 99);
		add_action('customize_controls_print_styles', __CLASS__.'::add_styles', 99);
	}

	/**
	 * Add JS to show/hide extra settings for the option 'contain' when said option
	 * is selected
	 *
	 */
	public static function add_scripts() { ?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$(document.body).on('change', '.woocommerce-cropping-control input[type="radio"]', function() {
					var wrapper = $(this).closest('.woocommerce-cropping-control');

					if ('contain' === wrapper.find('input:checked').val()) {
						wrapper.find('.woocommerce-cropping-control-contain-upscale').slideDown(200);
					} else {
						wrapper.find('.woocommerce-cropping-control-contain-upscale').hide();
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Add styling to upscale setting
	 *
	 */
	public static function add_styles() { ?>
		<style type="text/css">
			.woocommerce-cropping-control span.woocommerce-cropping-control-contain-upscale {
				display: inline-block;
				margin-top: .5em;
			}
		</style>
		<?php
	}

	/**
	 * Extend WooCommerce thumbnail settings
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public static function extend_thumbnail_settings($wp_customize) {
		// Add setting for upscaling smaller thumbnails
		$wp_customize->add_setting(
			'woocommerce_thumbnail_contain_upscale',
			array(
				'default'           => false,
				'type'              => 'option',
				'capability'        => 'manage_woocommerce',
				'sanitize_callback' => 'wc_clean',
			)
		);

		// Add setting for turning off responsive thumbnails (aka disabling srcset)
		$wp_customize->add_setting(
			'woocommerce_thumbnail_responsive',
			array(
				'default'           => true,
				'type'              => 'option',
				'capability'        => 'manage_woocommerce',
				'sanitize_callback' => 'wc_clean',
			)
		);

		// Get the original 'woocommerce_thumbnail_cropping' control to replace it
		$control = $wp_customize->get_control('woocommerce_thumbnail_cropping');

		// Rebuild settings array to be able to pass it as a valid parameter
		$settings = array();
		foreach ($control->settings as $key => $setting) {
			$settings[$key] = $setting->id;
		}
		$settings['upscale'] = 'woocommerce_thumbnail_contain_upscale';
		$settings['responsive'] = 'woocommerce_thumbnail_responsive';

		// Copy essential parameters from original 'woocommerce_thumbnail_cropping' controller
		$label = $control->label;
		$section = $control->section;
		$choices = $control->choices;
		$choices['contain'] = array(
			'label' => __('Contain', 'wc_thumbnail_contain'),
			'description' => __('Proportionally contain image within the dimensions of the thumbnail.', 'wc_thumbnail_contain')
		);

		// Include custom controller class
		include_once WC_Thumbnail_Contain::plugin_dir('includes/class-wc-customizer-control-cropping-extended.php');

		// Replace 'woocommerce_thumbnail_cropping' with new custom controller
		$wp_customize->add_control(
			new WC_Customizer_Control_Cropping_Extended(
				$wp_customize,
				'woocommerce_thumbnail_cropping',
				array(
					'section' => $section,
					'settings' => $settings,
					'label' => $label,
					'choices' => $choices
		)));
	}
}
endif;
?>

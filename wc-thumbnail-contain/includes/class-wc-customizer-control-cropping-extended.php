<?php
/**
 * Custom control for radio buttons with nested options.
 *
 * Used for our image cropping settings.
 *
 * @version 3.3.0
 * @package WooCommerce
 * @author  WooCommerce
 */

 // If called directly, abort
 if (!defined('ABSPATH')) exit;
 if (!class_exists('WC_Customizer_Control_Cropping_Extended')):

/**
 * Controller class to add, extends WC_Customizer_Control_Cropping
 * @see https://docs.woocommerce.com/wc-apidocs/source-class-WC_Customizer_Control_Cropping.html
 *
 */
class WC_Customizer_Control_Cropping_Extended extends WC_Customizer_Control_Cropping {

	/**
	 * Declare the control type.
	 *
	 * @var string
	 */
	public $type = 'woocommerce-cropping-control-extended';

	/**
	 * Render control.
	 *
	 */
	public function render_content() {
		if (empty($this->choices))
			return;

		$value = $this->value('cropping');
		$custom_width = $this->value('custom_width');
		$custom_height = $this->value('custom_height');

		// Additional values
		$upscale = $this->value('upscale');
		$responsive = $this->value('responsive');
		?>

		<span class="customize-control-title">
			<?php echo esc_html($this->label); ?>
		</span>

		<?php if (!empty($this->description)) : ?>
			<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
		<?php endif; ?>

		<ul id="input_<?php echo esc_attr($this->id); ?>" class="woocommerce-cropping-control">
			<?php foreach ($this->choices as $key => $radio) : ?>
				<li>
					<input type="radio" name="<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($this->id.$key); ?>" <?php $this->link('cropping'); ?> <?php checked($value, $key); ?> />
					<label for="<?php echo esc_attr($this->id.$key); ?>"><?php echo esc_html($radio['label']); ?><br/><span class="description"><?php echo esc_html($radio['description']); ?></span></label>

					<?php if ('custom' === $key) : ?>
						<span class="woocommerce-cropping-control-aspect-ratio">
							<input type="text" pattern="\d*" size="3" value="<?php echo esc_attr($custom_width); ?>" <?php $this->link('custom_width'); ?> /> : <input type="text" pattern="\d*" size="3" value="<?php echo esc_attr($custom_height); ?>" <?php $this->link('custom_height'); ?> />
						</span>
					<?php endif; ?>

					<?php // Custom contain option for thumbnail cropping preferences
						if ('contain' === $key) : ?>
						<span class="woocommerce-cropping-control-contain-upscale">
							<input type="checkbox" id="<?php echo esc_attr($this->id.$key.'_upscale'); ?>" <?php $this->link('upscale');?> <?php checked($upscale); ?>/>
							<label for="<?php echo esc_attr($this->id.$key.'_upscale'); ?>"><?php _e('Upscale smaller images', 'wc_thumbnail_contain'); ?></span>
						</span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php // Add controls for enabling/disabling responsive thumbnail images ?>
		<span class="customize-control-title">
			<?php _e('Responsive thumbnails', 'wc_thumbnail_contain'); ?>
		</span>
		<span class="woocommerce-thumbnail-responsiv-control">
			<input type="checkbox" id="<?php echo esc_attr( $this->id.$key.'_responsive'); ?>" <?php $this->link('responsive');?> <?php checked($responsive); ?>/>
			<label for="<?php echo esc_attr( $this->id.$key.'_responsive'); ?>"><?php _e('Use responsive images for product thumbnails', 'wc_thumbnail_contain'); ?></label>
		</span>
		<?php
	}
}

endif;

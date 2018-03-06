<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;
if (!class_exists('WC_Thumbnail_Contain_Thumbnail_Create')):

/**
 * Handles creation and triggering of regeneration of WooCommerce product thumbnails
 *
 */
class WC_Thumbnail_Contain_Thumbnail_Create {
	public static function init() {
		// Create contained thumbnails
		add_filter('wp_generate_attachment_metadata', __CLASS__.'::create_thumbnail', 99, 1);

		// Change thumbnails on the fly if using the WP Customizer
		if (is_customize_preview()) {
			add_filter('wp_get_attachment_image_src', __CLASS__.'::customizer_preview', 99, 4);
		}

		if (apply_filters('woocommerce_background_image_regeneration', true) && !is_multisite()) {
			/* Only works if it's earlier than WC_Regenerate_Images::maybe_regenerate_images
			 hence it's hooked in at 1 */
			add_action('customize_save_after', __CLASS__.'::maybe_regenerate', 1);
			add_action('after_switch_theme', __CLASS__.'::maybe_regenerate', 1);
		}

		// Disable responsive WooCommerce product thumbnails if settings say so
		add_action('wp_head', function() {
			if (!get_option('woocommerce_thumbnail_responsive')) {
				add_filter('wp_calculate_image_srcset_meta', __CLASS__.'::disable_thumbnail_srcset', 99, 4);
			}
		});
	}

	/**
	 * Disables responsive WooCommerce product thumbnails if enabled in Customizer settings
	 *
	 * @param array $image_meta
	 * @param int[] $size_array
	 * @param string $image_src
	 * @param int $attachment_id
	 * @return array
	 */
	public static function disable_thumbnail_srcset($image_meta, $size_array, $image_src, $attachment_id) {

		// If image has a post parent
		if ($type = get_post_type(wp_get_post_parent_id($attachment_id))) {

			// If image belongs to a WooCommerce product
			if ('product' == $type || 'product_variation' == $type) {

				// Statically store the WooCommerce product thumbnail dimensions
				static $size_ref = array();
				if (empty($size_ref))
					$size_ref = wc_get_image_size('thumbnail');

				// If the image seems to be a product thumbnail
				if (isset($size_ref['width'], $size_ref['height'])) {
					if ($size_ref['width'] == $size_array[0] && $size_ref['height'] == $size_array[1]) {
						return false;
					}
				}
			}
		}

		return $image_meta;
	}

	/**
	 * Check if width, height or upscale settings changed after a WP Customizer save,
	 * or theme change and requires a regeneration of all thumbnails
	 *
	 */
	public static function maybe_regenerate() {
		if ('contain' == get_option('woocommerce_thumbnail_cropping')) {
			if (!class_exists('WC_Regenerate_Images'))
				return;

			$sizes = wc_get_image_size('thumbnail');
			if (!isset($sizes['width'], $sizes['height']))
				return;

			$fingerprint = md5(wp_json_encode(array(
				$sizes['width'],
				$sizes['height'],
				get_option('woocommerce_thumbnail_contain_upscale', false)
			)));

			// Delete hash used by WooCommerce to trigger regeneration - forcing regeneration of thumbnails
			if (update_option('woocommerce_maybe_regenerate_images_contain_hash', $fingerprint)) {
				update_option('woocommerce_maybe_regenerate_images_hash', '');
			}
		}
	}

	/**
	 * Resize images on the fly for the WP Customizer Preview
	 *
	 * @param array        $image Properties of the image.
	 * @param int          $attachment_id Attachment ID.
	 * @param string|array $size Image size.
	 * @param bool         $icon If icon or not.
	 * @return array
	 */
	public static function customizer_preview($image, $attachment_id, $size, $icon) {
		if ($icon || 'woocommerce_thumbnail' != $size)
			return $image;

		if ('contain' !== get_option('woocommerce_thumbnail_cropping'))
			return $image;

		if (!apply_filters('woocommerce_resize_images', true))
			return $image;

		$meta_data = wp_get_attachment_metadata($attachment_id);
		if (empty($meta_data))
			return $image;

		// Create new thumbnail for the current preview settings
		$meta_data = static::create_thumbnail($meta_data);
		if (!isset($meta_data['sizes']['woocommerce_thumbnail']))
			return $image;

		// Return thumbnail data based on altered $meta_data from static::create_thumbnail
		list($folder, , ) = static::extract_path_info($meta_data['file']);
		$upload_url = wp_upload_dir()['baseurl'].'/';
		return array(
			$upload_url . $folder . $meta_data['sizes']['woocommerce_thumbnail']['file'],
			$meta_data['sizes']['woocommerce_thumbnail']['width'],
			$meta_data['sizes']['woocommerce_thumbnail']['height'],
			1
		);
	}

	/**
	 * Create contained thumbnail from source image if it doesn't already exist,
	 * and update data about the thumbnail in $image_data
	 *
	 * @param array $image_data
	 * @return array
	 */
	public static function create_thumbnail($image_data) {
		// Contain not chosen as option for thumbnail cropping
		if ('contain' != get_option('woocommerce_thumbnail_cropping')) {
			return $image_data;
		}

		// Get upload path and folders for source image
		$upload_dir = wp_upload_dir()['basedir'].'/';
		$src_path = $upload_dir.$image_data['file'];
		list($folder, $name, $mime) = static::extract_path_info($image_data['file']);

		$thumbnail_specs = wc_get_image_size('thumbnail');
		$target_width = $thumbnail_specs['width'];
		$target_height = $thumbnail_specs['height'];

		$src_width = $image_data['width'];
		$src_height = $image_data['height'];

		// If source image has same dimensions as thumbnail, no need to scale or fit
		if ($target_width == $src_width && $target_height == $src_height) {
			return $image_data;
		}

		// Whether option to upscale smaller images has been set to true or not
		$upscale = get_option('woocommerce_thumbnail_contain_upscale', false);

		// Compose thumbnail file name
		$thumbnail_name = "{$name}-{$target_width}x{$target_height}-contained";
		if ($src_width < $target_width && $src_height < $target_height && $upscale) {
			$thumbnail_name .= '-upscaled';
		}
		$thumbnail_name .= '.png';
		$thumbnail_path = $upload_dir.$folder.$thumbnail_name;

		// If thumbnail file doesn't already exist, create it
		if (!file_exists($thumbnail_path)) {
			if ($mime == 'jpg' || $mime == 'jpeg')
				$img = imagecreatefromjpeg($src_path);

			else if ($mime == 'png')
				$img = imagecreatefrompng($src_path);

			else if ($mime == 'gif')
				$img = imagecreatefromgif($src_path);

			else // Invalid MIME type
				return $image_data;

			$thumbnail = imagecreatetruecolor($target_width, $target_height);
			imagesavealpha($thumbnail, true);
			imagefill($thumbnail, 0, 0, imagecolorallocatealpha($thumbnail, 0, 0, 0, 127));

			// If source image is smaller than thumbnail
			if ($src_width < $target_width && $src_height < $target_height && !$upscale) {
				$new_width = $src_width;
				$new_height = $src_height;
				$new_x = 0;
				$new_x = absint(round(($target_width - $new_width) / 2));
				$new_y = absint(round(($target_height - $new_height) / 2));
			}
			else {
				// If source image is exceeding the width is more than it is exceeding the height
				if (($src_width - $target_width) >= ($src_height - $target_height)) {
					$new_width = $target_width;
					$new_height = absint(round($src_height / $src_width * $new_width));
					$new_x = 0;
					$new_y = absint(round(($target_height - $new_height) / 2));
				}
				else {
					$new_height = $target_height;
					$new_width = absint(round($src_width / $src_height * $new_height));
					$new_x = absint(round(($target_width - $new_width) / 2));
					$new_y = 0;
				}
			}

			// Place source image onto thumbnail
			imagecopyresampled($thumbnail, $img, $new_x, $new_y, 0, 0, $new_width, $new_height, $src_width, $src_height);

			// Save new image and free memory used for the thumbnail and source image
			imagepng($thumbnail, $thumbnail_path);
			imagedestroy($thumbnail);
			imagedestroy($img);
		}

		/* Update metadata:
		 * $target_height + 1 is to trick WooCommerce to regenerate this particular
		 * image when another option might be selected later (e.g. '1:1')
		 */
		$data = array(
			'file' => $thumbnail_name,
			'width' => $target_width,
			'height' => $target_height + 1,
			'mime-type' => 'image/png',
			'uncropped' => 0,
		);

		$image_data['sizes']['woocommerce_thumbnail'] = $data;
		$image_data['sizes']['shop_catalog'] = $data;

		return $image_data;
	}

	/**
	 * Extracts folder path, name and MIME-type from file path string
	 * .e.g. "2018/02/sweater.png" -> array("2018/02/", "sweater", "png")
	 *
	 * @param string $path
	 * @return string[]
	 */
	private static function extract_path_info($path) {
		if (empty($path) || !is_string($path))
			return;

		$info = explode('/', $path);

		// Get all but last parts of the '/' separated string
		$folder = '';
		for ($i = 0; $i + 1 < count($info); $i++) {
			$folder .= $info[$i].'/';
		}

		$file = explode('.', $info[count($info)-1]);

		// File type will be the last part of the '.' separated string
		$mime = strtolower($file[count($file)-1]);

		// Concatenate rest of exploded string in case filename contains any '.'
		$name = array();
		for ($i = 0; $i + 1 < count($file); $i++) {
			array_push($name, $file[$i]);
		}
		$name = implode('.', $name);

		return array($folder, $name, $mime);
	}
}

endif;
?>

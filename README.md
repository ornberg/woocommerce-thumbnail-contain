# Contain WooCommerce product thumbnails

I recently worked on a WooCommerce project with vast amounts of product images, some of which had quite different proportions.
Without manually editing the images before uploading, a product archive page would result in either this:

![option_1_1](https://raw.githubusercontent.com/ornberg/woocommerce-thumbnail-contain/master/img/option_1_1.png)

or when selecting `Uncropped` in the WP Customizer for WooCommerce thumbnails, this:

![option_uncropped](https://raw.githubusercontent.com/ornberg/woocommerce-thumbnail-contain/master/img/option_uncropped.png)

If you are able to edit the theme, two possible solutions to go for are:

1. Using the CSS-property `object-fit: contain;` and a polyfill like [Picturefill](https://scottjehl.github.io/picturefill/) if you need one.
2. Replace the `img` elements with `div` with the thumbnails as background images with `background-position: contain;`.

However, this plugin is meant to serve as a non-CSS/JS-based alternative solution, which will resize-to-fit the source image to the predfined thumbnail dimensions when the `Contain` option is selected under `WooCommerce`→`Product Images` in the WP Customizer:

![object_fit](https://raw.githubusercontent.com/ornberg/woocommerce-thumbnail-contain/master/img/object_fit.png)

resulting in this:

![option_contain](https://raw.githubusercontent.com/ornberg/woocommerce-thumbnail-contain/master/img/option_contain.png)

## Dependencies
- WordPress 4.7.9 and up
- WooCommerce 3.3.0 and up
- PHP 5.6 and up
- PHP GD library

## Issues with srcset
Some themes/sites might not display the correct thumbnails when using responsive images; you can disable this by deselecting `Responsive thumbnails` in the same WP Customizer section.

## Issues with thumbnail regeneration
I have had trouble letting WooCommerce regenerate thumbnails automatically when running WordPress locally, specifically in Docker containers. If you need to faster and more reliably do regenerations, checkout [Regenerate Thumbnails](https://wordpress.org/plugins/regenerate-thumbnails/).

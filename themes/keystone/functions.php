<?php
/**
 * Keystone theme bootstrap.
 *
 * Presentation only: every piece of business logic (post types, meta, REST, forms)
 * lives in the Keystone Properties plugin and is consumed through its keystone_* template API.
 *
 * @package Keystone
 */

defined( 'ABSPATH' ) || exit;

define( 'KEYSTONE_THEME_VERSION', '1.0.0' );
define( 'KEYSTONE_THEME_DIR', get_template_directory() );
define( 'KEYSTONE_THEME_URI', get_template_directory_uri() );

require KEYSTONE_THEME_DIR . '/inc/setup.php';
require KEYSTONE_THEME_DIR . '/inc/assets.php';
require KEYSTONE_THEME_DIR . '/inc/template-tags.php';
require KEYSTONE_THEME_DIR . '/inc/content.php';
require KEYSTONE_THEME_DIR . '/inc/blocks.php';
require KEYSTONE_THEME_DIR . '/inc/seo.php';

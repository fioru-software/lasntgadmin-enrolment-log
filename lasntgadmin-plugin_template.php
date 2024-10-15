<?php
/**
 * Plugin Name:       LASNTG Admin Plugin Template
 * Plugin URI:        https://github.com/fioru-software/lasntgadmin-plugin_template
 * Description:       An example plugin.
 * Version:           0.0.0
 * Requires PHP:      7.2
 * Text Domain:       lasntgadmin
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

use Lasntg\Admin\Translate;

// composer autoloading.
require_once getenv( 'COMPOSER_AUTOLOAD_FILEPATH' );

Translate::init();

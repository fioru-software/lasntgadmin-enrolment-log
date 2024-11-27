<?php
/**
 * Plugin Name:       LASNTG Enrolment Log
 * Plugin URI:        https://github.com/fioru-software/lasntgadmin-enrolment-log
 * Description:       Log of enrolments and cancellations.
 * Version:           1.0.0-rc8
 * Requires PHP:      7.2
 * Text Domain:       lasntgadmin
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

// composer autoloading.
require_once getenv( 'COMPOSER_AUTOLOAD_FILEPATH' );

use Lasntg\Admin\EnrolmentLog\{ PluginUtils, CustomPostType, RestApi, AdminListView, AdminEditView, ActionsFilters };

CustomPostType::init();
RestApi::init();
AdminListView::init();
AdminEditView::init();
ActionsFilters::init();

/**
 * Plugin activation
 */
register_activation_hook( __FILE__, [ PluginUtils::class, 'activate' ] );

/**
 * Plugin deactivation
 */
register_deactivation_hook( __FILE__, [ PluginUtils::class, 'deactivate' ] );

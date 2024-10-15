<?php

namespace Lasntg\Admin\EnrolmentLog;

use Lasntg\Admin\EnrolmentLog\{ CustomDbTable, Capabilities };

/**
 * Plugin utilities
 */
class PluginUtils {

	public static function activate() {
		Capabilities::add();
		CustomDbTable::install();
	}

	public static function deactivate() {
		Capabilities::remove();
	}

	public static function get_camel_case_name(): string {
		return 'lasntgadmin_enrolment_log';
	}

	public static function get_kebab_case_name(): string {
		return 'lasntgadmin-enrolment-log';
	}

	public static function get_absolute_plugin_path(): string {
		return sprintf( '/var/www/html/wp-content/plugins/%s', self::get_kebab_case_name() );
	}
}

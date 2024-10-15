<?php

namespace Lasntg\Admin\EnrolmentLog;

class CustomDbTable {

	static $db_version = '0.1';

	public static function install() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'enrolment_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			post_id bigint(20) unsigned NOT NULL,
			course_id bigint(20) unsigned NOT NULL,
			order_id bigint(20) unsigned NOT NULL,
			attendee_id bigint(20) unsigned NOT NULL,
			comment varchar(200) DEFAULT '' NOT NULL,
			KEY post_id (post_id),
			KEY course_id (course_id),
			KEY order_id (order_id),
			KEY attendee_id (attendee_id),
			KEY course_id_order_id (course_id,order_id),
			KEY course_id_order_id_attendee_id (course_id,order_id,attendee_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		add_option( sprintf('%s_enrolments_db_version', PluginUtils::get_camel_case_name() ), self::$db_version );
	}

}


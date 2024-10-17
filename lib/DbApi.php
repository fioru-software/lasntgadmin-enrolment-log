<?php

namespace Lasntg\Admin\EnrolmentLog;

use Lasntg\Admin\EnrolmentLog\{ LogEntry, CustomPostType, CustomDbTable };

use WP_Error;

/**
 * @see https://developer.wordpress.org/reference/classes/wpdb/
 */
class DbApi {

	/**
	 * @return int The number of entries added.
	 * @throws Exception
	 */
	public static function insertentry( LogEntry $entry ): int {

		global $wpdb;

		$post_id = wp_insert_post( 
			[
				'post_type' => CustomPostType::get_name(),
				'post_status' => 'publish'
			], 
			true 
		);

		if( is_wp_error( $post_id ) ) {
			$wp_error = $post_id;
			error_log( $wp_error->get_error_message() );
			throw new Exception(
				$wp_error->get_error_message(),
				$wp_error->get_error_code()
			);
		}

		$entry->post_id = $post_id;

		$count = $wpdb->insert(
			CustomDbTable::get_table_name(),
			$entry->toArray(),
			[ '%d', '%d', '%d', '%d', '%s' ]
		);

		if( $count === false ) {
			$error_msg = $wpdb->last_error;
			error_log( $error_msg );
			throw new Exception(
				$error_msg
			);
		}
		return (int)$count;

	}

	/**
	 * @param LogEntry[] $entries
	 * @return int The number of entries added.
	 * @throws Exception
	 */
	public static function insert_entries( array $entries ): int {
		global $wpdb;

		$wpdb->query('START TRANSACTION');

		try {

			$count = 0;

			foreach( $entries as $entry ) {
				$count += self::add_entry( $entry );
			}

			$wpdb->query('COMMIT');
			return $count;

		} catch( Exception $e ) {

			$wpdb->query('ROLLBACK');
			throw new Exception(
				$e->get_message(),
				$e->get_code(),
				$e
			);

		}
	}

}

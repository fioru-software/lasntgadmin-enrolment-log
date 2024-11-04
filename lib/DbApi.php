<?php

namespace Lasntg\Admin\EnrolmentLog;

use Lasntg\Admin\EnrolmentLog\{ LogEntry, CustomPostType, CustomDbTable };

use WP_Error;
use Exception;

/**
 * @see https://developer.wordpress.org/reference/classes/wpdb/
 */
class DbApi {

	public static function search_entry( LogEntry $entry ): LogEntry {

		global $wpdb;
		$table_name = CustomDbTable::get_table_name();

		if( ! isset( $entry->attendee_id) ) {
			throw new Exception(
				'Missing required attendee id'
			);
		}
		if( ! isset( $entry->course_id) ) {
			throw new Exception(
				'Missing required course id'
			);
		}
		if( ! isset( $entry->order_id) ) {
			throw new Exception(
				'Missing required order id'
			);
		}

		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM $table_name WHERE attendee_id = %d AND course_id = %d AND order_id = %d",
				$entry->attendee_id, $entry->course_id, $entry->order_id
			)
		);

		if( is_null( $post_id ) ) {
			$error_msg = $wpdb->last_error;
			error_log( $error_msg );
			throw new Exception(
				$error_msg
			);
		}
		return self::get_entry( $post_id );
	}

	/**
	 * @throws Exception
	 */
	public static function get_entry( int $post_id ): LogEntry {

		global $wpdb;
		$table_name = CustomDbTable::get_table_name();

		$post = get_post( $post_id );

		if( is_null( $post ) ) {
			throw new Exception(
				'Post not found'
			);
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE post_id = %d",
				$post_id
			)
		);

		if( is_null( $row ) ) {
			$error_msg = $wpdb->last_error;
			error_log( $error_msg );
			throw new Exception(
				$error_msg
			);
		}

		$log_entry = new LogEntry();
		$log_entry->post_id = $post_id;
		$log_entry->author_id = $post->post_author;
		$log_entry->created = get_post_datetime( $post_id, 'date', 'local' ); 
		$log_entry->modified = get_post_datetime( $post_id, 'modified', 'local');
		$log_entry->status = $post->post_status;
		$log_entry->course_id = $row->course_id;
		$log_entry->order_id = $row->order_id;
		$log_entry->attendee_id = $row->attendee_id;
		$log_entry->comment = $row->comment;

		return $log_entry;

	}

	/**
	 * @return int The number of entries updated.
	 * @throws Exception
	 */
	public static function update_entry( LogEntry $entry ): int {

		$post_id = wp_update_post(
			(object)[
				'ID' => $entry->post_id,
				'post_status' => $entry->status,
				'post_author' => $entry->author_id
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

		$count = $wpdb->update(
			CustomDbTable::get_table_name(),
			[
				'course_id' => $entry->course_id,
				'order_id' => $entry->order_id,
				'attendee_id' => $entry->attendee_id,
				'comment' => $entry->comment
			],
			array( 'post_id' => $entry->post_id ),
			array(
				'%s',	// value1
				'%d'	// value2
			),
			array( '%d' )
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
	 * @throws Exception
	 */
	public static function insert_entry( LogEntry $entry ): LogEntry {

		global $wpdb;

		$post_id = wp_insert_post(
			[
				'post_type' => CustomPostType::get_name(),
				'post_status' => $entry->status
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

		$count = $wpdb->insert(
			CustomDbTable::get_table_name(),
			[
				'post_id' => $post_id,
				'course_id' => $entry->course_id,
				'order_id' => $entry->order_id,
				'attendee_id' => $entry->attendee_id,
				'comment' => $entry->comment
			],
			[ '%d', '%d', '%d', '%d', '%s' ]
		);

		if( $count === false ) {
			$error_msg = $wpdb->last_error;
			error_log( $error_msg );
			throw new Exception(
				$error_msg
			);
		}

		$entry->post_id = $post_id;
		return $entry;

	}

	/**
	 * @param LogEntry[] $entries
	 * @return int The number of entries added.
	 * @throws Exception
	 */
	public static function insert_entries( array $log_entries ): array {

		global $wpdb;
		$db_entries = [];

		$wpdb->query('START TRANSACTION');

		try {

			foreach( $log_entries as $log_entry ) {
				array_push( $db_entries, self::add_entry( $log_entry ) );
			}

			$wpdb->query('COMMIT');
			return $db_entries;

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

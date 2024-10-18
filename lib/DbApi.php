<?php

namespace Lasntg\Admin\EnrolmentLog;

use Lasntg\Admin\EnrolmentLog\{ LogEntry, CustomPostType, CustomDbTable };

use WP_Error;
use Exception;

/**
 * @see https://developer.wordpress.org/reference/classes/wpdb/
 */
class DbApi {

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
		$log_entry->author_id = $post->get( 'post_author' );
		$log_entry->datetime = $post->get( 'post_date' );
		$log_entry->status = $post->get( 'post_status' );
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
	 * @return int The number of entries added.
	 * @throws Exception
	 */
	public static function insert_entry( LogEntry $entry ): int {

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

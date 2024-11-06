<?php

namespace Lasntg\Admin\EnrolmentLog;

use Lasntg\Admin\EnrolmentLog\{ LogEntry, CustomPostType, CustomDbTable, NotFoundException };

use WP_Error, Exception, Groups_Post_Access;

/**
 * @see https://developer.wordpress.org/reference/classes/wpdb/
 */
class DbApi {

	const ACTIVE_ENROLMENT_STATUS   = 'publish';
	const CANCELED_ENROLMENT_STATUS = 'cancelled';

	public static function search_entry( LogEntry $entry ): LogEntry {

		global $wpdb;
		$table_name = CustomDbTable::get_table_name();

		if ( ! isset( $entry->attendee_id ) ) {
			throw new Exception(
				'Missing required attendee id'
			);
		}
		if ( ! isset( $entry->course_id ) ) {
			throw new Exception(
				'Missing required course id'
			);
		}
		if ( ! isset( $entry->order_id ) ) {
			throw new Exception(
				'Missing required order id'
			);
		}
		if ( ! isset( $entry->status ) ) {
			throw new Exception(
				'Missing required post status'
			);
		}

		$prepared = $wpdb->prepare(
			"SELECT * FROM $table_name JOIN wp_posts ON $table_name.post_id = wp_posts.ID WHERE wp_posts.post_status = %s AND $table_name.course_id = %d AND $table_name.attendee_id = %d AND $table_name.order_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$entry->status,
			$entry->course_id,
			$entry->attendee_id,
			$entry->order_id
		);

		$row = $wpdb->get_row( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( is_null( $row ) ) {
			throw new NotFoundException( 'Enrolment log entry not found' );
		}

		$log_entry              = new LogEntry();
		$log_entry->post_id     = $row->ID;
		$log_entry->author_id   = $row->post_author;
		$log_entry->created     = get_post_datetime( $row->ID, 'date', 'local' );
		$log_entry->modified    = get_post_datetime( $row->ID, 'modified', 'local' );
		$log_entry->status      = $row->post_status;
		$log_entry->course_id   = $row->course_id;
		$log_entry->order_id    = $row->order_id;
		$log_entry->attendee_id = $row->attendee_id;
		$log_entry->comment     = $row->comment;

		return $log_entry;
	}

	/**
	 * @throws NotFoundException When wp_posts row does not exist.
	 */
	public static function get_entry( int $post_id ): LogEntry {

		global $wpdb;
		$table_name = CustomDbTable::get_table_name();

		$post = get_post( $post_id );

		if ( is_null( $post ) ) {
			throw new NotFoundException(
				'Post not found'
			);
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE post_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$post_id
			)
		);

		if ( is_null( $row ) ) {
			$error_msg = $wpdb->last_error;
			error_log( $error_msg );
			throw new NotFoundException(
				esc_attr( $error_msg )
			);
		}

		$log_entry              = new LogEntry();
		$log_entry->post_id     = $post_id;
		$log_entry->author_id   = $post->post_author;
		$log_entry->created     = get_post_datetime( $post_id, 'date', 'local' );
		$log_entry->modified    = get_post_datetime( $post_id, 'modified', 'local' );
		$log_entry->status      = $post->post_status;
		$log_entry->course_id   = $row->course_id;
		$log_entry->order_id    = $row->order_id;
		$log_entry->attendee_id = $row->attendee_id;
		$log_entry->comment     = $row->comment;

		return $log_entry;
	}

	/**
	 * @return int The number of entries updated.
	 * @throws Exception When unable to update the row.
	 */
	public static function update_entry( LogEntry $entry ): int {

		global $wpdb;

		$post_id = wp_update_post(
			(object) [
				'ID'          => $entry->post_id,
				'post_status' => $entry->status,
				'post_author' => $entry->author_id,
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$wp_error = $post_id;
			error_log( $wp_error->get_error_message() );
			throw new Exception(
				esc_html( $wp_error->get_error_message() ),
				esc_html( $wp_error->get_error_code() )
			);
		}

		$count = $wpdb->update(
			CustomDbTable::get_table_name(),
			[
				'course_id'   => $entry->course_id,
				'order_id'    => $entry->order_id,
				'attendee_id' => $entry->attendee_id,
				'comment'     => $entry->comment,
			],
			array( 'post_id' => $entry->post_id ),
			array(
				'%s',
				'%d',
			),
			array( '%d' )
		);

		if ( false === $count ) {
			$error_msg = $wpdb->last_error;
			error_log( $error_msg );
			throw new Exception(
				esc_html( $error_msg )
			);
		}
		return (int) $count;
	}


	/**
	 * @throws Exception When unable to insert row.
	 */
	public static function insert_entry( LogEntry $entry ): LogEntry {

		global $wpdb;

		try {
			self::search_entry( $entry );
			throw new Exception( 'Existing active enrolment' );
		} catch ( NotFoundException $e ) {
			$post_id = wp_insert_post(
				[
					'post_type'   => CustomPostType::get_name(),
					'post_status' => $entry->status,
				],
				true
			);

			Groups_Post_Access::create(
				[
					'post_id'  => $post_id,
					'group_id' => $entry->group_id,
				]
			);

			if ( is_wp_error( $post_id ) ) {
				$wp_error = $post_id;
				error_log( $wp_error->get_error_message() );
				throw new Exception(
					esc_html( $wp_error->get_error_message() ),
					esc_html( $wp_error->get_error_code() )
				);
			}

			$count = $wpdb->insert(
				CustomDbTable::get_table_name(),
				[
					'post_id'     => $post_id,
					'course_id'   => $entry->course_id,
					'order_id'    => $entry->order_id,
					'attendee_id' => $entry->attendee_id,
					'comment'     => $entry->comment,
				],
				[ '%d', '%d', '%d', '%d', '%s' ]
			);

			if ( false === $count ) {
				$error_msg = $wpdb->last_error;
				error_log( $error_msg );
				throw new Exception(
					esc_html( $error_msg )
				);
			}

			$entry->post_id = $post_id;
			return $entry;
		}//end try
	}
}

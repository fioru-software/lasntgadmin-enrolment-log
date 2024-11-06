<?php

namespace Lasntg\Admin\EnrolmentLog;

class AdminListView {


	public static function init() {
		self::add_actions();
		self::add_filters();
	}

	public static function add_actions() {
		if ( is_admin() ) {
			add_action( 'manage_enrolment_log_posts_custom_column', [ self::class, 'render_columns' ], 10, 2 );
		}
	}

	public static function add_filters() {
		if ( is_admin() ) {
			add_filter( 'manage_enrolment_log_posts_columns', [ self::class, 'add_columns' ], 11 );
		}
	}

	public static function render_columns( string $column, int $post_id ) {
		if ( in_array( get_post_status( $post_id ), [ 'publish','cancelled' ] ) ) {
			$post = get_post( $post_id );

			$entry = DbApi::get_entry( $post_id );
			switch ( $column ) {
				case 'post_id':
					echo esc_html( $entry->post_id );
					break;
				case 'course_id':
					echo esc_html( $entry->course_id );
					break;
				case 'order_id':
					echo esc_html( $entry->order_id );
					break;
				case 'attendee_id':
					echo esc_html( $entry->attendee_id );
					break;
				case 'comment':
					echo esc_html( $entry->comment );
					break;
			}
		}//end if
	}

	public static function add_columns( array $columns ): array {

		$date   = $columns['date'];
		$author = $columns['author'];
		$group  = $columns['groups-read'];

		unset( $columns['date'] );
		unset( $columns['groups-read'] );
		unset( $columns['title'] );

		$columns['order_id']    = __( 'Order ID', 'lasntgadmin' );
		$columns['course_id']   = __( 'Product ID', 'lasntgadmin' );
		$columns['attendee_id'] = __( 'Attendee ID', 'lasntgadmin' );
		$columns['comment']     = __( 'Comment', 'lasntgadmin' );
		$columns['date']        = $date;
		return $columns;
	}
}

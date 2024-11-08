<?php

namespace Lasntg\Admin\EnrolmentLog;

use Automattic\Jetpack\Constants;

class AdminListView {


	public static function init() {
		self::add_actions();
		self::add_filters();
	}

	public static function add_actions() {
		if ( is_admin() ) {
			add_action( 'manage_enrolment_log_posts_custom_column', [ self::class, 'render_columns' ], 10, 2 );
			add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_styles' ] );
		}
	}

	public static function add_filters() {
		if ( is_admin() ) {
			add_filter( 'manage_enrolment_log_posts_columns', [ self::class, 'add_columns' ], 11 );
		}
	}

	public static function enqueue_styles() {
		$version = Constants::get_constant( 'WC_VERSION' );
		$handle  = 'woocommerce_admin_styles';
		wp_register_style( $handle, WC()->plugin_url() . '/assets/css/admin.css', array(), $version );
		wp_enqueue_style( $handle );
	}

	public static function render_columns( string $column, int $post_id ) {
		$status = get_post_status( $post_id );
		if ( in_array( $status, [ 'publish','cancelled' ] ) ) {
			$post = get_post( $post_id );

			$entry = DbApi::get_entry( $post_id );
			switch ( $column ) {
				case 'post_id':
					echo esc_html( $entry->post_id );
					break;
				case 'course_id':
					$product = wc_get_product( $entry->course_id );
					echo esc_html( $product->get_title() );
					break;
				case 'order_id':
					echo esc_html( $entry->order_id );
					break;
				case 'attendee_id':
					$attendee_acf_fields = get_fields( $entry->attendee_id );
					echo esc_html( sprintf( '%s %s', $attendee_acf_fields['first_name'], $attendee_acf_fields['last_name'] ) );
					break;
				case 'status':
					// @see woocommerce/assets/css/admin.css
					$colour = 'cancelled' === $status ? 'on-hold' : 'processing';
					$status = 'publish' === $status ? 'Enrolled' : $status;
					echo wp_kses_post( "<mark class='order-status status-$colour tips'><span>" . ucfirst( $status ) . '</span></mark>' );
					break;
				case 'comment':
					echo esc_html( $entry->comment );
					break;
			}//end switch
		}//end if
	}

	public static function add_columns( array $columns ): array {

		$date   = $columns['date'];
		$author = $columns['author'];
		$group  = $columns['groups-read'];

		unset( $columns['date'] );
		unset( $columns['groups-read'] );
		unset( $columns['title'] );

		$columns['order_id']    = __( 'Order', 'lasntgadmin' );
		$columns['course_id']   = __( 'Product', 'lasntgadmin' );
		$columns['attendee_id'] = __( 'Attendee', 'lasntgadmin' );
		$columns['comment']     = __( 'Comment', 'lasntgadmin' );
		$columns['status']      = __( 'Status', 'lasntgadmin' );
		$columns['date']        = $date;
		return $columns;
	}
}

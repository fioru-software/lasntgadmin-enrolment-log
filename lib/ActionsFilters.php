<?php

namespace Lasntg\Admin\EnrolmentLog;

use Lasntg\Admin\EnrolmentLog\{ CustomDbTable, DbApi, CustomPostType };
use WC_Order;

class ActionsFilters {


	public static function init() {
		self::add_actions();
		self::add_filters();
	}

	public static function add_actions() {
		// Set enrolment log entries status = publish.
		add_action( 'woocommerce_payment_complete', [ self::class, 'publish_entries' ], 10, 2 );
		// Set enrolment log entries status = cancelled
		add_action( 'woocommerce_order_status_cancelled', [ self::class, 'cancel_entries' ], 50, 2 );
		add_action( 'admin_menu', [ self::class, 'add_submenu' ] );
	}

	public static function add_filters() {
	}


	public static function add_submenu() {
		add_submenu_page(
			'woocommerce',
			__( 'Enrolment Log', 'lasntgadmin' ),
			__( 'Enrolment Log', 'lasntgadmin' ),
			'view_enrolment_log',
			sprintf( 'edit.php?post_type=%s', CustomPostType::get_name() )
		);
	}

	public static function cancel_entries( int $order_id, WC_Order $order ) {

		global $wpdb;
		$table_name = CustomDbTable::get_table_name();

		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM $table_name WHERE order_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$order_id
			)
		);

		$wpdb->query( 'START TRANSACTION' );

		$count = 0;
		foreach ( $post_ids as $post_id ) {
			$count += $wpdb->query(
				$wpdb->prepare(
					"UPDATE $wpdb->posts SET post_status = %s WHERE ID = %d AND post_status IN (%s, %s)",
					DbApi::CANCELED_ENROLMENT_STATUS,
					$post_id,
					DbApi::PENDING_ENROLMENT_STATUS,
					DbApi::ACTIVE_ENROLMENT_STATUS
				)
			);
		}

		if ( $count === count( $post_ids ) ) {
			$wpdb->query( 'COMMIT' );
			wp_admin_notice(
				"Cancelled $count attendees",
				[
					'type'        => 'success',
					'dismissible' => true,

				]
			);
		} else {
			$wpdb->query( 'ROLLBACK' );
			error_log( "=== Unable to cancel attendees for order $order_id ===" );
			error_log( $wpdb->last_error );
			wp_admin_notice(
				'Unable to cancel attendees',
				[
					'type'        => 'error',
					'dismissible' => true,
				]
			);
		}//end if
	}

	/**
	 * Update enrolment log entries set status = enrolled WHERE order_id = $order_id AND status = 'pending'
	 */
	public static function publish_entries( int $order_id, string $transaction_id ) {

		global $wpdb;
		$table_name = CustomDbTable::get_table_name();

		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM $table_name WHERE order_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$order_id
			)
		);

		$wpdb->query( 'START TRANSACTION' );

		$results = [];
		foreach ( $post_ids as $post_id ) {
			array_push(
				$results,
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $wpdb->posts SET post_status = %s WHERE ID = %d AND post_status = %s",
						DbApi::ACTIVE_ENROLMENT_STATUS,
						$post_id,
						DbApi::PENDING_ENROLMENT_STATUS
					)
				)
			);
		}

		if ( in_array( false, $results, true ) ) {
			$wpdb->query( 'ROLLBACK' );
			error_log( "=== Unable to enrol attendees for order $order_id ===" );
			error_log( $wpdb->last_error );
			$order = wc_get_order( $order_id );
			$order->update_status( 'failed' );
			wp_admin_notice(
				'Unable to enrol attendees',
				[
					'type'        => 'error',
					'dismissible' => true,
				]
			);
		} else {
			$wpdb->query( 'COMMIT' );
			wp_admin_notice(
				"Enrolled $count attendees",
				[
					'type'        => 'success',
					'dismissible' => true,

				]
			);
		}//end if
	}
}

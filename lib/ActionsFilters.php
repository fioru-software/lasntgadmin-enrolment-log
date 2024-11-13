<?php

namespace Lasntg\Admin\EnrolmentLog;

use Lasntg\Admin\EnrolmentLog\{ CustomDbTable, DbApi };

class ActionsFilters {


	public static function init() {
		self::add_actions();
		self::add_filters();
	}

	public static function add_actions() {
		add_action( 'woocommerce_payment_complete', [ self::class, 'update_enrolment_log' ], 10, 2 );
	}

	public static function add_filters() {
		if ( is_admin() ) {
		}
	}

	/**
	 * Update enrolment log entries set status = enrolled WHERE order_id = $order_id AND status = 'pending'
	 */
	public static function update_enrolment_log( int $order_id, string $transaction_id ) {
		error_log("=== updating enrolment log entries to ");

		global $wpdb;
		$table_name = CustomDbTable::get_table_name();

		$post_ids = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id FROM $table_name WHERE order_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$order_id
			),
			ARRAY_N
		);
		error_log("=== post ids ===");
		error_log(print_r($post_ids, true));

		$wpdb->query('START TRANSACTION');

		$count = 0;
		foreach( $post_ids as $post_id ) {
			$count += $wpdb->query(
				$wpdb->prepare(
					"UPDATE $wpdb->posts SET post_status = 'publish' WHERE ID = %d AND post_status = %s",
					$post_id,
					DbApi::PENDING_ENROLMENT_STATUS
				)
			);
		}
		error_log("=== count ===");
		error_log(print_r($count, true));

		if( $count === count($post_ids) ) {
			$wpdb->query('COMMIT');
		} else {
			$wpdb->query('ROLLBACK');
		}

	}
}

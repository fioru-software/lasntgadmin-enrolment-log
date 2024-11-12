<?php

namespace Lasntg\Admin\EnrolmentLog;

class ActionsFilters {


	public static function init() {
		self::add_actions();
		self::add_filters();
	}

	public static function add_actions() {
		if ( is_admin() ) {
			add_action( 'woocommerce_payment_complete', [ self::class, 'update_enrolment_log' ], 10, 2 );
		}
	}

	public static function add_filters() {
		if ( is_admin() ) {
		}
	}

	/**
	 * Update enrolment log entries set status = enrolled WHERE order_id = $order_id AND status = 'pending'
	 * @todo
	 */
	public static function update_enrolment_log( int $order_id, string $transaction_id ) {
	}
}

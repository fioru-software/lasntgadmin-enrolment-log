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

		// Wrap function body in is_admin() conditional.
		if ( is_admin() ) {

			// Add enrolment log to WooCommerce menu.
			add_action( 'admin_menu', [ self::class, 'add_submenu' ] );

			// Prompt for order cancellation reason and add to the order's enrolment log entries.
			add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_script_to_order_list_view' ] );
		}
	}

	public static function add_filters() {
		// Wrap function body in is_admin() conditional.
	}

	public static function enqueue_script_to_order_list_view( string $hook ) {
		if ( in_array( $hook, [ 'edit.php' ], true ) ) {
			if ( function_exists( 'get_post_type' ) ) {
				if ( 'shop_order' === get_post_type() ) {
					$script_name = sprintf( '%s-bulk-action-order-cancellation-prompt', PluginUtils::get_kebab_case_name() );
					wp_register_script(
						$script_name,
						plugins_url( sprintf( '%s/assets/js/%s.js', PluginUtils::get_kebab_case_name(), $script_name ) ),
						[ 'jquery' ],
						PluginUtils::get_version(),
						[
							'in_footer' => true,
						]
					);
					wp_enqueue_script( $script_name );
				}
			}
		}
	}


	public static function add_submenu() {
		add_submenu_page(
			'woocommerce',
			__( 'Enrolment Log', 'lasntgadmin' ),
			__( 'Enrolment Log', 'lasntgadmin' ),
			'view_enrolment_logs',
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

		$results = [];
		foreach ( $post_ids as $post_id ) {
			array_push(
				$results,
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $wpdb->posts SET post_status = %s WHERE ID = %d AND post_status IN (%s, %s)",
						DbApi::CANCELED_ENROLMENT_STATUS,
						$post_id,
						DbApi::PENDING_ENROLMENT_STATUS,
						DbApi::ACTIVE_ENROLMENT_STATUS
					)
				)
			);
		}

		if ( in_array( false, $results, true ) ) {
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
		} else {
			$wpdb->query( 'COMMIT' );
			$count = array_reduce(
				$results,
				function ( $carry, $item ) {
					$carry += $item;
					return $carry;
				},
				0
			);
			wp_admin_notice(
				"Cancelled $count attendees",
				[
					'type'        => 'success',
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
				sprintf( 'Enrolled %d attendees', count( $results ) ),
				[
					'type'        => 'success',
					'dismissible' => true,

				]
			);
		}//end if
	}
}

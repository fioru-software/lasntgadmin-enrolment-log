<?php

namespace Lasntg\Admin\EnrolmentLog;

use Lasntg\Admin\EnrolmentLog\CustomPostType;

use Automattic\Jetpack\Constants, WP_Query, WC_Order;

class AdminListView {

	public static function init() {
		self::add_actions();
		self::add_filters();
	}

	public static function add_actions() {
		if ( is_admin() ) {
			add_action( 'manage_enrolment_log_posts_custom_column', [ self::class, 'render_columns' ], 10, 2 );
			add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_styles' ] );
			add_action( 'woocommerce_order_status_cancelled', [ self::class, 'add_cancellation_reasons' ], 50, 2 );
		}
	}

	public static function add_filters() {
		if ( is_admin() ) {
			add_filter( 'manage_enrolment_log_posts_columns', [ self::class, 'add_columns' ], 11 );
			add_filter( 'bulk_actions-edit-enrolment_log', [ self::class, 'modify_bulk_actions_dropdown' ] );

			// From product and order row action link.
			add_filter( 'posts_join', [ self::class, 'handle_join_clause_for_filter_request' ], 10, 2 );
			add_filter( 'posts_where', [ self::class, 'handle_where_clause_for_filter_request' ], 10, 2 );
		}
	}

	/**
	 * $_GET
	 * [enrolment_log_order_cancellations] => {\"256\":\"Hello\"}
	 * [post_type] => shop_order
	 * [_wpnonce] => a65ff286e9
	 * [action] => mark_cancelled
	 */
	public static function add_cancellation_reasons( int $order_id, WC_Order $order ): void {
		global $wpdb;

		if ( self::is_expected_get_request( [ 'post_type', '_wpnonce', 'action', 'enrolment_log_order_cancellations' ] ) ) {
			if ( 'mark_cancelled' === $_GET['action'] && 'shop_order' === $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated

				$reasons = json_decode( sanitize_text_field( wp_unslash( $_GET['enrolment_log_order_cancellations'] ) ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
				$reason  = $reasons[ "$order_id" ];

				// Add reason to all enrolment log entries with order_id = $order_id AND with post_status = completed
				$enrolment_log_table = CustomDbTable::get_table_name();
				$result              = $wpdb->query(
					$wpdb->prepare(
						"UPDATE $enrolment_log_table JOIN $wpdb->posts ON $enrolment_log_table.post_id = $wpdb->posts.ID SET $enrolment_log_table.comment = %s WHERE $enrolment_log_table.order_id = %d AND $wpdb->posts.post_status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$reason,
						$order_id,
						DbApi::ACTIVE_ENROLMENT_STATUS
					)
				);

				if ( false === $result ) {
					error_log( "=== Unable to add order cancellation reason to enrolment logs for order $order_id ===" );
					error_log( $wpdb->last_error );
					wp_admin_notice(
						'Unable to add cancellation reason to enrolment logs',
						[
							'type'        => 'error',
							'dismissible' => true,
						]
					);
				}
			}//end if
		}//end if
	}

	private static function is_expected_get_request( array $keys ): bool {
		foreach ( $keys as $key ) {
			if ( ! isset( $_GET[ $key ] ) || empty( $_GET[ $key ] ) ) {
				return false;
			}
		}
		return true;
	}


	public static function modify_bulk_actions_dropdown( array $actions ) {
		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * When clicking on enrolment logs from product row actions.
	 */
	public static function handle_join_clause_for_filter_request( string $join, WP_Query $query ): string {

		if ( ! is_search() && is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( ! is_null( $screen ) ) {
				if ( 'enrolment_log' === $screen->post_type && 'edit-enrolment_log' === $screen->id && 'enrolment_log' === $query->query_vars['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( isset( $_GET['product_id'] ) && ! empty( $_GET['product_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$join .= self::get_join_clause_to_filter_by_product_id( $join, $query );
					}
					if ( isset( $_GET['order_id'] ) && ! empty( $_GET['order_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$join .= self::get_join_clause_to_filter_by_order_id( $join, $query );
					}
				}
			}
		}//end if
		return $join;
	}

	/**
	 * When clicking on enrolment logs from product row actions.
	 */
	public static function handle_where_clause_for_filter_request( string $where, WP_Query $query ): string {

		if ( ! is_search() && is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( ! is_null( $screen ) ) {
				if ( 'enrolment_log' === $screen->post_type && 'edit-enrolment_log' === $screen->id && 'enrolment_log' === $query->query_vars['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( isset( $_GET['product_id'] ) && ! empty( $_GET['product_id'] && ! isset( $_GET['order_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$where .= self::get_where_clause_to_filter_by_product_id( $where, $query );
					}
					if ( isset( $_GET['order_id'] ) && ! empty( $_GET['order_id'] ) && ! isset( $_GET['product_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$where .= self::get_where_clause_to_filter_by_order_id( $where, $query );
					}
				}
			}
		}//end if
		return $where;
	}

	/**
	 * Joins enrolment_logs table to wp_posts table when product_id query param is present.
	 *
	 * @see self:handle_join_clause_for_filter_request()
	 */
	private static function get_join_clause_to_filter_by_product_id( string $join, WP_Query $query ): string {
		global $wpdb;
		$table_name = CustomDbTable::get_table_name();
		$join       = "JOIN $table_name ON $wpdb->posts.ID = $table_name.post_id ";
		return $join;
	}

	/**
	 * Joins enrolment_logs table to wp_posts table when order_id query param is present.
	 *
	 * @see self:handle_join_clause_for_filter_request()
	 */
	private static function get_join_clause_to_filter_by_order_id( string $join, WP_Query $query ): string {
		global $wpdb;
		$table_name = CustomDbTable::get_table_name();
		$join       = "JOIN $table_name ON $wpdb->posts.ID = $table_name.post_id ";
		return $join;
	}

	/**
	 * Where enrolment_logs.course_id = product_id query param.
	 *
	 * @see self:handle_where_clause_for_filter_request()
	 */
	private static function get_where_clause_to_filter_by_product_id( string $where, WP_Query $query ): string {
		global $wpdb;
		$product_id = absint( $_GET['product_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$table_name = CustomDbTable::get_table_name();
		$where      = " AND $table_name.course_id = $product_id ";
		return $where;
	}

	/**
	 * Where enrolment_logs.order_id = order_id query param.
	 *
	 * @see self:handle_where_clause_for_filter_request()
	 */
	private static function get_where_clause_to_filter_by_order_id( string $where, WP_Query $query ): string {
		global $wpdb;
		$order_id   = absint( $_GET['order_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$table_name = CustomDbTable::get_table_name();
		$where      = " AND $table_name.order_id = $order_id ";
		return $where;
	}


	public static function enqueue_admin_styles( string $hook ) {
		if ( in_array( $hook, [ 'edit.php' ], true ) ) {
			if ( function_exists( 'get_post_type' ) ) {
				if ( CustomPostType::get_name() === get_post_type() ) {
					$version = Constants::get_constant( 'WC_VERSION' );
					$handle  = 'woocommerce_admin_styles';
					wp_register_style( $handle, WC()->plugin_url() . '/assets/css/admin.css', array(), $version );
					wp_enqueue_style( $handle );

					$style_name = sprintf( '%s-admin-list-view', PluginUtils::get_kebab_case_name() );
					wp_register_style(
						$style_name,
						plugins_url( sprintf( '%s/assets/css/%s.css', PluginUtils::get_kebab_case_name(), $style_name ) ),
						[],
						PluginUtils::get_version()
					);
					wp_enqueue_style( $style_name );
				}
			}
		}//end if
	}

	public static function render_columns( string $column, int $post_id ) {
		$status              = get_post_status( $post_id );
		$post                = get_post( $post_id );
		$entry               = DbApi::get_entry( $post_id );
		$attendee_acf_fields = get_fields( $entry->attendee_id );

		switch ( $column ) {
			case 'attendee_full_name':
				echo esc_html( sprintf( '%s %s', $attendee_acf_fields['first_name'], $attendee_acf_fields['last_name'] ) );
				break;
			case 'attendee_employee_number':
				echo esc_html( $attendee_acf_fields['employee_number'] );
				break;
			case 'attendee_job_title':
				echo esc_html( $attendee_acf_fields['job_title'] );
				break;
			case 'attendee_department':
				echo esc_html( $attendee_acf_fields['department'] );
				break;
			case 'attendee_reasonable_accommodation':
				echo esc_html( $attendee_acf_fields['special_requirements'] );
				break;
			case 'status':
				$colour = self::get_status_style_name( $status );
				$status = EnrolmentLogUtils::get_translated_status_name( $status );
				echo wp_kses_post( "<mark class='order-status status-$colour tips'><span>" . ucfirst( $status ) . '</span></mark>' );
				break;
			case 'comment':
				echo esc_html( $entry->comment );
				break;
			case 'enrolment_date':
				echo esc_html( $post->post_modified );
				break;
		}//end switch
	}

	private static function get_translated_status_name(): string {
	}

	/**
	 * failed: red
	 * on-hold: yellow
	 * green: processing
	 * blue: completed
	 * grey: -
	 *
	 * @see woocommerce/assets/css/admin.css CSS class .order-status
	 */
	private static function get_status_style_name( string $status ): string {
		switch ( $status ) {
			case 'cancelled': // cancelled
				return 'on-hold'; // yellow
				break;
			case 'closed': // removed
				return 'removed'; // grey
				break;
			case 'publish': // publish
				return 'processing'; // green
				break;
			case 'pending': // pending
				return 'completed'; // blue
				break;
			default:
				return 'failed'; // red
		}
	}

	public static function add_columns( array $columns ): array {

		$date   = $columns['date'];
		$author = $columns['author'];
		$group  = $columns['groups-read'];

		unset( $columns['author'] );
		unset( $columns['date'] );
		unset( $columns['groups-read'] );
		unset( $columns['title'] );

		$columns['attendee_full_name']                = __( 'Full Name', 'lasntgadmin' );
		$columns['attendee_employee_number']          = __( 'Employee Number', 'lasntgadmin' );
		$columns['attendee_job_title']                = __( 'Job Title', 'lasntgadmin' );
		$columns['attendee_department']               = __( 'Department', 'lasntgadmin' );
		$columns['attendee_reasonable_accommodation'] = __( 'Reasonable Accommodation', 'lasntgadmin' );
		$columns['status']                            = __( 'Status', 'lasntgadmin' );
		$columns['comment']                           = __( 'Comment', 'lasntgadmin' );
		$columns['enrolment_date']                    = __( 'Date', 'lasntgadmin' );
		return $columns;
	}
}

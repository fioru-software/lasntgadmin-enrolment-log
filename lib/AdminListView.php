<?php

namespace Lasntg\Admin\EnrolmentLog;

use Automattic\Jetpack\Constants, WP_Query;

class AdminListView {


	public static function init() {
		self::add_actions();
		self::add_filters();
	}

	public static function add_actions() {
		if ( is_admin() ) {
			add_action( 'manage_enrolment_log_posts_custom_column', [ self::class, 'render_columns' ], 10, 2 );
			add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_styles' ] );
		}
	}

	public static function add_filters() {
		if ( is_admin() ) {
			add_filter( 'manage_enrolment_log_posts_columns', [ self::class, 'add_columns' ], 11 );
			add_filter( 'posts_join', [ self::class, 'handle_filter_request_join' ], 10, 2 );
		}
	}

	/**
	 * Add additional filters dropdowns.
	 */
	public static function handle_filter_request_join( string $join, WP_Query $query ): string {

		if ( ! is_search() && is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( ! is_null( $screen ) ) {
				if ( 'enrolment_log' === $screen->post_type && 'edit-enrolment_log' === $screen->id && 'enrolment_log' === $query->query_vars['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

					if ( isset( $_GET['product_id'] ) && ! empty( $_GET['product_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						error_log("=== filter enrolment log by product id ===");
						$join = self::get_join_to_filter_by_product_id( $join, $query );
					}
					if ( isset( $_GET['order_id'] ) && ! empty( $_GET['order_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						error_log("=== filter enrolment log by order id ===");
						$join = self::get_join_to_filter_by_order_id( $join, $query );
					}
				}
			}
		}//end if
		return $join;
	}

	private static function get_join_to_filter_by_product_id( string $join, WP_Query $query ): string {
		if ( isset( $_GET['product_id'] ) && ! empty( $_GET['product_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$product_id     = absint( $_GET['product_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}//end if
		return $join;
	}

	private static function get_join_to_filter_by_order_id( string $join, WP_Query $query ): string {
		if ( isset( $_GET['order_id'] ) && ! empty( $_GET['order_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order_id     = absint( $_GET['order_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		return $join;
	}

	public static function enqueue_admin_styles() {
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

	public static function render_columns( string $column, int $post_id ) {
		$status = get_post_status( $post_id );
		$post = get_post( $post_id );
		$entry = DbApi::get_entry( $post_id );
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
		switch( $status ) {
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

		$columns['attendee_full_name']    = __( 'Full Name', 'lasntgadmin' );
		$columns['attendee_employee_number']   = __( 'Employee Number', 'lasntgadmin' );
		$columns['attendee_job_title'] = __( 'Job Title', 'lasntgadmin' );
		$columns['attendee_department']     = __( 'Department', 'lasntgadmin' );
		$columns['attendee_reasonable_accommodation']     = __( 'Reasonable Accommodation', 'lasntgadmin' );
		$columns['status']      = __( 'Status', 'lasntgadmin' );
		$columns['comment']      = __( 'Comment', 'lasntgadmin' );
		$columns['enrolment_date']        = __( 'Date', 'lasntgadmin' );
		return $columns;
	}
}

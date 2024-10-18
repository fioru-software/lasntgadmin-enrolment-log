<?php

namespace Lasntg\Admin\EnrolmentLog;

use WP_REST_Request, WP_Error;

class RestApi {

	const PATH_PREFIX = 'lasntgadmin/enrolment/log/v1';

	public static function init() {
		add_action( 'rest_api_init', [ self::class, 'register_rest_routes' ] );
	}

	public static function register_rest_routes() {
		register_rest_route(
			self::PATH_PREFIX,
			'/test',
			[
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_entries' ],
				'permission_callback' => '__return_true'
			]
		);                                                                                                                                            
		register_rest_route(
			self::PATH_PREFIX,
			'/',
			[
				'methods'             => 'POST',
				'callback'            => [ self::class, 'add_entry' ],
				'permission_callback' => [ self::class, 'auth_nonce' ],
				'allow_batch'         => [ 'v1' => true ],
			]
		);                                                                                                                                            

	}

	public static function get_api_path(): string {
		return sprintf( '/%s', self::PATH_PREFIX );
	}

	/**
	 * @return bool|WP_Error
	 */
	public static function auth_nonce( WP_REST_Request $req ) {
		if ( ! wp_verify_nonce( $req->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', 'Invalid nonce', array( 'status' => 403 ) );
		}
		return true;
	}
	public static function get_entries( WP_REST_Request $req ) {
		return [];
	}

	public static function add_entry( WP_REST_Request $req ) {
		error_log(print_r($req->get_params(), true));

		$entry = new LogEntry();
		$entry->course_id = $req->get_param('product_id');
		$entry->order_id = $req->get_param('order_id');
		$entry->attendee_id = $req->get_param('attendee_id');
		$entry->comment = $req->get_param('comment');
		$entry->status = $req->get_param('status');
		$entry->comment = $req->get_param('comment');

		try {
			DbApi::insert_entry( $entry );

		} catch( Exception $e )  {

			return new WP_Error(
				$e->get_code(),
				$e->get_message(),
				$entry
			);
		}
	}

}

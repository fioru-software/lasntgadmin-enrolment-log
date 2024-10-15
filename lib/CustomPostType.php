<?php

namespace Lasntg\Admin\EnrolmentLog;

class CustomPostType {

	public static function init() {
		self::add_actions();
	}

	public static function add_actions() {
		add_action('init', [ self::class, 'register_custom_post_type' ]);
	}

	private static function get_labels(): array {
		return [
			'name'                  => _x( 'Enrolment Logs', 'Post Type General Name', 'lasntgadmin' ),
			'singular_name'         => _x( 'Enrolment Log', 'Post Type Singular Name', 'lasntgadmin' ),
			'menu_name'             => __( 'Enrolment Logs', 'lasntgadmin' ),
			'name_admin_bar'        => __( 'Enrolment Logs', 'lasntgadmin' ),
			'archives'              => __( 'Enrolment Logs Archive', 'lasntgadmin' ),
			'attributes'            => __( 'Enrolment Log Attributes', 'lasntgadmin' ),
			'parent_item_colon'     => __( 'Parent Item:', 'lasntgadmin' ),
			'all_items'             => __( 'All Enrolment Logs', 'lasntgadmin' ),
			'add_new_item'          => __( 'Add New Enrolment Log', 'lasntgadmin' ),
			'add_new'               => __( 'Add New', 'lasntgadmin' ),
			'new_item'              => __( 'New Enrolment Log', 'lasntgadmin' ),
			'edit_item'             => __( 'Edit Enrolment Log', 'lasntgadmin' ),
			'update_item'           => __( 'Update Enrolment Log', 'lasntgadmin' ),
			'view_item'             => __( 'View Enrolment Log', 'lasntgadmin' ),
			'view_items'            => __( 'View Enrolment Logs', 'lasntgadmin' ),
			'search_items'          => __( 'Search Enrolment Logs', 'lasntgadmin' ),
			'not_found'             => __( 'Not found', 'lasntgadmin' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'lasntgadmin' ),
			'featured_image'        => __( 'Featured Image', 'lasntgadmin' ),
			'set_featured_image'    => __( 'Set featured image', 'lasntgadmin' ),
			'uploaded_to_this_item' => __( 'Uploaded to this enrolment log', 'lasntgadmin' ),
			'items_list'            => __( 'Enrolment Logs List', 'lasntgadmin' ),
			'items_list_navigation' => __( 'Enrolment Logs List navigation', 'lasntgadmin' ),
			'filter_items_list'     => __( 'Filter Enrolment Logs', 'lasntgadmin' )
		];
	}

	public static function register_custom_post_type() {
		$post_type = register_post_type(
			'enrolment_log',
			[
				'labels' => self::get_labels(),
				'public' => true,
				'supports' => [ 'author' ],
			]
		);
	}
}

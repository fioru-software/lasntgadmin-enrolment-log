<?php

namespace Lasntg\Admin\EnrolmentLog;

use Lasntg\Admin\EnrolmentLog\CustomPostType;
use WP_Post;

class AdminEditView {


	public static function init() {
		self::add_actions();
		self::add_filters();
	}

	public static function add_actions() {
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_scripts' ], 99 );
			add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_styles' ] );
		}
	}

	public static function add_filters() {
		if ( is_admin() ) {
			add_filter( 'use_block_editor_for_post', [ self::class, 'remove_block_editor' ], 50, 2 );
			add_filter( 'user_can_richedit', [ self::class, 'remove_tinymce' ], 50 );
		}
	}

	public static function remove_tinymce( bool $wp_rich_edit ) {
		if ( function_exists( 'get_post_type' ) ) {
			if ( CustomPostType::get_name() === get_post_type() ) {
				return false;
			}
		}
		return $wp_rich_edit;
	}

	public static function remove_block_editor( bool $use_block_editor, WP_Post $post ) {
		if ( CustomPostType::get_name() === $post->post_type ) {
			return false;
		}
		return $use_block_editor;
	}

	public static function enqueue_admin_styles() {
		if ( wc_current_user_has_role( 'administrator' ) ) {
			$style_name = sprintf( '%s-admin-edit-view', PluginUtils::get_kebab_case_name() );
			wp_register_style(
				$style_name,
				plugins_url( sprintf( '%s/assets/css/%s.css', PluginUtils::get_kebab_case_name(), $style_name ) ),
				[],
				PluginUtils::get_version()
			);
			wp_enqueue_style( $style_name );
		}
	}

	public static function enqueue_admin_scripts( string $hook ) {
		if ( in_array( $hook, [ 'post-new.php', 'post.php' ], true ) ) {
			if ( function_exists( 'get_post_type' ) ) {
				if ( CustomPostType::get_name() === get_post_type() ) {
					$script_name = sprintf( '%s-custom-post-statuses', PluginUtils::get_kebab_case_name() );
					wp_register_script(
						$script_name,
						plugins_url( sprintf( '%s/assets/js/%s.js', PluginUtils::get_kebab_case_name(), $script_name ) ),
						[ 'jquery' ],
						PluginUtils::get_version(),
						[
							'strategy'  => 'async',
							'in_footer' => true,
						]
					);
					wp_enqueue_script( $script_name );
				}//end if
			}//end if
		}//end if
	}
}

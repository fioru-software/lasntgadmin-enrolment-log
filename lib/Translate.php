<?php

namespace Lasntg\Admin\EnrolmentLog;

class Translate {

	public static function init(): void {
		self::add_actions();
		self::add_filters();
	}

	public static function add_actions(): void {
		add_action( 'init', [ self::class, 'load_textdomain' ] );
	}

	public static function add_filters(): void {
		add_filter( 'load_textdomain_mofile', [ self::class, 'load_my_own_textdomain' ] );
	}

	public static function load_textdomain() {
		load_plugin_textdomain( 'lasntgadmin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		error_log( __( 'Featured', 'lasntgadmin' ) );
	}

	public static function load_my_own_textdomain( $mofile, $domain ) {
		if ( in_array( $domain, [ 'lasntgadmin', 'woocommerce' ] ) && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
			$locale = apply_filters( 'plugin_locale', determine_locale(), $domain ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$mofile = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/languages/' . $domain . '-' . $locale . '.mo';
		}
		return $mofile;
	}
}

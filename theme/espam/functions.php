<?php
/**
 * ESPAM PLUS theme. Design only — blocks, settings and setup live in the "espam-core" plugin.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/theme.css' );
		add_theme_support( 'responsive-embeds' );
		remove_theme_support( 'core-block-patterns' );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		$ver = wp_get_theme()->get( 'Version' );
		wp_enqueue_style( 'espam', get_theme_file_uri( 'assets/css/theme.css' ), array(), $ver );
		wp_enqueue_script( 'espam', get_theme_file_uri( 'assets/js/theme.js' ), array(), $ver, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
);

// Preload the Latin font used above the fold; flag JS early so reveal styles apply without a flash.
add_action(
	'wp_head',
	static function () {
		echo "<script>document.documentElement.classList.add('ep-js');</script>\n";
		foreach ( array( 'plus-jakarta-sans-latin-wght-normal.woff2' ) as $font ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_theme_file_uri( "assets/fonts/{$font}" ) ) );
		}
		echo '<meta name="theme-color" content="#0A65B8">' . "\n";
	},
	1
);

add_action(
	'admin_notices',
	static function () {
		if ( ! defined( 'ESPAM_VERSION' ) && current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-error"><p><strong>Tema „ESPAM PLUS"</strong> zahtijeva dodatak <strong>„ESPAM PLUS — Core"</strong>. Instalirajte ga i aktivirajte.</p></div>';
		}
	}
);

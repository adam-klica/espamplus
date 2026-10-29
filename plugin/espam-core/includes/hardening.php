<?php
/**
 * Small hardening: XML-RPC off, no author enumeration, no WP version in the head.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'xmlrpc_enabled', '__return_false' );
remove_action( 'wp_head', 'wp_generator' );

add_action(
	'template_redirect',
	static function () {
		if ( is_author() || isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}
);

add_filter(
	'rest_endpoints',
	static function ( array $endpoints ) {
		if ( ! current_user_can( 'list_users' ) ) {
			unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		}
		return $endpoints;
	}
);

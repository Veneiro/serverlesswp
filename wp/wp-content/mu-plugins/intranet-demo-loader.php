<?php
/*
Plugin Name: Intranet Demo Loader
Description: Carga automática del plugin Intranet Demo como MU plugin en ServerlessWP.
Version: 1.0.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$intranet_demo_plugin = WP_CONTENT_DIR . '/plugins/intranet-demo/intranet-demo.php';

if ( file_exists( $intranet_demo_plugin ) ) {
	require_once $intranet_demo_plugin;

	add_action(
		'init',
		function () {
			if ( get_option( 'intranet_demo_mu_initialized' ) ) {
				return;
			}

			if ( function_exists( 'intranet_demo_activate' ) ) {
				intranet_demo_activate();
				update_option( 'intranet_demo_mu_initialized', 1 );
			}
		},
		1
	);
}

<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function raiz_intranet_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
}
add_action( 'after_setup_theme', 'raiz_intranet_theme_setup' );

function raiz_intranet_enqueue_assets() {
    wp_enqueue_style(
        'raiz-intranet-fonts',
        'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Merriweather:wght@400;700&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'raiz-intranet-style',
        get_stylesheet_uri(),
        array( 'raiz-intranet-fonts' ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'raiz_intranet_enqueue_assets' );

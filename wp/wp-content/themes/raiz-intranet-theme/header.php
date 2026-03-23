<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="site-header__inner">
        <div>
            <p class="site-title">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
            </p>
            <p class="site-tagline"><?php bloginfo( 'description' ); ?></p>
        </div>
        <a class="intranet-button" href="<?php echo esc_url( home_url( '/intranet/' ) ); ?>">
            Entrar a la intranet
        </a>
    </div>
</header>

<main class="site-shell">

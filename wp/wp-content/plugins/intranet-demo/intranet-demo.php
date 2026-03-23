<?php
/*
Plugin Name: Intranet Demo
Description: Intranet privada con login propio y validacion contra usuarios existentes de WordPress.
Version: 2.0.0
Author: Custom
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function intranet_demo_activate() {
    // Funcion mantenida para compatibilidad con el cargador MU.
}

function intranet_demo_home_relative_path( $path ) {
    $home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );

    if ( ! is_string( $home_path ) || '/' === $home_path ) {
        return '/' . ltrim( $path, '/' );
    }

    $home_path = untrailingslashit( $home_path );

    if ( 0 === strpos( $path, $home_path ) ) {
        $path = substr( $path, strlen( $home_path ) );
    }

    return '/' . ltrim( (string) $path, '/' );
}

function intranet_demo_request_path() {
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    $path        = wp_parse_url( $request_uri, PHP_URL_PATH );

    if ( ! is_string( $path ) || '' === $path ) {
        return '/';
    }

    $path = intranet_demo_home_relative_path( $path );
    $path = untrailingslashit( $path );

    return '' === $path ? '/' : $path;
}

function intranet_demo_is_route( $route ) {
    return intranet_demo_request_path() === untrailingslashit( $route );
}

function intranet_demo_login_error_text( $code ) {
    $messages = array(
        'nonce'      => 'La sesion ha expirado. Vuelve a intentarlo.',
        'empty'      => 'Debes indicar usuario/correo y contrasena.',
        'no_user'    => 'No existe un usuario con ese identificador.',
        'auth'       => 'Credenciales invalidas. Verifica tus datos.',
        'logged_out' => 'Sesion cerrada correctamente.',
    );

    return isset( $messages[ $code ] ) ? $messages[ $code ] : '';
}

function intranet_demo_resolve_login_identifier( $identifier ) {
    $identifier = sanitize_text_field( (string) $identifier );

    if ( '' === $identifier ) {
        return '';
    }

    if ( is_email( $identifier ) ) {
        $user = get_user_by( 'email', $identifier );
        return $user ? $user->user_login : '';
    }

    $user = get_user_by( 'login', $identifier );
    return $user ? $user->user_login : '';
}

function intranet_demo_handle_login_post() {
    if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
        return;
    }

    if ( ! isset( $_POST['intranet_action'] ) || 'login' !== sanitize_key( wp_unslash( $_POST['intranet_action'] ) ) ) {
        return;
    }

    $nonce = isset( $_POST['intranet_nonce'] ) ? wp_unslash( $_POST['intranet_nonce'] ) : '';
    if ( ! wp_verify_nonce( $nonce, 'intranet_login' ) ) {
        wp_safe_redirect( add_query_arg( 'error', 'nonce', home_url( '/intranet/' ) ) );
        exit;
    }

    $identifier = isset( $_POST['log'] ) ? wp_unslash( $_POST['log'] ) : '';
    $password   = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '';

    if ( '' === trim( $identifier ) || '' === trim( $password ) ) {
        wp_safe_redirect( add_query_arg( 'error', 'empty', home_url( '/intranet/' ) ) );
        exit;
    }

    $user_login = intranet_demo_resolve_login_identifier( $identifier );
    if ( '' === $user_login ) {
        wp_safe_redirect( add_query_arg( 'error', 'no_user', home_url( '/intranet/' ) ) );
        exit;
    }

    $creds = array(
        'user_login'    => $user_login,
        'user_password' => $password,
        'remember'      => true,
    );

    $user = wp_signon( $creds, is_ssl() );
    if ( is_wp_error( $user ) ) {
        wp_safe_redirect( add_query_arg( 'error', 'auth', home_url( '/intranet/' ) ) );
        exit;
    }

    wp_safe_redirect( home_url( '/intranet/' ) );
    exit;
}

function intranet_demo_render_login_page() {
    status_header( 200 );
    nocache_headers();

    $error_code = isset( $_GET['error'] ) ? sanitize_key( wp_unslash( $_GET['error'] ) ) : '';
    $error_text = intranet_demo_login_error_text( $error_code );

    ?>
    <!doctype html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Intranet | <?php bloginfo( 'name' ); ?></title>
        <style>
            :root {
                --bg: #f4f0e9;
                --ink: #1c1917;
                --ink-soft: #5e5248;
                --brand: #0d5c63;
                --brand-strong: #08363a;
                --alert: #b42318;
                --line: #dccdb9;
                --panel: #fff9f1;
            }

            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                color: var(--ink);
                background: radial-gradient(circle at 5% 15%, #f8dfc0 0%, rgba(248,223,192,0) 30%), var(--bg);
                font-family: "Segoe UI", sans-serif;
                display: grid;
                place-items: center;
                padding: 1rem;
            }

            .panel {
                width: min(460px, 100%);
                background: var(--panel);
                border: 1px solid var(--line);
                border-radius: 14px;
                padding: 1.2rem;
                box-shadow: 0 14px 34px rgba(20, 17, 14, 0.12);
            }

            h1 {
                margin-top: 0;
                margin-bottom: 0.3rem;
                font-size: 1.65rem;
            }

            p.sub {
                margin-top: 0;
                color: var(--ink-soft);
            }

            label {
                display: block;
                margin: 0.75rem 0 0.3rem;
                font-weight: 600;
            }

            input {
                width: 100%;
                border: 1px solid var(--line);
                border-radius: 10px;
                padding: 0.72rem;
                font-size: 0.95rem;
            }

            button {
                margin-top: 1rem;
                width: 100%;
                border: 0;
                border-radius: 10px;
                padding: 0.75rem;
                color: #ffffff;
                background: linear-gradient(120deg, var(--brand) 0%, var(--brand-strong) 100%);
                font-weight: 700;
                cursor: pointer;
            }

            .error {
                margin-bottom: 0.75rem;
                color: var(--alert);
                font-weight: 600;
            }

            .links {
                margin-top: 0.85rem;
                font-size: 0.9rem;
                text-align: center;
            }

            .links a {
                color: var(--brand);
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <section class="panel">
            <h1>Intranet privada</h1>
            <p class="sub">Acceso exclusivo para usuarios ya registrados en la base de datos de WordPress.</p>

            <?php if ( '' !== $error_text ) : ?>
                <p class="error"><?php echo esc_html( $error_text ); ?></p>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( home_url( '/intranet/' ) ); ?>">
                <input type="hidden" name="intranet_action" value="login">
                <input type="hidden" name="intranet_nonce" value="<?php echo esc_attr( wp_create_nonce( 'intranet_login' ) ); ?>">

                <label for="log">Usuario o correo</label>
                <input id="log" name="log" type="text" autocomplete="username" required>

                <label for="pwd">Contrasena</label>
                <input id="pwd" name="pwd" type="password" autocomplete="current-password" required>

                <button type="submit">Entrar</button>
            </form>

            <p class="links">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Volver a la portada publica</a>
            </p>
        </section>
    </body>
    </html>
    <?php
}

function intranet_demo_render_dashboard_page() {
    status_header( 200 );
    nocache_headers();

    $user         = wp_get_current_user();
    $recent_posts = get_posts(
        array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 5,
        )
    );

    ?>
    <!doctype html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Panel intranet | <?php bloginfo( 'name' ); ?></title>
        <style>
            :root {
                --bg: #f4f0e9;
                --ink: #1c1917;
                --ink-soft: #5e5248;
                --line: #dccdb9;
                --panel: #fff9f1;
                --brand: #0d5c63;
            }

            * { box-sizing: border-box; }
            body {
                margin: 0;
                color: var(--ink);
                background: var(--bg);
                font-family: "Segoe UI", sans-serif;
            }

            .wrap {
                max-width: 980px;
                margin: 0 auto;
                padding: 1rem;
            }

            .top {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
                margin-bottom: 1rem;
                flex-wrap: wrap;
            }

            .chip {
                display: inline-block;
                border-radius: 999px;
                border: 1px solid var(--line);
                padding: 0.4rem 0.8rem;
                text-decoration: none;
            }

            .panel {
                background: var(--panel);
                border: 1px solid var(--line);
                border-radius: 14px;
                padding: 1rem;
                box-shadow: 0 12px 28px rgba(20, 17, 14, 0.09);
            }

            ul { margin: 0; padding-left: 1.1rem; }
            li + li { margin-top: 0.5rem; }
            small { color: var(--ink-soft); }
            a { color: var(--brand); text-decoration: none; }
        </style>
    </head>
    <body>
        <main class="wrap">
            <section class="top">
                <div>
                    <h1>Panel de intranet</h1>
                    <p>Sesion iniciada como <strong><?php echo esc_html( $user->display_name ?: $user->user_login ); ?></strong>.</p>
                </div>
                <div>
                    <a class="chip" href="<?php echo esc_url( home_url( '/' ) ); ?>">Web publica</a>
                    <a class="chip" href="<?php echo esc_url( home_url( '/intranet/logout/' ) ); ?>">Cerrar sesion</a>
                </div>
            </section>

            <section class="panel">
                <h2>Ultimas publicaciones del blog</h2>
                <?php if ( ! empty( $recent_posts ) ) : ?>
                    <ul>
                        <?php foreach ( $recent_posts as $post_item ) : ?>
                            <li>
                                <a href="<?php echo esc_url( get_permalink( $post_item ) ); ?>"><?php echo esc_html( get_the_title( $post_item ) ); ?></a>
                                <br>
                                <small><?php echo esc_html( get_the_date( '', $post_item ) ); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p>No hay publicaciones aun.</p>
                <?php endif; ?>
            </section>
        </main>
    </body>
    </html>
    <?php
}

function intranet_demo_route_request() {
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
        return;
    }

    if ( intranet_demo_is_route( '/intranet/logout' ) ) {
        wp_logout();
        wp_safe_redirect( add_query_arg( 'error', 'logged_out', home_url( '/intranet/' ) ) );
        exit;
    }

    if ( ! intranet_demo_is_route( '/intranet' ) ) {
        return;
    }

    intranet_demo_handle_login_post();

    if ( ! is_user_logged_in() ) {
        intranet_demo_render_login_page();
        exit;
    }

    intranet_demo_render_dashboard_page();
    exit;
}
add_action( 'template_redirect', 'intranet_demo_route_request', 0 );

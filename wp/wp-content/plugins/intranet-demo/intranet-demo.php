<?php
/*
Plugin Name: Intranet Demo
Description: Ejemplo pequeño de intranet para WordPress: acceso privado, anuncios internos y panel con shortcode.
Version: 1.2.0
Author: ServerlessWP Example
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function intranet_demo_get_env( $key, $default = '' ) {
	if ( defined( $key ) && '' !== constant( $key ) ) {
		return constant( $key );
	}

	if ( isset( $_ENV[ $key ] ) && '' !== $_ENV[ $key ] ) {
		return $_ENV[ $key ];
	}

	return $default;
}

function intranet_demo_microsoft_config() {
	return array(
		'tenant_id'      => (string) intranet_demo_get_env( 'INTRANET_MS_TENANT_ID', 'common' ),
		'client_id'      => (string) intranet_demo_get_env( 'INTRANET_MS_CLIENT_ID', '' ),
		'client_secret'  => (string) intranet_demo_get_env( 'INTRANET_MS_CLIENT_SECRET', '' ),
		'allowed_domain' => ltrim( strtolower( (string) intranet_demo_get_env( 'INTRANET_MS_ALLOWED_DOMAIN', '' ) ), '@' ),
	);
}

function intranet_demo_microsoft_enabled() {
	$config = intranet_demo_microsoft_config();

	return '' !== $config['client_id'] && '' !== $config['client_secret'];
}

function intranet_demo_microsoft_auth_url( $redirect_to = '' ) {
	$default_redirect = home_url( '/intranet/' );

	if ( '' === $redirect_to ) {
		$redirect_to = $default_redirect;
	}

	$redirect_to = wp_validate_redirect( $redirect_to, $default_redirect );

	return add_query_arg(
		array(
			'action'      => 'intranet_ms_auth',
			'redirect_to' => $redirect_to,
		),
		admin_url( 'admin-post.php' )
	);
}

function intranet_demo_get_request_path() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$path        = wp_parse_url( $request_uri, PHP_URL_PATH );

	if ( ! is_string( $path ) || '' === $path ) {
		return '/';
	}

	$path = untrailingslashit( $path );

	return '' === $path ? '/' : $path;
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

	return '/' . ltrim( $path, '/' );
}

function intranet_demo_is_intranet_request() {
	$request_path  = intranet_demo_get_request_path();
	$intranet_path = wp_parse_url( home_url( '/intranet/' ), PHP_URL_PATH );

	if ( ! is_string( $intranet_path ) || '' === $intranet_path ) {
		$intranet_path = '/intranet';
	}

	$intranet_path = untrailingslashit( $intranet_path );

	if ( '' === $intranet_path ) {
		$intranet_path = '/intranet';
	}

	return $request_path === $intranet_path || 0 === strpos( $request_path, $intranet_path . '/' );
}

function intranet_demo_top_button() {
	if ( is_admin() || ! is_front_page() ) {
		return;
	}

	$GLOBALS['intranet_demo_top_button_rendered'] = true;

	echo intranet_demo_get_top_button_markup();
}
add_action( 'wp_body_open', 'intranet_demo_top_button', 1 );

function intranet_demo_get_top_button_markup() {
	$intranet_url = home_url( '/intranet/' );

	ob_start();
	?>
	<div class="intranet-topbar">
		<div class="intranet-topbar__inner">
			<p class="intranet-topbar__title"><?php esc_html_e( 'Portal universitario', 'intranet-demo' ); ?></p>
			<a class="intranet-topbar__button" href="<?php echo esc_url( $intranet_url ); ?>">
				<?php esc_html_e( 'Entrar a la intranet', 'intranet-demo' ); ?>
			</a>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

function intranet_demo_frontpage_top_button_fallback( $content ) {
	if ( is_admin() || ! is_front_page() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	if ( ! empty( $GLOBALS['intranet_demo_top_button_rendered'] ) ) {
		return $content;
	}

	return intranet_demo_get_top_button_markup() . $content;
}
add_filter( 'the_content', 'intranet_demo_frontpage_top_button_fallback', 5 );

function intranet_demo_enqueue_styles() {
	if ( is_admin() ) {
		return;
	}

	if ( ! is_front_page() && ! intranet_demo_is_intranet_request() ) {
		return;
	}

	wp_register_style( 'intranet-demo-styles', false, array(), '1.2.0' );
	wp_enqueue_style( 'intranet-demo-styles' );

	$css = '
	.intranet-topbar {
		margin-bottom: 16px;
		padding: 12px 16px;
		border-bottom: 1px solid #e2e4e7;
	}
	.intranet-topbar__inner {
		max-width: 980px;
		margin: 0 auto;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		flex-wrap: wrap;
	}
	.intranet-topbar__title {
		margin: 0;
		font-size: 1rem;
		font-weight: 600;
	}
	.intranet-topbar__button {
		display: inline-block;
		padding: 8px 14px;
		border: 1px solid currentColor;
		border-radius: 6px;
		text-decoration: none;
		font-weight: 600;
	}
	.intranet-dashboard {
		max-width: 980px;
		margin: 24px auto;
		padding: 0 16px;
	}
	.intranet-dashboard__header {
		margin-bottom: 16px;
	}
	.intranet-dashboard__header h2 {
		margin-bottom: 6px;
	}
	.intranet-dashboard__grid {
		display: grid;
		grid-template-columns: 2fr 1fr;
		gap: 16px;
	}
	.intranet-card {
		padding: 16px;
		border: 1px solid #e2e4e7;
		border-radius: 8px;
	}
	.intranet-card h3 {
		margin-top: 0;
		margin-bottom: 12px;
	}
	.intranet-announcements {
		list-style: none;
		margin: 0;
		padding: 0;
		display: grid;
		gap: 12px;
	}
	.intranet-announcement {
		padding-bottom: 12px;
		border-bottom: 1px solid #f0f0f1;
	}
	.intranet-announcement:last-child {
		padding-bottom: 0;
		border-bottom: none;
	}
	.intranet-announcement__meta {
		margin: 4px 0 8px;
		font-size: 0.875rem;
		opacity: 0.8;
	}
	.intranet-links {
		list-style: none;
		margin: 0;
		padding: 0;
		display: grid;
		gap: 8px;
	}
	.intranet-links a {
		text-decoration: none;
	}
	@media (max-width: 782px) {
		.intranet-dashboard__grid {
			grid-template-columns: 1fr;
		}
	}
	';

	wp_add_inline_style( 'intranet-demo-styles', $css );
}
add_action( 'wp_enqueue_scripts', 'intranet_demo_enqueue_styles' );

function intranet_demo_register_post_type() {
	$labels = array(
		'name'               => __( 'Anuncios', 'intranet-demo' ),
		'singular_name'      => __( 'Anuncio', 'intranet-demo' ),
		'add_new'            => __( 'Añadir anuncio', 'intranet-demo' ),
		'add_new_item'       => __( 'Añadir nuevo anuncio', 'intranet-demo' ),
		'edit_item'          => __( 'Editar anuncio', 'intranet-demo' ),
		'new_item'           => __( 'Nuevo anuncio', 'intranet-demo' ),
		'view_item'          => __( 'Ver anuncio', 'intranet-demo' ),
		'search_items'       => __( 'Buscar anuncios', 'intranet-demo' ),
		'not_found'          => __( 'No hay anuncios', 'intranet-demo' ),
		'not_found_in_trash' => __( 'No hay anuncios en la papelera', 'intranet-demo' ),
		'menu_name'          => __( 'Anuncios intranet', 'intranet-demo' ),
	);

	register_post_type(
		'intranet_announcement',
		array(
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 25,
			'menu_icon'       => 'dashicons-megaphone',
			'has_archive'     => false,
			'rewrite'         => false,
			'show_in_rest'    => true,
			'supports'        => array( 'title', 'editor', 'author', 'excerpt' ),
			'capability_type' => 'post',
		)
	);
}
add_action( 'init', 'intranet_demo_register_post_type' );

function intranet_demo_activate() {
	add_role(
		'intranet_employee',
		__( 'Empleado intranet', 'intranet-demo' ),
		array(
			'read' => true,
		)
	);

	intranet_demo_register_post_type();

	$page = get_page_by_path( 'intranet' );
	if ( $page && isset( $page->ID ) ) {
		update_option( 'intranet_demo_page_id', (int) $page->ID );
	} else {
		$page_id = wp_insert_post(
			array(
				'post_title'   => 'Intranet',
				'post_name'    => 'intranet',
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '[intranet_dashboard]',
			)
		);

		if ( ! is_wp_error( $page_id ) ) {
			update_option( 'intranet_demo_page_id', (int) $page_id );
		}
	}

	$existing = get_posts(
		array(
			'post_type'      => 'intranet_announcement',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		)
	);

	if ( empty( $existing ) ) {
		wp_insert_post(
			array(
				'post_type'    => 'intranet_announcement',
				'post_status'  => 'publish',
				'post_title'   => 'Bienvenido a la intranet',
				'post_content' => 'Este es un anuncio de ejemplo. Puedes editarlo o crear nuevos desde el menú "Anuncios intranet".',
			)
		);
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'intranet_demo_activate' );

function intranet_demo_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'intranet_demo_deactivate' );

function intranet_demo_require_login() {
	if ( is_user_logged_in() || is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	if ( ! intranet_demo_is_intranet_request() ) {
		return;
	}

	$request_path = intranet_demo_get_request_path();
	$redirect_to  = home_url( intranet_demo_home_relative_path( $request_path ) );

	if ( ! intranet_demo_microsoft_enabled() ) {
		wp_die(
			esc_html__( 'La intranet requiere Microsoft SSO. Configura INTRANET_MS_CLIENT_ID e INTRANET_MS_CLIENT_SECRET.', 'intranet-demo' ),
			esc_html__( 'Microsoft SSO no configurado', 'intranet-demo' ),
			array( 'response' => 503 )
		);
	}

	wp_safe_redirect( intranet_demo_microsoft_auth_url( $redirect_to ) );
	exit;
}
add_action( 'template_redirect', 'intranet_demo_require_login', 0 );

function intranet_demo_render_dashboard() {
	if ( ! is_user_logged_in() ) {
		return '<p>Necesitas iniciar sesión para ver la intranet.</p>';
	}

	$current_user  = wp_get_current_user();
	$announcements = get_posts(
		array(
			'post_type'      => 'intranet_announcement',
			'post_status'    => 'publish',
			'posts_per_page' => 5,
		)
	);

	ob_start();
	?>
	<section class="intranet-dashboard">
		<header class="intranet-dashboard__header">
			<h2><?php echo esc_html( sprintf( __( 'Hola, %s', 'intranet-demo' ), $current_user->display_name ) ); ?></h2>
			<p><?php esc_html_e( 'Bienvenido al panel interno de la universidad.', 'intranet-demo' ); ?></p>
		</header>

		<div class="intranet-dashboard__grid">
			<article class="intranet-card">
				<h3><?php esc_html_e( 'Últimos anuncios', 'intranet-demo' ); ?></h3>
				<?php if ( ! empty( $announcements ) ) : ?>
					<ul class="intranet-announcements">
						<?php foreach ( $announcements as $announcement ) : ?>
							<li class="intranet-announcement">
								<strong><?php echo esc_html( get_the_title( $announcement ) ); ?></strong>
								<p class="intranet-announcement__meta"><?php echo esc_html( get_the_date( get_option( 'date_format' ), $announcement ) ); ?></p>
								<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $announcement->post_content ), 30 ) ); ?></p>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p><?php esc_html_e( 'Todavía no hay anuncios.', 'intranet-demo' ); ?></p>
				<?php endif; ?>
			</article>

			<aside class="intranet-card">
				<h3><?php esc_html_e( 'Accesos rápidos', 'intranet-demo' ); ?></h3>
				<ul class="intranet-links">
					<li><a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>"><?php esc_html_e( 'Mi perfil', 'intranet-demo' ); ?></a></li>
					<?php if ( current_user_can( 'edit_posts' ) ) : ?>
						<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=intranet_announcement' ) ); ?>"><?php esc_html_e( 'Gestionar anuncios', 'intranet-demo' ); ?></a></li>
					<?php endif; ?>
					<li><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Cerrar sesión', 'intranet-demo' ); ?></a></li>
				</ul>
				<?php if ( intranet_demo_microsoft_enabled() ) : ?>
					<p class="intranet-announcement__meta"><?php esc_html_e( 'Acceso protegido con Microsoft.', 'intranet-demo' ); ?></p>
				<?php endif; ?>
			</aside>
		</div>
	</section>
	<?php

	return ob_get_clean();
}
add_shortcode( 'intranet_dashboard', 'intranet_demo_render_dashboard' );

function intranet_demo_microsoft_login_message( $message ) {
	if ( ! intranet_demo_microsoft_enabled() ) {
		return $message;
	}

	$redirect_to = home_url( '/intranet/' );
	if ( isset( $_GET['redirect_to'] ) ) {
		$redirect_to = wp_validate_redirect( wp_unslash( $_GET['redirect_to'] ), $redirect_to );
	}

	$auth_url = intranet_demo_microsoft_auth_url( $redirect_to );

	if ( isset( $_GET['intranet_ms_error'] ) ) {
		$error_message = sanitize_text_field( wp_unslash( $_GET['intranet_ms_error'] ) );
		$message      .= '<p class="message">' . esc_html( $error_message ) . '</p>';
	}

	$message .= '<p class="message">' . esc_html__( 'Este acceso usa Microsoft. Inicia con tu cuenta universitaria.', 'intranet-demo' ) . '</p>';
	$message .= '<p class="intranet-ms-login">';
	$message .= '<a class="button button-primary button-large" href="' . esc_url( $auth_url ) . '">';
	$message .= esc_html__( 'Iniciar sesión con Microsoft (universidad)', 'intranet-demo' );
	$message .= '</a>';
	$message .= '</p>';

	return $message;
}
add_filter( 'login_message', 'intranet_demo_microsoft_login_message' );

function intranet_demo_login_styles() {
	if ( ! intranet_demo_microsoft_enabled() ) {
		return;
	}

	echo '<style>#loginform,#nav{display:none}.intranet-ms-login{text-align:center;margin-top:18px}.intranet-ms-login .button{width:100%;max-width:340px}</style>';
}
add_action( 'login_head', 'intranet_demo_login_styles' );

function intranet_demo_force_microsoft_login() {
	if ( is_user_logged_in() || ! intranet_demo_microsoft_enabled() ) {
		return;
	}

	if ( isset( $_GET['intranet_ms_error'] ) ) {
		return;
	}

	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login';

	if ( in_array( $action, array( 'logout', 'lostpassword', 'retrievepassword', 'rp', 'resetpass', 'postpass', 'confirmaction' ), true ) ) {
		return;
	}

	$redirect_to = home_url( '/intranet/' );
	if ( isset( $_REQUEST['redirect_to'] ) ) {
		$redirect_to = wp_validate_redirect( wp_unslash( $_REQUEST['redirect_to'] ), $redirect_to );
	}

	wp_safe_redirect( intranet_demo_microsoft_auth_url( $redirect_to ) );
	exit;
}
add_action( 'login_init', 'intranet_demo_force_microsoft_login', 1 );

function intranet_demo_ms_auth_start() {
	if ( ! intranet_demo_microsoft_enabled() ) {
		wp_die(
			esc_html__( 'Microsoft SSO no está configurado todavía para esta intranet.', 'intranet-demo' ),
			esc_html__( 'Microsoft SSO no configurado', 'intranet-demo' ),
			array( 'response' => 503 )
		);
	}

	$redirect_to = home_url( '/intranet/' );
	if ( isset( $_REQUEST['redirect_to'] ) ) {
		$redirect_to = wp_validate_redirect( wp_unslash( $_REQUEST['redirect_to'] ), $redirect_to );
	}

	if ( is_user_logged_in() ) {
		wp_safe_redirect( $redirect_to );
		exit;
	}

	$config      = intranet_demo_microsoft_config();
	$callback    = admin_url( 'admin-post.php?action=intranet_ms_callback' );
	$state       = wp_generate_password( 32, false, false );
	$state_key   = 'intranet_ms_state_' . $state;
	$state_value = wp_json_encode(
		array(
			'redirect_to' => $redirect_to,
		)
	);

	set_transient( $state_key, $state_value, 10 * MINUTE_IN_SECONDS );

	$authorize_url = add_query_arg(
		array(
			'client_id'     => $config['client_id'],
			'response_type' => 'code',
			'redirect_uri'  => $callback,
			'response_mode' => 'query',
			'scope'         => 'openid profile email User.Read',
			'state'         => $state,
			'prompt'        => 'select_account',
		),
		'https://login.microsoftonline.com/' . rawurlencode( $config['tenant_id'] ) . '/oauth2/v2.0/authorize'
	);

	wp_safe_redirect( $authorize_url );
	exit;
}
add_action( 'admin_post_nopriv_intranet_ms_auth', 'intranet_demo_ms_auth_start' );
add_action( 'admin_post_intranet_ms_auth', 'intranet_demo_ms_auth_start' );

function intranet_demo_ms_error_redirect( $message ) {
	$login_url = wp_login_url( home_url( '/intranet/' ) );
	$login_url = add_query_arg( 'intranet_ms_error', $message, $login_url );

	wp_safe_redirect( $login_url );
	exit;
}

function intranet_demo_base64url_decode( $value ) {
	$remainder = strlen( $value ) % 4;

	if ( 0 !== $remainder ) {
		$value .= str_repeat( '=', 4 - $remainder );
	}

	return base64_decode( strtr( $value, '-_', '+/' ) );
}

function intranet_demo_decode_id_token_claims( $id_token ) {
	$parts = explode( '.', (string) $id_token );

	if ( count( $parts ) < 2 ) {
		return array();
	}

	$payload = intranet_demo_base64url_decode( $parts[1] );
	$claims  = json_decode( $payload, true );

	return is_array( $claims ) ? $claims : array();
}

function intranet_demo_extract_profile_from_microsoft( $token_data ) {
	$profile = array(
		'email'        => '',
		'display_name' => '',
		'first_name'   => '',
		'last_name'    => '',
	);

	if ( ! empty( $token_data['id_token'] ) ) {
		$claims = intranet_demo_decode_id_token_claims( $token_data['id_token'] );

		if ( ! empty( $claims['email'] ) && is_email( $claims['email'] ) ) {
			$profile['email'] = strtolower( $claims['email'] );
		} elseif ( ! empty( $claims['preferred_username'] ) && is_email( $claims['preferred_username'] ) ) {
			$profile['email'] = strtolower( $claims['preferred_username'] );
		} elseif ( ! empty( $claims['upn'] ) && is_email( $claims['upn'] ) ) {
			$profile['email'] = strtolower( $claims['upn'] );
		}

		if ( ! empty( $claims['name'] ) ) {
			$profile['display_name'] = sanitize_text_field( $claims['name'] );
		}

		if ( ! empty( $claims['given_name'] ) ) {
			$profile['first_name'] = sanitize_text_field( $claims['given_name'] );
		}

		if ( ! empty( $claims['family_name'] ) ) {
			$profile['last_name'] = sanitize_text_field( $claims['family_name'] );
		}
	}

	if ( '' === $profile['email'] && ! empty( $token_data['access_token'] ) ) {
		$graph_response = wp_remote_get(
			'https://graph.microsoft.com/v1.0/me?$select=mail,userPrincipalName,displayName,givenName,surname',
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token_data['access_token'],
				),
			)
		);

		if ( ! is_wp_error( $graph_response ) ) {
			$graph_data = json_decode( wp_remote_retrieve_body( $graph_response ), true );

			if ( is_array( $graph_data ) ) {
				if ( ! empty( $graph_data['mail'] ) && is_email( $graph_data['mail'] ) ) {
					$profile['email'] = strtolower( $graph_data['mail'] );
				} elseif ( ! empty( $graph_data['userPrincipalName'] ) && is_email( $graph_data['userPrincipalName'] ) ) {
					$profile['email'] = strtolower( $graph_data['userPrincipalName'] );
				}

				if ( '' === $profile['display_name'] && ! empty( $graph_data['displayName'] ) ) {
					$profile['display_name'] = sanitize_text_field( $graph_data['displayName'] );
				}

				if ( '' === $profile['first_name'] && ! empty( $graph_data['givenName'] ) ) {
					$profile['first_name'] = sanitize_text_field( $graph_data['givenName'] );
				}

				if ( '' === $profile['last_name'] && ! empty( $graph_data['surname'] ) ) {
					$profile['last_name'] = sanitize_text_field( $graph_data['surname'] );
				}
			}
		}
	}

	return $profile;
}

function intranet_demo_create_or_get_user( $profile ) {
	$email = strtolower( (string) $profile['email'] );

	if ( ! is_email( $email ) ) {
		return null;
	}

	$user = get_user_by( 'email', $email );

	if ( ! $user ) {
		$base_username = sanitize_user( strstr( $email, '@', true ), true );

		if ( '' === $base_username ) {
			$base_username = 'intranetuser';
		}

		$username = $base_username;
		$counter  = 1;

		while ( username_exists( $username ) ) {
			$username = $base_username . $counter;
			++$counter;
		}

		$user_id = wp_create_user( $username, wp_generate_password( 32, true, true ), $email );

		if ( is_wp_error( $user_id ) ) {
			return null;
		}

		if ( get_role( 'intranet_employee' ) ) {
			$user_obj = new WP_User( $user_id );
			$user_obj->set_role( 'intranet_employee' );
		}

		$user = get_user_by( 'id', $user_id );
	}

	if ( ! $user ) {
		return null;
	}

	$update = array(
		'ID' => $user->ID,
	);

	if ( ! empty( $profile['display_name'] ) ) {
		$update['display_name'] = $profile['display_name'];
	}

	if ( ! empty( $profile['first_name'] ) ) {
		$update['first_name'] = $profile['first_name'];
	}

	if ( ! empty( $profile['last_name'] ) ) {
		$update['last_name'] = $profile['last_name'];
	}

	if ( count( $update ) > 1 ) {
		wp_update_user( $update );
	}

	return get_user_by( 'id', $user->ID );
}

function intranet_demo_ms_auth_callback() {
	if ( ! intranet_demo_microsoft_enabled() ) {
		intranet_demo_ms_error_redirect( __( 'Configura Microsoft SSO para continuar.', 'intranet-demo' ) );
	}

	if ( isset( $_GET['error'] ) ) {
		intranet_demo_ms_error_redirect( __( 'Microsoft rechazó el acceso.', 'intranet-demo' ) );
	}

	$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
	$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

	if ( '' === $code || '' === $state ) {
		intranet_demo_ms_error_redirect( __( 'Respuesta de autenticación inválida.', 'intranet-demo' ) );
	}

	$state_data = get_transient( 'intranet_ms_state_' . $state );
	delete_transient( 'intranet_ms_state_' . $state );

	if ( ! $state_data ) {
		intranet_demo_ms_error_redirect( __( 'La sesión de inicio expiró. Intenta de nuevo.', 'intranet-demo' ) );
	}

	$state_data  = json_decode( $state_data, true );
	$redirect_to = home_url( '/intranet/' );

	if ( is_array( $state_data ) && ! empty( $state_data['redirect_to'] ) ) {
		$redirect_to = wp_validate_redirect( $state_data['redirect_to'], $redirect_to );
	}

	$config       = intranet_demo_microsoft_config();
	$callback_url = admin_url( 'admin-post.php?action=intranet_ms_callback' );
	$token_url    = 'https://login.microsoftonline.com/' . rawurlencode( $config['tenant_id'] ) . '/oauth2/v2.0/token';

	$response = wp_remote_post(
		$token_url,
		array(
			'timeout' => 20,
			'body'    => array(
				'client_id'     => $config['client_id'],
				'client_secret' => $config['client_secret'],
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'redirect_uri'  => $callback_url,
				'scope'         => 'openid profile email User.Read',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		intranet_demo_ms_error_redirect( __( 'No se pudo conectar con Microsoft.', 'intranet-demo' ) );
	}

	$token_data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $token_data ) || empty( $token_data['access_token'] ) ) {
		intranet_demo_ms_error_redirect( __( 'No fue posible validar la cuenta Microsoft.', 'intranet-demo' ) );
	}

	$profile = intranet_demo_extract_profile_from_microsoft( $token_data );

	if ( empty( $profile['email'] ) || ! is_email( $profile['email'] ) ) {
		intranet_demo_ms_error_redirect( __( 'No se encontró un correo válido en tu cuenta Microsoft.', 'intranet-demo' ) );
	}

	if ( '' !== $config['allowed_domain'] ) {
		$required_suffix = '@' . strtolower( $config['allowed_domain'] );
		$email           = strtolower( (string) $profile['email'] );

		if ( ! str_ends_with( $email, $required_suffix ) ) {
			intranet_demo_ms_error_redirect( __( 'Solo se permiten cuentas de tu dominio universitario.', 'intranet-demo' ) );
		}
	}

	$user = intranet_demo_create_or_get_user( $profile );

	if ( ! $user ) {
		intranet_demo_ms_error_redirect( __( 'No se pudo iniciar sesión en WordPress.', 'intranet-demo' ) );
	}

	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true );
	do_action( 'wp_login', $user->user_login, $user );

	wp_safe_redirect( $redirect_to );
	exit;
}
add_action( 'admin_post_nopriv_intranet_ms_callback', 'intranet_demo_ms_auth_callback' );
add_action( 'admin_post_intranet_ms_callback', 'intranet_demo_ms_auth_callback' );

function intranet_demo_activation_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( ! get_option( 'intranet_demo_page_id' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'plugins' !== $screen->id ) {
		return;
	}

	$page_id = (int) get_option( 'intranet_demo_page_id' );
	$page_url = $page_id ? get_permalink( $page_id ) : home_url( '/intranet/' );
	?>
	<div class="notice notice-success is-dismissible">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					__( 'Intranet Demo activa. Página principal: <a href="%s" target="_blank" rel="noopener">ver intranet</a>.', 'intranet-demo' ),
					esc_url( $page_url )
				)
			);
			?>
		</p>
	</div>
	<?php

	delete_option( 'intranet_demo_page_id' );
}
add_action( 'admin_notices', 'intranet_demo_activation_notice' );

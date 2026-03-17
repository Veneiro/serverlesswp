<?php
/*
Plugin Name: Intranet Demo
Description: Ejemplo pequeño de intranet para WordPress: acceso privado, anuncios internos y panel con shortcode.
Version: 1.0.0
Author: ServerlessWP Example
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

	$allowed_paths = array(
		'/wp-login.php',
		'/wp-register.php',
		'/wp-cron.php',
		'/xmlrpc.php',
		'/wp-json/',
	);

	foreach ( $allowed_paths as $path ) {
		if ( false !== strpos( $request_uri, $path ) ) {
			return;
		}
	}

	$redirect_to = home_url( $request_uri );
	wp_safe_redirect( wp_login_url( $redirect_to ) );
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
		<h2><?php echo esc_html( sprintf( __( 'Hola, %s', 'intranet-demo' ), $current_user->display_name ) ); ?></h2>
		<p><?php esc_html_e( 'Este es un panel interno básico para compartir información del equipo.', 'intranet-demo' ); ?></p>

		<h3><?php esc_html_e( 'Últimos anuncios', 'intranet-demo' ); ?></h3>
		<?php if ( ! empty( $announcements ) ) : ?>
			<ul>
				<?php foreach ( $announcements as $announcement ) : ?>
					<li>
						<strong><?php echo esc_html( get_the_title( $announcement ) ); ?></strong><br />
						<?php echo esc_html( wp_trim_words( wp_strip_all_tags( $announcement->post_content ), 24 ) ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p><?php esc_html_e( 'Todavía no hay anuncios.', 'intranet-demo' ); ?></p>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Accesos rápidos', 'intranet-demo' ); ?></h3>
		<ul>
			<li><a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>"><?php esc_html_e( 'Mi perfil', 'intranet-demo' ); ?></a></li>
			<?php if ( current_user_can( 'edit_posts' ) ) : ?>
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=intranet_announcement' ) ); ?>"><?php esc_html_e( 'Gestionar anuncios', 'intranet-demo' ); ?></a></li>
			<?php endif; ?>
			<li><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Cerrar sesión', 'intranet-demo' ); ?></a></li>
		</ul>
	</section>
	<?php

	return ob_get_clean();
}
add_shortcode( 'intranet_dashboard', 'intranet_demo_render_dashboard' );

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

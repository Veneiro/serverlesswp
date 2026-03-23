<?php
/*
Plugin Name: Intranet Demo
Description: Intranet privada con login propio, secciones por departamentos y planner de equipo.
Version: 3.0.0
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

function intranet_demo_notice_text( $code ) {
    $messages = array(
        'dept_saved'    => 'Registro de departamento guardado.',
        'dept_deleted'  => 'Registro de departamento eliminado.',
        'task_saved'    => 'Tarea del planner guardada.',
        'task_updated'  => 'Estado de tarea actualizado.',
        'task_deleted'  => 'Tarea eliminada.',
    );

    return isset( $messages[ $code ] ) ? $messages[ $code ] : '';
}

function intranet_demo_departments() {
    return array(
        'electrica' => 'Electrica',
        'mecanica'  => 'Mecanica',
        'gestion'   => 'Gestion',
    );
}

function intranet_demo_task_statuses() {
    return array(
        'todo'  => 'Pendiente',
        'doing' => 'En curso',
        'done'  => 'Completada',
    );
}

function intranet_demo_options_department_items() {
    $items = get_option( 'intranet_demo_department_items', array() );
    return is_array( $items ) ? $items : array();
}

function intranet_demo_options_tasks() {
    $tasks = get_option( 'intranet_demo_planner_tasks', array() );
    return is_array( $tasks ) ? $tasks : array();
}

function intranet_demo_save_department_items( $items ) {
    update_option( 'intranet_demo_department_items', array_values( $items ), false );
}

function intranet_demo_save_tasks( $tasks ) {
    update_option( 'intranet_demo_planner_tasks', array_values( $tasks ), false );
}

function intranet_demo_next_id( $records ) {
    $max_id = 0;

    foreach ( $records as $record ) {
        $record_id = isset( $record['id'] ) ? (int) $record['id'] : 0;
        if ( $record_id > $max_id ) {
            $max_id = $record_id;
        }
    }

    return $max_id + 1;
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

function intranet_demo_get_current_tab() {
    $allowed_tabs = array( 'resumen', 'departamentos', 'planner' );
    $tab          = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'resumen';

    return in_array( $tab, $allowed_tabs, true ) ? $tab : 'resumen';
}

function intranet_demo_handle_department_post() {
    $nonce = isset( $_POST['intranet_nonce'] ) ? wp_unslash( $_POST['intranet_nonce'] ) : '';
    if ( ! wp_verify_nonce( $nonce, 'intranet_dashboard' ) ) {
        return;
    }

    $action      = isset( $_POST['intranet_action'] ) ? sanitize_key( wp_unslash( $_POST['intranet_action'] ) ) : '';
    $departments = intranet_demo_departments();

    if ( 'save_department_item' === $action ) {
        $department = isset( $_POST['department'] ) ? sanitize_key( wp_unslash( $_POST['department'] ) ) : '';
        $entry_type = isset( $_POST['entry_type'] ) ? sanitize_key( wp_unslash( $_POST['entry_type'] ) ) : '';
        $title      = isset( $_POST['entry_title'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_title'] ) ) : '';
        $details    = isset( $_POST['entry_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['entry_details'] ) ) : '';

        if ( isset( $departments[ $department ] ) && '' !== $title ) {
            $items   = intranet_demo_options_department_items();
            $items[] = array(
                'id'           => intranet_demo_next_id( $items ),
                'department'   => $department,
                'entry_type'   => 'electrica' === $department && in_array( $entry_type, array( 'alta', 'baja' ), true ) ? $entry_type : '',
                'entry_title'  => $title,
                'entry_details'=> $details,
                'created_by'   => get_current_user_id(),
                'created_at'   => current_time( 'mysql' ),
            );
            intranet_demo_save_department_items( $items );
        }

        wp_safe_redirect( add_query_arg( array( 'tab' => 'departamentos', 'msg' => 'dept_saved' ), home_url( '/intranet/' ) ) );
        exit;
    }

    if ( 'delete_department_item' === $action ) {
        $item_id = isset( $_POST['item_id'] ) ? (int) wp_unslash( $_POST['item_id'] ) : 0;

        if ( $item_id > 0 ) {
            $items = intranet_demo_options_department_items();
            $items = array_values(
                array_filter(
                    $items,
                    function ( $item ) use ( $item_id ) {
                        return (int) ( $item['id'] ?? 0 ) !== $item_id;
                    }
                )
            );
            intranet_demo_save_department_items( $items );
        }

        wp_safe_redirect( add_query_arg( array( 'tab' => 'departamentos', 'msg' => 'dept_deleted' ), home_url( '/intranet/' ) ) );
        exit;
    }
}

function intranet_demo_handle_planner_post() {
    $nonce = isset( $_POST['intranet_nonce'] ) ? wp_unslash( $_POST['intranet_nonce'] ) : '';
    if ( ! wp_verify_nonce( $nonce, 'intranet_dashboard' ) ) {
        return;
    }

    $action      = isset( $_POST['intranet_action'] ) ? sanitize_key( wp_unslash( $_POST['intranet_action'] ) ) : '';
    $statuses    = intranet_demo_task_statuses();
    $departments = intranet_demo_departments();

    if ( 'save_task' === $action ) {
        $title       = isset( $_POST['task_title'] ) ? sanitize_text_field( wp_unslash( $_POST['task_title'] ) ) : '';
        $details     = isset( $_POST['task_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['task_details'] ) ) : '';
        $department  = isset( $_POST['task_department'] ) ? sanitize_key( wp_unslash( $_POST['task_department'] ) ) : '';
        $status      = isset( $_POST['task_status'] ) ? sanitize_key( wp_unslash( $_POST['task_status'] ) ) : 'todo';
        $assignee_id = isset( $_POST['task_assignee_id'] ) ? (int) wp_unslash( $_POST['task_assignee_id'] ) : 0;
        $due_date    = isset( $_POST['task_due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['task_due_date'] ) ) : '';

        if ( '' !== $title && isset( $departments[ $department ] ) && isset( $statuses[ $status ] ) ) {
            $tasks   = intranet_demo_options_tasks();
            $tasks[] = array(
                'id'           => intranet_demo_next_id( $tasks ),
                'task_title'   => $title,
                'task_details' => $details,
                'department'   => $department,
                'status'       => $status,
                'assignee_id'  => $assignee_id,
                'due_date'     => preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', $due_date ) ? $due_date : '',
                'created_by'   => get_current_user_id(),
                'created_at'   => current_time( 'mysql' ),
            );
            intranet_demo_save_tasks( $tasks );
        }

        wp_safe_redirect( add_query_arg( array( 'tab' => 'planner', 'msg' => 'task_saved' ), home_url( '/intranet/' ) ) );
        exit;
    }

    if ( 'update_task_status' === $action ) {
        $task_id    = isset( $_POST['task_id'] ) ? (int) wp_unslash( $_POST['task_id'] ) : 0;
        $new_status = isset( $_POST['new_status'] ) ? sanitize_key( wp_unslash( $_POST['new_status'] ) ) : '';

        if ( $task_id > 0 && isset( $statuses[ $new_status ] ) ) {
            $tasks = intranet_demo_options_tasks();

            foreach ( $tasks as $idx => $task ) {
                if ( (int) ( $task['id'] ?? 0 ) === $task_id ) {
                    $tasks[ $idx ]['status'] = $new_status;
                    break;
                }
            }

            intranet_demo_save_tasks( $tasks );
        }

        wp_safe_redirect( add_query_arg( array( 'tab' => 'planner', 'msg' => 'task_updated' ), home_url( '/intranet/' ) ) );
        exit;
    }

    if ( 'delete_task' === $action ) {
        $task_id = isset( $_POST['task_id'] ) ? (int) wp_unslash( $_POST['task_id'] ) : 0;

        if ( $task_id > 0 ) {
            $tasks = intranet_demo_options_tasks();
            $tasks = array_values(
                array_filter(
                    $tasks,
                    function ( $task ) use ( $task_id ) {
                        return (int) ( $task['id'] ?? 0 ) !== $task_id;
                    }
                )
            );
            intranet_demo_save_tasks( $tasks );
        }

        wp_safe_redirect( add_query_arg( array( 'tab' => 'planner', 'msg' => 'task_deleted' ), home_url( '/intranet/' ) ) );
        exit;
    }
}

function intranet_demo_handle_dashboard_post() {
    if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
        return;
    }

    if ( ! isset( $_POST['intranet_action'] ) ) {
        return;
    }

    $action = sanitize_key( wp_unslash( $_POST['intranet_action'] ) );

    if ( in_array( $action, array( 'save_department_item', 'delete_department_item' ), true ) ) {
        intranet_demo_handle_department_post();
    }

    if ( in_array( $action, array( 'save_task', 'update_task_status', 'delete_task' ), true ) ) {
        intranet_demo_handle_planner_post();
    }
}

function intranet_demo_render_notice() {
    $msg  = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : '';
    $text = intranet_demo_notice_text( $msg );

    if ( '' === $text ) {
        return;
    }

    echo '<p class="flash">' . esc_html( $text ) . '</p>';
}

function intranet_demo_render_department_columns( $items, $department ) {
    $filtered = array_values(
        array_filter(
            $items,
            function ( $item ) use ( $department ) {
                return ( $item['department'] ?? '' ) === $department;
            }
        )
    );

    if ( 'electrica' === $department ) {
        $altas = array_values(
            array_filter(
                $filtered,
                function ( $item ) {
                    return ( $item['entry_type'] ?? '' ) === 'alta';
                }
            )
        );

        $bajas = array_values(
            array_filter(
                $filtered,
                function ( $item ) {
                    return ( $item['entry_type'] ?? '' ) === 'baja';
                }
            )
        );

        ?>
        <div class="dept-grid dept-grid--double">
            <section class="panel-inner">
                <h4>Altas</h4>
                <?php intranet_demo_render_department_list( $altas ); ?>
            </section>
            <section class="panel-inner">
                <h4>Bajas</h4>
                <?php intranet_demo_render_department_list( $bajas ); ?>
            </section>
        </div>
        <?php

        return;
    }

    ?>
    <section class="panel-inner">
        <?php intranet_demo_render_department_list( $filtered ); ?>
    </section>
    <?php
}

function intranet_demo_render_department_list( $items ) {
    if ( empty( $items ) ) {
        echo '<p>No hay registros todavia.</p>';
        return;
    }

    foreach ( $items as $item ) {
        $created_by = isset( $item['created_by'] ) ? get_user_by( 'id', (int) $item['created_by'] ) : false;
        ?>
        <article class="item-card">
            <h5><?php echo esc_html( $item['entry_title'] ?? '' ); ?></h5>
            <?php if ( ! empty( $item['entry_details'] ) ) : ?>
                <p><?php echo esc_html( $item['entry_details'] ); ?></p>
            <?php endif; ?>
            <small>
                Creado por <?php echo esc_html( $created_by ? $created_by->display_name : 'Usuario' ); ?>
                el <?php echo esc_html( mysql2date( 'd/m/Y H:i', $item['created_at'] ?? current_time( 'mysql' ) ) ); ?>
            </small>
            <form method="post" action="<?php echo esc_url( add_query_arg( array( 'tab' => 'departamentos' ), home_url( '/intranet/' ) ) ); ?>">
                <input type="hidden" name="intranet_action" value="delete_department_item">
                <input type="hidden" name="intranet_nonce" value="<?php echo esc_attr( wp_create_nonce( 'intranet_dashboard' ) ); ?>">
                <input type="hidden" name="item_id" value="<?php echo esc_attr( (int) ( $item['id'] ?? 0 ) ); ?>">
                <button class="button button-danger" type="submit">Eliminar</button>
            </form>
        </article>
        <?php
    }
}

function intranet_demo_render_departments_tab( $items ) {
    $departments = intranet_demo_departments();

    ?>
    <section class="panel">
        <h2>Departamentos</h2>
        <p>Gestiona registros internos por departamento. En Electrica se separa Alta y Baja.</p>

        <form class="entry-form" method="post" action="<?php echo esc_url( add_query_arg( array( 'tab' => 'departamentos' ), home_url( '/intranet/' ) ) ); ?>">
            <input type="hidden" name="intranet_action" value="save_department_item">
            <input type="hidden" name="intranet_nonce" value="<?php echo esc_attr( wp_create_nonce( 'intranet_dashboard' ) ); ?>">

            <label>Titulo</label>
            <input name="entry_title" type="text" required>

            <label>Detalle</label>
            <textarea name="entry_details" rows="3"></textarea>

            <label>Departamento</label>
            <select name="department" required>
                <?php foreach ( $departments as $slug => $label ) : ?>
                    <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Tipo (solo Electrica)</label>
            <select name="entry_type">
                <option value="">No aplica</option>
                <option value="alta">Alta</option>
                <option value="baja">Baja</option>
            </select>

            <button class="button" type="submit">Guardar registro</button>
        </form>
    </section>

    <?php foreach ( $departments as $slug => $label ) : ?>
        <section class="panel">
            <h3><?php echo esc_html( $label ); ?></h3>
            <?php intranet_demo_render_department_columns( $items, $slug ); ?>
        </section>
    <?php endforeach; ?>
    <?php
}

function intranet_demo_tasks_by_status( $tasks, $status ) {
    return array_values(
        array_filter(
            $tasks,
            function ( $task ) use ( $status ) {
                return ( $task['status'] ?? '' ) === $status;
            }
        )
    );
}

function intranet_demo_render_task_card( $task ) {
    $departments = intranet_demo_departments();
    $statuses    = intranet_demo_task_statuses();
    $assignee    = isset( $task['assignee_id'] ) ? get_user_by( 'id', (int) $task['assignee_id'] ) : false;

    ?>
    <article class="task-card" draggable="true" data-task-id="<?php echo esc_attr( (int) ( $task['id'] ?? 0 ) ); ?>">
        <h5><?php echo esc_html( $task['task_title'] ?? '' ); ?></h5>
        <?php if ( ! empty( $task['task_details'] ) ) : ?>
            <p><?php echo esc_html( $task['task_details'] ); ?></p>
        <?php endif; ?>
        <small>
            Departamento: <?php echo esc_html( $departments[ $task['department'] ?? '' ] ?? 'N/D' ); ?><br>
            Asignado a: <?php echo esc_html( $assignee ? $assignee->display_name : 'Sin asignar' ); ?><br>
            Fecha limite: <?php echo esc_html( ! empty( $task['due_date'] ) ? $task['due_date'] : 'Sin fecha' ); ?>
        </small>

        <form method="post" action="<?php echo esc_url( add_query_arg( array( 'tab' => 'planner' ), home_url( '/intranet/' ) ) ); ?>">
            <input type="hidden" name="intranet_action" value="update_task_status">
            <input type="hidden" name="intranet_nonce" value="<?php echo esc_attr( wp_create_nonce( 'intranet_dashboard' ) ); ?>">
            <input type="hidden" name="task_id" value="<?php echo esc_attr( (int) ( $task['id'] ?? 0 ) ); ?>">

            <label>Estado</label>
            <select name="new_status">
                <?php foreach ( $statuses as $slug => $label ) : ?>
                    <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $task['status'] ?? '' ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button" type="submit">Actualizar</button>
        </form>

        <form method="post" action="<?php echo esc_url( add_query_arg( array( 'tab' => 'planner' ), home_url( '/intranet/' ) ) ); ?>">
            <input type="hidden" name="intranet_action" value="delete_task">
            <input type="hidden" name="intranet_nonce" value="<?php echo esc_attr( wp_create_nonce( 'intranet_dashboard' ) ); ?>">
            <input type="hidden" name="task_id" value="<?php echo esc_attr( (int) ( $task['id'] ?? 0 ) ); ?>">
            <button class="button button-danger" type="submit">Eliminar</button>
        </form>
    </article>
    <?php
}

function intranet_demo_render_planner_calendar( $tasks ) {
    $month_raw = isset( $_GET['month'] ) ? sanitize_text_field( wp_unslash( $_GET['month'] ) ) : gmdate( 'Y-m' );
    $month     = preg_match( '/^\\d{4}-\\d{2}$/', $month_raw ) ? $month_raw : gmdate( 'Y-m' );

    $first_day_ts = strtotime( $month . '-01 00:00:00' );
    if ( false === $first_day_ts ) {
        $first_day_ts = strtotime( gmdate( 'Y-m-01 00:00:00' ) );
    }

    $days_in_month  = (int) gmdate( 't', $first_day_ts );
    $first_weekday  = (int) gmdate( 'N', $first_day_ts );
    $month_label    = gmdate( 'F Y', $first_day_ts );

    $tasks_by_day = array();
    foreach ( $tasks as $task ) {
        $due_date = $task['due_date'] ?? '';
        if ( preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', (string) $due_date ) && 0 === strpos( $due_date, $month . '-' ) ) {
            $day = (int) substr( $due_date, 8, 2 );
            if ( ! isset( $tasks_by_day[ $day ] ) ) {
                $tasks_by_day[ $day ] = array();
            }
            $tasks_by_day[ $day ][] = $task;
        }
    }

    $prev_month = gmdate( 'Y-m', strtotime( '-1 month', $first_day_ts ) );
    $next_month = gmdate( 'Y-m', strtotime( '+1 month', $first_day_ts ) );
    $base_url   = home_url( '/intranet/' );

    ?>
    <section class="panel">
        <div class="calendar-head">
            <h3>Calendario planner: <?php echo esc_html( $month_label ); ?></h3>
            <div class="calendar-nav">
                <a class="chip" href="<?php echo esc_url( add_query_arg( array( 'tab' => 'planner', 'month' => $prev_month ), $base_url ) ); ?>">Mes anterior</a>
                <a class="chip" href="<?php echo esc_url( add_query_arg( array( 'tab' => 'planner', 'month' => $next_month ), $base_url ) ); ?>">Mes siguiente</a>
            </div>
        </div>

        <div class="calendar-grid">
            <div class="calendar-cell calendar-cell--head">Lun</div>
            <div class="calendar-cell calendar-cell--head">Mar</div>
            <div class="calendar-cell calendar-cell--head">Mie</div>
            <div class="calendar-cell calendar-cell--head">Jue</div>
            <div class="calendar-cell calendar-cell--head">Vie</div>
            <div class="calendar-cell calendar-cell--head">Sab</div>
            <div class="calendar-cell calendar-cell--head">Dom</div>

            <?php for ( $blank = 1; $blank < $first_weekday; $blank++ ) : ?>
                <div class="calendar-cell calendar-cell--empty"></div>
            <?php endfor; ?>

            <?php for ( $day = 1; $day <= $days_in_month; $day++ ) : ?>
                <div class="calendar-cell">
                    <strong><?php echo esc_html( (string) $day ); ?></strong>
                    <?php if ( ! empty( $tasks_by_day[ $day ] ) ) : ?>
                        <ul>
                            <?php foreach ( $tasks_by_day[ $day ] as $task ) : ?>
                                <li><?php echo esc_html( $task['task_title'] ?? '' ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </section>
    <?php
}

function intranet_demo_render_planner_tab( $tasks, $users ) {
    $departments = intranet_demo_departments();
    $statuses    = intranet_demo_task_statuses();

    ?>
    <section class="panel">
        <h2>Planner de equipo</h2>
        <p>Gestion de tareas con departamento, persona asignada y estado, como flujo tipo Planner.</p>

        <form class="entry-form" method="post" action="<?php echo esc_url( add_query_arg( array( 'tab' => 'planner' ), home_url( '/intranet/' ) ) ); ?>">
            <input type="hidden" name="intranet_action" value="save_task">
            <input type="hidden" name="intranet_nonce" value="<?php echo esc_attr( wp_create_nonce( 'intranet_dashboard' ) ); ?>">

            <label>Titulo de tarea</label>
            <input name="task_title" type="text" required>

            <label>Descripcion</label>
            <textarea name="task_details" rows="3"></textarea>

            <label>Departamento</label>
            <select name="task_department" required>
                <?php foreach ( $departments as $slug => $label ) : ?>
                    <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Asignado a</label>
            <select name="task_assignee_id">
                <option value="0">Sin asignar</option>
                <?php foreach ( $users as $user ) : ?>
                    <option value="<?php echo esc_attr( (int) $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Fecha limite</label>
            <input name="task_due_date" type="date">

            <label>Estado inicial</label>
            <select name="task_status">
                <?php foreach ( $statuses as $slug => $label ) : ?>
                    <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>

            <button class="button" type="submit">Crear tarea</button>
        </form>
    </section>

    <section class="panel">
        <h3>Tablero planner</h3>
        <form id="planner-dnd-form" method="post" action="<?php echo esc_url( add_query_arg( array( 'tab' => 'planner' ), home_url( '/intranet/' ) ) ); ?>">
            <input type="hidden" name="intranet_action" value="update_task_status">
            <input type="hidden" name="intranet_nonce" value="<?php echo esc_attr( wp_create_nonce( 'intranet_dashboard' ) ); ?>">
            <input type="hidden" name="task_id" value="">
            <input type="hidden" name="new_status" value="">
        </form>
        <div class="planner-board">
            <?php foreach ( $statuses as $slug => $label ) : ?>
                <section class="board-col" data-status="<?php echo esc_attr( $slug ); ?>">
                    <h4><?php echo esc_html( $label ); ?></h4>
                    <?php
                    $by_status = intranet_demo_tasks_by_status( $tasks, $slug );
                    if ( empty( $by_status ) ) {
                        echo '<p class="muted">Sin tareas.</p>';
                    }
                    foreach ( $by_status as $task ) {
                        intranet_demo_render_task_card( $task );
                    }
                    ?>
                </section>
            <?php endforeach; ?>
        </div>
    </section>

    <?php intranet_demo_render_planner_calendar( $tasks ); ?>

    <script>
    (function () {
        var draggedTaskId = null;
        var taskCards = document.querySelectorAll('.task-card[data-task-id]');
        var boardCols = document.querySelectorAll('.board-col[data-status]');
        var dndForm = document.getElementById('planner-dnd-form');

        if (!dndForm) {
            return;
        }

        taskCards.forEach(function (card) {
            card.addEventListener('dragstart', function () {
                draggedTaskId = card.getAttribute('data-task-id');
                card.classList.add('is-dragging');
            });

            card.addEventListener('dragend', function () {
                card.classList.remove('is-dragging');
            });
        });

        boardCols.forEach(function (col) {
            col.addEventListener('dragover', function (event) {
                event.preventDefault();
                col.classList.add('is-drop-ready');
            });

            col.addEventListener('dragleave', function () {
                col.classList.remove('is-drop-ready');
            });

            col.addEventListener('drop', function (event) {
                event.preventDefault();
                col.classList.remove('is-drop-ready');

                if (!draggedTaskId) {
                    return;
                }

                var status = col.getAttribute('data-status');
                if (!status) {
                    return;
                }

                dndForm.querySelector('input[name="task_id"]').value = draggedTaskId;
                dndForm.querySelector('input[name="new_status"]').value = status;
                dndForm.submit();
            });
        });
    })();
    </script>
    <?php
}

function intranet_demo_render_dashboard_page() {
    status_header( 200 );
    nocache_headers();

    $user         = wp_get_current_user();
    $current_tab  = intranet_demo_get_current_tab();
    $dept_items   = intranet_demo_options_department_items();
    $tasks        = intranet_demo_options_tasks();
    $users        = get_users( array( 'fields' => array( 'ID', 'display_name' ) ) );
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
                --brand-2: #08363a;
                --danger: #a82020;
                --flash: #edf9f1;
            }

            * { box-sizing: border-box; }
            body {
                margin: 0;
                color: var(--ink);
                background: radial-gradient(circle at 4% 8%, #f3d7b3 0%, rgba(243,215,179,0) 30%), var(--bg);
                font-family: "Segoe UI", sans-serif;
            }

            .wrap {
                max-width: 1180px;
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

            .tabs {
                display: flex;
                gap: 0.55rem;
                margin-bottom: 1rem;
                flex-wrap: wrap;
            }

            .chip {
                display: inline-block;
                border-radius: 999px;
                border: 1px solid var(--line);
                padding: 0.4rem 0.8rem;
                text-decoration: none;
                color: var(--brand);
            }

            .chip.is-active {
                background: linear-gradient(120deg, var(--brand) 0%, var(--brand-2) 100%);
                color: #fff;
                border-color: transparent;
            }

            .flash {
                border: 1px solid #c8e7d1;
                background: var(--flash);
                border-radius: 10px;
                padding: 0.65rem 0.8rem;
            }

            .panel {
                background: var(--panel);
                border: 1px solid var(--line);
                border-radius: 14px;
                padding: 1rem;
                box-shadow: 0 12px 28px rgba(20, 17, 14, 0.08);
                margin-bottom: 1rem;
            }

            .panel-inner {
                background: #fff;
                border: 1px solid #eadfce;
                border-radius: 12px;
                padding: 0.8rem;
            }

            .dept-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }

            .dept-grid--double {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }

            .entry-form {
                display: grid;
                gap: 0.45rem;
                margin-top: 0.7rem;
            }

            label {
                font-size: 0.9rem;
                font-weight: 600;
            }

            input,
            textarea,
            select {
                width: 100%;
                border: 1px solid var(--line);
                border-radius: 10px;
                padding: 0.6rem;
                font: inherit;
                background: #fff;
            }

            .button {
                display: inline-block;
                border-radius: 10px;
                border: 0;
                padding: 0.58rem 0.8rem;
                font-weight: 700;
                color: #fff;
                background: linear-gradient(120deg, var(--brand) 0%, var(--brand-2) 100%);
                cursor: pointer;
                margin-top: 0.45rem;
            }

            .button-danger {
                background: var(--danger);
            }

            .item-card,
            .task-card {
                border: 1px solid #eadfce;
                border-radius: 10px;
                padding: 0.7rem;
                background: #fff;
                margin-bottom: 0.65rem;
            }

            .task-card {
                cursor: grab;
            }

            .task-card.is-dragging {
                opacity: 0.55;
            }

            .item-card h5,
            .task-card h5 {
                margin: 0 0 0.35rem;
                font-size: 1rem;
            }

            .item-card p,
            .task-card p,
            .muted {
                margin: 0.3rem 0;
                color: var(--ink-soft);
            }

            .planner-board {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 0.8rem;
            }

            .board-col {
                background: #fff;
                border: 1px solid #eadfce;
                border-radius: 12px;
                padding: 0.75rem;
                min-height: 140px;
            }

            .board-col.is-drop-ready {
                border-color: var(--brand);
                box-shadow: 0 0 0 2px rgba(13, 92, 99, 0.2) inset;
            }

            .calendar-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
                flex-wrap: wrap;
            }

            .calendar-nav {
                display: flex;
                gap: 0.5rem;
            }

            .calendar-grid {
                display: grid;
                grid-template-columns: repeat(7, minmax(0, 1fr));
                gap: 0.35rem;
            }

            .calendar-cell {
                border: 1px solid #e5d7c3;
                border-radius: 8px;
                background: #fff;
                min-height: 95px;
                padding: 0.42rem;
                font-size: 0.85rem;
            }

            .calendar-cell ul {
                margin: 0.2rem 0 0;
                padding-left: 1rem;
            }

            .calendar-cell--head {
                min-height: 0;
                font-weight: 700;
                text-align: center;
                padding: 0.3rem;
                background: #f5e9d8;
            }

            .calendar-cell--empty {
                background: transparent;
                border-style: dashed;
            }

            ul.news {
                margin: 0;
                padding-left: 1.1rem;
            }

            @media (max-width: 760px) {
                .calendar-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .calendar-cell--head {
                    display: none;
                }
            }
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

            <?php intranet_demo_render_notice(); ?>

            <nav class="tabs" aria-label="Navegacion intranet">
                <a class="chip <?php echo 'resumen' === $current_tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'tab' => 'resumen' ), home_url( '/intranet/' ) ) ); ?>">Resumen</a>
                <a class="chip <?php echo 'departamentos' === $current_tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'tab' => 'departamentos' ), home_url( '/intranet/' ) ) ); ?>">Departamentos</a>
                <a class="chip <?php echo 'planner' === $current_tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'tab' => 'planner' ), home_url( '/intranet/' ) ) ); ?>">Planner</a>
            </nav>

            <?php if ( 'resumen' === $current_tab ) : ?>
                <section class="panel">
                    <h2>Resumen del equipo</h2>
                    <p>
                        Departamentos activos: <strong><?php echo esc_html( (string) count( intranet_demo_departments() ) ); ?></strong>
                        | Registros internos: <strong><?php echo esc_html( (string) count( $dept_items ) ); ?></strong>
                        | Tareas planner: <strong><?php echo esc_html( (string) count( $tasks ) ); ?></strong>
                    </p>
                </section>

                <section class="panel">
                    <h3>Ultimas publicaciones del blog</h3>
                    <?php if ( ! empty( $recent_posts ) ) : ?>
                        <ul class="news">
                            <?php foreach ( $recent_posts as $post_item ) : ?>
                                <li>
                                    <a href="<?php echo esc_url( get_permalink( $post_item ) ); ?>"><?php echo esc_html( get_the_title( $post_item ) ); ?></a>
                                    <small> (<?php echo esc_html( get_the_date( '', $post_item ) ); ?>)</small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p>No hay publicaciones aun.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ( 'departamentos' === $current_tab ) : ?>
                <?php intranet_demo_render_departments_tab( $dept_items ); ?>
            <?php endif; ?>

            <?php if ( 'planner' === $current_tab ) : ?>
                <?php intranet_demo_render_planner_tab( $tasks, $users ); ?>
            <?php endif; ?>
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

    intranet_demo_handle_dashboard_post();
    intranet_demo_render_dashboard_page();
    exit;
}
add_action( 'template_redirect', 'intranet_demo_route_request', 0 );

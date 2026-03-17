# Intranet Demo

Pequeño ejemplo de intranet para WordPress, pensado para esta base de ServerlessWP.

## Qué incluye

- Home pública con botón superior **Entrar a la intranet**.
- Acceso privado solo para `/intranet` (el resto del sitio puede quedar público).
- Tipo de contenido interno `Anuncios intranet`.
- Shortcode `[intranet_dashboard]` con panel básico para empleados.
- Creación automática de una página `/intranet` al activar el plugin.
- Inicio de sesión con Microsoft (cuenta universitaria) en `wp-login.php` cuando está configurado.

## Uso rápido

1. Ve a **Plugins** y activa **Intranet Demo**.
2. Entra a `/intranet` con un usuario con sesión iniciada.
3. Publica anuncios en **Anuncios intranet**.
4. (Opcional) Asigna el rol **Empleado intranet** a usuarios internos.

## Login Microsoft (universidad)

Configura estas variables de entorno en tu despliegue (Vercel/Netlify):

- `INTRANET_MS_TENANT_ID` (ej. tenant de tu universidad o `common`)
- `INTRANET_MS_CLIENT_ID`
- `INTRANET_MS_CLIENT_SECRET`
- `INTRANET_MS_ALLOWED_DOMAIN` (ej. `universidad.edu`)

URL de redirección en Azure App Registration:

- `https://TU-DOMINIO/wp-admin/admin-post.php?action=intranet_ms_callback`

Con esto, en la pantalla de login aparecerá el botón:

- **Iniciar sesión con Microsoft (universidad)**

## Nota

Este plugin es una demo mínima. Puedes extenderlo con documentos internos, directorio de empleados o políticas de RRHH según tus necesidades.

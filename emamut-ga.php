<?php
/**
 * Plugin Name:       Google Analytics
 * Plugin URI:        https://github.com/emamut-dev/emamut-ga
 * Description:       Añade la etiqueta de Google Analytics (GA4 / gtag.js) en todas las páginas de tu sitio WordPress de forma sencilla, segura y optimizada.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            emamut
 * Author URI:        https://emamut.netlify.app/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       emamut-ga
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EMAMUT_GA_VERSION', '1.0.0' );
define( 'EMAMUT_GA_OPTION_NAME', 'emamut_ga_settings' );

class Emamut_Google_Analytics {

	/**
	 * Instancia única (Singleton)
	 *
	 * @var Emamut_Google_Analytics|null
	 */
	private static $instance = null;

	/**
	 * Obtener la instancia de la clase
	 *
	 * @return Emamut_Google_Analytics
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Inserción del script en el frontend
		add_action( 'wp_head', array( $this, 'render_analytics_script' ), 1 );

		// Panel de administración
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
		}
	}

	/**
	 * Valida si un ID de medición cumple con el formato estándar de Google (GA4, Google Tag, UA, Ads)
	 *
	 * @param string $id ID a validar.
	 * @return bool
	 */
	public static function is_valid_measurement_id( $id ) {
		if ( ! is_string( $id ) || '' === trim( $id ) ) {
			return false;
		}

		return (bool) preg_match( '/^(G|GT|UA|AW)-[A-Z0-9]+(-[0-9]+)?$/', strtoupper( trim( $id ) ) );
	}

	/**
	 * Obtiene el ID de medición configurado (o constante EMAMUT_GA_ID)
	 *
	 * @return string
	 */
	public function get_measurement_id() {
		if ( defined( 'EMAMUT_GA_ID' ) && is_string( EMAMUT_GA_ID ) && '' !== trim( EMAMUT_GA_ID ) ) {
			$constant_id = sanitize_text_field( trim( EMAMUT_GA_ID ) );
			return self::is_valid_measurement_id( $constant_id ) ? strtoupper( $constant_id ) : '';
		}

		$options = get_option( EMAMUT_GA_OPTION_NAME, array() );
		if ( ! empty( $options['measurement_id'] ) ) {
			$option_id = sanitize_text_field( trim( $options['measurement_id'] ) );
			return self::is_valid_measurement_id( $option_id ) ? strtoupper( $option_id ) : '';
		}

		return '';
	}

	/**
	 * Comprueba si la exclusión de administradores está activa
	 *
	 * @return bool
	 */
	public function should_exclude_admin() {
		$options = get_option( EMAMUT_GA_OPTION_NAME, array() );
		return ! empty( $options['exclude_admin'] );
	}

	/**
	 * Renderiza el snippet de Google Analytics en <head>
	 */
	public function render_analytics_script() {
		// Evitar inserción en feeds, robots, trackbacks o llamadas API/Admin
		if ( is_admin() || is_feed() || is_robots() || is_trackback() ) {
			return;
		}

		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
			return;
		}

		$measurement_id = $this->get_measurement_id();
		if ( empty( $measurement_id ) || ! self::is_valid_measurement_id( $measurement_id ) ) {
			return;
		}

		// Comprobar si se excluye a usuarios administradores
		if ( $this->should_exclude_admin() && current_user_can( 'manage_options' ) ) {
			return;
		}

		/**
		 * Permite a plugins de cookies, consentimiento (RGPD / CMP) o temas desactivar el rastreo.
		 *
		 * @param bool $disable_tracking True para omitir el rastreo, false por defecto.
		 */
		if ( apply_filters( 'emamut_ga_disable_tracking', false ) ) {
			return;
		}

		$escaped_id    = esc_attr( $measurement_id );
		$escaped_js_id = esc_js( $measurement_id );
		$nonce_attr    = '';

		/**
		 * Permite inyectar un atributo nonce para sitios con Content Security Policy (CSP) estricta.
		 *
		 * @param string $nonce Atributo o valor del nonce (debe ser una cadena Base64 válida).
		 */
		$nonce = apply_filters( 'emamut_ga_script_nonce', '' );
		if ( ! empty( $nonce ) && is_string( $nonce ) && preg_match( '/^[A-Za-z0-9+\/=\-_]+$/', $nonce ) ) {
			$nonce_attr = ' nonce="' . esc_attr( $nonce ) . '"';
		}

		/**
		 * Permite configurar los parámetros por defecto de Google Consent Mode v2.
		 *
		 * @param array $consent_defaults Parámetros de consentimiento por defecto (ej: array( 'analytics_storage' => 'denied' )).
		 */
		$consent_defaults = apply_filters( 'emamut_ga_consent_defaults', array() );
		?>
<!-- Google tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $escaped_id; ?>"<?php echo $nonce_attr; ?>></script>
<script<?php echo $nonce_attr; ?>>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
<?php if ( ! empty( $consent_defaults ) && is_array( $consent_defaults ) ) : ?>
  gtag('consent', 'default', <?php echo wp_json_encode( $consent_defaults ); ?>);
<?php endif; ?>
  gtag('js', new Date());

  gtag('config', '<?php echo $escaped_js_id; ?>');
</script>
<!-- End Google tag (gtag.js) -->
<?php
	}

	/**
	 * Añade la página de ajustes al menú de Ajustes en wp-admin
	 */
	public function register_admin_menu() {
		add_options_page(
			__( 'Google Analytics', 'emamut-ga' ),
			__( 'Google Analytics', 'emamut-ga' ),
			'manage_options',
			'emamut-ga',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Registra los ajustes, sección y campos usando la API de Settings de WordPress
	 */
	public function register_settings() {
		register_setting(
			'emamut_ga_options_group',
			EMAMUT_GA_OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest'      => false,
				'capability'        => 'manage_options',
				'default'           => array(
					'measurement_id' => '',
					'exclude_admin'  => 1,
				),
			)
		);

		add_settings_section(
			'emamut_ga_main_section',
			__( 'Configuración de Google Analytics', 'emamut-ga' ),
			array( $this, 'render_section_info' ),
			'emamut-ga'
		);

		add_settings_field(
			'measurement_id',
			__( 'ID de Medición (Measurement ID)', 'emamut-ga' ),
			array( $this, 'render_measurement_id_field' ),
			'emamut-ga',
			'emamut_ga_main_section'
		);

		add_settings_field(
			'exclude_admin',
			__( 'Excluir Administradores', 'emamut-ga' ),
			array( $this, 'render_exclude_admin_field' ),
			'emamut-ga',
			'emamut_ga_main_section'
		);
	}

	/**
	 * Sanitiza y valida las opciones antes de guardar
	 *
	 * @param array $input Valores recibidos del formulario
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();
		$existing  = get_option( EMAMUT_GA_OPTION_NAME, array() );

		if ( ! is_array( $input ) ) {
			return is_array( $existing ) ? $existing : array();
		}

		if ( isset( $input['measurement_id'] ) ) {
			$raw_id   = trim( (string) $input['measurement_id'] );
			$clean_id = strtoupper( $raw_id );

			if ( '' !== $clean_id && ! self::is_valid_measurement_id( $clean_id ) ) {
				if ( function_exists( 'add_settings_error' ) ) {
					add_settings_error(
						EMAMUT_GA_OPTION_NAME,
						'invalid_measurement_id',
						__( 'El ID de medición no es válido. Debe tener un formato válido como G-XXXXXXXXXX o UA-XXXXXXXX-X.', 'emamut-ga' ),
						'error'
					);
				}
				// Mantener el ID previo para no perder la configuración por un error tipográfico
				$sanitized['measurement_id'] = isset( $existing['measurement_id'] ) ? $existing['measurement_id'] : '';
			} else {
				$sanitized['measurement_id'] = sanitize_text_field( $clean_id );
			}
		} else {
			$sanitized['measurement_id'] = '';
		}

		$sanitized['exclude_admin'] = ! empty( $input['exclude_admin'] ) ? 1 : 0;

		return $sanitized;
	}

	/**
	 * Información introductoria de la sección
	 */
	public function render_section_info() {
		echo '<p>' . esc_html__( 'Introduce tu ID de medición para comenzar a recopilar métricas de visitas en tu sitio web.', 'emamut-ga' ) . '</p>';
	}

	/**
	 * Campo para el ID de medición
	 */
	public function render_measurement_id_field() {
		$options        = get_option( EMAMUT_GA_OPTION_NAME, array() );
		$is_defined     = defined( 'EMAMUT_GA_ID' ) && '' !== trim( EMAMUT_GA_ID );
		$measurement_id = $is_defined ? sanitize_text_field( trim( EMAMUT_GA_ID ) ) : ( isset( $options['measurement_id'] ) ? $options['measurement_id'] : '' );
		$is_valid_id    = self::is_valid_measurement_id( $measurement_id );
		?>
		<input
			type="text"
			name="<?php echo esc_attr( EMAMUT_GA_OPTION_NAME ); ?>[measurement_id]"
			id="emamut_ga_measurement_id"
			value="<?php echo esc_attr( $measurement_id ); ?>"
			placeholder="G-XXXXXXXXXX"
			class="regular-text"
			<?php echo $is_defined ? 'readonly' : ''; ?>
		/>
		<?php if ( $is_defined ) : ?>
			<p class="description" style="color: <?php echo $is_valid_id ? '#0073aa' : '#d63638'; ?>;">
				<?php
				if ( $is_valid_id ) {
					printf(
						/* translators: %s: constant name */
						esc_html__( 'El ID está definido mediante la constante %s en wp-config.php y tiene prioridad.', 'emamut-ga' ),
						'<code>EMAMUT_GA_ID</code>'
					);
				} else {
					printf(
						/* translators: %s: constant name */
						esc_html__( 'Atención: La constante %s está definida en wp-config.php pero su formato no es válido.', 'emamut-ga' ),
						'<code>EMAMUT_GA_ID</code>'
					);
				}
				?>
			</p>
		<?php else : ?>
			<p class="description">
				<?php
				printf(
					/* translators: %s: example tag */
					esc_html__( 'Ejemplo: %s (Google Analytics 4). Lo encuentras en tu cuenta de Google Analytics > Administrador > Flujos de datos.', 'emamut-ga' ),
					'<code>G-XXXXXXXXXX</code>'
				);
				?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Campo para excluir administradores
	 */
	public function render_exclude_admin_field() {
		$options       = get_option( EMAMUT_GA_OPTION_NAME, array( 'exclude_admin' => 1 ) );
		$exclude_admin = isset( $options['exclude_admin'] ) ? (bool) $options['exclude_admin'] : true;
		?>
		<label for="emamut_ga_exclude_admin">
			<input
				type="checkbox"
				name="<?php echo esc_attr( EMAMUT_GA_OPTION_NAME ); ?>[exclude_admin]"
				id="emamut_ga_exclude_admin"
				value="1"
				<?php checked( $exclude_admin, true ); ?>
			/>
			<?php esc_html_e( 'No rastrear visitas de usuarios administradores.', 'emamut-ga' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Recomendado para evitar que tus propias visitas influyan en las estadísticas del sitio.', 'emamut-ga' ); ?>
		</p>
		<?php
	}

	/**
	 * Renderiza la página de administración completa
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_id = $this->get_measurement_id();
		$is_active  = ! empty( $current_id );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div style="margin: 15px 0 20px; padding: 12px 18px; border-left: 4px solid <?php echo $is_active ? '#46b450' : '#dba617'; ?>; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<strong><?php esc_html_e( 'Estado del servicio:', 'emamut-ga' ); ?></strong>
				<?php if ( $is_active ) : ?>
					<span style="color: #46b450; font-weight: bold; margin-left: 6px;">&#10004; <?php esc_html_e( 'Activo y rastreando', 'emamut-ga' ); ?></span>
					<span style="color: #555; margin-left: 10px;">(ID: <code><?php echo esc_html( $current_id ); ?></code>)</span>
				<?php else : ?>
					<span style="color: #dba617; font-weight: bold; margin-left: 6px;">&#9888; <?php esc_html_e( 'Inactivo (Falta configurar el ID de medición)', 'emamut-ga' ); ?></span>
				<?php endif; ?>
			</div>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'emamut_ga_options_group' );
				do_settings_sections( 'emamut-ga' );
				submit_button( __( 'Guardar cambios', 'emamut-ga' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Muestra un aviso en el admin si el plugin está activo pero no tiene ID configurado
	 */
	public function render_admin_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// No mostrar aviso dentro de la propia página del plugin
		$screen = get_current_screen();
		if ( $screen && 'settings_page_emamut-ga' === $screen->id ) {
			return;
		}

		$measurement_id = $this->get_measurement_id();
		if ( empty( $measurement_id ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Google Analytics:', 'emamut-ga' ); ?></strong>
					<?php esc_html_e( 'Aún no has configurado tu ID de medición.', 'emamut-ga' ); ?>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=emamut-ga' ) ); ?>">
						<?php esc_html_e( 'Configurar ahora', 'emamut-ga' ); ?> &rarr;
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Añade enlace directo a "Ajustes" en la lista de plugins instalados
	 *
	 * @param array $links Enlaces existentes
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$settings_url  = admin_url( 'options-general.php?page=emamut-ga' );
		$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Ajustes', 'emamut-ga' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}

// Inicializar el plugin
add_action( 'plugins_loaded', array( 'Emamut_Google_Analytics', 'get_instance' ) );

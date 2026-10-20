=== Emamut Google Analytics ===
Contributors: emamut
Tags: google analytics, ga4, analytics, gtag, tracking
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Añade la etiqueta de Google Analytics (GA4 / gtag.js) en todas las páginas de tu sitio WordPress de forma sencilla, segura y optimizada.

== Description ==

**Emamut Google Analytics** es un plugin ultraligero y seguro para integrar Google Analytics 4 (GA4) en WordPress.

### Características:
* **Fácil configuración:** Simplemente ingresa tu ID de medición (`G-XXXXXXXXXX`) y listo.
* **Inserción automática en `<head>`:** Se inserta en todas las páginas públicas del frontend con la prioridad recomendada por Google.
* **Exclusión de administradores:** Evita que tus visitas como administrador distorsionen las estadísticas de tu sitio web.
* **Seguro y optimizado:** Sanitización y validación estricta de datos, soporte para CSP nonces, sin dependencias externas innecesarias ni impacto en la velocidad del sitio.
* **Soporte para constante en wp-config.php:** Puedes definir `define('EMAMUT_GA_ID', 'G-XXXXXXXXXX');` para entornos automatizados o CI/CD.
* **Listo para RGPD y Consent Mode v2:** Filtros para integración con CMPs y configuración de consentimiento (`emamut_ga_disable_tracking` y `emamut_ga_consent_defaults`).

== Installation ==

1. Sube la carpeta `emamut-ga` al directorio `/wp-content/plugins/` de tu sitio.
2. Activa el plugin a través del menú 'Plugins' en WordPress.
3. Ve a **Ajustes > Google Analytics** e introduce tu ID de Medición (ej: `G-XXXXXXXXXX`).
4. Haz clic en **Guardar cambios**.

== Frequently Asked Questions ==

= ¿Dónde encuentro mi ID de medición de Google Analytics 4? =
1. Inicia sesión en [Google Analytics](https://analytics.google.com/).
2. Ve a **Administrador** (icono de engranaje abajo a la izquierda).
3. En la columna de la propiedad, haz clic en **Flujos de datos** y selecciona tu flujo web.
4. Verás el **ID de medición** en la esquina superior derecha con formato `G-XXXXXXXXXX`.

= ¿El script se carga en el panel de administración de WordPress? =
No, el script solo se carga en las páginas públicas del sitio web para no saturar el panel de administración con llamadas innecesarias.

= ¿Puedo excluir mis propias visitas del rastreo? =
Sí, el plugin incluye una casilla para excluir visitas de usuarios con rol de Administrador. Si usas plugins de caché de página completa (como WP Rocket, LiteSpeed o WP Super Cache), asegúrate de que no almacenen páginas en caché para usuarios conectados (comportamiento habitual por defecto).

= ¿Es compatible con RGPD / cookies y Google Consent Mode v2? =
Sí. Puedes pausar el rastreo dinámicamente mediante el filtro `emamut_ga_disable_tracking` o inicializar parámetros de Consent Mode v2 utilizando el filtro `emamut_ga_consent_defaults`.

== Changelog ==

= 1.0.0 =
* Versión inicial con soporte para GA4 (gtag.js), panel de ajustes y exclusión de administradores.

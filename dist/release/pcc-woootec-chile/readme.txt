=== PCC-WooOTEC-Chile ===
Contributors: PCC
Tags: moodle, woocommerce, otec, cursos
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integración ligera entre WooCommerce y Moodle para sincronizar cursos y matricular automáticamente tras pago completado.

== Description ==

PCC-WooOTEC-Chile conecta WooCommerce con Moodle vía Moodle Web Services (REST):

* Sincroniza cursos desde Moodle a productos WooCommerce.
* Al completar una orden, crea/busca usuario en Moodle y lo matricula en el/los cursos comprados.
* Maneja fallos con cola de reintentos (WP-Cron) y ejecución manual desde el admin.
* Registra logs en `wp-content/uploads/pcc-woootec/logs/pcc-woootec.log`.

== Installation ==

1. Sube el ZIP desde WP Admin → Plugins → Añadir nuevo → Subir plugin.
2. Activa el plugin.
3. Ve a PCC WooOTEC → Settings y configura:
   * Moodle URL
   * Moodle Token
   * Role ID Estudiante
4. (Opcional) Configura Aula URL y Modo Debug.

== Frequently Asked Questions ==

= ¿Qué web services debo habilitar en Moodle? =

Debes habilitar al menos:

* core_course_get_courses
* core_user_create_users
* core_user_get_users
* enrol_manual_enrol_users
* core_enrol_get_users_courses
* core_webservice_get_site_info

= ¿Qué pasa si Moodle no responde al completar la orden? =

Se encola el intento de matrícula y se reintenta automáticamente (WP-Cron). También puedes forzarlo desde PCC WooOTEC → Reintentos.

== Changelog ==

= 1.0.0 =
* Versión inicial: settings, sync, matrícula, logs, cola de reintentos y ejecución manual.

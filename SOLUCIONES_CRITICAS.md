# 🔴 SOLUCIONES PARA ERRORES CRÍTICOS
## Plugin PCC-WooOTEC-Chile v3.0.8

**URGENCIA:** Estos errores impiden que el plugin funcione. Aplica TODAS estas soluciones AHORA.

---

## ⚠️ ANTES DE COMENZAR
- Haz backup completo de la base de datos
- Haz backup del directorio `/wp-content/plugins/woo-otec-moodle/`
- **CAMBIA INMEDIATAMENTE** el token de Moodle (está comprometido)

---

# SOLUCIÓN 1: Remover métodos duplicados en class-admin-settings.php

## Problema
El archivo `includes/class-admin-settings.php` tiene dos declaraciones duplicadas:
- `ajax_save_metadata()` declarado 2 veces (líneas ~333 y ~598)
- `ajax_reset_metadata()` declarado 2 veces (líneas ~351 y ~619)
- Un comentario de cierre `*/` sin apertura (línea 365)

Error resultante:
```
Fatal error: Cannot redeclare Woo_OTEC_Moodle\Admin_Settings::ajax_save_metadata()
```

## Solución

**PASO 1:** Abre el archivo `includes/class-admin-settings.php`

**PASO 2:** Localiza y ELIMINA las líneas 598-643 (segunda copia de métodos):

```php
// ❌ ELIMINAR TODO ESTO (líneas 598-643):

	/**
	 * AJAX Handler para guardar metadatos
	 */
	public function ajax_save_metadata() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$metadata = isset( $_POST['metadata'] ) ? json_decode( sanitize_text_field( $_POST['metadata'] ), true ) : array();

		if ( ! is_array( $metadata ) ) {
			wp_send_json_error( 'Formato inválido' );
		}

		update_option( 'woo_otec_moodle_metadata_enabled', $metadata );
		$this->logger->log( 'SUCCESS', 'Metadatos guardados' );

		wp_send_json_success( 'Metadatos guardados' );
	}

	/**
	 * AJAX Handler para resetear metadatos
	 */
	public function ajax_reset_metadata() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		delete_option( 'woo_otec_moodle_metadata_enabled' );
		$this->logger->log( 'SUCCESS', 'Metadatos reseteados' );

		wp_send_json_success( 'Metadatos reseteados' );
	}
	 */
	public function ajax_preview_template() {
```

**PASO 3:** Deja que la primera copia (líneas ~333-365) sea la ÚNICA:

```php
// ✅ MANTENER SOLO ESTO (primera copia):

	/**
	 * AJAX Handler para guardar metadatos
	 */
	public function ajax_save_metadata() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$metadata = isset( $_POST['metadata'] ) ? json_decode( sanitize_text_field( $_POST['metadata'] ), true ) : array();

		if ( ! is_array( $metadata ) ) {
			wp_send_json_error( 'Formato inválido' );
		}

		update_option( 'woo_otec_moodle_metadata_enabled', $metadata );
		$this->logger->log( 'SUCCESS', 'Metadatos guardados' );

		wp_send_json_success( 'Metadatos guardados' );
	}

	/**
	 * AJAX Handler para resetear metadatos
	 */
	public function ajax_reset_metadata() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		delete_option( 'woo_otec_moodle_metadata_enabled' );
		$this->logger->log( 'SUCCESS', 'Metadatos reseteados' );

		wp_send_json_success( 'Metadatos reseteados' );
	}

	/**
	 * AJAX Handler para guardar intervalo de CRON
	 */
	public function ajax_save_cron_interval() {
		// ... resto del código
	}
```

**Verificación:** Después de hacer esto, deberías encontrar que `ajax_save_metadata()` aparece UNA SOLA VEZ en el archivo.

---

# SOLUCIÓN 2: Actualizar constructor de Admin_Settings

## Problema
El archivo `woo-otec-moodle.php` pasa 6 argumentos al constructor de `Admin_Settings`, pero el constructor solo acepta 4:

```php
// ❌ INCORRECTO (woo-otec-moodle.php línea ~178)
new \Woo_OTEC_Moodle\Admin_Settings(
    $api_client,
    $logger,
    $this->metadata_manager,
    $this->template_manager,
    $template_customizer,        // ← Extra
    $preview_generator           // ← Extra
);

// ❌ CONSTRUCTOR ACTUAL (class-admin-settings.php línea 58)
public function __construct( $api_client, $logger, $metadata_manager = null, $template_manager = null ) {
```

Error resultante:
```
TypeError: Too many arguments passed
```

## Solución

**Opción A: Actualizar el constructor** (RECOMENDADO - más flexible)

Abre `includes/class-admin-settings.php` y cambia el constructor (línea ~58):

```php
// ❌ ANTES:
public function __construct( $api_client, $logger, $metadata_manager = null, $template_manager = null ) {
    $this->api_client       = $api_client;
    $this->logger           = $logger;
    $this->metadata_manager = $metadata_manager;

    // Instanciar Template Managers
    $this->template_manager   = $template_manager ?: new \Woo_OTEC_Moodle\Template_Manager();
    $this->template_customizer = new \Woo_OTEC_Moodle\Template_Customizer( $this->template_manager );
    $this->preview_generator   = new \Woo_OTEC_Moodle\Preview_Generator( $this->template_manager );
    $this->field_mapper        = new \Woo_OTEC_Moodle\Field_Mapper();

// ✅ DESPUÉS:
public function __construct( 
    $api_client, 
    $logger, 
    $metadata_manager = null, 
    $template_manager = null,
    $template_customizer = null,
    $preview_generator = null
) {
    $this->api_client       = $api_client;
    $this->logger           = $logger;
    $this->metadata_manager = $metadata_manager;

    // Instanciar Template Managers
    $this->template_manager   = $template_manager ?: new \Woo_OTEC_Moodle\Template_Manager();
    $this->template_customizer = $template_customizer ?: new \Woo_OTEC_Moodle\Template_Customizer( $this->template_manager );
    $this->preview_generator   = $preview_generator ?: new \Woo_OTEC_Moodle\Preview_Generator( $this->template_manager );
    $this->field_mapper        = new \Woo_OTEC_Moodle\Field_Mapper();
```

**VENTAJA:** Permite inyección de dependencias (mejor para testing y flexibilidad)

---

# SOLUCIÓN 3: Remover hooks AJAX duplicados

## Problema
El mismo hook `wp_ajax_wom_set_product_image` está registrado en DOS clases diferentes:

1. `includes/class-course-sync.php` línea ~35
2. `includes/class-admin-settings.php` línea ~73

Ambas clases tienen su propio `ajax_set_product_image()`, causando conflicto.

## Solución

**OPCIÓN A: Mantener en Admin_Settings (RECOMENDADO)**

**PASO 1:** Abre `includes/class-course-sync.php`

**PASO 2:** Busca y COMENTA la línea ~35:

```php
// ❌ ELIMINAR O COMENTAR:
// add_action( 'wp_ajax_wom_set_product_image', array( $this, 'ajax_set_product_image' ) );
```

**PASO 3:** Busca y ELIMINA también el método `ajax_set_product_image()` en class-course-sync.php (aproximadamente líneas 285-310)

```php
// ❌ ELIMINAR TAMBIÉN ESTE MÉTODO:
public function ajax_set_product_image() {
    // ... código ...
}
```

El método en `includes/class-admin-settings.php` (línea ~244) gestionará todo.

---

# SOLUCIÓN 4: Arreglar Token de Moodle Comprometido

## ⚠️ CRÍTICO - SEGURIDAD

## Problema
El token de API está hardcodeado en el código:

```php
// ❌ COMPROMETIDO - CAMBIAR YA
$token = get_option( 'woo_otec_moodle_api_token', 'd4c5be6e5cefe4bbb025ae28ba5630df' );
```

Este token está visible en:
- Repositorio Git
- Historial de commits
- Backups
- Logs

## Solución

**PASO 1:** Accede a tu Moodle como administrador

**PASO 2:** Ve a Settings → Server → Web Services

**PASO 3:** Regresa el token comprometido:
   - Busca el token `d4c5be6e5cefe4bbb025ae28ba5630df`
   - Haz clic en "Remove"

**PASO 4:** Crea un nuevo token:
   - Click en "Create Token"
   - Selecciona usuario admin
   - Nombre: "WooCommerce Integration"
   - Guarda el nuevo token

**PASO 5:** Actualiza el código en `includes/class-api-client.php` línea ~31:

```php
// ❌ ANTES:
$token = get_option( 'woo_otec_moodle_api_token', 'd4c5be6e5cefe4bbb025ae28ba5630df' );

// ✅ DESPUÉS (sin valor por defecto):
$token = get_option( 'woo_otec_moodle_api_token', '' );
if ( empty( $token ) ) {
    return new WP_Error( 'missing_token', 'Token de API de Moodle no configurado' );
}
```

**PASO 6:** En WordPress admin, ve a OTEC Moodle → Configuración y luego actualiza el token:
   - Pegua el nuevo token del paso 4
   - Haz clic en "Guardar Configuración"

---

# SOLUCIÓN 5: Arreglar argumentos en llamada a log_sync

## Problema
En `includes/class-course-sync.php` línea ~348, se llama a `log_sync()` incorrectamente:

```php
// ❌ INCORRECTO (1 argumento):
$this->logger->log_sync( "Sincronización completada: $synced cursos, $applied con template aplicado" );

// ✅ ESPERADO (3 argumentos):
public function log_sync( $source, $count, $details = '' ) {
```

## Solución

Abre `includes/class-course-sync.php` y busca línea ~348:

```php
// ❌ ANTES:
$this->logger->log_sync( "Sincronización completada: $synced cursos, $applied con template aplicado" );

// ✅ DESPUÉS:
$this->logger->log_sync( 
    'course_sync', 
    $synced,
    "Cursos sincronizados: $synced, con template aplicado: $applied"
);
```

---

# SOLUCIÓN 6: Remover segundo AJAX handler de Template Config

## Problema
El hook `wp_ajax_wom_save_template_config` está registrado en DOS clases:

1. `includes/class-template-manager.php` línea ~88
2. `includes/class-admin-settings.php` línea ~82

Ambas declaran `ajax_save_template_config()` causando conflicto.

## Solución

**OPCIÓN A: Mantener solo en Admin_Settings**

**PASO 1:** Abre `includes/class-template-manager.php`

**PASO 2:** Comenta el registro del hook (línea ~88):

```php
// ❌ COMENTAR:
// add_action( 'wp_ajax_wom_save_template_config', array( $this, 'ajax_save_template_config' ) );
// add_action( 'wp_ajax_wom_preview_template', array( $this, 'ajax_preview_template' ) );
// add_action( 'wp_ajax_wom_reset_template', array( $this, 'ajax_reset_template' ) );

// ✅ O MANTENER SOLO ESTO:
// El Template_Manager solo proporciona métodos, no registra hooks
```

**PASO 3:** Verifica que `class-admin-settings.php` tenga estoslosamente registrados:

```php
// ✅ CORRECTO (en class-admin-settings.php línea ~82):
add_action( 'wp_ajax_wom_save_template_config', array( $this, 'ajax_save_template_config' ) );
add_action( 'wp_ajax_wom_preview_template', array( $this, 'ajax_preview_template' ) );
add_action( 'wp_ajax_wom_reset_template', array( $this, 'ajax_reset_template' ) );
```

---

# ✅ PLAN DE IMPLEMENTACIÓN

## Orden recomendado:

1. **PRIMERO:** Solución 4 (Token de Moodle) - SEGURIDAD CRÍTICA
2. **SEGUNDO:** Solución 1 (Remover métodos duplicados)
3. **TERCERO:** Solución 2 (Actualizar constructor)
4. **CUARTO:** Solución 3 (Remover hooks duplicados)
5. **QUINTO:** Solución 5 (Arreglar log_sync)
6. **SEXTO:** Solución 6 (Template Config hooks)

## Pasos después de aplicar cada solución:

```bash
1. Guarda el archivo
2. Ve a WordPress dashboard
3. Revisa si hay errores en WP_DEBUG (activalo si no está activo)
4. Recarga la página
5. Verifica que la página de admin cargue sin errores
```

---

# 🚨 VERIFICACIÓN FINAL

Después de aplicar TODAS las soluciones:

1. **Revisa el dashboard de OTEC Moodle** - debe cargarse sin errores
2. **Revisa cada página de admin:**
   - ✅ Dashboard
   - ✅ Configuración
   - ✅ Cursos
   - ✅ Metadatos
   - ✅ Personalización
3. **Prueba las funciones AJAX:**
   - Intenta cambiar configuración
   - Intenta sincronizar cursos
   - Revisa que guarde sin errores

---

# 📋 PROBLEMAS DE SEGURIDAD A CORREGIR DESPUÉS

Una vez que el plugin esté funcionando, aplica estas mejoras:

## Sanitización mejorada (includes/class-admin-settings.php línea 274):

```php
// ❌ ANTES (inseguro):
$fields = isset( $_POST['fields'] ) ? (array) $_POST['fields'] : array();

// ✅ DESPUÉS:
$fields = isset( $_POST['fields'] ) ? array_map( 'sanitize_text_field', (array) $_POST['fields'] ) : array();
```

## Mejor manejo de JSON (includes/class-template-manager.php línea 302):

```php
// ❌ ANTES:
$config = isset( $_POST['config'] ) ? json_decode( stripslashes( $_POST['config'] ), true ) : array();

// ✅ DESPUÉS:
$config_json = isset( $_POST['config'] ) ? sanitize_text_field( wp_unslash( $_POST['config'] ) ) : '{}';
$config = json_decode( $config_json, true );
if ( ! is_array( $config ) ) {
    $config = array();
}
```

---

# 📞 SOPORTE

Si tienes problemas:

1. **Revisa WP_DEBUG:** Agrega a wp-config.php:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

2. **Revisa los logs:** `/wp-content/debug.log`

3. **Restaura desde backup:** Si algo va mal, restaura y vuelve a intentar con cuidado

---

**Documento generado:** 12 de Abril de 2026
**Versión:** Plugin v3.0.8
**Criticidad:** 🔴 URGENTE - Aplica todas estas soluciones YA

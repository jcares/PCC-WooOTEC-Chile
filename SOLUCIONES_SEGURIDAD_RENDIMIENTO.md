# 🟠 SOLUCIONES DE SEGURIDAD Y RENDIMIENTO
## Plugin PCC-WooOTEC-Chile v3.0.8

Aplica estas soluciones DESPUÉS de haber arreglado los errores críticos.

---

# 🔒 PROBLEMAS DE SEGURIDAD

## SEGURIDAD 1: Sanitizar $_POST['fields'] correctamente

**Archivo:** `includes/class-admin-settings.php` línea ~274  
**Severidad:** ALTA (Stored XSS)  
**Impacto:** Posibilidad de inyectar scripts maliciosos en la BD

### Problema:
```php
$fields = isset( $_POST['fields'] ) ? (array) $_POST['fields'] : array();
// Sin sanitización de elementos individuales
```

### Solución:
```php
// ✅ CORRECTO
$fields = isset( $_POST['fields'] ) ? array_map( 'sanitize_text_field', (array) $_POST['fields'] ) : array();

// O si necesitas mantener estructura de arrays anidados:
if ( isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ) {
    $fields = array_map( function( $item ) {
        if ( is_array( $item ) ) {
            return array_map( 'sanitize_text_field', $item );
        }
        return sanitize_text_field( $item );
    }, $_POST['fields'] );
} else {
    $fields = array();
}
```

**Verificación:**
```bash
grep -n "array( $_POST\['fields'\]" includes/class-admin-settings.php
```

---

## SEGURIDAD 2: Mejorar uso de stripslashes()

**Archivo:** `includes/class-template-manager.php` líneas 302 y 329  
**Severidad:** MEDIA (JSON Injection)  
**Impacto:** Malformed JSON, inyección de caracteres especiales

### Problema:
```php
// Línea 302:
$config = isset( $_POST['config'] ) ? json_decode( stripslashes( $_POST['config'] ), true ) : array();

// Línea 329:
$config = isset( $_POST['config'] ) ? json_decode( sanitize_text_field( $_POST['config'] ), true ) : array();
```

### Solución:
```php
// ✅ CORRECTO - Mejor práctica WordPress
$config_json = isset( $_POST['config'] ) ? sanitize_text_field( wp_unslash( $_POST['config'] ) ) : '{}';
$config = json_decode( $config_json, true );
if ( ! is_array( $config ) ) {
    $config = array();
}
```

**Por qué:**
- `wp_unslash()` es más seguro que `stripslashes()`
- `sanitize_text_field()` es mejor que nada
- Validar resultado de `json_decode()` es crítico

---

## SEGURIDAD 3: Validar respuestas de API de Moodle

**Archivo:** `includes/class-api-client.php` líneas 50-68  
**Severidad:** MEDIA (Potencial DoS o inyección)  
**Impacto:** Si Moodle retorna datos inesperados, puede causar fatal errors

### Problema:
```php
public function request( $endpoint, $params = array() ) {
    // ... código ...
    $response = wp_remote_post( $url, $args );
    
    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    
    if ( isset( $data['exception'] ) ) {
        return new WP_Error( 'moodle_exception', $data['message'], $data );
    }
    return $data;  // ← Puede ser null, false, o inesperado
}
```

### Solución:
```php
public function request( $endpoint, $params = array() ) {
    // ... código existente ...
    $response = wp_remote_post( $url, $args );
    
    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    // ✅ Validar que sea array
    if ( ! is_array( $data ) ) {
        return new WP_Error( 
            'invalid_response', 
            'Respuesta inválida de Moodle',
            array( 'body' => $body )
        );
    }
    
    if ( isset( $data['exception'] ) ) {
        return new WP_Error( 
            'moodle_exception', 
            $data['message'] ?? 'Error en Moodle',
            $data 
        );
    }
    
    return $data;
}
```

---

## SEGURIDAD 4: Remover valores por defecto sensibles

**Archivo:** `includes/class-api-client.php` líneas 30-31  
**Severidad:** CRÍTICA (Código Exposure)

### Problema:
```php
$url   = get_option( 'woo_otec_moodle_api_url', 'https://cipresalto.cl/aulavirtual' );
$token = get_option( 'woo_otec_moodle_api_token', 'd4c5be6e5cefe4bbb025ae28ba5630df' );
```

### Solución:
```php
// ✅ CORRECTO - Sin valores por defecto comprometidos
$url   = get_option( 'woo_otec_moodle_api_url', '' );
$token = get_option( 'woo_otec_moodle_api_token', '' );

// Validar que existan
if ( empty( $url ) || empty( $token ) ) {
    $this->logger->log( 'ERROR', 'Configuración de API de Moodle no completa' );
    return new WP_Error( 
        'moodle_config_incomplete', 
        'Por favor configura Moodle en OTEC Moodle → Configuración'
    );
}

// Validar URL
$parsed_url = parse_url( $url );
if ( ! $parsed_url || ! isset( $parsed_url['host'] ) ) {
    return new WP_Error( 'invalid_moodle_url', 'URL de Moodle inválida' );
}
```

---

## SEGURIDAD 5: Validar NONCE en save_moodle_id_field()

**Archivo:** `includes/class-course-sync.php` líneas 277-281  
**Severidad:** MEDIA (CSRF)  
**Impacto:** Posibilidad de modificar cursos sin autorización

### Problema:
```php
public function save_moodle_id_field( $post_id ) {
    $moodle_id = isset( $_POST['_moodle_course_id'] ) ? sanitize_text_field( $_POST['_moodle_course_id'] ) : '';
    update_post_meta( $post_id, '_moodle_course_id', $moodle_id );
}
```

### Solución:
```php
public function save_moodle_id_field( $post_id ) {
    // ✅ Validar nonce
    if ( ! isset( $_POST['_wpnonce_product_meta'] ) || 
         ! wp_verify_nonce( $_POST['_wpnonce_product_meta'], 'save_product_meta' ) ) {
        return;
    }

    // Validar capacidad
    if ( ! current_user_can( 'edit_product', $post_id ) ) {
        return;
    }

    $moodle_id = isset( $_POST['_moodle_course_id'] ) ? sanitize_text_field( $_POST['_moodle_course_id'] ) : '';
    
    // Validar que sea número
    if ( ! empty( $moodle_id ) && ! is_numeric( $moodle_id ) ) {
        return;
    }

    update_post_meta( $post_id, '_moodle_course_id', $moodle_id );
}
```

---

# ⚡ PROBLEMAS DE RENDIMIENTO

## RENDIMIENTO 1: Paginar sincronización de productos

**Archivo:** `includes/class-admin-settings.php` líneas 494-545  
**Severidad:** ALTA (Timeout en 1000+ productos)  
**Impacto:** Bloqueo de admin, timeout, alto uso de memoria

### Problema:
```php
$products = get_posts( array(
    'post_type'      => 'product',
    'numberposts'    => -1,      // ← Carga TODOS sin límite
    'posts_per_page' => -1,
) );

foreach ( $products as $product_post ) {
    // Multiple update_post_meta() calls para cada producto
}
```

### Solución - Opción A: Usar WP-CLI o Background Job:
```php
// ✅ MEJOR - Usar WP Cron o Background Job
public function apply_template_settings_to_products( $template_id, $settings ) {
    // Crear background job en lugar de ejecutar inmediatamente
    as_schedule_single_action( 
        0, 
        'wom_apply_template_settings', 
        array( $template_id, $settings )
    );
    
    return array( 'scheduled' => true );
}

// Aquí agregamos el handler async
add_action( 'wom_apply_template_settings', function( $template_id, $settings ) {
    $this->do_apply_template_settings_paginated( $template_id, $settings );
}, 10, 2 );

// Función que procesa en lotes
public function do_apply_template_settings_paginated( $template_id, $settings, $page = 1 ) {
    $per_page = 50; // Procesar 50 productos por batch
    
    $products = get_posts( array(
        'post_type'      => 'product',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'fields'         => 'ids',
    ) );

    if ( empty( $products ) ) {
        $this->logger->log( 'SUCCESS', 
            "Configuración de template '$template_id' aplicada a todos los productos" );
        return;
    }

    foreach ( $products as $product_id ) {
        // Aplicar configuración
        update_post_meta( $product_id, 'wom_template_' . $template_id, $settings );
    }

    // Schedule siguiente página
    as_schedule_single_action(
        0,
        'wom_apply_template_settings_paginated',
        array( $template_id, $settings, $page + 1 )
    );
}
```

**Requiere:** [Action Scheduler](https://actionscheduler.org/) (generalmente ya está en WooCommerce)

### Solución - Opción B: Sin dependencias externas (más simple):
```php
// ✅ ALTERNATIVA - Procesamiento en AJAX con progreso
public function ajax_apply_template_settings() {
    check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'No autorizado' );
    }

    $template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
    $page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
    $settings = isset( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : array();

    $per_page = 25;
    $products = get_posts( array(
        'post_type'      => 'product',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'fields'         => 'ids',
    ) );

    if ( empty( $products ) ) {
        wp_send_json_success( array( 'done' => true, 'message' => 'Completado' ) );
    }

    foreach ( $products as $product_id ) {
        update_post_meta( $product_id, 'wom_template_' . $template_id, $settings );
    }

    wp_send_json_success( array(
        'done'    => false,
        'page'    => $page + 1,
        'message' => "Procesados " . ( $page * $per_page ) . " productos"
    ) );
}

// En el frontend (admin-app.js):
// while (!response.done) {
//     response = await fetch(..., { 
//         body: formData + '&page=' + response.page 
//     });
// }
```

---

## RENDIMIENTO 2: Cachear búsquedas por meta_key

**Archivo:** `includes/class-course-sync.php` líneas 204-217  
**Severidad:** MEDIA (100+ queries en sincronización)  
**Impacto:** Ralentización en sitios con 100+ cursos

### Problema:
```php
public function get_product_by_moodle_id( $moodle_id ) {
    $args = array(
        'post_type'  => 'product',
        'meta_query' => array(
            array(
                'key'   => '_moodle_course_id',
                'value' => $moodle_id,
            ),
        ),
        'posts_per_page' => 1,
        'fields'         => 'ids',
    );
    // ... ejecuta query SIN CACHEO
}
```

### Solución:
```php
// ✅ MEJOR - Usar transients para cachear
public function get_product_by_moodle_id( $moodle_id ) {
    // Cachear durante 1 hora
    $cache_key = 'wom_product_moodle_id_' . $moodle_id;
    $product_id = get_transient( $cache_key );
    
    if ( false !== $product_id ) {
        return $product_id;
    }

    $args = array(
        'post_type'  => 'product',
        'meta_query' => array(
            array(
                'key'   => '_moodle_course_id',
                'value' => $moodle_id,
            ),
        ),
        'posts_per_page' => 1,
        'fields'         => 'ids',
    );

    $products = get_posts( $args );
    $product_id = ! empty( $products ) ? $products[0] : null;

    // Cachear resultado
    if ( $product_id ) {
        set_transient( $cache_key, $product_id, HOUR_IN_SECONDS );
    }

    return $product_id;
}

// Invalidar caché cuando cambia producto
add_action( 'save_post_product', function( $post_id ) {
    $moodle_id = get_post_meta( $post_id, '_moodle_course_id', true );
    if ( $moodle_id ) {
        delete_transient( 'wom_product_moodle_id_' . $moodle_id );
    }
}, 10, 1 );
```

---

## RENDIMIENTO 3: Cambiar time() por versión del plugin

**Archivo:** `frontend/class-frontend-renderer.php` líneas 47-48  
**Severidad:** BAJA (Navegadores no cachean CSS)  
**Impacto:** Más ancho de banda, slower page loads

### Problema:
```php
wp_enqueue_style( 'wom-frontend-style', WOO_OTEC_MOODLE_URL . 'frontend/css/courses.css', array(), time() );
wp_enqueue_script( 'wom-frontend-js', WOO_OTEC_MOODLE_URL . 'frontend/js/template-shortcodes.js', array( 'jquery' ), time(), true );
```

### Solución:

**PASO 1:** En `woo-otec-moodle.php` línea ~15, define versión:
```php
// ✅ Agregar después de define( 'WOO_OTEC_MOODLE_PATH', ... ):
define( 'WOO_OTEC_MOODLE_VERSION', '3.0.8' );
```

**PASO 2:** En `frontend/class-frontend-renderer.php`, usa la constante:
```php
// ❌ ANTES:
wp_enqueue_style( 'wom-frontend-style', WOO_OTEC_MOODLE_URL . 'frontend/css/courses.css', array(), time() );

// ✅ DESPUÉS:
wp_enqueue_style( 'wom-frontend-style', WOO_OTEC_MOODLE_URL . 'frontend/css/courses.css', array(), WOO_OTEC_MOODLE_VERSION );
```

**Repetir para todos los `wp_enqueue_style()` y `wp_enqueue_script()`.**

---

## RENDIMIENTO 4: Agregar timeout a downloads de imágenes

**Archivo:** `includes/class-admin-settings.php` línea ~515  
**Severidad:** MEDIA (Puede colgarse descargando imágenes)  
**Impacto:** Timeouts en admin

### Problema:
```php
$tmp = download_url( $image_url );
if ( is_wp_error( $tmp ) ) {
    return false;
}
```

### Solución:
```php
// ✅ MEJOR - Agregar timeout explícito
$tmp = download_url( $image_url, 30 ); // 30 segundos de timeout
if ( is_wp_error( $tmp ) ) {
    $this->logger->log( 'ERROR', 'No se pudo descargar imagen: ' . $tmp->get_error_message() );
    return false;
}
```

---

# 🧹 MEJORAS ADICIONALES

## Agregarconstante de versión donde falta

En `woo-otec-moodle.php` línea ~15:

```php
// ✅ Agregar después de constantes existentes:
define( 'WOO_OTEC_MOODLE_VERSION', '3.0.8' );
define( 'WOO_OTEC_MOODLE_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );
```

## Estandarizar prefijo de opciones WordPress

Todos deben usar `wom_*`:
- ❌ Cambiar: `woo_otec_moodle_*` → `wom_*`
- ❌ Cambiar: `woo_otec_email_*` → `wom_email_*`

```bash
# Script para actualizar BD (CUIDADO):
UPDATE wp_options SET option_name = 'wom_api_url' WHERE option_name = 'woo_otec_moodle_api_url';
UPDATE wp_options SET option_name = 'wom_api_token' WHERE option_name = 'woo_otec_moodle_api_token';
UPDATE wp_options SET option_name = 'wom_metadata_enabled' WHERE option_name = 'woo_otec_moodle_metadata_enabled';
```

---

# 📋 CHECKLIST FINAL

Después de aplicar estas soluciones:

- [ ] Cambiar token de Moodle
- [ ] Sanitizar `$_POST['fields']`
- [ ] Mejorar stripslashes()
- [ ] Validar respuestas de API
- [ ] Remover valores por defecto sensibles
- [ ] Agregar validación NONCE en save_moodle_id_field()
- [ ] Implementar paginación en sincronización
- [ ] Cachear búsquedas por meta
- [ ] Cambiar time() a versión del plugin
- [ ] Agregar timeout a downloads
- [ ] Estandarizar prefijos de opciones

---

**Documento generado:** 12 de Abril de 2026
**Criticidad:** 🟠 MEDIA - Aplica después de errores críticos

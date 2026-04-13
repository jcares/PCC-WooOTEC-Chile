# 🔥 SOLUCIÓN: Páginas Admin que No Funcionan
## Plugin PCC-WooOTEC-Chile v3.0.8

Este documento soluciona los problemas específicos que reportaste:
- ❌ `page=woo-otec-moodle-courses` - no sincroniza
- ❌ `page=woo-otec-moodle-metadata` - no deja seleccionar curso
- ❌ `page=woo-otec-moodle-template-builder` - no muestra vista en vivo
- ❌ Template Builder con `template=sample-product` y `template=email` - con problemas

---

# 📍 CAUSA RAÍZ

El problema principal es la duplicación de métodos AJAX:

```
Lo que sucede ahora:
1. Admin_Settings registra: ajax_save_metadata() 
2. Admin_Settings registra otra vez: ajax_save_metadata() (línea 598)
   → PHP Fatal Error: Cannot redeclare
   → Plugin nunca carga
   → Todas las páginas admin fallan
```

---

# 🔧 SOLUCIÓN PASO A PASO

## PASO 1: Arregla los errores críticos (si no lo has hecho ya)

**Esto es OBLIGATORIO antes de continuar:**

1. Abre `includes/class-admin-settings.php`
2. **ELIMINA líneas 598-643** (segunda copia de métodos duplicados)
3. **ELIMINA la línea 365** que tiene un `*/` sin apertura

✅ Verificación:
```bash
grep -c "public function ajax_save_metadata" includes/class-admin-settings.php
# Debe retornar: 1 (solo una vez)
```

## PASO 2: Recarga WordPress admin

Ve a tu dashboard de WordPress. Si todavía ves errores:

1. Abre `/wp-content/debug.log` en tu servidor
2. Busca errores (línea final será el más reciente)
3. Revisa qué línea exacta está causando el error

---

# 🛠️ PROBLEMA 1: Páginas de Courses no sincroniza

**Síntoma:** La página `woo-otec-moodle-courses` no muestra cursos o no sincroniza  
**Causa:** Métodos duplicados + hooks AJAX conflictivos

### Verificación:

Abre navegador → Consola DevTools (F12) → Pestaña "Network":

1. Click en botón "Sincronizar Cursos"
2. En Network, busca requests AJAX a `admin-ajax.php`
3. Si no aparece nada, el AJAX nunca se envió
4. Si aparece pero con error, el AJAX falló

### Solución:

**A) Verificar que el hook está registrado correctamente:**

En `includes/class-admin-settings.php`, busca línea ~74:

```php
// ✅ DEBE ESTAR ASÍ:
add_action( 'wp_ajax_wom_sync_courses', array( $this, 'ajax_sync_courses' ) );
```

**B) Si el AJAX devuelve error 500:**

Activa WP_DEBUG:
```php
// En wp-config.php, agrega:
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Luego ve a `/wp-content/debug.log` y busca "wom_sync_courses".

**C) Si el AJAX retorna 400 o 403:**

Problema de nonce. Verifica:
```php
// En clase-admin-settings.php línea ~46
public function ajax_sync_courses() {
    check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' ); // ← Debe estar
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'No autorizado' );
    }
```

**D) En el frontend (admin/js/admin-app.js):**

Verifica que está pasando el nonce correctamente:

```javascript
// ✅ DEBE ESTAR ASÍ:
const data = new FormData();
data.append('action', 'wom_sync_courses');
data.append('nonce', woomootecVars.nonce);
data.append('template_id', document.getElementById('template-select').value);

fetch(ajaxurl, {
    method: 'POST',
    body: data
})
.then(r => r.json())
.then(data => {
    if (data.success) {
        console.log('Sincronización completada');
        location.reload();
    } else {
        console.error('Error:', data.data);
    }
});
```

---

# 🛠️ PROBLEMA 2: Metadata - No deja seleccionar curso

**Síntoma:** Dropdown de cursos no muestra opciones o está deshabilitado  
**Causa:** Variable `$template_manager` no está disponible en la vista

### Solución:

**En `admin/partials/metadata-display.php` línea ~1:**

Verifica que la variable está disponible:

```php
// ✅ AL INICIO DEL ARCHIVO DEBE ESTAR:
<?php
// Estas variables deben estar disponibles del render_metadata_page()
// $template_manager
// $logger

// Si no está, agrega:
if ( ! isset( $template_manager ) ) {
    echo '<div class="wom-error">Error: template_manager no disponible</div>';
    return;
}
?>
```

**En `includes/class-admin-settings.php` línea ~207 (render_metadata_page):**

Verifica que pasa las variables correctamente:

```php
// ✅ DEBE ESTAR ASÍ:
public function render_metadata_page() {
    $template_manager = $this->template_manager;
    $logger = $this->logger;
    require_once WOO_OTEC_MOODLE_PATH . 'admin/partials/metadata-display.php';
}
```

**En `admin/partials/metadata-display.php` línea ~45:**

Si tienes un dropdown de cursos, verifica que se llena:

```php
// ✅ CORRECTO:
$templates = $template_manager->get_available_templates();
if ( ! empty( $templates ) ) {
    foreach ( $templates as $template_id => $template_data ) {
        echo '<option value="' . esc_attr( $template_id ) . '">' . esc_html( $template_data['name'] ) . '</option>';
    }
}
```

---

# 🛠️ PROBLEMA 3: Template Builder - No muestra vista en vivo

**Síntoma:** La página carga pero no muestra preview de template  
**Causa:** `get_template_defaults()` vs `get_default_config()` (ya corregimos esto)

### Solución (ya aplicada):

En `admin/partials/template-builder.php` línea 17:

```php
// ✅ CORRECTO (ya lo arreglamos):
$defaults = $template_manager->get_template_defaults();

// ❌ INCORRECTO (lo que causaba error):
$defaults = $template_manager->get_default_config();
```

### Si aún no muestra preview:

**Verificación 1:** Abre DevTools (F12) → Consola → busca errores Javascript

```javascript
// Típicamente verás algo como:
Uncaught TypeError: Cannot read property 'colors' of undefined
```

**Solución:** En `admin/js/template-builder.js`, verifica que se cargan los defaults:

```javascript
// ✅ CORRECTO:
jQuery(document).ready(function($) {
    const defaults = woomootecTemplateVars.defaults; // De wp_localize_script
    if (!defaults) {
        console.error('Defaults no disponibles');
        return;
    }
    // ... resto del código
});
```

**Verificación 2:** Verifica que `wp_localize_script` está siendo llamado

En `includes/class-admin-settings.php` línea ~162 (enqueue_assets):

```php
// ✅ DEBE ESTAR:
if ( strpos( $hook, 'template-builder' ) !== false ) {
    wp_enqueue_style( 'wom-template-builder-style', WOO_OTEC_MOODLE_URL . 'admin/css/template-builder.css', array(), time() );
    wp_enqueue_script( 'wom-template-builder-js', WOO_OTEC_MOODLE_URL . 'admin/js/template-builder.js', array( 'jquery', 'wp-color-picker' ), time(), true );
    
    // ✅ AQUÍ DEBE ESTAR ESTO:
    wp_localize_script( 'wom-template-builder-js', 'woomootecTemplateVars', array(
        'defaults' => $this->template_manager->get_template_defaults(),
        'nonce'    => wp_create_nonce( 'woo-otec-moodle-nonce' ),
        'ajaxurl'  => admin_url( 'admin-ajax.php' ),
    ) );
}
```

---

# 🛠️ PROBLEMA 4: Templates `sample-product` y `email` con problemas

**Síntoma:** Páginas `template=sample-product` y `template=email` no funcionan  
**Causa:** Template Manager no define todas las plantillas o hay métodos faltantes

### Verificación 1: ¿Existen las plantillas?

En `includes/class-template-manager.php` línea ~20-35:

```php
// ✅ DEBE CONTENER:
private $templates = array(
    'product-catalogue' => array(
        'id'          => 'product-catalogue',
        'name'        => 'Catálogo de Productos',
        'description' => 'Grid de productos...',
        'file'        => 'templates/template-product-catalogue.php',
    ),
    'sample-product' => array(  // ← Verificar que existe
        'id'          => 'sample-product',
        'name'        => 'Producto Individual',
        'description' => 'Vista detallada...',
        'file'        => 'templates/template-sample-product.php',
    ),
    'email' => array(  // ← Verificar que existe
        'id'          => 'email',
        'name'        => 'Email de Matrícula',
        'description' => 'Correo que recibe...',
        'file'        => 'templates/template-email.php',
    ),
);
```

### Verificación 2: ¿Existen los archivos?

```bash
ls -la templates/template-product-catalogue.php
ls -la templates/template-sample-product.php
ls -la templates/template-email.php
```

Si NO existen, créalos con contenido básico:

```php
// ✅ templates/template-sample-product.php:
<?php
/**
 * Plantilla: Producto Individual
 */
echo '<div class="wom-sample-product">';
echo '<h2>' . esc_html( get_the_title() ) . '</h2>';
echo '<p>Contenido del producto...</p>';
echo '</div>';
?>
```

### Verificación 3: ¿El formulario envía correctamente?

En `admin/js/template-builder.js`, verifica que el valor está en el formato correcto:

```javascript
// ✅ CORRECTO - Debe enviar template_id como string:
const templateId = 'sample-product'; // No un número
const formData = {
    template_id: templateId,
    config: JSON.stringify(...),
    nonce: woomootecTemplateVars.nonce
};

fetch(ajaxurl, {
    method: 'POST',
    body: new URLSearchParams({
        action: 'wom_save_template_config',
        ...formData
    })
});
```

### Verificación 4: ¿El AJAX handler valida correctamente?

En `includes/class-template-manager.php` línea ~294:

```php
// ✅ DEBE VALIDAR:
public function ajax_save_template_config() {
    check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'No autorizado' );
    }

    $template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
    
    // ✅ VALIDAR QUE EXISTE:
    if ( ! isset( $this->templates[ $template_id ] ) ) {
        wp_send_json_error( 'Template ID inválido: ' . $template_id );
    }

    $config = isset( $_POST['config'] ) ? json_decode( wp_unslash( $_POST['config'] ), true ) : array();
    
    if ( ! is_array( $config ) ) {
        wp_send_json_error( 'Config debe ser JSON válido' );
    }

    if ( $this->save_config( $template_id, $config ) ) {
        wp_send_json_success( array(
            'message' => 'Configuración guardada para: ' . $template_id,
        ) );
    } else {
        wp_send_json_error( 'No se pudo guardar configuración' );
    }
}
```

---

# 🔍 DEBUGGING AVANZADO

Si todavía tienes problemas, agrega este código temporal:

**En `includes/class-admin-settings.php` línea 1 (después de `<?php`):**

```php
<?php
/**
 * DEBUG MODE - Agregar esto TEMPORALMENTE para debugging
 */
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'ADMIN_SETTINGS LOADED - ' . current_time( 'mysql' ) );
}

// ... resto del código
```

**En `admin/partials/template-builder.php` línea 1:**

```php
<?php
// DEBUG
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'TEMPLATE_BUILDER LOADED' );
    error_log( 'Template Manager: ' . ( isset( $template_manager ) ? 'OK' : 'MISSING' ) );
    error_log( 'Active template: ' . $active_template );
}
?>
```

Luego revisa `/wp-content/debug.log` para ver qué sucede.

---

# 📋 CHECKLIST - PÁGINAS ADMIN

Verifica cada página cargue correctamente:

- [ ] Dashboard (`woo-otec-moodle`) - No debería tener errores
- [ ] Configuración (`woo-otec-moodle-settings`) - Debería mostrar campos
- [ ] **Cursos** (`woo-otec-moodle-courses`) - Click "Sincronizar" sin errores
- [ ] Metadatos (`woo-otec-moodle-metadata`) - Dropdown funciona
- [ ] **Template Builder** (`woo-otec-moodle-template-builder`) - Preview funciona
- [ ] Template Builder - `template=sample-product` - Funciona
- [ ] Template Builder - `template=email` - Funciona
- [ ] Sincronización (`woo-otec-moodle-cron`) - Sin errores
- [ ] Email (`woo-otec-moodle-email`) - Sin errores
- [ ] Usuarios (`woo-otec-moodle-users`) - Sin errores
- [ ] Bitácora (`woo-otec-moodle-logs`) - Muestra logs

---

# 🚨 SI NADA FUNCIONA

Este es el procedimiento nuclear:

**Paso 1:** SSH a servidor y desactiva el plugin:
```bash
cd /wp-content/plugins/woo-otec-moodle/
mv woo-otec-moodle.php woo-otec-moodle.php.bak
```

**Paso 2:** Revisa `/wp-content/debug.log` y busca el PRIMER error crítico:

```bash
tail -100 /wp-content/debug.log | grep -i "fatal\|error"
```

**Paso 3:** Reporta ese error específico

**Paso 4:** Reactiva:
```bash
mv woo-otec-moodle.php.bak woo-otec-moodle.php
```

---

# 💡 PRÓXIMOS PASOS

Una vez que las paginas funcionen:

1. ✅ Sincroniza cursos y verifica que aparezcan productos
2. ✅ Prueba personalización de templates
3. ✅ Revisa logs en Bitácora
4. ✅ Revisa que emails se envíen correctamente
5. ✅ Aplica las soluciones de seguridad del otro documento

---

**Documento generado:** 12 de Abril de 2026
**Última actualización:** Hoy
**Criticidad:** 🔴 URGENTE - Requiere acción inmediata


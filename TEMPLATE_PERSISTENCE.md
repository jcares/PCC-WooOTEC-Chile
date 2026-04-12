# Template Parameter Persistence - Implementación v3.0.8

## Resumen de Cambios

Se ha implementado un sistema completo de persistencia de parámetros de template a WooCommerce, permitiendo que la configuración visual y de ajustes del template se aplique automáticamente a los productos de cursos.

## Características Implementadas

### 1. **Sincronización de Parámetros de Template a Metadatos de Producto**

#### Nuevo método en `class-admin-settings.php`:
```php
ajax_save_template_config()
- Guarda configuración de template con opción de aplicar a todos los productos
- Campos aplicables: default_price, default_image, show_category, show_price, show_meta, button_text, button_text_enroll, layout, columns
```

#### Nuevo método privado:
```php
apply_template_settings_to_products()
- Itera sobre todos los productos de WooCommerce
- Aplica precio por defecto si el producto no tiene precio
- Aplica imagen por defecto si el producto no tiene imagen
- Guarda opciones de visibilidad como metatags
- Guarda textos de botones
- Guarda configuración de layout y columnas
```

### 2. **Gestión de Imágenes en Cursos**

#### Nuevo handler AJAX en `class-course-sync.php`:
```php
ajax_set_product_image()
- Recibe: product_id, attachment_id
- Establece la imagen como thumbnail del producto
- Retorna confirmación y URL de la imagen
```

#### Interfaz en `courses-display.php`:
- Botón de cámara en cada fila de producto
- Click en imagen abre media uploader
- Update automático al seleccionar imagen
- Placeholder si no hay imagen

### 3. **Sincronización Con Template al Actualizar**

#### Nuevo handler AJAX en `class-course-sync.php`:
```php
ajax_sync_courses_with_template()
- Sincroniza cursos desde Moodle
- Aplica metatags de template si se solicita
- Guarda datos del curso como metatags
- Control granular con parámetro apply_template
```

#### Método privado:
```php
apply_template_metatags()
- Guarda: _moodle_course_name, _moodle_course_summary, _moodle_course_category_id
- Marca con _moodle_template_applied = 1
```

### 4. **Metatags Guardados en Productos**

```php
// Parámetros de template aplicados
_moodle_course_default_price      => precio por defecto
_moodle_course_default_image      => ID del attachment
_moodle_show_category             => '1' o '0'
_moodle_show_price                => '1' o '0'
_moodle_show_meta                 => '1' o '0'
_moodle_button_text               => texto del botón
_moodle_button_text_enroll        => texto de inscripción
_moodle_layout                    => layout seleccionado
_moodle_columns                   => número de columnas

// Información del curso
_moodle_course_name               => nombre del curso
_moodle_course_summary            => descripción
_moodle_course_category_id        => ID de categoría
_moodle_template_applied          => '1' si template fue aplicado
```

## Flujos de Trabajo

### Flujo 1: Guardar Template con Aplicación a Productos

1. Usuario edita template en Template Builder
2. Selecciona "Aplicar a todos los productos"
3. AJAX Handler `ajax_save_template_config()` se ejecuta
4. Sistema itera sobre todos los productos WooCommerce
5. Aplica precio por defecto, imagen, y opciones de visibilidad
6. Cada producto recibe metatags correspondientes

**Endpoint AJAX:**
```
action: wom_save_template_config
POST data:
  - nonce: verificación seguridad
  - template_id: ID de template
  - config: objeto JSON con settings
  - apply_to_products: boolean
```

### Flujo 2: Cambiar Imagen de Producto

1. Usuario hace click en botón cámara en Cursos
2. Abre media uploader de WordPress
3. Selecciona imagen
4. AJAX Handler `ajax_set_product_image()` se ejecuta
5. Imagen se establece como thumbnail
6. DOM se actualiza sin reload

**Endpoint AJAX:**
```
action: wom_set_product_image
POST data:
  - nonce: verificación seguridad
  - product_id: ID del producto
  - attachment_id: ID del attachment
```

### Flujo 3: Sincronizar Cursos Desde Moodle

1. Usuario hace click "Sincronizar ahora" en Cursos
2. AJAX Handler `ajax_sync_courses_with_template()` se ejecuta
3. Obtiene cursos desde API de Moodle
4. Para cada curso:
   - Busca o crea producto correspondiente
   - Aplica metatags de información del curso
   - Si apply_template=true, aplica settings de template
5. Retorna estadísticas de sincronización

**Endpoint AJAX:**
```
action: wom_sync_courses
POST data:
  - nonce: verificación seguridad
  - apply_template: boolean (aplica settings)
```

## Archivos Modificados

### `/includes/class-admin-settings.php`
- ✅ Nuevo método `ajax_save_template_config()`
- ✅ Nuevo método `apply_template_settings_to_products()`
- ✅ Nuevo método `get_or_create_attachment()` para descargar imágenes

### `/includes/class-course-sync.php`
- ✅ Registrados nuevos AJAX handlers en constructor
- ✅ Nuevo método `ajax_set_product_image()`
- ✅ Nuevo método `ajax_sync_courses_with_template()`
- ✅ Nuevo método `apply_template_metatags()`

### `/admin/partials/courses-display.php`
- ✅ UI mejorado con columna de Imagen
- ✅ Botón de cámara para cambiar imagen
- ✅ AJAX handler para sincronización con template
- ✅ Feedback visual con notificaciones

## Integración Con Sistema Existente

### Template Manager
- Utiliza métodos existentes `get_saved_config()`, `save_config()`, `validate_config()`
- Mantiene compatibilidad con estructura de opciones

### Logger
- Todos los eventos se registran con `$logger->log()`
- Nuevo método `log_sync()` para eventos de sincronización

### AJAX Security
- Todos los endpoints verifican nonce con `check_ajax_referer()`
- Verifican permisos con `current_user_can( 'manage_options' )`
- Sanitización completa de datos POST

## Testing Manual

### Test 1: Guardar Template y Aplicar a Productos
```php
// En Consola JavaScript del Admin
jQuery.ajax({
  url: ajaxurl,
  type: 'POST',
  data: {
    action: 'wom_save_template_config',
    nonce: wooOtecMoodle.nonce,
    template_id: 'product-catalogue',
    config: JSON.stringify({
      colors: { primary: '#6366f1' },
      settings: {
        default_price: 29990,
        columns: 3
      }
    }),
    apply_to_products: true
  }
});
```

### Test 2: Cambiar Imagen de Producto
```javascript
jQuery.ajax({
  url: ajaxurl,
  type: 'POST',
  data: {
    action: 'wom_set_product_image',
    nonce: wooOtecMoodle.nonce,
    product_id: 123,
    attachment_id: 456
  }
});
```

### Test 3: Sincronizar con Template
```javascript
jQuery.ajax({
  url: ajaxurl,
  type: 'POST',
  data: {
    action: 'wom_sync_courses',
    nonce: wooOtecMoodle.nonce,
    apply_template: true
  }
});
```

## Validación de Implementación

✅ **Persistencia de Parámetros:**
- default_price guardado en `_moodle_course_default_price`
- default_image guardado como attachment ID
- Opciones de visibilidad guardadas como metatags

✅ **Sincronización de Imagen:**
- Imagen se establece como thumbnail
- Interfaz actualiza sin reload
- Feedback visual confirmando cambio

✅ **Metatags de Producto:**
- Información del curso guardada en product meta
- Template settings aplicables a todos los productos
- Marcador de template aplicado

✅ **Seguridad:**
- Nonce verification en todos los endpoints
- Capability checks (manage_options)
- Sanitización de datos POST

## Próximos Pasos

1. **Frontend Renderer**: Actualizar para leer metatags de producto
2. **Preview Generator**: Generar previsualización con datos reales
3. **Email Template**: Incluir metatags de producto en emails de inscripción
4. **Reportes**: Dashboard mostrando estadísticas de template aplicado

## Errores Comunes y Soluciones

### "No autorizado"
- Verificar que usuario esté logueado como admin
- Verificar nonce en petición AJAX

### "Imagen no se actualiza"
- Verificar que attachment_id exista en WordPress
- Verificar que producto exista

### "Metatags no se guardan"
- Verificar que producto_id sea válido
- Verificar que wp_options esté escribible

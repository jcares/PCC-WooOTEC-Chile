# Template Shortcodes - Guía de Uso

**Versión:** 3.0.8+
**Última Actualización:** 11 de Abril de 2026

---

## 📋 Resumen

Se han agregado 3 shortcodes nuevos que permiten mostrar productos con metadatas desde Moodle incluidas:

1. **[wom_sample_product]** - Producto individual con detalles completos
2. **[wom_product_catalogue]** - Catálogo de productos en grid
3. **[wom_product_enroll]** - Formulario de inscripción rápida

---

## 🔧 Shortcodes Disponibles

### 1. [wom_sample_product]

Muestra un producto individual con toda su información de Moodle.

**Sintaxis:**
```php
[wom_sample_product id="123"]
[wom_sample_product id="123" template="sample-product"]
```

**Atributos:**
- `id` **(requerido)**: ID del producto WooCommerce
- `template` (opcional): Template a usar (default: "sample-product")

**Ejemplo de Uso:**
```html
<!-- Producto individual simple -->
<div class="course-detail">
    [wom_sample_product id="42"]
</div>

<!-- Con template específico -->
[wom_sample_product id="42" template="sample-product"]
```

**Información Mostrada:**
- ✅ Imagen del producto (con fallback a placeholder)
- ✅ Título (nombre de curso desde Moodle)
- ✅ Descripción (summary desde Moodle)
- ✅ Categoría (si está configurado mostrar)
- ✅ Precio por defecto (si está configurado)
- ✅ Botones: "Ver Curso" y "Matricularme"

**Metadatas Leídas:**
```php
_moodle_course_name
_moodle_course_summary
_moodle_course_category_id
_moodle_course_default_price
_moodle_course_default_image
_moodle_show_category
_moodle_show_price
_moodle_show_meta
_moodle_button_text
_moodle_button_text_enroll
```

---

### 2. [wom_product_catalogue]

Muestra un catálogo de productos en grid, ideal para landing pages.

**Sintaxis:**
```php
[wom_product_catalogue]
[wom_product_catalogue limit="6" columns="3"]
[wom_product_catalogue template="product-catalogue" category="python"]
```

**Atributos:**
- `template` (opcional): Template a usar (default: "product-catalogue")
- `limit` (opcional): Número de productos a mostrar (default: 6)
- `columns` (opcional): Número de columnas en el grid (default: 3)
- `category` (opcional): Filtrar por categoría (slug)

**Ejemplos de Uso:**
```html
<!-- Catálogo por defecto (6 productos, 3 columnas) -->
[wom_product_catalogue]

<!-- Catálogo personalizado -->
[wom_product_catalogue limit="12" columns="4" template="product-catalogue"]

<!-- Catálogo filtrado por categoría -->
[wom_product_catalogue category="programacion" limit="8" columns="2"]

<!-- En elemento div -->
<section class="courses">
    [wom_product_catalogue limit="9" columns="3"]
</section>
```

**Características:**
- ✅ Grid responsive (1 columna en móvil)
- ✅ Espaciado profesional entre productos
- ✅ Cada producto es un shortcode [wom_sample_product]
- ✅ Filtrado por categoría

---

### 3. [wom_product_enroll]

Formulario simple de inscripción para un producto.

**Sintaxis:**
```php
[wom_product_enroll id="123"]
```

**Atributos:**
- `id` **(requerido)**: ID del producto WooCommerce

**Ejemplo de Uso:**
```html
<!-- Formulario de inscripción en página de detalles -->
<div class="enrollment">
    [wom_product_enroll id="42"]
</div>

<!-- En página de producto -->
[wom_product_enroll id="123"]
```

**Información Mostrada:**
- ✅ Nombre del curso
- ✅ Precio
- ✅ Botón "Inscribirse Ahora"
- ✅ Validación de usuario logueado

---

## 🎨 Estilos Incluidos

Todos los shortcodes incluyen estilos profesionales:

- **Colores:** Respetan configuración de template (color primario)
- **Tipografía:** System fonts responsive
- **Animaciones:** Transiciones suaves al hover
- **Responsive:** Adaptan a todos los tamaños de pantalla
- **Accesibilidad:** Buttons con estados claros

**Archivo de estilos:** `frontend/css/template-shortcodes.css`

---

## 🔗 Funcionalidades AJAX

### Inscripción desde Shortcode

Cuando un usuario hace click en **"Matricularme"** o **"Inscribirse Ahora"**:

1. Validación de usuario logueado
2. Confirmación del usuario
3. AJAX request a `wom_enroll_product`
4. Creación de orden automática
5. Procesamiento de inscripción en Moodle
6. Email de bienvenida enviado
7. Recarga de página

**Flujo de Datos:**
```
Click en Botón
    ↓
Validar usuario logueado
    ↓
Confirmar inscripción
    ↓
AJAX POST: wom_enroll_product
    ↓
Crear orden WooCommerce
    ↓
Procesar inscripción (Moodle Enroll)
    ↓
Enviar email
    ↓
Notificación de éxito
```

---

## 📱 Responsive Behavior

### Escritorio (1024px+)
- [wom_product_catalogue] → 3 columnas (default)
- Espaciado: 24px entre items
- Ancho máximo: 100%

### Tablet (768px - 1023px)
- [wom_product_catalogue] → 2 columnas
- Espaciado: 20px
- Ancho: 95%

### Móvil (<768px)
- [wom_product_catalogue] → 1 columna
- Espaciado: 16px
- Ancho: 100%

---

## 🔒 Seguridad

✅ **Nonce Verification:** Todos los AJAX requests verifican nonce
✅ **Capability Checks:** Solo usuarios logueados pueden inscribirse
✅ **Data Sanitization:** IDs y datos sanitizados
✅ **Escaping:** HTML escapado correctamente

---

## 🐛 Solución de Problemas

### Shortcode no se muestra
- ✅ Verificar que el plugin está activo
- ✅ Verificar ID del producto es válido
- ✅ Verificar que producto tiene metatags (_moodle_course_id)

### Imagen no se carga
- ✅ Verificar que attachment existe
- ✅ Verificar metatag `_moodle_course_default_image`
- ✅ Verificar permisos de lectura del archivo

### Inscripción no funciona
- ✅ Verificar usuario está logueado
- ✅ Verificar nonce es válido
- ✅ Revisar logs en WordPress (Debug)
- ✅ Verificar API de Moodle está conectada

### Template no se aplica
- ✅ Verificar template existe en Template Manager
- ✅ Verificar nombre de template es correcto
- ✅ Verificar configuración guardada en wp_options

---

## 💡 Casos de Uso

### 1. Landing Page de Cursos
```html
<section class="courses-hero">
    <h1>Nuestros Cursos Disponibles</h1>
    [wom_product_catalogue limit="12" columns="3"]
</section>
```

### 2. Catálogo por Categoría
```html
[wom_product_catalogue category="programa-python" limit="6"]
[wom_product_catalogue category="data-science" limit="6"]
```

### 3. Página Detallada de Curso
```html
<div class="course-details">
    [wom_sample_product id="42"]
</div>

<div class="enrollment-section">
    [wom_product_enroll id="42"]
</div>
```

### 4. Dashboard de Usuario Logueado
```html
<?php if (is_user_logged_in()) : ?>
    <h2>Mis Cursos Disponibles</h2>
    [wom_product_catalogue limit="8"]
<?php else : ?>
    <p>Por favor <a href="<?php echo wp_login_url(); ?>">inicia sesión</a></p>
<?php endif; ?>
```

---

## 📊 Metadatas Utilizados

Los shortcodes leen automáticamente estos metadatas:

**De Template:**
- `_moodle_course_default_price` - Precio por defecto
- `_moodle_course_default_image` - ID de imagen
- `_moodle_show_category`, `_moodle_show_price`, `_moodle_show_meta`
- `_moodle_button_text`, `_moodle_button_text_enroll`
- `_moodle_layout`, `_moodle_columns`

**De Moodle:**
- `_moodle_course_name` - Nombre del curso
- `_moodle_course_summary` - Descripción
- `_moodle_course_category_id` - Categoría
- `_moodle_course_id` - Link a Moodle

**Producto WooCommerce:**
- `_price` - Precio del producto
- `_thumbnail_id` - Imagen del producto
- `post_title` - Nombre del producto
- `post_content` - Descripción del producto

---

## 🔧 Archivos Relacionados

- `includes/class-template-shortcodes.php` - Lógica de shortcodes
- `frontend/js/template-shortcodes.js` - JavaScript de interacción
- `frontend/css/template-shortcodes.css` - Estilos
- `includes/class-enrollment-manager.php` - Handler AJAX de inscripción

---

## ✅ Validación

✅ Shortcodes funcionan en cualquier página/post
✅ Metatags se sincronizados automáticamente
✅ Inscripción procesa correctamente
✅ Emails se envían después de inscripción
✅ Responsive en todos los dispositivos
✅ Seguridad implementada

---

*Documentación de Template Shortcodes v3.0.8+*
*Última actualización: 11 Abril 2026*

# Template Builder URLs - Guía de Acceso Correcto

**Versión:** 3.0.8+
**Última Actualización:** 11 de Abril de 2026

---

## ⚠️ PROBLEMA: URLs Antiguas No Funcionan

Las URLs con formato antiguo **NO funcionan**:
```
❌ ?page=woo-otec-moodle&tab=template-builder&template=sample-product
❌ ?page=woo-otec-moodle&tab=template-builder&template=semail
```

**Razón:** El sistema ha sido actualizado a usar una arquitectura de "páginas" en lugar de "tabuladores". Ahora cada sección tiene su propia página.

---

## ✅ URLs Correctas

### Acceso a Template Builder (Personalización)

La nueva estructura es:
```
?page=woo-otec-moodle-template-builder&template=[TEMPLATE_ID]
```

### Templates Disponibles

| Template ID | Nombre | URL |
|------------|--------|-----|
| `product-catalogue` | Catálogo de Productos | `?page=woo-otec-moodle-template-builder&template=product-catalogue` |
| `sample-product` | Producto Individual | `?page=woo-otec-moodle-template-builder&template=sample-product` |
| `email` | Email de Matrícula | `?page=woo-otec-moodle-template-builder&template=email` |

### Ejemplos Correctos

```
✅ ?page=woo-otec-moodle-template-builder&template=sample-product
✅ ?page=woo-otec-moodle-template-builder&template=email
✅ ?page=woo-otec-moodle-template-builder&template=product-catalogue
```

---

## 🔗 Todas las Páginas del Admin

```
Dashboard
?page=woo-otec-moodle

Configuración
?page=woo-otec-moodle-settings

Cursos
?page=woo-otec-moodle-courses

Metadatos
?page=woo-otec-moodle-metadata

Personalización (Template Builder)
?page=woo-otec-moodle-template-builder
?page=woo-otec-moodle-template-builder&template=sample-product
?page=woo-otec-moodle-template-builder&template=email

WooCommerce
?page=woo-otec-moodle-wc

Email
?page=woo-otec-moodle-email

Usuarios
?page=woo-otec-moodle-users

Bitácora
?page=woo-otec-moodle-logs
```

---

## 🎯 Cómo Cambiar Entre Templates

1. **Acceder a Personalización:**
   - Click en pestaña "Personalización" en Admin
   - O acceder a: `?page=woo-otec-moodle-template-builder`

2. **Ver Tabs de Templates:**
   - En la página template-builder verás tabs para cada template
   - Tabs disponibles:
     - Catálogo de Productos (product-catalogue)
     - Producto Individual (sample-product)
     - Email de Matrícula (email)

3. **Hacer Click en Tab Deseado:**
   - La URL actualiza automáticamente con el parámetro `&template=[ID]`
   - Ejemplo: Click en "Producto Individual" → URL se actualiza a `&template=sample-product`

---

## 🔍 Validación de Templates

El sistema valida que:
1. ✅ El parámetro `template` sea válido
2. ✅ Exista en la lista de templates disponibles
3. ✅ Si no es válido, muestra por defecto "product-catalogue"

**Código de validación:**
```php
$templates = $template_manager->get_available_templates();
$active_template = isset( $_GET['template'] ) ? sanitize_text_field( $_GET['template'] ) : 'product-catalogue';

if ( ! isset( $templates[ $active_template ] ) ) {
    $active_template = 'product-catalogue'; // Fallback
}
```

---

## 🛠️ Referencia de Templates

### 1. Catálogo de Productos (`product-catalogue`)
**Descripción:** Grid de productos con metadatos seleccionados
**URL:** `?page=woo-otec-moodle-template-builder&template=product-catalogue`
**Parámetros Configurables:**
- Colors (primario, hover, texto, bordes)
- Typography (fuente, tamaños)
- Layout settings (columnas, espaciado)
- Visibilidad (mostrar categoría, precio, metadatos)

### 2. Producto Individual (`sample-product`)
**Descripción:** Vista detallada de un producto
**URL:** `?page=woo-otec-moodle-template-builder&template=sample-product`
**Parámetros Configurables:**
- Colors completos
- Tipografía
- Textos de botones
- Mostrar/ocultar secciones

### 3. Email de Matrícula (`email`)
**Descripción:** Correo que recibe el alumno al matricularse
**URL:** `?page=woo-otec-moodle-template-builder&template=email`
**Parámetros Configurables:**
- Logo del email
- Colors
- Textos (saludo, instrucciones)
- Footer personalizado

---

## 📝 Cambios de Arquitectura (v3.0.7→v3.0.8)

**Antes (Deprecated):**
```
Menú: Parámetro "tab" dentro de página única
URL: ?page=woo-otec-moodle&tab=template-builder&template=...
```

**Ahora (Actual):**
```
Menú: Páginas diferentes en el menú lateral
URL: ?page=woo-otec-moodle-template-builder&template=...
```

**Beneficios:**
- ✅ URLs más limpias y predecibles
- ✅ Mejor SEO
- ✅ Cada página es independiente
- ✅ Navegación más rápida

---

## 🔗 Links Rápidos (Para Bookmarks)

Copia estos links para acceder rápidamente:

**Template Builder - Catálogo:**
```
wp-admin/?page=woo-otec-moodle-template-builder&template=product-catalogue
```

**Template Builder - Producto Individual:**
```
wp-admin/?page=woo-otec-moodle-template-builder&template=sample-product
```

**Template Builder - Email:**
```
wp-admin/?page=woo-otec-moodle-template-builder&template=email
```

**Dashboard:**
```
wp-admin/?page=woo-otec-moodle
```

---

## ✅ Verificación

Si las URLs siguen sin funcionar:

1. ✅ Verificar que el plugin está activo
2. ✅ Limpiar cache de navegador (Ctrl+Shift+Del)
3. ✅ Verificar que template ID existe en class-template-manager.php
4. ✅ Revisar error en consola JavaScript (F12)
5. ✅ Verificar permisos (debe ser admin)

---

*Documentación de URLs de Template Builder v3.0.8+*
*Última actualización: 11 Abril 2026*

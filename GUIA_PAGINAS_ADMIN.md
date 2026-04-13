# 🖥️ GUÍA COMPLETA - PÁGINAS DEL ADMIN

**Documento:** Especificación de funcionalidad por página  
**Plugin:** WOO-OTEC-MOODLE v3.0.8  
**Fecha:** 12 de Abril de 2026  
**URL Base:** `https://cipresalto.cl/wp-admin/admin.php?page=woo-otec-moodle-***`

---

## 📍 ÍNDICE DE PÁGINAS

1. [Dashboard](#1-dashboard)
2. [Configuración](#2-configuración)
3. [Cursos](#3-cursos)
4. [SSO](#4-sso)
5. [Metadatos](#5-metadatos) ← FOCO DEL DÍA
6. [Template Builder](#6-template-builder)
7. [Email](#7-email)
8. [Cron](#8-cron)
9. [Logs](#9-logs)
10. [Usuarios](#10-usuarios)
11. [WooCommerce](#11-woocommerce)

---

## 1. 📊 DASHBOARD

**URL:** `?page=woo-otec-moodle`  
**Roles requeridos:** `manage_options`

### ¿Qué debe mostrar?

```
┌─────────────────────────────────────────────┐
│ 🏠 OTEC Moodle Integration - Panel Principal│
├─────────────────────────────────────────────┤
│                                             │
│  📦 Cursos Sincronizados:        45        │
│  👥 Usuarios Sincronizados:    1,230      │
│  ✅ Última Sincronización: Hoy 14:35      │
│  ⚙️  Estado del Plugin:      ✅ Activo    │
│                                             │
│  [Sincronizar Ahora] [Configurar]         │
│                                             │
└─────────────────────────────────────────────┘
```

### Funcionalidad:
- ✅ Mostrar 4 tarjetas de estadísticas principales
- ✅ Botones de acción rápida
- ✅ Mostrar estado del plugin (verde = ok, rojo = error)

### Archivos involucrados:
- `includes/class-admin-settings.php` - `render_dashboard_page()`
- `admin/partials/dashboard-display.php` - Template HTML

---

## 2. ⚙️ CONFIGURACIÓN

**URL:** `?page=woo-otec-moodle-settings`  
**Roles requeridos:** `manage_options`

### ¿Qué debe mostrar?

```
┌─────────────────────────────────────────────┐
│ ⚙️ Configuración General                    │
├─────────────────────────────────────────────┤
│                                             │
│ 📍 URL de Moodle:                          │
│   [https://cipresalto.cl/aulavirtual    ]  │
│                                             │
│ 🔑 Token de Moodle:                        │
│   [••••••••••••••••••••••••••••••••••] 👁️ │
│                                             │
│ [Probar Conexión] [Guardar]               │
│                                             │
│ ✅ Conexión: OK                             │
│ 📌 Última prueba: 12 Apr 2026 14:35 UTC   │
│                                             │
└─────────────────────────────────────────────┘
```

### Funcionalidad:
- ✅ Guardar URL de Moodle
- ✅ Guardar Token de Moodle (encriptado)
- ✅ Botón "Probar Conexión" → AJAX
- ✅ Mostrar resultado (verde/rojo)
- ✅ Mostrar fecha de última prueba

### IMPORTANTE - Token Comprometido:
⚠️ Token anterior está EXPUESTO: `d4c5be6e5cefe4bbb025ae28ba5630df`
- **Acción urgente:** Generar nuevo token en Moodle y pegar aquí

### Archivos involucrados:
- `includes/class-api-client.php` - Conexión
- `admin/partials/settings-display.php` - Template

---

## 3. 📚 CURSOS

**URL:** `?page=woo-otec-moodle-courses`  
**Roles requeridos:** `manage_options`

### ¿Qué debe mostrar?

```
┌─────────────────────────────────────────────────────┐
│ 📚 Mis Cursos                                       │
├──────────────1──────────────────────────────────────┤
│                                                     │
│ Sincronización:                                     │
│  [🔄 Sincronizar Cursos Ahora]                      │
│  ✅ Última sincronización: 12 Apr 14:20            │
│                                                     │
├──────────────────────────────────────────────────────┤
│ ID │ Nombre         │ Imagen  │ Precio │ Acciones   │
├────┼────────────────┼─────────┼────────┼────────────┤
│ 1  │ JavaScript101  │ 📷 [·]  │ $99    │ 🎥 ✏️ 🗑️ │
│ 2  │ React Básico   │ 📷 [·]  │ $149   │ 🎥 ✏️ 🗑️ │
│ 3  │ Node.js        │ 📷 [·]  │ $129   │ 🎥 ✏️ 🗑️ │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Funcionalidad:
- ✅ **Botón "Sincronizar Cursos Ahora"** → AJAX
  - Llama `action=wom_sync_courses`
  - Obtiene cursos desde Moodle
  - Crea/actualiza productos en WC
  - Muestra resultado ✅/❌

- ✅ **Tabla de Cursos:**
  - Columnas: ID | Nombre | Imagen | Precio | Acciones
  
- ✅ **Acciones por Curso:**
  - 📷 Botón cámara → Media Uploader
  - ✏️ Editar precio inline
  - 🗑️ Eliminar

- ✅ **Edición en Vivo:**
  - Click en precio → input editable
  - Save automático con AJAX
  
- ✅ **Carga de Imagen:**
  - Media Uploader nativo de WordPress
  - Sin reload de página
  - Notificación de éxito

### Archivos involucrados:
- `includes/class-course-sync.php` - `ajax_sync_courses()`, `ajax_set_product_image()`
- `admin/partials/courses-display.php` - Template
- `admin/js/admin-app.js` - JavaScript

---

## 4. 🔐 SSO (Single Sign-On)

**URL:** `?page=woo-otec-moodle-sso`  
**Roles requeridos:** `manage_options`

### ¿Qué debe mostrar?

```
┌──────────────────────────────────────────┐
│ 🔐 Autenticación (SSO)                   │
├──────────────────────────────────────────┤
│                                          │
│ ☑️ Habilitar SSO automático              │
│                                          │
│ URL de Retorno (Moodle):                 │
│ https://cipresalto.cl/wp-login.php       │
│                                          │
│ Mapeo de Campos:                         │
│  Nombre Moodle → Nombre WordPress        │
│  Email Moodle → Email WordPress          │
│                                          │
│ [Guardar] [Probar]                      │
│                                          │
│ Estado: ✅ SSO Activo                    │
│ Usuarios autenticados vía SSO: 145       │
│                                          │
└──────────────────────────────────────────┘
```

### Funcionalidad:
- ✅ Toggle On/Off SSO
- ✅ Mostrar URL de retorno
- ✅ Mapeo de campos (puede ser editable)
- ✅ Botón "Guardar"
- ✅ Botón "Test SSO"
- ✅ Mostrar estadísticas

### Archivos involucrados:
- `includes/class-sso-manager.php` - Lógica SSO
- `admin/partials/sso-display.php` - Template

---

## 5. 🏷️ METADATOS ← **IMPORTANTE**

**URL:** `?page=woo-otec-moodle-metadata`  
**Roles requeridos:** `manage_options`

### ¿Qué debe mostrar?

#### TAB 1: Mapeo de Campos

```
┌────────────────────────────────────────────────┐
│ 🏷️ Metadatos                                   │
│ [Mapeo de Campos] [Vista en Vivo]             │
├────────────────────────────────────────────────┤
│                                                │
│ Total: 13 | Habilitados: 12 | Deshabilitados:1│
│                                                │
│ ☑ │ Campo             │ Descripción            │
├───┼──────────────────┼──────────────────────┤
│ ☑ │ course_name      │ Nombre del curso       │
│ ☑ │ course_summary   │ Descripción            │
│ ☑ │ course_category  │ Categoría              │
│ ☑ │ default_price    │ Precio por defecto     │
│ ☑ │ default_image    │ Imagen por defecto     │
│ ☐ │ advanced_option  │ Opción avanzada        │
│                                                │
│ [Restaurar Predeterminados]                  │
│                                                │
└────────────────────────────────────────────────┘
```

#### TAB 2: Vista en Vivo

```
┌────────────────────────────────────────────────┐
│ 👁️ Vista en Vivo                               │
│ [Mapeo de Campos] [Vista en Vivo]             │
├────────────────────────────────────────────────┤
│                                                │
│ Curso: [▼ Selecciona un curso...]            │
│                                                │
│ ┌──────────────────────────────────────────┐  │
│ │ JavaScript Avanzado                      │  │
│ │                                          │  │
│ │ Materia: Programación                    │  │
│ │ Descripción: Curso de JavaScript...      │  │
│ │ Precio: $99 USD                          │  │
│ │ Imagen: [████████████] 80%               │  │
│ │                                          │  │
│ │ [Ver en Tienda]                         │  │
│ └──────────────────────────────────────────┘  │
│                                                │
└────────────────────────────────────────────────┘
```

### Funcionalidad REQUERIDA:

#### Tab 1 - Mapeo:
- ✅ Mostrar tabla de 13 campos mapeables
- ✅ Checkbox de habilitación con AJAX
- ✅ Contador de Total | Habilitados | Deshabilitados
- ✅ Botón "Restaurar" → Reset a defaults
- ✅ Notificación de cambio guardado
- ✅ Sin reload de página

#### Tab 2 - Vista en Vivo:
- ✅ Dropdown con lista de cursos (productos con `_moodle_course_id`)
- ✅ Al seleccionar curso → cargar metadatos via AJAX
- ✅ Mostrar preview HTML con metadatos formateados
- ✅ Mostrar valores actuales del metadato

### AJAX Endpoints usados:
- `action=woo_otec_update_field_mapping` - Guardar checkbox
- `action=woo_otec_reset_field_mappings` - Reset
- `action=woo_otec_load_course_metadata` - Vista en vivo

### 13 Campos Sincronizados:

**De Moodle:**
1. `_moodle_course_id` - ID único del curso
2. `_moodle_course_name` - Nombre
3. `_moodle_course_summary` - Descripción

**Template (Visual):**
4. `_moodle_default_price` - Precio por defecto
5. `_moodle_default_image` - ID de imagen por defecto
6. `_moodle_show_category` - Mostrar categoría (bool)
7. `_moodle_show_price` - Mostrar precio (bool)
8. `_moodle_show_meta` - Mostrar detalles (bool)
9. `_moodle_button_text` - Texto botón catálogo
10. `_moodle_button_text_enroll` - Texto botón individual

**Control:**
11. `_moodle_template_applied` - Marca si aplicó template
12. `_moodle_category_id` - Categoría del curso
13. `_moodle_custom_field_1` - Campo personalizado

### Archivos involucrados:
- `includes/class-field-mapper.php` - Mapeo
- `admin/partials/metadata-display.php` - Template
- `admin/js/admin-app.js` - JavaScript AJAX

---

## 6. 🎨 TEMPLATE BUILDER

**URL:** `?page=woo-otec-moodle-template-builder&template=[product-catalogue|sample-product|email]`  
**Roles requeridos:** `manage_options`

### Estructura

```
┌─────────────────────────────────────────────────────┐
│ 🎨 Personalizador de Plantillas                    │
│ [Catálogo] [Producto] [Email]                      │
├─────────────────────────────────────────────────────┤
│ ┌──────────┬──────────┬──────────┐                 │
│ │ COLORES  │ TEXTOS   │ BOTONES  │                 │
│ │ Primary  │ Título   │ Guardar  │                 │
│ │ Text     │ Botón    │ Reset    │                 │
│ │ etc...   │ etc...   │          │                 │
│ └──────────┴──────────┴──────────┘                 │
│                                                     │
│ ┌─────────────────────────────────────────────┐   │
│ │ VISTA PREVIA (3 columnas para catálogo)     │   │
│ │ [Producto 1] [Producto 2] [Producto 3]     │   │
│ │ [Producto 4] [Producto 5] [Producto 6]     │   │
│ └─────────────────────────────────────────────┘   │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### Template: product-catalogue

**¿Qué hace?**
- Personaliza vista de grid (3 columnas)
- Edita colores primario/texto
- Edita textos: título, botón

**Opciones:**
- Color primario
- Color de texto
- Color de bordes
- Título de encabezado
- Texto de botón
- Mostrar precio: ☑
- Mostrar categoría: ☑

**Preview:**
- Muestra 3 productos REALES de catálogo
- En tiempo real (live preview)
- Aplica configuración al instante

### Template: sample-product

**¿Qué hace?**
- Personaliza vista de producto individual
- Selector para cambiar el producto

**Novedades (hoy):**
- ✅ **Selector dinámico** → carga todos los productos
- ✅ **Producto por defecto** → carga el primero automáticamente
- ✅ **Preview del producto seleccionado**

**Opciones:**
- Color primario
- Texto botón "Matricularme"
- Mostrar precio
- Mostrar detalles

### Template: email

**¿Qué hace?**
- Plantilla de email de matrícula
- Colores y textos para notificación

**Opciones:**
- Color de encabezado
- Color de botón
- Texto del asunto
- Texto del footer

---

## 7. 📧 EMAIL

**URL:** `?page=woo-otec-moodle-email`  
**Roles requeridos:** `manage_options`

### ¿Qué debe mostrar?

```
┌───────────────────────────────────────────┐
│ 📧 Email de Matrícula                     │
├───────────────────────────────────────────┤
│                                           │
│ Asunto:                                   │
│ [¡Bienvenido a {COURSE_NAME}!          ] │
│                                           │
│ Cuerpo (Editor WYSIWYG):                 │
│ ┌─────────────────────────────────────┐  │
│ │ ¡Hola {USER_NAME}!                  │  │
│ │                                     │  │
│ │ Te has matriculado en:              │  │
│ │ {COURSE_NAME}                       │  │
│                                           │
│ [Guardar] [Test: Enviar a mi email]     │
│                                           │
└───────────────────────────────────────────┘
```

### Funcionalidad:
- ✅ Editor WYSIWYG para HTML
- ✅ Variables: {USER_NAME}, {COURSE_NAME}, {DATE}, etc.
- ✅ Preview del email
- ✅ Botón "Enviar Prueba"
- ✅ Guardar plantilla

### Archivos involucrados:
- `admin/partials/email-display.php` - Template
- `includes/class-email-manager.php` - Lógica

---

## 8. ⏰ CRON (Tareas Automáticas)

**URL:** `?page=woo-otec-moodle-cron`  
**Roles requeridos:** `manage_options`

### Funcionalidad:
- ✅ Mostrar interval de sincronización
- ✅ Mostrar última ejecución
- ✅ Botón "Ejecutar Ahora"
- ✅ Logs del último cron

---

## 9. 📋 LOGS

**URL:** `?page=woo-otec-moodle-logs`  
**Roles requeridos:** `manage_options`

### Funcionalidad:
- ✅ Tabla con Fecha | Tipo | Mensaje
- ✅ Filtros por tipo (error, sync, success)
- ✅ Botón limpiar logs
- ✅ Exportar logs (opcional)

---

## 10. 👥 USUARIOS

**URL:** `?page=woo-otec-moodle-users`  
**Roles requeridos:** `manage_options`

### Funcionalidad:
- ✅ Sincronizar usuarios desde Moodle
- ✅ Listar usuarios creados
- ✅ Mostrar su rol en Moodle

---

## 11. 🛒 WOOCOMMERCE

**URL:** `?page=woo-otec-moodle-woocommerce`  
**Roles requeridos:** `manage_options`

### Funcionalidad:
- ✅ Configuración de WooCommerce
- ✅ Mapeo de categorías

---

## ✅ CHECKLIST RÁPIDO

Todo debe funcionar:
- [ ] Dashboard: Mostrar estadísticas
- [ ] Configuración: Guardar token + URL
- [ ] Cursos: Sincronizar + editar imagen + editar precio
- [ ] SSO: Habilitar/deshabilitar
- [ ] **Metadatos: Mapeo + Vista en Vivo**
- [ ] Template Builder: 3 templates con preview
- [ ] Email: Editor WYSIWYG
- [ ] Cron: Mostrar status
- [ ] Logs: Filtrar + limpiar
- [ ] Usuarios: Sincronizar
- [ ] WooCommerce: Configurar

---

**Actualizado:** 12 Abril 2026  
**Autor:** GitHub Copilot  
**Estado:** ✅ Sincronizado con código actual

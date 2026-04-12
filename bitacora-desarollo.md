# Bitácora de Desarrollo - Woo OTEC Moodle 3.0.8

**Última Actualización:** 11 de Abril de 2026 (18:40 UTC)

> Este documento es una referencia secundaria. Consulta **AUDIT_REPORT.md** para la documentación completa.
> Archivos históricos guardados en `historial/archived/`

---

## 📋 Tabla de Contenidos

1. [Descripción General](#descripción-general)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Estado Actual de Pestañas](#estado-actual-de-pestañas)
4. [Cambios Recientes (11 Abril v3.0.8)](#cambios-recientes-11-abril-v308)
5. [Menú y Navegación](#menú-y-navegación)

---

## Descripción General

Plugin de integración profesional entre WooCommerce y Moodle para la gestión de cursos OTEC.

### Características Principales
- Sincronización bidireccional de cursos
- Matriculación automática en Moodle
- Sistema de notificaciones por email
- Gestión de metadatos de cursos
- Panel administrativo Premium

---

## Estructura del Proyecto

```
ciprealto/
├── woo-otec-moodle.php                Main plugin file v3.0.7
├── includes/                          9 clases PHP
│   ├── class-logger.php              Logging y auditoría
│   ├── class-api-client.php          REST API a Moodle
│   ├── class-admin-settings.php      Panel administrativo
│   ├── class-course-sync.php         Sincronización de cursos
│   ├── class-enrollment-manager.php  Gestión de matrículas
│   ├── class-email-manager.php       Notificaciones
│   ├── class-metadata-manager.php    Campos personalizados
│   ├── class-template-manager.php    Plantillas
│   ├── class-template-customizer.php Personalización
│   └── class-preview-generator.php   Vista previa
├── admin/
│   ├── css/admin-style.css          Estilos completos
│   ├── js/admin-app.js              Funcionalidad admin
│   └── partials/                    9 vistas parciales
├── frontend/
│   ├── class-frontend-renderer.php  Renderizado frontend
│   └── css/courses.css              Estilos frontend
├── templates/                       Plantillas WooCommerce
├── assets/                          Recursos compartidos
└── dist/                            Distribución (espejo)
```

---

## Estado Actual de Pestañas

| Pestaña | Estado | Descripción |
|---------|--------|-------------|
| Dashboard | ✅ Completado | Estadísticas y actividad reciente |
| Configuración | ✅ Completado | Credenciales API y ajustes |
| Cursos | ✅ Completado | Sincronización de cursos |
| Metadatos | ✅ Completado | Gestión de campos personalizados |
| Personalización | ✅ Completado | Editor de plantillas |
| WooCommerce | ✅ Completado | Integración de productos |
| Email | ✅ Completado | Configuración SMTP |
| Usuarios | ✅ Completado | Gestión de matrículas |
| Bitácora | ✅ Completado | Registro de eventos |

---

## Cambios Recientes

### 11 de Abril de 2026 (v3.0.8) - Template Parameter Persistence

#### ✅ NUEVA CARACTERÍSTICA: Persistencia de Parámetros de Template

**Problema:** Los parámetros de configuración de template (precio por defecto, imagen, opciones) se guardaban en `wp_options` pero no se aplicaban a los productos de WooCommerce.

**Solución Implementada:**

1. **Sincronización Template → Product Meta**
   - Nuevo método AJAX: `wom_save_template_config`
   - Aplica settings a todos los productos existentes
   - Campos sincronizados: default_price, default_image, columns, visibility options, texts

2. **Gestión de Imágenes de Producto**
   - Nuevo método AJAX: `wom_set_product_image`
   - UI mejorado en Cursos: columna de imagen con botón de cámara
   - Media uploader de WordPress integrado
   - Update sin reload de página

3. **Metatags de Producto (13 campos)**
   - De template: `_moodle_course_default_price`, `_moodle_course_default_image`, `_moodle_show_*`, `_moodle_button_*`, `_moodle_layout`, `_moodle_columns`
   - De Moodle: `_moodle_course_name`, `_moodle_course_summary`, `_moodle_course_category_id`
   - Control: `_moodle_template_applied` (marker)

4. **Enhanced Course Sync**
   - Nuevo método AJAX: `wom_sync_courses`
   - Sincroniza desde Moodle y aplica template settings
   - Guarda información de curso como metatags

**Archivos Modificados:**
- ✅ `includes/class-admin-settings.php` (+3 métodos, 170 líneas)
- ✅ `includes/class-course-sync.php` (+3 métodos, +2 AJAX actions, 120 líneas)
- ✅ `admin/partials/courses-display.php` (UI mejorado con imagen + sync)

**Sincronización Dist:**
✅ 22/22 archivos validados y sincronizados (18:39:42 UTC)

**Documentación:**
- Creado: `TEMPLATE_PERSISTENCE.md` (completamente documentado)
- Incluye: Test flows, API endpoints, integración

---

## Cambios Anteriores (v3.0.7)

### 11 de Abril de 2026

#### Propiedades Dinámicas (PHP 8.2+)
✅ Se agregaron propiedades privadas declaradas en la clase para eliminar deprecation warnings.

#### Namespaces Correctos
✅ Se corrigieron referencias de clases en admin/partials/ de formato antiguo a namespace correcto.

#### Estilos de Formularios
✅ Se agregaron estilos completos para `.wom-form-group`, `.wom-input`, `.wom-form-row` y elementos relacionados.

---

## Menú y Navegación

### Estructura de Menú
```
OTEC Moodle (Menú Principal)
├── Dashboard              Vistas generales y estadísticas
├── Configuración          Credenciales y configuración
├── Cursos                 Sincronización de cursos con template
├── Metadatos              Gestión de campos personalizados
├── Personalización        Editor de plantillas
├── WooCommerce            Integración de productos
├── Email                  Configuración de notificaciones
├── Usuarios               Gestión de matrículas
└── Bitácora               Registro de eventos
```

### Características de Navegación
- Icono personalizado SVG en menú lateral
- Header común (`tabs-header.php`) para todas las vistas
- Ancho consistente: 1400px máximo
- Layout responsive a 300px mínimo
- Image management en Cursos (NEW)

---

## Soporte

Para reportar problemas, consulta [AUDIT_REPORT.md](AUDIT_REPORT.md).
- Ajustes específicos de pedidos y sincronización.

### Email
- Estado: completado.
- Configuración de remitente, SMTP y plantillas.

### Usuarios
- Estado: completado.
- Informe de últimos matriculados.

### Bitácora
- Estado: completado.
- Registro de operaciones y eventos.

---

## 4. Etapas realizadas
- [x] Estructura de menú con pestañas y subpáginas.
- [x] Icono del plugin en el menú de WordPress.
- [x] Estilo uniforme entre páginas con `wrap wom-wrap`.
- [x] Centralización de documentación en esta bitácora.
- [x] Revisión de los archivos `admin/partials/*.php` para consistencia de interfaz.
- [x] Corregido error crítico en Template Builder.
- [x] Agregada carga automática de preview en Metadatos.
- [x] Mejorado layout horizontal en todas las vistas.
- [x] Corregido layout de página de Configuración.

---

## 5. Etapas faltantes

### Mejoras pendientes
- [x] Unificar el diseño de formularios en todas las pestañas con clases CSS compartidas.
- [x] Forzar prioridad de colores personalizados sobre nativos con !important.
- [x] Mejorar debugging del color picker en Template Builder.
- [x] Implementar campos condicionales por plantilla en Template Builder.
- [x] Agregar manejo de cambio de pestañas en Template Builder.
- [x] Agregar selector de producto real para preview de Sample Product.
- [ ] Validar la visibilidad del icono en todas las versiones de WP.
- [ ] Documentar flujos de usuario y estados de error en cada pestaña.
- [ ] Agregar pruebas de aceptación / QA para los pasos de administración.

### Funcionalidades por agregar
- [ ] Soporte multisitio.
- [ ] Manejo de roles y permisos más granular.
- [ ] Exportar / importar configuración.
- [ ] Reportes avanzados de sincronización.

---

## 6. Archivo de referencia
- `historial desarollo/bitacora-desarollo.md` (esta bitácora centralizada)
- `admin/partials/tabs-header.php` (navegación principal dentro de cada página)
- `includes/class-admin-settings.php` (menú y carga de assets)

---

## 8. Mejoras de UI/UX (Última actualización)

### Layout más espacioso
- Aumentado el ancho máximo de `.wom-wrap` de 1280px a 1400px.
- Incrementado el padding de secciones de 24px a 32px.
- Incrementado el padding de cards de 24px a 32px.
- Más espacio horizontal en todas las vistas.

### Vista de Metadatos
- Agregada carga automática de preview al seleccionar producto de prueba.
- Nuevo método AJAX `wom_load_product_preview` para renderizar preview en vivo.
- Evento `change` en `#sample-product-select` para activar la carga automática.

### Página de Configuración
- Corregido layout que se achicaba y causaba desbordamiento del menú.
- Agregada clase `.wom-card--vertical` para cards que no usan flex layout.
- Cards de configuración ahora usan `display: block` en lugar de `flex`.

### Template Builder
- Corregido error crítico al acceder a `page=woo-otec-moodle-template-builder`.
- Eliminadas variables globales, ahora usa variables locales pasadas desde `render_template_builder_page()`.

---

## 9. Archivos modificados recientemente
- `admin/partials/template-builder.php` - Corregido uso de variables globales.
- `admin/partials/metadata-display.php` - Agregada carga automática de preview.
- `admin/partials/settings-display.php` - Agregada clase `wom-card--vertical`.
- `admin/partials/tabs-header.php` - Agregada pestaña Personalización.
- `admin/css/admin-style.css` - Aumentado espacio horizontal, nueva clase `.wom-card--vertical`.
- `includes/class-admin-settings.php` - Nuevo método AJAX `ajax_load_product_preview`, hook agregado.
- `historial desarollo/bitacora-desarollo.md` - Actualizada con cambios recientes.

Esta bitácora debe ser el punto único de consulta para el estado del plugin, sus etapas completadas y los pendientes. Si quieres, puedo continuar y mover aquí los resúmenes existentes de `*.md` en el root para centralizar totalmente la documentación.

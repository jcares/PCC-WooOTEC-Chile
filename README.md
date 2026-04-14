# WOO OTEC Moodle - Plugin Professional

Integración profesional entre **WooCommerce**, **WordPress** y **Moodle** para la venta y administración de cursos online. Arquitectura modular, escalable y desacoplada orientada a eventos.

## Características

- ✅ **Sincronización automática** entre plataformas
- ✅ **Event-Driven Architecture** - Desacoplamiento de componentes
- ✅ **Gestión completa de matrículas** - Post-compra automatizado
- ✅ **Dashboard con métricas** - Ventas, usuarios, actividad
- ✅ **Plantillas personalizables** - Branding configurable
- ✅ **Gestión de usuarios y roles** - RBAC integrado
- ✅ **Sistema de logs centralizados** - Auditoría completa
- ✅ **Seguridad robusta** - Nonces, sanitización, validación
- ✅ **API integrada** - Endpoints REST y AJAX seguros

## Arquitectura

```
includes/
├── Core/
│   ├── Plugin.php              # Punto de entrada, inicialización
│   ├── ServiceContainer.php    # Inyección de dependencias
│   └── EventBus.php            # Sistema de eventos centralizado
├── Foundation/
│   ├── Logger.php              # Logging PSR-3 compatible
│   └── Validator.php           # Validación centralizada
├── API/
│   ├── Response.php            # Respuestas estándar
│   └── Endpoints/              # Endpoints REST
├── Services/                   # Lógica de negocio
│   ├── CourseService.php
│   ├── EnrollmentService.php
│   ├── EmailService.php
│   └── ...
├── Integrations/
│   ├── Moodle/
│   │   ├── MoodleClient.php
│   │   └── MoodleSync.php
│   └── WooCommerce/
│       ├── OrderHandler.php
│       └── ProductSync.php
├── Models/                     # Modelos de datos
├── Validators/                 # Validadores específicos
└── Listeners/                  # Event listeners
```

## Patrones de Diseño

### 1. Singleton Pattern
La clase `Plugin` implementa Singleton para garantizar una única instancia.

### 2. Service Container (Dependency Injection)
```php
$plugin = \Woo_OTEC_Moodle\Core\Plugin::instance();
$logger = $plugin->get( 'logger' );
```

### 3. Event Bus (Observer Pattern)
```php
$plugin->events->listen( 'order_completed', function( $data ) {
    // Handle order completion
} );

$plugin->events->fire( 'order_completed', array( 'order_id' => 123 ) );
```

### 4. Service Layer
Toda la lógica de negocio está en Services, no en controladores/handlers.

## Flujo de Eventos

El sistema opera bajo eventos principales:

```
1. order_completed             → Validación de pago
2. order_processing           → Creación de usuario WordPress
3. user_created               → Integración con Moodle
4. course_enrolled            → Notificación por email
5. sync_failed               → Log y reintentos
```

## Stack Tecnológico

- **PHP**: 8.0+ (namespaces, strict types recomendado)
- **WordPress**: 5.8+
- **WooCommerce**: 5.0+
- **Moodle**: 4.0+ (REST API)
- **MySQL**: 5.7+
- **JavaScript**: ES6, jQuery (admin)
- **CSS**: Grid, Flexbox, Responsive

## Seguridad

### Principios implementados:

- **Nonces WordPress** - CSRF protection en todos los formularios
- **Sanitización** - `sanitize_*()` en inputs
- **Validación** - Schema validation centralizada
- **Permisos** - Role-based access control (RBAC)
- **Logs de auditoría** - Registro de todas las acciones
- **Tokens seguros** - Moodle API tokens encriptados

Ejemplo:
```php
// Validar nonce
if ( ! wp_verify_nonce( $_POST['nonce'], 'woo-otec-moodle-nonce' ) ) {
    wp_send_json_error( 'Invalid nonce' );
}

// Sanitizar y validar
$email = sanitize_email( $_POST['email'] );
$validator = new \Woo_OTEC_Moodle\Validators\UserValidator();
if ( ! $validator->validate( array( 'email' => $email ) ) ) {
    wp_send_json_error( $validator->errors() );
}
```

## Instalación

1. Descargar plugin
2. Colocar en `/wp-content/plugins/woo-otec-moodle/`
3. Activar desde WordPress Admin > Plugins
4. Configurar en Admin > WOO OTEC Settings

## Configuración

### Moodle Integration

```
Admin > WOO OTEC Settings > Moodle
├── API URL: https://moodle.example.com
├── API Token: (generar en Moodle)
└── Category ID: (ID de categoría Moodle)
```

### WooCommerce Sync

```
Admin > WOO OTEC Settings > WooCommerce
├── Auto-sync cursos: ON
├── Auto-enroll post-compra: ON
└── Notificación email: ON
```

## Desarrollo

### Agregar un nuevo servicio

```php
// 1. Crear clase en includes/Services/
namespace Woo_OTEC_Moodle\Services;

class MyService {
    public function __construct( $logger ) {
        $this->logger = $logger;
    }
    
    public function do_something() {
        $this->logger->info( 'Doing something...' );
    }
}

// 2. Registrar en Plugin::register_services()
$this->container->register( 'my_service', function( $c ) {
    return new Services\MyService( $c->get( 'logger' ) );
} );

// 3. Usar en el código
$plugin = Plugin::instance();
$service = $plugin->get( 'my_service' );
$service->do_something();
```

### Escuchar eventos

```php
$plugin = Plugin::instance();

$plugin->events->listen( 'order_completed', function( $data ) {
    error_log( 'Order ' . $data['order_id'] . ' completed' );
}, 10 ); // Priority
```

### Logging

```php
$logger = $plugin->get( 'logger' );

$logger->debug( 'Debug message', array( 'user_id' => 123 ) );
$logger->info( 'Info message' );
$logger->warning( 'Warning!' );
$logger->error( 'Error occurred', array( 'error_code' => 500 ) );
$logger->critical( 'Critical issue!' );
```

## Testing

```bash
# Unit tests (cuando existan)
phpunit

# Integration tests
phpunit --group integration
```

## Rendimiento

### Optimizaciones implementadas:

- **Caching** - Transients para datos frecuentes
- **Lazy loading** - Servicios se cargan bajo demanda
- **Batch processing** - Operaciones masivas optimizadas
- **Queue system** - CRON para tareas pesadas
- **DB indexes** - En campos críticos

## Depuración

Activar constantes en `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Ver logs en `/wp-content/debug.log` y `/logs/woo-otec-moodle.log`

## Requisitos

- PHP 8.0+
- WordPress 5.8+
- WooCommerce 5.0+
- Moodle 4.0+ con REST API habilitado
- cURL enabled en PHP
- mbstring extension

## Roadmap

- [ ] Multi-idioma completo
- [ ] Webhooks bidireccionales
- [ ] API pública del plugin
- [ ] Generación de certificados
- [ ] Integración SENCE
- [ ] Dashboard mejorado
- [ ] Tests automatizados

## Soporte

- **Documentación**: Ver `/docs`
- **Issues**: GitHub Issues
- **Email**: support@cipresalto.cl

## Licencia

Licencia propietaria. Todos los derechos reservados.

## Versión

**4.0.0** - Refactorización profesional con arquitectura modular y event-driven.

---

*Desarrollado para OTEC Chile - Venta profesional de cursos online.*

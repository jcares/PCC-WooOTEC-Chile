# QUICK START – Arquitectura Profesional WOO OTEC

## Instalación Rápida del Desarrollo

### 1. Acceder al Plugin
```bash
cd /wp-content/plugins/woo-otec-moodle
```

### 2. Estructura Actual
```
includes/
├── Core/                 # ✅ NUEVA INFRAESTRUCTURA
│   ├── Plugin.php       # Punto entrada
│   ├── EventBus.php     # Sistema eventos
│   └── ServiceContainer.php
├── Foundation/           # ✅ NUEVA INFRAESTRUCTURA
│   ├── Logger.php
│   └── Validator.php
├── class-*.php          # ⚠️ LEGACY (todavía activo)
└── ...
```

---

## Patrón 1: Usar Logger

```php
<?php
// En cualquier archivo PHP del plugin
$plugin = \Woo_OTEC_Moodle\Core\Plugin::instance();
$logger = $plugin->get( 'logger' );

// Logging diferenciado por nivel
$logger->debug( 'Debug info', array( 'user_id' => 123 ) );
$logger->info( 'Operation successful' );
$logger->warning( 'Deprecated function used' );
$logger->error( 'API error', array( 'status' => 500 ) );
$logger->critical( 'Database error!' );

// Logs aparecen en:
// /wp-content/plugins/woo-otec-moodle/logs/woo-otec-moodle.log
```

---

## Patrón 2: Disparar Eventos

```php
<?php
// Disparar evento desde cualquier lado
$plugin = \Woo_OTEC_Moodle\Core\Plugin::instance();

$plugin->events->fire( 'order_completed', array(
    'order_id' => 123,
    'user_email' => 'user@example.com',
    'course_id' => 456
) );

// Los listeners escuchan automáticamente
```

---

## Patrón 3: Escuchar Eventos

```php
<?php
// En Plugin::register_listeners() o en startup hook
$plugin = \Woo_OTEC_Moodle\Core\Plugin::instance();

$plugin->events->listen( 'order_completed', function( $data ) {
    error_log( 'Procesando orden: ' . $data['order_id'] );
    
    // Aquí va lógica de matrícula, email, etc.
    // Sin acoplamiento a otros componentes
}, 10 ); // Prioridad
```

---

## Patrón 4: Validar Datos

```php
<?php
// Validar POST data
$validator = new \Woo_OTEC_Moodle\Foundation\Validator();

$rules = array(
    'email' => 'required|email',
    'name' => 'required|min:3|max:100',
    'age' => 'numeric'
);

if ( $validator->validate( $_POST, $rules ) ) {
    // Datos válidos
    wp_send_json_success( array( 'message' => 'Valid data' ) );
} else {
    // Con errores
    wp_send_json_error( $validator->errors() );
}
```

---

## Patrón 5: Crear un Nuevo Servicio

```php
<?php
// 1. Archivo: includes/Services/MyCustomService.php
namespace Woo_OTEC_Moodle\Services;

use Woo_OTEC_Moodle\Foundation\Logger;

class MyCustomService {
    private $logger;

    public function __construct( Logger $logger ) {
        $this->logger = $logger;
    }

    public function execute() {
        $this->logger->info( 'Executing custom service' );
        return array( 'success' => true );
    }
}

// 2. Registrar en Core/Plugin.php - register_services()
$this->container->register( 'my_custom_service', function( $c ) {
    return new Services\MyCustomService( $c->get( 'logger' ) );
} );

// 3. Usar desde cualquier lado
$plugin = \Woo_OTEC_Moodle\Core\Plugin::instance();
$service = $plugin->get( 'my_custom_service' );
$result = $service->execute();
```

---

## Patrón 6: Crear un Event Listener

```php
<?php
// 1. Archivo: includes/Listeners/OrderCompletedListener.php
namespace Woo_OTEC_Moodle\Listeners;

use Woo_OTEC_Moodle\Foundation\Logger;
use Woo_OTEC_Moodle\Services\EnrollmentService;

class OrderCompletedListener {
    private $logger;
    private $enrollment_service;

    public function __construct( Logger $logger, EnrollmentService $service ) {
        $this->logger = $logger;
        $this->enrollment_service = $service;
    }

    public function handle( $data ) {
        $order_id = $data['order_id'];
        $user_email = $data['user_email'];

        try {
            $this->enrollment_service->enroll_user( $user_email );
            $this->logger->info( "User $user_email enrolled", array( 'order_id' => $order_id ) );
        } catch ( \Exception $e ) {
            $this->logger->error( 'Enrollment failed', array( 'error' => $e->getMessage() ) );
        }
    }
}

// 2. Registrar en Core/Plugin.php - register_listeners()
$this->events->listen( 'order_completed', function( $data ) {
    $listener = new Listeners\OrderCompletedListener(
        $this->logger,
        $this->container->get( 'enrollment_service' )
    );
    $listener->handle( $data );
}, 10 );

// 3. Automáticamente se ejecuta cuando se dispara el evento
```

---

## Commando CLI para Desarrolladores

### Ver últimos logs
```bash
tail -100 /wp-content/plugins/woo-otec-moodle/logs/woo-otec-moodle.log
```

### Buscar en logs
```bash
grep "error\|critical" /wp-content/plugins/woo-otec-moodle/logs/woo-otec-moodle.log
```

### Limpiar logs (archiva)
```bash
# Los logs se rotan automáticamente a 10MB
# Archivo anterior: woo-otec-moodle.log.20260414235959
```

---

## Debugging

### 1. Activar modo debug en wp-config.php
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### 2. Ver ambos logs
```bash
# Plugin logs
tail /wp-content/plugins/woo-otec-moodle/logs/woo-otec-moodle.log

# WordPress logs
tail /wp-content/debug.log
```

### 3. Verificar carga de plugin
```php
<?php
// En functions.php temporal para verificar
add_action( 'plugins_loaded', function() {
    error_log( 'Plugin loaded: ' . class_exists( '\Woo_OTEC_Moodle\Core\Plugin' ) ? 'YES' : 'NO' );
}, 999 );
```

---

## Migrando Código Antiguo

### Antes (Estilo antiguo)
```php
<?php
$logger = new \Woo_OTEC_Moodle\Logger();
$logger->log( 'info', 'Message' );

$api = new \Woo_OTEC_Moodle\API_Client();
$api->do_something();
```

### Después (Estilo nuevo)
```php
<?php
$plugin = \Woo_OTEC_Moodle\Core\Plugin::instance();

$logger = $plugin->get( 'logger' );
$logger->info( 'Message' );

$api = $plugin->get( 'moodle_api_service' ); // Cuando esté refactorizado
$api->do_something();
```

---

## Puntos Clave

1. **Siempre usar `Plugin::instance()`** para acceder a servicios
2. **Logger**: `$plugin->get('logger')` y luego usar `->info()`, `->error()`, etc.
3. **Events**: Disparar con `$plugin->events->fire()`, escuchar con `->listen()`
4. **Validación**: Usar `\Woo_OTEC_Moodle\Foundation\Validator` para inputs
5. **Servicios nuevos**: Indicar dependencias en constructor (inyección)
6. **Logs**: Siempre loguear operaciones críticas

---

## Recursos

- **README.md** – Documentación completa
- **REFACTORING_GUIDE.md** – Guía de refactorización
- **includes/Core/Plugin.php** – Punto entrada, estudiar `boot()`
- **includes/Core/EventBus.php** – Sistema eventos (simple pero poderoso)
- **includes/Foundation/Logger.php** – Logging centralizado

---

**¡Listo para empezar! El plugin está correctamente estructurado y listo para desarrollo profesional.**

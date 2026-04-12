<?php
/**
 * Gestor de configuración de email y envíos con soporte SMTP.
 * 
 * @package    Woo_OTEC_Moodle
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Email_Manager {

	/**
	 * Gestor de Logs.
	 */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;

		// AJAX para probar envío de email
		add_action( 'wp_ajax_woo_otec_test_email', array( $this, 'ajax_test_email' ) );
		add_action( 'wp_ajax_woo_otec_reset_email_config', array( $this, 'ajax_reset_email_config' ) );
		
		// Registrar opciones de email
		add_action( 'admin_init', array( $this, 'register_email_settings' ) );
	}

	/**
	 * Registrar opciones de email
	 */
	public function register_email_settings() {
		$options = array(
			'woo_otec_email_use_smtp',
			'woo_otec_email_smtp_host',
			'woo_otec_email_smtp_port',
			'woo_otec_email_smtp_secure',
			'woo_otec_email_smtp_user',
			'woo_otec_email_smtp_pass',
			'woo_otec_email_from_name',
			'woo_otec_email_from_address',
			'woo_otec_email_logo_id',
		);

		foreach ( $options as $option ) {
			register_setting( 'woo_otec_moodle_group', $option );
		}
	}

	/**
	 * Obtener configuración de SMTP
	 */
	public function get_smtp_config() {
		return array(
			'use_smtp'     => (bool) get_option( 'woo_otec_email_use_smtp', false ),
			'host'         => get_option( 'woo_otec_email_smtp_host', '' ),
			'port'         => (int) get_option( 'woo_otec_email_smtp_port', 587 ),
			'secure'       => get_option( 'woo_otec_email_smtp_secure', 'tls' ),
			'username'     => get_option( 'woo_otec_email_smtp_user', '' ),
			'password'     => get_option( 'woo_otec_email_smtp_pass', '' ),
			'from_name'    => get_option( 'woo_otec_email_from_name', 'Cipres Alto Virtual' ),
			'from_address' => get_option( 'woo_otec_email_from_address', get_option( 'admin_email' ) ),
			'logo_id'      => (int) get_option( 'woo_otec_email_logo_id', 0 ),
		);
	}

	/**
	 * Enviar email con soporte SMTP
	 */
	public function send_email( $to, $subject, $message, $headers = array() ) {
		$config = $this->get_smtp_config();

		// Encabezados por defecto
		if ( empty( $headers ) ) {
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		}

		// Agregar From si está configurado
		if ( ! empty( $config['from_address'] ) ) {
			$from_header = 'From: ' . $config['from_name'] . ' <' . $config['from_address'] . '>';
			if ( ! in_array( $from_header, $headers ) ) {
				$headers[] = $from_header;
			}
		}

		// Si se usa SMTP, usar PHPMailer
		if ( $config['use_smtp'] && ! empty( $config['host'] ) ) {
			return $this->send_via_smtp( $to, $subject, $message, $headers, $config );
		}

		// Fallback a wp_mail estándar
		$sent = wp_mail( $to, $subject, $message, $headers );
		
		if ( $sent ) {
			$this->logger->log( 'SUCCESS', "Email enviado a {$to}: {$subject}" );
		} else {
			$this->logger->log( 'ERROR', "Fallo al enviar email a {$to}: {$subject}" );
		}

		return $sent;
	}

	/**
	 * Enviar email via SMTP con PHPMailer
	 */
	private function send_via_smtp( $to, $subject, $message, $headers, $config ) {
		if ( ! class_exists( 'PHPMailer\\PHPMailer\\PHPMailer' ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		}

		try {
			$mail = new PHPMailer\PHPMailer\PHPMailer( true );
			
			// Configuración SMTP
			$mail->isSMTP();
			$mail->Host       = $config['host'];
			$mail->SMTPAuth   = true;
			$mail->Username   = $config['username'];
			$mail->Password   = $config['password'];
			$mail->SMTPSecure = 'tls' === $config['secure'] ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port       = $config['port'];

			// Datos del email
			$mail->setFrom( $config['from_address'], $config['from_name'] );
			$mail->addAddress( $to );
			$mail->Subject = $subject;
			$mail->isHTML( true );
			$mail->Body = $message;

			$result = $mail->send();
			
			if ( $result ) {
				$this->logger->log( 'SUCCESS', "Email SMTP enviado a {$to}: {$subject}" );
			}
			
			return $result;

		} catch ( Exception $e ) {
			$this->logger->log( 'ERROR', "Error SMTP al enviar a {$to}: " . $e->getMessage() );
			return false;
		}
	}

	/**
	 * AJAX Handler para probar envío de email
	 */
	public function ajax_test_email() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$config = $this->get_smtp_config();
		
		// Obtener correo de prueba del request, validar y sanitizar
		$test_email = isset( $_POST['test_email'] ) ? sanitize_email( $_POST['test_email'] ) : get_option( 'admin_email' );
		
		if ( ! is_email( $test_email ) ) {
			wp_send_json_error( 'Correo electrónico inválido: ' . esc_html( $test_email ) );
		}

		$logo_html = '';
		if ( $config['logo_id'] > 0 ) {
			$logo_url = wp_get_attachment_url( $config['logo_id'] );
			if ( $logo_url ) {
				$logo_html = '<div class="wom-logo-container"><img src="' . esc_url( $logo_url ) . '" alt="Logo"></div>';
			}
		}

		$subject = 'Prueba de Email - Woo OTEC Moodle';
		
		$message = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<style>
		body { font-family: Arial, sans-serif; color: #333; }
		.container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 8px; }
		.header { background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); color: white; padding: 20px; text-align: center; border-radius: 4px; margin-bottom: 20px; }
		.header h2 { margin: 0; }
		.header p { margin: 10px 0 0 0; font-size: 14px; }
		.content { background: white; padding: 20px; border-radius: 4px; line-height: 1.6; }
		.info-box { background: #e7f3ff; border-left: 4px solid #0073aa; padding: 15px; margin: 15px 0; border-radius: 4px; }
		.info-box-success { background: #d4edda; border-color: #28a745; }
		.info-box-success strong { color: #28a745; }
		.info-box-success p { margin: 10px 0 0 0; font-size: 13px; }
		.status-item { margin: 10px 0; padding: 8px; background: #f5f5f5; border-radius: 4px; }
		.status-item strong { display: block; margin-bottom: 4px; }
		.success { color: #28a745; font-weight: bold; }
		.footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; text-align: center; }
	</style>
</head>
<body>
	<div class="container">
		' . $logo_html . '
		<div class="header">
			<h2>✓ Prueba de Email</h2>
			<p>Woo OTEC Moodle v' . WOO_OTEC_MOODLE_VERSION . '</p>
		</div>
		
		<div class="content">
			<h3>Confirmación de Configuración</h3>
			<p>Si está recibiendo este email, significa que tu configuración <strong>SMTP es correcta</strong> y los correos de matriculación se enviarán sin problemas.</p>
			
			<div class="info-box">
				<strong>Información de Configuración Utilizada:</strong>
				<div class="status-item">
					<strong>Remitente:</strong> ' . esc_html( $config['from_name'] ) . ' &lt;' . esc_html( $config['from_address'] ) . '&gt;
				</div>' .
				( $config['use_smtp'] ? 
					'<div class="status-item">
						<strong>Servidor SMTP:</strong> ' . esc_html( $config['host'] ) . ':' . esc_html( $config['port'] ) . ' (' . esc_html( $config['secure'] ) . ')
					</div>
					<div class="status-item">
						<strong>Usuario SMTP:</strong> ' . esc_html( $config['username'] ) . '
					</div>'
				: 
					'<div class="status-item">
						<strong>Método:</strong> Servidor de correo predeterminado (sin SMTP)
					</div>'
				) . '
			</div>
			
			<div class="info-box info-box-success">
				<strong>✓ Sistema listo para funcionar</strong>
				<p>Los correos de notificación de matriculación se enviarán correctamente con esta configuración.</p>
			</div>
		</div>
		
		<div class="footer">
			<p>Este es un email de prueba automatizado. No responda a este email.</p>
			<p>Woo OTEC Moodle • ' . current_time( 'Y-m-d H:i:s' ) . '</p>
		</div>
	</div>
</body>
</html>';

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent = $this->send_email( $test_email, $subject, $message, $headers );

		if ( $sent ) {
			wp_send_json_success( 'Email de prueba enviado correctamente a ' . esc_html( $test_email ) . '✓ Revisa tu bandeja de entrada en unos segundos.' );
		} else {
			wp_send_json_error( 'No se pudo enviar el email de prueba. Verifica tu configuración SMTP, especialmente usuario, contraseña y credenciales.' );
		}
	}

	/**
	 * AJAX Handler para resetear la configuración de email
	 */
	public function ajax_reset_email_config() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$defaults = array(
			'woo_otec_email_use_smtp'       => 0,
			'woo_otec_email_smtp_host'      => 'smtp.gmail.com',
			'woo_otec_email_smtp_port'      => 587,
			'woo_otec_email_smtp_secure'    => 'tls',
			'woo_otec_email_smtp_user'      => '',
			'woo_otec_email_smtp_pass'      => '',
			'woo_otec_email_from_name'      => 'Cipres Alto Virtual',
			'woo_otec_email_from_address'   => get_option( 'admin_email' ),
			'woo_otec_email_logo_id'        => 0,
		);

		foreach ( $defaults as $option => $value ) {
			update_option( $option, $value );
		}

		$this->logger->log( 'INFO', 'Configuración de email reseteada a valores por defecto' );
		wp_send_json_success( 'Configuración reseteada a valores por defecto.' );
	}
}

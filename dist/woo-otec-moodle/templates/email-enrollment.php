<?php
/**
 * Plantilla de Email: Notificación de Matrícula Moodle
 * 
 * @package    Woo_OTEC_Moodle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables disponibles:
 * - $user_name: Nombre del alumno.
 * - $user_email: Email del alumno.
 * - $user_pass: Contraseña temporal (opcional).
 * - $courses: Array de nombres de cursos matriculados.
 * - $order_id: ID del pedido.
 * - $moodle_url: URL del aula virtual.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 0; }
		.container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
		.header { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); padding: 40px; text-align: center; color: #ffffff; }
		.header h1 { margin: 0; font-size: 26px; }
		.header p { margin: 8px 0 0; opacity: 0.9; }
		.content { padding: 40px; line-height: 1.6; }
		.course-list { background: #f1f5f9; padding: 20px; border-radius: 8px; list-style: none; margin: 20px 0; border-left: 4px solid #6366f1; }
		.course-list li { margin-bottom: 8px; font-weight: 600; }
		.access-box { background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.2); padding: 25px; border-radius: 8px; margin-top: 30px; }
		.access-box h3 { margin-top: 0; color: #4f46e5; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; font-size: 16px; }
		.btn { display: inline-block; background: #6366f1; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: 700; margin-top: 20px; text-align: center; }
		.footer { background: #f8fafc; padding: 25px; text-align: center; font-size: 13px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h1>🎓 ¡Bienvenido a Cipres Alto!</h1>
			<p>Tu inscripción ha sido procesada con éxito.</p>
		</div>
		
		<div class="content">
			<p>Hola <strong><?php echo esc_html( $user_name ); ?></strong>,</p>
			<p>Nos complace informarte que ya tienes acceso a tu(s) curso(s) en nuestra aula virtual. A continuación te detallamos los cursos inscritos:</p>
			
			<ul class="course-list">
				<?php foreach ( $courses as $course_name ) : ?>
					<li>✅ <?php echo esc_html( $course_name ); ?></li>
				<?php endforeach; ?>
			</ul>
			
			<div class="access-box">
				<h3>🔑 Credenciales de Acceso</h3>
				<p style="margin-bottom: 5px;"><strong>Usuario:</strong> <?php echo esc_html( $user_email ); ?></p>
				<?php if ( ! empty( $user_pass ) ) : ?>
					<p style="margin-top: 0;"><strong>Contraseña temporal:</strong> <?php echo esc_html( $user_pass ); ?></p>
					<p><small style="color: #64748b;">(Te recomendamos cambiarla al primer acceso)</small></p>
				<?php else : ?>
					<p style="margin-top: 0;"><small>Usa tu contraseña habitual de Moodle.</small></p>
				<?php endif; ?>
				
				<p><strong>Pedido:</strong> #<?php echo esc_html( $order_id ); ?></p>
				
				<a href="<?php echo esc_url( $moodle_url ); ?>" class="btn">🚀 Acceder al Aula Virtual</a>
			</div>
			
			<p style="margin-top: 30px; font-size: 14px; color: #64748b;">Si tienes alguna dificultad para acceder, por favor responde a este correo.</p>
		</div>
		
		<div class="footer">
			<p>Cipres Alto Virtual - Gestión OTEC Integrada</p>
			<p>Este es un mensaje automático, por favor no responder directamente.</p>
		</div>
	</div>
</body>
</html>

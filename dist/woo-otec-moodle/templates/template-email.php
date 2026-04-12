<?php
/**
 * Template: Email Enrollment
 * 
 * Plantilla de email que se envía al alumno cuando se matricula
 *
 * @package WOO_OTEC_Moodle
 * @subpackage Templates
 * @version 3.0.7
 *
 * Variables disponibles:
 * @var string $user_name Nombre del usuario
 * @var string $user_email Email del usuario
 * @var array $courses Array de nombres de cursos
 * @var string $moodle_url URL del aula virtual
 * @var string $login_url URL para acceder
 * @var array $config Array de configuración
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_name = isset( $user_name ) ? $user_name : 'Estudiante';
$courses = isset( $courses ) ? (array) $courses : array();
$moodle_url = isset( $moodle_url ) ? $moodle_url : '';
$login_url = isset( $login_url ) ? $login_url : '#';
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Matrícula Confirmada</title>
</head>
<body style="font-family: var(--wom-primary-font); line-height: var(--wom-line-height); margin: 0; padding: 0; background: #f9fafb;">

	<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #f9fafb; padding: 20px 0;">
		<tr>
			<td align="center">
				<!-- Email Container -->
				<table width="600" border="0" cellspacing="0" cellpadding="0" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">

					<!-- Header -->
					<tr>
						<td style="background: linear-gradient(135deg, var(--wom-primary) 0%, var(--wom-primary-hover) 100%); padding: 40px 20px; text-align: center; color: white;">
							<h2 style="margin: 0; font-size: 24px; font-weight: 600;">¡Bienvenido a nuestros cursos!</h2>
							<p style="margin: 10px 0 0 0; opacity: 0.9; font-size: 16px;">Tu matrícula ha sido completada exitosamente</p>
						</td>
					</tr>

					<!-- Content -->
					<tr>
						<td style="padding: 30px; color: var(--wom-text);">

							<!-- Greeting -->
							<p style="margin: 0 0 20px 0; font-size: 16px;">
								Hola <strong><?php echo esc_html( $user_name ); ?></strong>,
							</p>

							<p style="margin: 0 0 20px 0; font-size: 15px; color: var(--wom-text-light); line-height: var(--wom-line-height);">
								¡Gracias por inscribirse! Ahora tienes acceso completo a los siguientes cursos. Puedes comenzar a aprender en cualquier momento desde tu área de estudiante.
							</p>

							<!-- Courses List -->
							<?php if ( ! empty( $courses ) ) : ?>
								<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #f9fafb; border-left: 4px solid var(--wom-primary); margin: 20px 0; border-radius: 4px;">
									<tr>
										<td style="padding: 15px;">
											<p style="margin: 0 0 15px 0; font-weight: 600; color: var(--wom-text);">Tus Cursos:</p>
											<?php
											foreach ( $courses as $course ) :
												?>
												<div style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; color: var(--wom-text);">
													<span style="color: var(--wom-primary); font-weight: 600;">✓</span> <?php echo esc_html( $course ); ?>
												</div>
											<?php
											endforeach;
											?>
										</td>
									</tr>
								</table>
							<?php endif; ?>

							<!-- CTA Button -->
							<div style="text-align: center; margin: 30px 0;">
								<a href="<?php echo esc_url( $login_url ); ?>" style="display: inline-block; background: var(--wom-button); color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;">
									Acceder a Mi Aula Virtual
								</a>
							</div>

							<!-- Instructions -->
							<div style="background: #f0f4ff; border-left: 4px solid var(--wom-primary); padding: 15px; border-radius: 4px; margin: 20px 0;">
								<p style="margin: 0; font-size: 14px; color: var(--wom-text);">
									<strong>Próximos pasos:</strong>
								</p>
								<ul style="margin: 10px 0 0 0; padding-left: 20px; color: var(--wom-text-light); font-size: 14px;">
									<li>Accede a tu aula virtual con tus credenciales</li>
									<li>Completa tu perfil de estudiante</li>
									<li>Inicia el primer módulo de tu curso</li>
								</ul>
							</div>

							<!-- Support -->
							<p style="margin: 20px 0 0 0; font-size: 14px; color: var(--wom-text-light);">
								¿Tienes preguntas? No dudes en contactarnos en <strong>support@example.com</strong>
							</p>

						</td>
					</tr>

					<!-- Footer -->
					<tr>
						<td style="background: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb; color: var(--wom-text-light); font-size: 12px;">
							<p style="margin: 0;">© 2026 WOO-OTEC-MOODLE. Todos los derechos reservados.</p>
							<p style="margin: 8px 0 0 0; font-size: 11px;">
								Este correo fue enviado porque te registraste en nuestro portal.
							</p>
						</td>
					</tr>

				</table>
			</td>
		</tr>
	</table>

</body>
</html>

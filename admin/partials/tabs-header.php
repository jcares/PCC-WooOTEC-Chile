<?php
/**
 * Cabecera de pestañas Premium (Expansion completa a 6 pestañas).
 */
$current_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : 'woo-otec-moodle';

$tabs = array(
    'woo-otec-moodle'          => array( 'title' => 'Dashboard', 'icon' => 'dashicons-dashboard' ),
    'woo-otec-moodle-settings' => array( 'title' => 'Configuración', 'icon' => 'dashicons-admin-settings' ),
    'woo-otec-moodle-courses'  => array( 'title' => 'Cursos', 'icon' => 'dashicons-welcome-learn-more' ),
    'woo-otec-moodle-metadata' => array( 'title' => 'Metadatos', 'icon' => 'dashicons-list-view' ),
    'woo-otec-moodle-template-builder' => array( 'title' => 'Personalización', 'icon' => 'dashicons-admin-customizer' ),
    'woo-otec-moodle-email'    => array( 'title' => 'Email', 'icon' => 'dashicons-email' ),
    'woo-otec-moodle-users'    => array( 'title' => 'Usuarios', 'icon' => 'dashicons-admin-users' ),
	'woo-otec-moodle-cron'    => array( 'title' => 'Programador', 'icon' => 'dashicons-update' ),
    'woo-otec-moodle-logs'     => array( 'title' => 'Bitácora', 'icon' => 'dashicons-media-text' ),
);
?>

<div class="wrap wom-wrap">
    <h1 class="wp-heading-inline" style="margin-bottom: 20px;">
        <span class="dashicons dashicons-mortarboard" style="font-size: 24px; width: 24px; height: 24px; margin-right: 6px; vertical-align: middle; color:#4f46e5; display: inline-block;"></span>
        <?php esc_html_e( 'Woo OTEC Moodle Integration', 'woo-otec-moodle' ); ?>
        <span style="font-size: 13px; font-weight: 400; color: #6b7280; vertical-align: middle; margin-left: 10px;">
            v<?php echo esc_html( WOO_OTEC_MOODLE_VERSION ); ?>
        </span>
    </h1>

    <h2 class="nav-tab-wrapper wom-nav-tabs" style="margin-bottom: 20px; border-bottom: 1px solid #c3c4c7; margin-top: 10px;">
        <?php foreach ( $tabs as $slug => $tab ) : ?>
            <a href="?page=<?php echo esc_attr( $slug ); ?>" class="nav-tab <?php echo $current_page === $slug ? 'nav-tab-active' : ''; ?>" style="font-weight: 500;">
                <span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>" style="margin-top:4px;"></span>
                <?php echo esc_html( $tab['title'] ); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div id="wom-notifications"></div>

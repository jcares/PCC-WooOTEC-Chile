<?php
/**
 * Página de Email
 * 
 * ¿Qué hace?
 * - Configurar plantilla de email de matrícula
 * - Personalizar asunto y contenido
 * - Variables como {USER_NAME}, {COURSE_NAME}, {DATE}
 * 
 * ¿Qué debe funcionar?
 * ✅ Editor WYSIWYG para HTML (TinyMCE)
 * ✅ Campo de asunto editable
 * ✅ Variables disponibles (insert variables)
 * ✅ Preview del email
 * ✅ Botón "Probar Envío" → envía a admin
 * ✅ Botón "Guardar" → guarda plantilla
 * 
 * Características:
 * - Campos de prueba de email con input dinámico
 * - Botones show/hide para contraseñas y tokens
 * - Uploader para logo de email
 * - Mejor formato de inputs según tipo (text, email, number, password)
 * - Validaciones mejoradas
 * - Interfaz responsive y profesional
 */

include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';

// Obtener configuración actual
$email_config = array(
    'use_smtp'         => (bool) get_option( 'woo_otec_email_use_smtp', false ),
    'host'             => get_option( 'woo_otec_email_smtp_host', 'smtp.gmail.com' ),
    'port'             => (int) get_option( 'woo_otec_email_smtp_port', 587 ),
    'secure'           => get_option( 'woo_otec_email_smtp_secure', 'tls' ),
    'username'         => get_option( 'woo_otec_email_smtp_user', '' ),
    'password'         => get_option( 'woo_otec_email_smtp_pass', '' ),
    'from_name'        => get_option( 'woo_otec_email_from_name', 'Cipres Alto Virtual' ),
    'from_address'     => get_option( 'woo_otec_email_from_address', get_option( 'admin_email' ) ),
    'logo_id'          => (int) get_option( 'woo_otec_email_logo_id', 0 ),
    'test_email'       => get_option( 'admin_email' ),
);

// Enqueue WordPress Media Uploader
wp_enqueue_media();
?>

<div class="wom-wrap">
    <div class="wom-card">
        <h2><span class="dashicons dashicons-email-alt"></span> Configuración de Email</h2>
        <p>Configura los parámetros de envío de correos electrónicos para notificaciones de matriculación.</p>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields( 'woo_otec_moodle_group' ); ?>

        <!-- SECCIÓN: Datos del Remitente -->
        <div class="wom-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-info"></span> Datos del Remitente</h3>
            
            <div class="wom-form-group">
                <label for="woo_otec_email_from_name">Nombre del remitente</label>
                <input 
                    type="text" 
                    id="woo_otec_email_from_name"
                    name="woo_otec_email_from_name"
                    value="<?php echo esc_attr( $email_config['from_name'] ); ?>"
                    placeholder="Cipres Alto Virtual"
                    class="wom-input wom-input-text"
                    required
                />
                <small>Nombre que aparecerá como remitente en los emails</small>
            </div>

            <div class="wom-form-group">
                <label for="woo_otec_email_from_address">Correo del remitente</label>
                <input 
                    type="email" 
                    id="woo_otec_email_from_address"
                    name="woo_otec_email_from_address"
                    value="<?php echo esc_attr( $email_config['from_address'] ); ?>"
                    placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
                    class="wom-input wom-input-email"
                    required
                />
                <small>Email desde el cual se enviarán los correos. Debe ser válido y verificado.</small>
            </div>

            <!-- LOGO PARA EMAIL -->
            <div class="wom-form-group">
                <label for="woo_otec_email_logo">Logo para Email
                    <span class="dashicons dashicons-format-image" style="color: #6366f1; font-size: 14px;"></span>
                </label>
                <div style="display: flex; gap: 15px; align-items: flex-start; flex-wrap: wrap; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                    <div id="logo-preview" style="flex: 0 0 auto;">
                        <?php
                        $logo_id = $email_config['logo_id'];
                        if ( $logo_id ) {
                            $logo_url = wp_get_attachment_url( $logo_id );
                            echo '<img src="' . esc_url( $logo_url ) . '" alt="Logo" style="max-width: 180px; max-height: 120px; border-radius: 4px; border: 1px solid #ddd; padding: 4px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
                        } else {
                            echo '<div style="width: 180px; height: 120px; background: white; border: 2px dashed #ccc; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">Sin Logo</div>';
                        }
                        ?>
                    </div>
                    <div style="flex: 1; min-width: 250px;">
                        <input 
                            type="hidden" 
                            id="woo_otec_email_logo_id"
                            name="woo_otec_email_logo_id"
                            value="<?php echo esc_attr( $logo_id ); ?>"
                        />
                        <button 
                            type="button" 
                            id="upload-logo-btn"
                            class="wom-btn wom-btn-primary"
                            style="margin-bottom: 8px;"
                        >
                            <span class="dashicons dashicons-format-image"></span> Seleccionar Logo
                        </button>
                        <?php if ( $logo_id ) : ?>
                        <button 
                            type="button" 
                            id="remove-logo-btn"
                            class="wom-btn"
                            style="margin-left: 5px; background: #dc3545; color: white;"
                        >
                            <span class="dashicons dashicons-trash"></span> Eliminar
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN: Configuración SMTP -->
        <div class="wom-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-admin-network"></span> Configuración SMTP</h3>
            
            <div class="wom-form-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input 
                        type="checkbox" 
                        id="woo_otec_email_use_smtp"
                        name="woo_otec_email_use_smtp"
                        value="1"
                        <?php checked( $email_config['use_smtp'], 1 ); ?>
                    />
                    <span>Activar envío SMTP (Recomendado)</span>
                </label>
            </div>

            <div id="smtp-fields" style="<?php echo $email_config['use_smtp'] ? '' : 'display: none;'; ?>">
                <div class="wom-form-group">
                    <label for="woo_otec_email_smtp_host">Servidor SMTP</label>
                    <input 
                        type="text" 
                        id="woo_otec_email_smtp_host"
                        name="woo_otec_email_smtp_host"
                        value="<?php echo esc_attr( $email_config['host'] ); ?>"
                        placeholder="smtp.gmail.com"
                        class="wom-input wom-input-text"
                    />
                </div>

                <div class="wom-form-row">
                    <div class="wom-form-group">
                        <label for="woo_otec_email_smtp_port">Puerto SMTP</label>
                        <input 
                            type="number" 
                            id="woo_otec_email_smtp_port"
                            name="woo_otec_email_smtp_port"
                            value="<?php echo esc_attr( $email_config['port'] ); ?>"
                            min="1"
                            max="65535"
                            class="wom-input wom-input-number"
                            placeholder="587"
                        />
                    </div>

                    <div class="wom-form-group">
                        <label for="woo_otec_email_smtp_secure">Tipo de Encriptación</label>
                        <select id="woo_otec_email_smtp_secure" name="woo_otec_email_smtp_secure" class="wom-input wom-input-select">
                            <option value="tls" <?php selected( $email_config['secure'], 'tls' ); ?>>TLS (Recomendado - 587)</option>
                            <option value="ssl" <?php selected( $email_config['secure'], 'ssl' ); ?>>SSL (Seguro - 465)</option>
                            <option value="none" <?php selected( $email_config['secure'], 'none' ); ?>>Sin Encriptación (25)</option>
                        </select>
                    </div>
                </div>

                <div class="wom-form-group">
                    <label for="woo_otec_email_smtp_user">Usuario SMTP (Email)</label>
                    <div class="wom-input-group">
                        <input 
                            type="text" 
                            id="woo_otec_email_smtp_user"
                            name="woo_otec_email_smtp_user"
                            value="<?php echo esc_attr( $email_config['username'] ); ?>"
                            placeholder="tu-email@gmail.com"
                            class="wom-input"
                            autocomplete="off"
                        />
                        <button 
                            type="button" 
                            class="wom-toggle-btn wom-copy-btn" 
                            data-target="#woo_otec_email_smtp_user" 
                            title="Copiar al portapapeles"
                            data-field-type="email"
                        >
                            <span class="dashicons dashicons-admin-page"></span>
                        </button>
                    </div>
                </div>

                <div class="wom-form-group">
                    <label for="woo_otec_email_smtp_pass">
                        Contraseña SMTP 
                        <span class="dashicons dashicons-lock" style="color: #dc3545; font-size: 12px; margin-left: 4px;" title="Campo protegido"></span>
                    </label>
                    <div class="wom-input-group">
                        <input 
                            type="password" 
                            id="woo_otec_email_smtp_pass"
                            name="woo_otec_email_smtp_pass"
                            value="<?php echo esc_attr( $email_config['password'] ); ?>"
                            placeholder="••••••••"
                            class="wom-input"
                            autocomplete="off"
                        />
                        <button 
                            type="button" 
                            class="wom-toggle-btn wom-toggle-password" 
                            data-target="#woo_otec_email_smtp_pass" 
                            title="Mostrar/ocultar contraseña"
                        >
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN: Envío de Prueba -->
        <div class="wom-card" style="margin-top: 20px; padding: 16px;">
            <div style="display: grid; grid-template-columns: auto 1fr auto auto; gap: 12px; align-items: flex-end;">
                <span style="font-weight: 600; font-size: 12px;">Prueba de Configuración:</span>
                <input 
                    type="email" 
                    id="test-email-address"
                    placeholder="tu-correo@example.com"
                    value="<?php echo esc_attr( $email_config['test_email'] ); ?>"
                    class="wom-input"
                    style="margin: 0; font-size: 12px; padding: 6px 10px;"
                />
                <button 
                    type="button" 
                    id="test-email-btn"
                    class="wom-btn"
                    style="padding: 6px 12px; font-size: 12px; white-space: nowrap; background: #0073aa;"
                >
                    <span class="dashicons dashicons-email-alt" style="font-size: 12px; width: 12px; height: 12px; margin-right: 4px;"></span> Enviar
                </button>
                <div id="test-email-result" style="font-size: 11px; display: none; padding: 4px 8px; border-radius: 3px; min-width: 150px; text-align: center;"></div>
            </div>
        </div>

        <!-- BOTONES DE ACCIÓN PRINCIPALES -->
        <div class="wom-card" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 4px; display: flex; gap: 10px; flex-wrap: wrap;">
            <button 
                type="submit" 
                class="wom-btn wom-btn-primary"
            >
                <span class="dashicons dashicons-admin-customizer"></span> Guardar Configuración
            </button>
            
            <button 
                type="button" 
                id="reset-config-btn"
                class="wom-btn"
                style="background: #6c757d; color: white; border: none;"
                title="Restaurar configuración por defecto"
            >
                <span class="dashicons dashicons-image-rotate"></span> Restaurar Valores Por Defecto
            </button>
        </div>
    </form>

    <div class="wom-footer">
        Woo OTEC Moodle v<?php echo WOO_OTEC_MOODLE_VERSION; ?>
    </div>
</div>

<!-- ESTILOS MEJORADOS -->
<style>
    .wom-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .wom-form-group {
        margin-bottom: 15px;
    }
    
    .wom-form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .wom-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    
    .wom-input:focus {
        border-color: #0073aa;
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
    }
    
    .wom-input-text,
    .wom-input-email,
    .wom-input-number,
    .wom-input-select {
        /* Estilos específicos pueden agregarse aquí */
    }
    
    .wom-input[type="number"] {
        max-width: 150px;
    }
    
    /* Input Group - Para inputs con botones anexos */
    .wom-input-group {
        display: flex;
        align-items: center;
        gap: 0;
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
        background: white;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    
    .wom-input-group:focus-within {
        border-color: #0073aa;
        box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
    }
    
    .wom-input-group .wom-input {
        border: none;
        flex: 1;
        margin: 0;
        border-radius: 0;
        box-shadow: none;
    }
    
    .wom-input-group .wom-input:focus {
        box-shadow: none;
    }
    
    .wom-toggle-btn {
        background: #f5f5f5;
        border: none;
        border-left: 1px solid #ddd;
        padding: 10px 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        color: #666;
        flex-shrink: 0;
    }
    
    .wom-toggle-btn:hover {
        background: #e0e0e0;
        color: #333;
    }
    
    .wom-toggle-btn:active {
        background: #d0d0d0;
    }
    
    .wom-toggle-btn .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }
    
    .wom-toggle-password.active {
        background: #d4edda;
        color: #28a745;
    }
    
    .wom-copy-btn {
        background: #e7f3ff;
        border-left-color: #0073aa;
        color: #0073aa;
    }
    
    .wom-copy-btn:hover {
        background: #d1ecf1;
    }
    
    .wom-copy-btn.copied {
        background: #d4edda;
        color: #28a745;
    }
    
    .wom-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        font-size: 14px;
        white-space: nowrap;
    }
    
    .wom-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .wom-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .wom-btn-primary {
        background: #0073aa;
        color: white;
    }
    
    .wom-btn-primary:hover {
        background: #005a87;
    }
    
    .wom-btn-secondary {
        background: #007cba;
        color: white;
    }
    
    .wom-btn-secondary:hover {
        background: #0066a1;
    }
    
    .wom-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    
    .wom-btn:disabled:hover {
        box-shadow: none;
    }
    
    .wom-btn-send-test {
        height: 38px;
    }
    
    .wom-btn-spinner {
        display: inline-flex;
        align-items: center;
    }
    
    .wom-test-email-container {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 15px;
        align-items: flex-end;
        padding: 15px;
        background: white;
        border-radius: 4px;
    }
    
    .wom-card-highlight {
        box-shadow: 0 2px 8px rgba(0, 115, 170, 0.15);
    }
    
    .wom-notice {
        padding: 12px;
        border-radius: 4px;
        border-left: 4px solid;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .wom-notice-success {
        background: #d4edda;
        border-color: #28a745;
        color: #155724;
    }
    
    .wom-notice-error {
        background: #f8d7da;
        border-color: #dc3545;
        color: #721c24;
    }
    
    .wom-notice-info {
        background: #d1ecf1;
        border-color: #0c5460;
        color: #0c5460;
    }
    
    .wom-notice-warning {
        background: #fff3cd;
        border-color: #856404;
        color: #856404;
    }
    
    small {
        display: block;
        margin-top: 4px;
        color: #666;
        font-size: 12px;
        line-height: 1.4;
    }
    
    small a {
        color: #0073aa;
        text-decoration: underline;
    }
    
    small a:hover {
        color: #005a87;
    }
    
    @media (max-width: 768px) {
        .wom-form-row {
            grid-template-columns: 1fr;
        }
        
        .wom-test-email-container {
            grid-template-columns: 1fr;
        }
        
        .wom-btn-send-test {
            width: 100%;
            height: auto;
            justify-content: center;
        }
        
        .wom-input-group {
            flex-direction: column;
        }
        
        .wom-input-group .wom-input {
            border-radius: 4px 4px 0 0;
        }
        
        .wom-toggle-btn {
            border-left: none;
            border-top: 1px solid #ddd;
            width: 100%;
            border-radius: 0 0 4px 4px;
        }
    }
</style>

<!-- SCRIPT MEJORADO CON TODAS LAS FUNCIONALIDADES -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const useSmtpCheckbox = document.getElementById('woo_otec_email_use_smtp');
    const smtpFields = document.getElementById('smtp-fields');
    const testEmailBtn = document.getElementById('test-email-btn');
    const testResult = document.getElementById('test-email-result');
    const testEmailAddress = document.getElementById('test-email-address');
    const uploadLogoBtn = document.getElementById('upload-logo-btn');
    const removeLogoBtn = document.getElementById('remove-logo-btn');
    const logoPreview = document.getElementById('logo-preview');
    const logoIdInput = document.getElementById('woo_otec_email_logo_id');
    const resetBtn = document.getElementById('reset-config-btn');

    // ===== SMTP TOGGLE =====
    if (useSmtpCheckbox) {
        useSmtpCheckbox.addEventListener('change', function() {
            smtpFields.style.display = this.checked ? 'block' : 'none';
        });
    }

    // ===== TOGGLE PASSWORD / SHOW-HIDE =====
    document.querySelectorAll('.wom-toggle-password').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const inputField = document.querySelector(targetId);
            const isPassword = inputField.type === 'password';
            
            inputField.type = isPassword ? 'text' : 'password';
            this.classList.toggle('active', isPassword);
            
            // Cambiar icono
            const icon = this.querySelector('.dashicons');
            if (icon) {
                icon.className = isPassword ? 'dashicons dashicons-visibility-alt' : 'dashicons dashicons-visibility';
            }
        });
    });

    // ===== COPY TO CLIPBOARD =====
    document.querySelectorAll('.wom-copy-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const inputField = document.querySelector(targetId);
            
            if (inputField && inputField.value) {
                navigator.clipboard.writeText(inputField.value).then(() => {
                    this.classList.add('copied');
                    const originalIcon = this.innerHTML;
                    this.innerHTML = '<span class="dashicons dashicons-yes" style="color: #28a745;"></span>';
                    
                    setTimeout(() => {
                        this.classList.remove('copied');
                        this.innerHTML = originalIcon;
                    }, 2000);
                });
            }
        });
    });

    // ===== MEDIA UPLOADER PARA LOGO =====
    if (uploadLogoBtn) {
        uploadLogoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const mediaFrame = wp.media({
                title: 'Seleccionar Logo para Email',
                button: { text: 'Usar esta imagen' },
                library: { type: 'image' },
                multiple: false
            });
            
            mediaFrame.on('select', function() {
                const attachment = mediaFrame.state().get('selection').first().toJSON();
                logoIdInput.value = attachment.id;
                
                // Actualizar preview
                logoPreview.innerHTML = '<img src="' + attachment.url + '" alt="Logo" style="max-width: 180px; max-height: 120px; border-radius: 4px; border: 1px solid #ddd; padding: 4px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
                
                // Mostrar botón eliminar si no existe
                if (!removeLogoBtn) {
                    const newBtn = document.createElement('button');
                    newBtn.type = 'button';
                    newBtn.id = 'remove-logo-btn';
                    newBtn.className = 'wom-btn';
                    newBtn.style.marginLeft = '5px';
                    newBtn.style.background = '#dc3545';
                    newBtn.style.color = 'white';
                    newBtn.innerHTML = '<span class="dashicons dashicons-trash"></span> Eliminar';
                    uploadLogoBtn.parentNode.insertBefore(newBtn, uploadLogoBtn.nextSibling);
                    
                    newBtn.addEventListener('click', removeLogo);
                }
                
                showNotification('Logo cargado exitosamente', 'success');
            });
            
            mediaFrame.open();
        });
    }

    // ===== ELIMINAR LOGO =====
    function removeLogo(e) {
        e.preventDefault();
        logoIdInput.value = '';
        logoPreview.innerHTML = '<div style="width: 180px; height: 120px; background: #f0f0f0; border: 2px dashed #ccc; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999;">Sin Logo</div>';
        this.remove();
        showNotification('Logo eliminado', 'info');
    }

    if (removeLogoBtn) {
        removeLogoBtn.addEventListener('click', removeLogo);
    }

    // ===== ENVIAR EMAIL DE PRUEBA =====
    if (testEmailBtn) {
        testEmailBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const email = testEmailAddress.value.trim();
            
            if (!email) {
                showNotification('Por favor ingresa un correo electrónico', 'error');
                testEmailAddress.focus();
                return;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showNotification('Correo electrónico inválido', 'error');
                testEmailAddress.focus();
                return;
            }
            
            testEmailBtn.disabled = true;
            document.querySelector('.wom-btn-text').style.display = 'none';
            document.querySelector('.wom-btn-spinner').style.display = 'inline-flex';
            testResult.style.display = 'none';
            testResult.className = '';
            
            fetch(wooOtecMoodle.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'woo_otec_test_email',
                    nonce: wooOtecMoodle.nonce,
                    test_email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                testResult.style.display = 'block';
                if (data.success) {
                    testResult.className = 'wom-notice wom-notice-success';
                    testResult.innerHTML = '<strong>✓ Éxito:</strong> ' + data.data;
                    showNotification('Email de prueba enviado', 'success');
                } else {
                    testResult.className = 'wom-notice wom-notice-error';
                    testResult.innerHTML = '<strong>✗ Error:</strong> ' + (data.data || 'Error desconocido');
                    showNotification('Error al enviar email de prueba', 'error');
                }
            })
            .catch(error => {
                testResult.style.display = 'block';
                testResult.className = 'wom-notice wom-notice-error';
                testResult.innerHTML = '<strong>✗ Error:</strong> ' + error.message;
                showNotification('Error de conexión', 'error');
            })
            .finally(() => {
                testEmailBtn.disabled = false;
                document.querySelector('.wom-btn-text').style.display = 'inline';
                document.querySelector('.wom-btn-spinner').style.display = 'none';
            });
        });
    }

    // ===== RESETEAR CONFIGURACIÓN =====
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('¿Estás seguro de que deseas restaurar los valores por defecto?\n\nEsta acción no se puede deshacer.')) {
                fetch(wooOtecMoodle.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'woo_otec_reset_email_config',
                        nonce: wooOtecMoodle.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Configuración restaurada', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Error al resetear', 'error');
                    }
                })
                .catch(error => showNotification('Error: ' + error.message, 'error'));
            }
        });
    }

    // ===== FUNCIÓN DE NOTIFICACIONES =====
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = 'wom-notice wom-notice-' + type;
        notification.innerHTML = message;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }

    // ===== ENTER PARA ENVIAR PRUEBA =====
    if (testEmailAddress) {
        testEmailAddress.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && testEmailBtn) {
                testEmailBtn.click();
            }
        });
    }
});
</script>

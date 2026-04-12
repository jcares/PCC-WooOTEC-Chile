<?php
/**
 * WordPress function stubs for Intelephense.
 *
 * Este archivo solo existe para mejorar el análisis estático en el editor.
 * Nunca se debe incluir en runtime en un entorno real de WordPress.
 */

if ( false ) {
    function get_option( string $option, $default = null ) {}
    function settings_fields( string $option_group ) {}
    function esc_attr( $text ) {}
    function esc_html( $text ) {}
    function esc_url( $url ) {}
    function esc_textarea( $text ) {}
    function checked( $checked, $current = true, $echo = true ) {}
    function selected( $selected, $current = true, $echo = true ) {}
    function submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) {}
    function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {}
    function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {}
    function wp_localize_script( $handle, $object_name, $l10n ) {}
    function wp_create_nonce( $action = -1 ) {}
    function admin_url( $path = '', $scheme = 'admin' ) {}
    function sanitize_text_field( $str ) {}
    function sanitize_email( $email ) {}
    function wc_get_order( $order_id ) {}
    function get_post_meta( $post_id, $key = '', $single = false ) {}
    function update_post_meta( $post_id, $meta_key, $meta_value ) {}
    function delete_post_meta( $post_id, $meta_key, $meta_value = '' ) {}
    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {}
    function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {}
    function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ) {}
    function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {}
    function register_setting( $option_group, $option_name, $args = array() ) {}
    function wp_enqueue_media() {}
    function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) {}
    function current_user_can( $capability, ...$args ) {}
    function wp_send_json_error( $data = null, $status_code = null ) {}
    function wp_send_json_success( $data = null, $status_code = null ) {}
    function delete_option( $option ) {}
    function is_wp_error( $thing ) {}
    function get_post_type( $post = null ) {}
    function set_post_thumbnail( $post_id, $thumbnail_id ) {}
    function wc_get_product( $product_id ) {}
    function get_post( $post = null, $output = OBJECT, $filter = 'raw' ) {}
    function wp_kses_post( $data ) {}
    function wp_trim_words( $text, $num_words = 55, $more = null ) {}
    function do_action( $tag, ...$args ) {}
    function apply_filters( $tag, $value ) {}
    function esc_html_e( $text, $domain = 'default' ) {}
    function esc_attr_e( $text, $domain = 'default' ) {}
    function __e( $text, $domain = 'default' ) {}
    function __ngettext( $single, $plural, $number, $domain = 'default' ) {}

    // Class stubs
    class WP_Post {
        public $ID;
        public $post_title;
        public $post_content;
        public $post_excerpt;
        public $post_status;
        public $post_type;
        public $post_date;
        public $post_modified;
    }

    class WC_Product {
        public function get_name() {}
        public function get_description() {}
        public function get_price() {}
        public function get_id() {}
        public function is_in_stock() {}
    }

    // Custom plugin classes
    class Woo_OTEC_Moodle_Template_Manager {}
    class Woo_OTEC_Moodle_Template_Customizer {}
    class Woo_OTEC_Moodle_Preview_Generator {}

    // WP_Error class
    class WP_Error {
        public function __construct($code, $message, $data = null) {}
        public function get_error_code() {}
        public function get_error_message() {}
    }

    // Additional WordPress functions
    function __( $text, $domain = 'default' ) {}
    function trailingslashit( $string ) {}
    function add_query_arg( $key, $value = null, $url = null ) {}
    function wp_remote_get( $url, $args = array() ) {}
    function wp_remote_retrieve_body( $response ) {}
    function is_wp_error( $thing ) {}
}

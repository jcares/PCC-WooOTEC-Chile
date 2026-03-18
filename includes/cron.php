<?php

if (!defined('ABSPATH')) {
    exit;
}

class PCC_WooOTEC_Cron {
    public const CRON_HOOK = 'pcc_woootec_retry_failed_enrollments';
    public const CRON_SCHEDULE = 'pcc_woootec_five_minutes';
    public const SYNC_HOOK = 'pcc_woootec_hourly_sync';
    public const SYNC_SCHEDULE = 'pcc_woootec_hourly';

    public static function install_table() {
        global $wpdb;

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            user_email VARCHAR(190) NOT NULL,
            course_id BIGINT(20) UNSIGNED NOT NULL,
            attempts INT(11) UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            last_try DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_course (order_id, course_id),
            KEY status_attempts (status, attempts),
            KEY user_email (user_email)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'pcc_failed_enrollments';
    }

    public static function add_cron_schedule($schedules) {
        if (!isset($schedules[self::CRON_SCHEDULE])) {
            $schedules[self::CRON_SCHEDULE] = array(
                'interval' => 5 * MINUTE_IN_SECONDS,
                'display'  => 'PCC WooOTEC: cada 5 minutos',
            );
        }

        if (!isset($schedules[self::SYNC_SCHEDULE])) {
            $schedules[self::SYNC_SCHEDULE] = array(
                'interval' => HOUR_IN_SECONDS,
                'display'  => 'PCC WooOTEC: cada 1 hora',
            );
        }

        return $schedules;
    }

    public static function ensure_scheduled() {
        if (!wp_next_scheduled(self::CRON_HOOK) && apply_filters('pcc_enable_retry_cron', true)) {
            wp_schedule_event(time() + 120, self::CRON_SCHEDULE, self::CRON_HOOK);
        }

        if (!wp_next_scheduled(self::SYNC_HOOK) && apply_filters('pcc_enable_sync_cron', true)) {
            wp_schedule_event(time() + 300, self::SYNC_SCHEDULE, self::SYNC_HOOK);
        }
    }

    public static function unschedule() {
        foreach (array(self::CRON_HOOK, self::SYNC_HOOK) as $hook) {
            $timestamp = wp_next_scheduled($hook);
            while ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
                $timestamp = wp_next_scheduled($hook);
            }
        }
    }

    public static function enqueue_failed($order_id, $user_email, $course_id, $error_message = '') {
        global $wpdb;

        $order_id = (int) $order_id;
        $course_id = (int) $course_id;
        $user_email = sanitize_email((string) $user_email);

        if ($order_id <= 0 || $course_id <= 0 || $user_email === '') {
            PCC_Logger::error('enqueue_failed: datos invalidos', array('order_id' => $order_id, 'course_id' => $course_id));
            return false;
        }

        $table = self::table_name();
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE order_id = %d AND course_id = %d LIMIT 1",
                $order_id,
                $course_id
            )
        );

        $now = current_time('mysql');
        if ($existing) {
            $wpdb->update(
                $table,
                array(
                    'status'   => 'pending',
                    'last_try' => $now,
                ),
                array('id' => (int) $existing->id),
                array('%s', '%s'),
                array('%d')
            );
            return (int) $existing->id;
        }

        $wpdb->insert(
            $table,
            array(
                'order_id'   => $order_id,
                'user_email' => $user_email,
                'course_id'  => $course_id,
                'attempts'   => 0,
                'status'     => 'pending',
                'last_try'   => $now,
            ),
            array('%d', '%s', '%d', '%d', '%s', '%s')
        );

        if ($wpdb->insert_id && $error_message !== '') {
            PCC_Logger::error('Matricula en cola', array('order_id' => $order_id, 'course_id' => $course_id, 'error' => $error_message));
        }

        return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
    }

    public static function get_queue_counts() {
        global $wpdb;

        $table = self::table_name();
        $rows = $wpdb->get_results("SELECT status, COUNT(1) AS c FROM {$table} GROUP BY status");
        $counts = array(
            'pending'   => 0,
            'failed'    => 0,
            'enrolled'  => 0,
            'abandoned' => 0,
        );

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $status = isset($row->status) ? (string) $row->status : '';
                if (isset($counts[$status])) {
                    $counts[$status] = (int) $row->c;
                }
            }
        }

        return $counts;
    }

    public static function get_queue_rows($limit = 50, $statuses = array('pending', 'failed', 'abandoned', 'enrolled')) {
        global $wpdb;

        $limit = max(1, min(200, (int) $limit));
        $statuses = array_values(array_filter(array_map('sanitize_key', (array) $statuses)));
        if (empty($statuses)) {
            $statuses = array('pending', 'failed', 'abandoned', 'enrolled');
        }

        $table = self::table_name();
        $placeholders = implode(',', array_fill(0, count($statuses), '%s'));

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE status IN ({$placeholders}) ORDER BY created_at DESC LIMIT %d",
                array_merge($statuses, array($limit))
            )
        );
    }

    public static function retry_failed_enrollments($return_details = false) {
        global $wpdb;

        $max_attempts = max(1, (int) apply_filters('pcc_retry_max_attempts', 6));
        $batch_size = max(1, (int) apply_filters('pcc_retry_batch_size', 10));

        $table = self::table_name();
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE status IN ('pending','failed')
                   AND attempts < %d
                 ORDER BY COALESCE(last_try, created_at) ASC
                 LIMIT %d",
                $max_attempts,
                $batch_size
            )
        );

        if (empty($rows)) {
            return $return_details ? array() : null;
        }

        $details = array();
        foreach ($rows as $row) {
            $result = self::retry_row($row, $max_attempts);
            if ($return_details && is_array($result)) {
                $details[] = $result;
            }
        }

        return $return_details ? $details : null;
    }

    private static function retry_row($row, $max_attempts) {
        global $wpdb;

        $table = self::table_name();
        $id = (int) $row->id;
        $order_id = (int) $row->order_id;
        $course_id = (int) $row->course_id;
        $user_email = (string) $row->user_email;
        $status_before = isset($row->status) ? (string) $row->status : '';
        $attempts = (int) $row->attempts + 1;

        $wpdb->update(
            $table,
            array(
                'attempts' => $attempts,
                'last_try' => current_time('mysql'),
                'status'   => 'pending',
            ),
            array('id' => $id),
            array('%d', '%s', '%s'),
            array('%d')
        );

        if (!function_exists('wc_get_order')) {
            $status_after = self::mark_failed($id, $attempts, $max_attempts, 'WooCommerce no disponible');
            return self::result_array($id, $order_id, $course_id, $status_before, $status_after, 'WooCommerce no disponible');
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            $status_after = self::mark_failed($id, $attempts, $max_attempts, 'Orden no encontrada');
            return self::result_array($id, $order_id, $course_id, $status_before, $status_after, 'Orden no encontrada');
        }

        $user = get_user_by('email', $user_email);
        if (!($user instanceof WP_User)) {
            $status_after = self::mark_failed($id, $attempts, $max_attempts, 'Usuario WP no encontrado por email');
            return self::result_array($id, $order_id, $course_id, $status_before, $status_after, 'Usuario WP no encontrado por email');
        }

        $moodle_user_id = (int) get_user_meta($user->ID, 'pcc_moodle_user_id', true);
        if ($moodle_user_id <= 0) {
            $create_info = function_exists('pcc_moodle_get_or_create_user_with_password') ? pcc_moodle_get_or_create_user_with_password($user) : false;
            if (!empty($create_info['id'])) {
                $moodle_user_id = (int) $create_info['id'];
                update_user_meta($user->ID, 'pcc_moodle_user_id', $moodle_user_id);
            }
        }

        if ($moodle_user_id <= 0) {
            $status_after = self::mark_failed($id, $attempts, $max_attempts, 'No se pudo obtener/crear usuario Moodle');
            return self::result_array($id, $order_id, $course_id, $status_before, $status_after, 'No se pudo obtener/crear usuario Moodle');
        }

        $ok = function_exists('pcc_moodle_enroll_user') ? pcc_moodle_enroll_user($moodle_user_id, $course_id) : false;
        if (!$ok) {
            $status_after = self::mark_failed($id, $attempts, $max_attempts, 'Fallo matricula Moodle');
            return self::result_array($id, $order_id, $course_id, $status_before, $status_after, 'Fallo matricula Moodle');
        }

        $wpdb->update(
            $table,
            array('status' => 'enrolled'),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        $already = $order->get_meta('_pcc_moodle_enrolled_courses');
        if (!is_array($already)) {
            $already = array();
        }

        if (!in_array($course_id, $already, true)) {
            $already[] = $course_id;
            $order->update_meta_data('_pcc_moodle_enrolled_courses', array_values(array_unique($already)));
        }

        if (self::count_pending_for_order($order_id, $max_attempts) === 0) {
            $order->update_meta_data('_pcc_moodle_enrollment_complete', 1);
        }

        $order->save();

        PCC_Logger::log('Reintento matricula OK', 'info', array('order_id' => $order_id, 'course_id' => $course_id), true);

        return self::result_array($id, $order_id, $course_id, $status_before, 'enrolled', 'OK');
    }

    private static function mark_failed($id, $attempts, $max_attempts, $reason) {
        global $wpdb;

        $status = $attempts >= $max_attempts ? 'abandoned' : 'failed';

        $wpdb->update(
            self::table_name(),
            array('status' => $status),
            array('id' => (int) $id),
            array('%s'),
            array('%d')
        );

        PCC_Logger::error('Reintento matricula fallo', array('row_id' => (int) $id, 'attempts' => (int) $attempts, 'reason' => (string) $reason));

        return $status;
    }

    private static function count_pending_for_order($order_id, $max_attempts) {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(1) FROM " . self::table_name() . "
                 WHERE order_id = %d
                   AND status IN ('pending','failed')
                   AND attempts < %d",
                (int) $order_id,
                (int) $max_attempts
            )
        );
    }

    private static function result_array($row_id, $order_id, $course_id, $status_before, $status_after, $message) {
        return array(
            'row_id'    => (int) $row_id,
            'order_id'  => (int) $order_id,
            'course_id' => (int) $course_id,
            'from'      => (string) $status_before,
            'to'        => (string) $status_after,
            'message'   => (string) $message,
        );
    }
}

<?php

if (!defined('ABSPATH')) {
    exit;
}

class PCC_WooOTEC_Updater {
    private const CACHE_KEY = 'pcc_github_release_payload';
    private const CACHE_TTL = 3600;

    public static function inject_update($transient) {
        if (!is_object($transient)) {
            $transient = new stdClass();
        }

        $release = self::get_release_data();
        if (!$release || empty($release['version'])) {
            return $transient;
        }

        if (version_compare((string) $release['version'], PCC_WOOOTEC_VERSION, '<=')) {
            return $transient;
        }

        $item = (object) array(
            'slug'        => dirname(PCC_WOOOTEC_BASENAME),
            'plugin'      => PCC_WOOOTEC_BASENAME,
            'new_version' => (string) $release['version'],
            'package'     => !empty($release['download_url']) ? (string) $release['download_url'] : '',
            'url'         => !empty($release['homepage']) ? (string) $release['homepage'] : '',
            'tested'      => !empty($release['tested']) ? (string) $release['tested'] : '',
            'requires_php'=> '8.1',
        );

        if (!isset($transient->response) || !is_array($transient->response)) {
            $transient->response = array();
        }

        $transient->response[PCC_WOOOTEC_BASENAME] = $item;
        return $transient;
    }

    public static function plugins_api($result, $action, $args) {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== dirname(PCC_WOOOTEC_BASENAME)) {
            return $result;
        }

        $release = self::get_release_data();
        if (!$release) {
            return $result;
        }

        return (object) array(
            'name'          => 'PCC-WooOTEC-Chile',
            'slug'          => dirname(PCC_WOOOTEC_BASENAME),
            'version'       => (string) $release['version'],
            'author'        => '<a href="https://github.com">PCC</a>',
            'homepage'      => !empty($release['homepage']) ? (string) $release['homepage'] : '',
            'requires_php'  => '8.1',
            'download_link' => !empty($release['download_url']) ? (string) $release['download_url'] : '',
            'sections'      => array(
                'description' => !empty($release['description']) ? wp_kses_post($release['description']) : 'Integracion Moodle + WooCommerce para cursos online.',
                'changelog'   => !empty($release['changelog']) ? wp_kses_post($release['changelog']) : 'Sin changelog disponible.',
            ),
        );
    }

    public static function maybe_enable_auto_update($should_update, $item) {
        if (empty($item->plugin) || $item->plugin !== PCC_WOOOTEC_BASENAME) {
            return $should_update;
        }

        return (bool) pcc_get_option('pcc_github_auto_update', false);
    }

    public static function maybe_render_notice() {
        if (!current_user_can('update_plugins')) {
            return;
        }

        $release = self::get_release_data();
        if (!$release || empty($release['version'])) {
            return;
        }

        if (version_compare((string) $release['version'], PCC_WOOOTEC_VERSION, '<=')) {
            return;
        }

        echo '<div class="notice notice-info"><p>';
        echo 'PCC-WooOTEC-Chile tiene una nueva version disponible: ';
        echo esc_html((string) $release['version']);
        echo '. Revisa la pantalla de plugins para actualizar.';
        echo '</p></div>';
    }

    private static function get_release_data() {
        $cached = get_transient(self::CACHE_KEY);
        if (is_array($cached) && !empty($cached['version'])) {
            return $cached;
        }

        $payload = self::fetch_release_data();
        if (is_array($payload) && !empty($payload['version'])) {
            set_transient(self::CACHE_KEY, $payload, self::CACHE_TTL);
            return $payload;
        }

        return false;
    }

    private static function fetch_release_data() {
        $release_json_url = trim((string) pcc_get_option('pcc_github_release_json', ''));
        if ($release_json_url !== '') {
            $data = self::request_json($release_json_url);
            if (is_array($data)) {
                return self::normalize_release($data);
            }
        }

        $repo = trim((string) pcc_get_option('pcc_github_repo', ''));
        if ($repo === '' || strpos($repo, '/') === false) {
            return false;
        }

        $data = self::request_json('https://api.github.com/repos/' . $repo . '/releases/latest');
        if (!is_array($data)) {
            return false;
        }

        return self::normalize_release($data, $repo);
    }

    private static function request_json($url) {
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept'     => 'application/json',
                'User-Agent' => 'PCC-WooOTEC-Chile/' . PCC_WOOOTEC_VERSION,
            ),
        ));

        if (is_wp_error($response)) {
            return false;
        }

        if ((int) wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : false;
    }

    private static function normalize_release($data, $repo = '') {
        $version = '';
        if (!empty($data['version'])) {
            $version = ltrim((string) $data['version'], 'v');
        } elseif (!empty($data['tag_name'])) {
            $version = ltrim((string) $data['tag_name'], 'v');
        }

        if ($version === '') {
            return false;
        }

        $download_url = '';
        if (!empty($data['download_url'])) {
            $download_url = (string) $data['download_url'];
        } elseif (!empty($data['zipball_url'])) {
            $download_url = (string) $data['zipball_url'];
        }

        $homepage = !empty($data['html_url']) ? (string) $data['html_url'] : '';
        if ($homepage === '' && $repo !== '') {
            $homepage = 'https://github.com/' . $repo;
        }

        return array(
            'version'      => $version,
            'download_url' => $download_url,
            'homepage'     => $homepage,
            'description'  => !empty($data['description']) ? (string) $data['description'] : '',
            'changelog'    => !empty($data['body']) ? nl2br(esc_html((string) $data['body'])) : '',
            'tested'       => !empty($data['tested']) ? (string) $data['tested'] : '',
        );
    }
}

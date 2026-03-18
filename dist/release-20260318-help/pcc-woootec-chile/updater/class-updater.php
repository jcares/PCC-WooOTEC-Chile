<?php

if (!defined('ABSPATH')) {
    exit;
}

final class PCC_WooOTEC_Pro_Updater {
    private static ?PCC_WooOTEC_Pro_Updater $instance = null;

    public static function instance(): PCC_WooOTEC_Pro_Updater {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
    }

    public function boot(): void {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'inject_update'));
        add_filter('plugins_api', array($this, 'plugins_api'), 10, 3);
        add_filter('auto_update_plugin', array($this, 'handle_auto_update'), 10, 2);
    }

    public function inject_update(mixed $transient): mixed {
        if (!is_object($transient)) {
            $transient = new stdClass();
        }

        $release = $this->get_release();
        if (!$release || version_compare((string) $release['version'], PCC_WOOOTEC_PRO_VERSION, '<=')) {
            return $transient;
        }

        $transient->response[PCC_WOOOTEC_PRO_BASENAME] = (object) array(
            'slug'        => dirname(PCC_WOOOTEC_PRO_BASENAME),
            'plugin'      => PCC_WOOOTEC_PRO_BASENAME,
            'new_version' => (string) $release['version'],
            'package'     => (string) ($release['download_url'] ?? ''),
            'url'         => (string) ($release['homepage'] ?? ''),
        );

        return $transient;
    }

    public function plugins_api(mixed $result, string $action, object $args): mixed {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== dirname(PCC_WOOOTEC_PRO_BASENAME)) {
            return $result;
        }

        $release = $this->get_release();
        if (!$release) {
            return $result;
        }

        return (object) array(
            'name'          => 'PCC-WooOTEC-Chile PRO',
            'slug'          => dirname(PCC_WOOOTEC_PRO_BASENAME),
            'version'       => (string) $release['version'],
            'download_link' => (string) ($release['download_url'] ?? ''),
            'homepage'      => (string) ($release['homepage'] ?? ''),
            'sections'      => array(
                'description' => (string) ($release['description'] ?? ''),
                'changelog'   => (string) ($release['changelog'] ?? ''),
            ),
        );
    }

    public function handle_auto_update($should_update, object $item): bool {
        if (empty($item->plugin) || $item->plugin !== PCC_WOOOTEC_PRO_BASENAME) {
            return (bool) $should_update;
        }

        return PCC_WooOTEC_Pro_Core::instance()->get_option('auto_update', 'no') === 'yes';
    }

    public function get_release_data(): array|false {
        return $this->get_release();
    }

    public function has_update_available(): bool {
        $release = $this->get_release();
        return is_array($release) && !empty($release['version']) && version_compare((string) $release['version'], PCC_WOOOTEC_PRO_VERSION, '>');
    }

    private function get_release(): array|false {
        $cache_key = 'pcc_woootec_pro_release';
        $cached = get_transient($cache_key);
        if (is_array($cached) && !empty($cached['version'])) {
            return $cached;
        }

        $release = $this->fetch_release();
        if ($release) {
            set_transient($cache_key, $release, HOUR_IN_SECONDS);
        }

        return $release;
    }

    private function fetch_release(): array|false {
        $release_url = trim((string) PCC_WooOTEC_Pro_Core::instance()->get_option('github_release_url', ''));
        if ($release_url !== '') {
            $release_url = $this->normalize_release_url($release_url);
            $release = $this->request_json($release_url);
            if ($release) {
                return $release;
            }
        }

        $repo = trim((string) PCC_WooOTEC_Pro_Core::instance()->get_option('github_repo', ''));
        if ($repo === '' || !str_contains($repo, '/')) {
            return false;
        }

        return $this->request_json('https://api.github.com/repos/' . $repo . '/releases/latest');
    }

    private function normalize_release_url(string $url): string {
        $url = trim($url);
        if (str_contains($url, 'github.com/') && str_contains($url, '/blob/')) {
            $url = str_replace('https://github.com/', 'https://raw.githubusercontent.com/', $url);
            $url = str_replace('/blob/', '/', $url);
        }

        return $url;
    }

    private function request_json(string $url): array|false {
        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 15,
                'headers' => array(
                    'Accept'     => 'application/json',
                    'User-Agent' => 'PCC-WooOTEC-Chile-PRO/' . PCC_WOOOTEC_PRO_VERSION,
                ),
            )
        );

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($decoded)) {
            return false;
        }

        if (!empty($decoded['tag_name'])) {
            $decoded['version'] = ltrim((string) $decoded['tag_name'], 'v');
            $decoded['download_url'] = !empty($decoded['zipball_url']) ? (string) $decoded['zipball_url'] : '';
            $decoded['homepage'] = !empty($decoded['html_url']) ? (string) $decoded['html_url'] : '';
            $decoded['changelog'] = !empty($decoded['body']) ? nl2br(esc_html((string) $decoded['body'])) : '';
        }

        return !empty($decoded['version']) ? $decoded : false;
    }
}

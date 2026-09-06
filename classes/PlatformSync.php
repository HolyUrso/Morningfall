<?php

if (!class_exists('PlatformHttpClient', false)) {
    class PlatformHttpClient
    {
        private $config;

        public function __construct(array $config)
        {
            $this->config = $config;
        }

        public function getJson(string $url, array $headers = []): array
        {
            return $this->request($url, false, [], $headers);
        }

        public function postForm(string $url, array $fields, array $headers = []): array
        {
            return $this->request($url, true, $fields, $headers);
        }

        private function request(string $url, bool $post, array $fields, array $headers): array
        {
            if (!function_exists('curl_init')) {
                throw new RuntimeException('A extensão cURL do PHP não está habilitada.');
            }

            $ch = curl_init($url);
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => (int)($this->config['timeout'] ?? 12),
                CURLOPT_CONNECTTIMEOUT => (int)($this->config['connect_timeout'] ?? 6),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_USERAGENT => (string)($this->config['user_agent'] ?? 'Morningfall Creators/1.0'),
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ];

            if ($post) {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = http_build_query($fields);
            }

            curl_setopt_array($ch, $options);
            $body = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($body === false || $status < 200 || $status >= 300) {
                $message = $post ? 'Falha na autenticação externa' : 'Falha na consulta externa';
                throw new RuntimeException($message.' (HTTP '.$status.').'.($error !== '' ? ' '.$error : ''));
            }

            $data = json_decode($body, true);
            if (!is_array($data)) {
                throw new RuntimeException('A plataforma retornou JSON inválido.');
            }

            return $data;
        }
    }
}

if (!class_exists('PlatformSync', false)) {
    class PlatformSync
    {
        private $config;
        private $adapters = [];
        private $http;
        private $pdo;

        public function __construct(?array $config = null, ?PDO $pdo = null)
        {
            $defaults = require __DIR__.'/../config/platforms.php';
            $customConfig = $config ?? [];
            $this->pdo = $pdo ?: ($customConfig['pdo'] ?? null);
            unset($customConfig['pdo']);
            $this->config = $config === null ? $defaults : array_replace_recursive($defaults, $customConfig);
            $this->http = new PlatformHttpClient($this->config);
            $this->registerAdapters();
        }

        public function resolve(string $input, ?string $platform = null): array
        {
            $platform = $this->detectPlatform($input, $platform);
            $platformConfig = $this->config['platforms'][$platform] ?? null;
            if (!is_array($platformConfig) || empty($platformConfig['enabled'])) {
                throw new RuntimeException('A plataforma informada não está habilitada.');
            }

            $username = $this->normalizeUsername($input, $platform);
            if ($username === '') {
                throw new InvalidArgumentException('Informe um login ou URL de canal válido.');
            }

            if (!isset($this->adapters[$platform])) {
                throw new RuntimeException('Não há adapter configurado para a plataforma informada.');
            }

            return $this->adapters[$platform]->resolve($username);
        }

        public function resolveChannel(string $input, ?string $platform = null): array
        {
            return $this->resolve($input, $platform);
        }

        public function sync(int $streamerId): array
        {
            if ($streamerId <= 0) {
                throw new InvalidArgumentException('ID de streamer inválido.');
            }

            $pdo = $this->database();
            $cacheTtl = max(0, (int)($this->config['sync_cache_ttl'] ?? 900));
            $query = $pdo->prepare('SELECT id,platform,username,last_synced_at FROM streamer_platforms WHERE streamer_id=? AND active=1 ORDER BY is_primary DESC,id ASC');
            $query->execute([$streamerId]);

            $result = [
                'streamer_id' => $streamerId,
                'synced' => [],
                'cached' => [],
                'errors' => []
            ];

            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $channel) {
                $channelId = (int)$channel['id'];
                $lastSyncedAt = (string)($channel['last_synced_at'] ?? '');
                $isFresh = $lastSyncedAt !== '' && strtotime($lastSyncedAt) !== false
                    && (time() - strtotime($lastSyncedAt)) < $cacheTtl;

                if ($isFresh) {
                    $result['cached'][] = $channelId;
                    continue;
                }

                try {
                    $data = $this->resolve((string)$channel['username'], (string)$channel['platform']);
                    $update = $pdo->prepare('UPDATE streamer_platforms SET username=?,display_name=?,channel_id=?,avatar_url=?,channel_url=?,last_synced_at=NOW(),sync_error=NULL WHERE id=?');
                    $update->execute([
                        $data['username'],
                        $data['display_name'],
                        $data['channel_id'],
                        $data['avatar_url'],
                        $data['channel_url'],
                        $channelId
                    ]);
                    $result['synced'][] = $channelId;
                } catch (Throwable $e) {
                    $result['errors'][$channelId] = $e->getMessage();
                    try {
                        $errorUpdate = $pdo->prepare('UPDATE streamer_platforms SET sync_error=? WHERE id=?');
                        $errorUpdate->execute([substr($e->getMessage(), 0, 500), $channelId]);
                    } catch (Throwable $ignored) {
                    }
                }
            }

            return $result;
        }

        public function detectPlatform(string $input, ?string $platform = null): string
        {
            if ($platform !== null && trim($platform) !== '') {
                $normalized = strtolower(trim($platform));
                if (!in_array($normalized, ['twitch', 'youtube', 'kick', 'tiktok'], true)) {
                    throw new InvalidArgumentException('Plataforma não suportada.');
                }
                return $normalized;
            }

            $value = trim($input);
            if ($value === '') {
                throw new InvalidArgumentException('Informe um login ou URL de canal.');
            }

            $url = $value;
            if (strpos($url, '://') === false) {
                $url = 'https://'.$url;
            }
            $host = strtolower((string)parse_url($url, PHP_URL_HOST));
            $host = preg_replace('/^www\./', '', $host);

            if (in_array($host, ['twitch.tv', 'm.twitch.tv'], true) || substr($host, -10) === '.twitch.tv') {
                return 'twitch';
            }
            if (in_array($host, ['youtube.com', 'm.youtube.com', 'youtu.be'], true) || substr($host, -12) === '.youtube.com') {
                return 'youtube';
            }
            if ($host === 'kick.com' || substr($host, -9) === '.kick.com') {
                return 'kick';
            }
            if ($host === 'tiktok.com' || substr($host, -11) === '.tiktok.com') {
                return 'tiktok';
            }

            throw new InvalidArgumentException('Não foi possível identificar a plataforma pela entrada informada.');
        }

        public function normalizeUsername(string $input, string $platform): string
        {
            $value = trim($input);
            if ($value === '') {
                return '';
            }

            $url = $value;
            if (strpos($url, '://') === false && strpos($url, '/') !== false) {
                $url = 'https://'.$url;
            }
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $path = trim((string)parse_url($url, PHP_URL_PATH), '/');
                $segments = $path === '' ? [] : explode('/', $path);
                if ($platform === 'youtube') {
                    if (($segments[0] ?? '') === 'channel' && isset($segments[1])) {
                        return $segments[1];
                    }
                    if (($segments[0] ?? '') === 'user' && isset($segments[1])) {
                        return $segments[1];
                    }
                    if (($segments[0] ?? '') === 'c' && isset($segments[1])) {
                        return $segments[1];
                    }
                    return $segments[0] ?? '';
                }
                return $segments[0] ?? '';
            }

            return ltrim($value, '@/');
        }

        public function getJson(string $url, array $headers = []): array
        {
            return $this->http->getJson($url, $headers);
        }

        public function postForm(string $url, array $fields, array $headers = []): array
        {
            return $this->http->postForm($url, $fields, $headers);
        }

        private function registerAdapters(): void
        {
            foreach ((array)($this->config['platforms'] ?? []) as $platform => $platformConfig) {
                if (!is_array($platformConfig) || empty($platformConfig['enabled'])) {
                    continue;
                }

                $class = (string)($platformConfig['adapter'] ?? $this->adapterClassName($platform));
                $file = (string)($platformConfig['adapter_file'] ?? (__DIR__.'/Platforms/'.$class.'.php'));
                if (!is_file($file)) {
                    continue;
                }
                require_once $file;
                if (class_exists($class, false)) {
                    $this->adapters[$platform] = new $class($this->http, $platformConfig);
                }
            }
        }

        private function adapterClassName(string $platform): string
        {
            $names = [
                'tiktok' => 'TikTokAdapter',
                'youtube' => 'YoutubeAdapter'
            ];
            return $names[$platform] ?? ucfirst($platform).'Adapter';
        }

        private function database(): PDO
        {
            if ($this->pdo instanceof PDO) {
                return $this->pdo;
            }

            if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
                return $GLOBALS['pdo'];
            }

            require_once __DIR__.'/../config/database.php';
            if (isset($pdo) && $pdo instanceof PDO) {
                return $pdo;
            }

            throw new RuntimeException('A conexão com o banco de dados não está disponível.');
        }
    }
}

class PlatformSync
{
    private $config;
    private $adapters = [];

    public function __construct(?array $config = null)
    {
        $defaults = require __DIR__.'/../config/platforms.php';
        $this->config = $config === null ? $defaults : array_replace_recursive($defaults, $config);
        $this->registerAdapters();
    }

    public function resolve(string $input, ?string $platform = null): array
    {
        $platform = $this->detectPlatform($input, $platform);
        $platformConfig = $this->config['platforms'][$platform] ?? null;
        if (!is_array($platformConfig) || empty($platformConfig['enabled'])) {
            throw new RuntimeException('A plataforma informada não está habilitada.');
        }

        $username = $this->normalizeUsername($input, $platform);
        if ($username === '') {
            throw new InvalidArgumentException('Informe um login ou URL de canal válido.');
        }

        if (!isset($this->adapters[$platform])) {
            throw new RuntimeException('Não há adapter configurado para a plataforma informada.');
        }

        return $this->adapters[$platform]->resolve($username);
    }

    public function resolveChannel(string $input, ?string $platform = null): array
    {
        return $this->resolve($input, $platform);
    }

    public function detectPlatform(string $input, ?string $platform = null): string
    {
        if ($platform !== null && trim($platform) !== '') {
            $normalized = strtolower(trim($platform));
            if (!in_array($normalized, ['twitch', 'youtube', 'kick', 'tiktok'], true)) {
                throw new InvalidArgumentException('Plataforma não suportada.');
            }
            return $normalized;
        }

        $value = trim($input);
        if ($value === '') {
            throw new InvalidArgumentException('Informe um login ou URL de canal.');
        }

        $url = $value;
        if (strpos($url, '://') === false) {
            $url = 'https://'.$url;
        }
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);

        if (in_array($host, ['twitch.tv', 'm.twitch.tv'], true) || substr($host, -10) === '.twitch.tv') {
            return 'twitch';
        }
        if (in_array($host, ['youtube.com', 'm.youtube.com', 'youtu.be'], true) || substr($host, -12) === '.youtube.com') {
            return 'youtube';
        }
        if ($host === 'kick.com' || substr($host, -9) === '.kick.com') {
            return 'kick';
        }
        if ($host === 'tiktok.com' || substr($host, -11) === '.tiktok.com') {
            return 'tiktok';
        }

        throw new InvalidArgumentException('Não foi possível identificar a plataforma pela entrada informada.');
    }

    public function normalizeUsername(string $input, string $platform): string
    {
        $value = trim($input);
        if ($value === '') {
            return '';
        }

        $url = $value;
        if (strpos($url, '://') === false && strpos($url, '/') !== false) {
            $url = 'https://'.$url;
        }
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $path = trim((string)parse_url($url, PHP_URL_PATH), '/');
            $segments = $path === '' ? [] : explode('/', $path);
            if ($platform === 'youtube') {
                if (($segments[0] ?? '') === 'channel' && isset($segments[1])) {
                    return $segments[1];
                }
                if (($segments[0] ?? '') === 'user' && isset($segments[1])) {
                    return $segments[1];
                }
                if (($segments[0] ?? '') === 'c' && isset($segments[1])) {
                    return $segments[1];
                }
                return $segments[0] ?? '';
            }
            return $segments[0] ?? '';
        }

        return ltrim($value, '@/');
    }

    public function getJson(string $url, array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('A extensão cURL do PHP não está habilitada.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => (int)($this->config['timeout'] ?? 12),
            CURLOPT_CONNECTTIMEOUT => (int)($this->config['connect_timeout'] ?? 6),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => (string)($this->config['user_agent'] ?? 'Morningfall Creators/1.0'),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('Falha na consulta externa (HTTP '.$status.').'.($error !== '' ? ' '.$error : ''));
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new RuntimeException('A plataforma retornou JSON inválido.');
        }
        return $data;
    }

    public function postForm(string $url, array $fields, array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('A extensão cURL do PHP não está habilitada.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => (int)($this->config['timeout'] ?? 12),
            CURLOPT_CONNECTTIMEOUT => (int)($this->config['connect_timeout'] ?? 6),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => (string)($this->config['user_agent'] ?? 'Morningfall Creators/1.0'),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('Falha na autenticação externa (HTTP '.$status.').'.($error !== '' ? ' '.$error : ''));
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new RuntimeException('A plataforma retornou JSON inválido.');
        }
        return $data;
    }

    private function registerAdapters(): void
    {
        $platforms = $this->config['platforms'] ?? [];
        $this->adapters = [
            'twitch' => new TwitchAdapter($this, (array)($platforms['twitch'] ?? [])),
            'youtube' => new YoutubeAdapter($this, (array)($platforms['youtube'] ?? [])),
            'kick' => new KickAdapter($this, (array)($platforms['kick'] ?? [])),
            'tiktok' => new TikTokAdapter($this, (array)($platforms['tiktok'] ?? []))
        ];
    }
}

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
            $query = $pdo->prepare('SELECT id,platform,username,last_sync FROM streamer_platforms WHERE streamer_id=? AND active=1 ORDER BY is_primary DESC,id ASC');
            $query->execute([$streamerId]);

            $result = [
                'streamer_id' => $streamerId,
                'synced' => [],
                'cached' => [],
                'errors' => []
            ];

            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $channel) {
                $channelId = (int)$channel['id'];
                $lastSync = (string)($channel['last_sync'] ?? '');
                $isFresh = $lastSync !== '' && strtotime($lastSync) !== false
                    && (time() - strtotime($lastSync)) < $cacheTtl;

                if ($isFresh) {
                    $result['cached'][] = $channelId;
                    continue;
                }

                try {
                    $data = $this->resolve((string)$channel['username'], (string)$channel['platform']);
                    $this->storeResolvedChannel($pdo, $streamerId, $data);
                    $result['synced'][] = $channelId;
                } catch (Throwable $e) {
                    $result['errors'][$channelId] = $e->getMessage();
                    $this->storeChannelError($pdo, $streamerId, (string)$channel['platform'], (string)$channel['username'], $e->getMessage());
                }
            }

            return $result;
        }

        public function storeResolvedChannel(PDO $pdo, int $streamerId, array $data): void
        {
            $platform = strtolower(trim((string)($data['platform'] ?? '')));
            if ($streamerId <= 0 || $platform === '') {
                throw new InvalidArgumentException('Dados de plataforma inválidos.');
            }

            $pdo->prepare('UPDATE streamer_platforms SET is_primary=0 WHERE streamer_id=?')->execute([$streamerId]);
            $lookup = $pdo->prepare('SELECT id FROM streamer_platforms WHERE streamer_id=? AND platform=? LIMIT 1');
            $lookup->execute([$streamerId, $platform]);
            $platformId = $lookup->fetchColumn();
            $values = [
                (string)($data['username'] ?? ''),
                (string)($data['display_name'] ?? ''),
                (string)($data['channel_id'] ?? ''),
                (string)($data['avatar_url'] ?? ''),
                (string)($data['channel_url'] ?? '')
            ];

            if ($platformId !== false) {
                $update = $pdo->prepare('UPDATE streamer_platforms SET username=?,display_name=?,channel_id=?,avatar_url=?,channel_url=?,is_primary=1,active=1,enabled=1,last_sync=NOW() WHERE id=?');
                $update->execute(array_merge($values, [(int)$platformId]));
            } else {
                $insert = $pdo->prepare('INSERT INTO streamer_platforms(streamer_id,platform,username,display_name,channel_id,avatar_url,channel_url,is_primary,active,enabled,last_sync) VALUES(?,?,?,?,?,?,?,1,1,1,NOW())');
                $insert->execute(array_merge([$streamerId, $platform], $values));
            }

            $channelLookup = $pdo->prepare('SELECT id FROM streamer_channels WHERE streamer_id=? AND platform=? LIMIT 1');
            $channelLookup->execute([$streamerId, $platform]);
            $channelId = $channelLookup->fetchColumn();
            $channelValues = [
                (string)($data['channel_url'] ?? ''),
                (string)($data['username'] ?? ''),
                (string)($data['channel_id'] ?? ''),
                (string)($data['display_name'] ?? '')
            ];

            if ($channelId !== false) {
                $channelUpdate = $pdo->prepare('UPDATE streamer_channels SET channel_url=?,channel_login=?,external_id=?,display_name=?,active=1,last_synced_at=NOW(),last_error=NULL WHERE id=?');
                $channelUpdate->execute(array_merge($channelValues, [(int)$channelId]));
            } else {
                $channelInsert = $pdo->prepare('INSERT INTO streamer_channels(streamer_id,platform,channel_url,channel_login,external_id,display_name,active,last_synced_at,last_error) VALUES(?,?,?,?,?,?,1,NOW(),NULL)');
                $channelInsert->execute(array_merge([$streamerId, $platform], $channelValues));
            }
        }

        public function storeChannelError(PDO $pdo, int $streamerId, string $platform, string $input, string $error): void
        {
            $platform = strtolower(trim($platform));
            if ($streamerId <= 0 || $platform === '') {
                return;
            }

            $lookup = $pdo->prepare('SELECT id FROM streamer_channels WHERE streamer_id=? AND platform=? LIMIT 1');
            $lookup->execute([$streamerId, $platform]);
            $channelId = $lookup->fetchColumn();
            $message = substr(trim($error), 0, 1000);

            if ($channelId !== false) {
                $update = $pdo->prepare('UPDATE streamer_channels SET channel_login=?,last_error=? WHERE id=?');
                $update->execute([$input, $message, (int)$channelId]);
                return;
            }

            $insert = $pdo->prepare('INSERT INTO streamer_channels(streamer_id,platform,channel_url,channel_login,active,last_error) VALUES(?,?,?,?,1,?)');
            $insert->execute([$streamerId, $platform, $input, $input, $message]);
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

<?php

require_once __DIR__.'/../PlatformAdapterInterface.php';

class YoutubeAdapter implements PlatformAdapterInterface
{
    private $client;
    private $config;

    public function __construct($client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function resolve(string $identifier): array
    {
        $this->assertConfigured();

        $identifier = $this->normalizeIdentifier($identifier);
        if ($identifier === '') {
            throw new RuntimeException('O identificador do YouTube não pode ficar vazio.');
        }

        $params = [
            'part' => 'snippet',
            'key' => $this->config['api_key']
        ];
        if (strpos($identifier, 'UC') === 0) {
            $params['id'] = $identifier;
        } elseif (strpos($identifier, '@') === 0) {
            $params['forHandle'] = $identifier;
        } else {
            $params['forUsername'] = $identifier;
        }

        $url = rtrim($this->config['api_url'], '/').'/channels?'.http_build_query($params);
        $data = $this->client->getJson($url);
        $channel = $data['items'][0] ?? null;

        if (!is_array($channel) && isset($params['forUsername'])) {
            unset($params['forUsername']);
            $params['forHandle'] = '@'.$identifier;
            $handleUrl = rtrim($this->config['api_url'], '/').'/channels?'.http_build_query($params);
            $handleData = $this->client->getJson($handleUrl);
            $channel = $handleData['items'][0] ?? null;
        }

        if (!is_array($channel) && isset($params['forHandle'])) {
            unset($params['forHandle']);
            $params['q'] = ltrim($identifier, '@');
            $params['type'] = 'channel';
            $params['maxResults'] = 1;
            $searchUrl = rtrim($this->config['api_url'], '/').'/search?'.http_build_query($params);
            $search = $this->client->getJson($searchUrl);
            $searchItem = $search['items'][0] ?? null;
            $channelId = $searchItem['id']['channelId'] ?? '';
            if ($channelId !== '') {
                $channelData = $this->client->getJson(rtrim($this->config['api_url'], '/').'/channels?'.http_build_query([
                    'part' => 'snippet',
                    'id' => $channelId,
                    'key' => $this->config['api_key']
                ]));
                $channel = $channelData['items'][0] ?? null;
            }
        }

        if (!is_array($channel)) {
            throw new RuntimeException('Canal YouTube não encontrado.');
        }

        $snippet = is_array($channel['snippet'] ?? null) ? $channel['snippet'] : [];
        $thumbnails = is_array($snippet['thumbnails'] ?? null) ? $snippet['thumbnails'] : [];
        $thumbnail = $thumbnails['high'] ?? ($thumbnails['medium'] ?? ($thumbnails['default'] ?? []));
        $channelId = (string)($channel['id'] ?? '');
        $displayName = (string)($snippet['title'] ?? $identifier);
        $customUrl = (string)($snippet['customUrl'] ?? '');
        $username = $customUrl !== '' ? ltrim($customUrl, '@') : ltrim($identifier, '@');

        if ($channelId === '') {
            throw new RuntimeException('A resposta do YouTube não contém o ID do canal.');
        }

        return [
            'platform' => 'youtube',
            'username' => $username,
            'display_name' => $displayName,
            'channel_id' => $channelId,
            'avatar_url' => (string)($thumbnail['url'] ?? ''),
            'channel_url' => 'https://www.youtube.com/channel/'.rawurlencode($channelId)
        ];
    }

    private function normalizeIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return '';
        }

        $url = $identifier;
        if (strpos($url, '://') === false && strpos($url, '/') !== false) {
            $url = 'https://'.$url;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $identifier;
        }

        $segments = explode('/', trim((string)parse_url($url, PHP_URL_PATH), '/'));
        if (($segments[0] ?? '') === 'channel' && isset($segments[1])) {
            return $segments[1];
        }
        if (($segments[0] ?? '') === 'user' && isset($segments[1])) {
            return $segments[1];
        }
        if (($segments[0] ?? '') === 'c' && isset($segments[1])) {
            return $segments[1];
        }
        if (isset($segments[0]) && $segments[0] !== '') {
            return $segments[0];
        }

        throw new RuntimeException('URL do YouTube inválida.');
    }

    private function assertConfigured(): void
    {
        if (trim((string)($this->config['api_key'] ?? '')) === '') {
            throw new RuntimeException('A chave da API do YouTube não está configurada.');
        }
        if (trim((string)($this->config['api_url'] ?? '')) === '') {
            throw new RuntimeException('O endpoint da API do YouTube não está configurado.');
        }
    }
}

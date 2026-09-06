<?php

require_once __DIR__.'/../PlatformAdapterInterface.php';

class TikTokAdapter implements PlatformAdapterInterface
{
    private $client;
    private $config;

    public function __construct($client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function resolve(string $username): array
    {
        $username = trim($username);
        if ($username === '') {
            throw new RuntimeException('O login da TikTok não pode ficar vazio.');
        }

        $template = trim((string)($this->config['profile_url'] ?? ''));
        if ($template === '') {
            throw new RuntimeException('A integração da TikTok não está configurada.');
        }

        $url = str_replace('{username}', rawurlencode($username), $template);
        $data = $this->client->getJson($url);
        $profile = is_array($data['data'] ?? null) ? $data['data'] : $data;
        if (!is_array($profile)) {
            throw new RuntimeException('Perfil TikTok não encontrado.');
        }

        $user = is_array($profile['user'] ?? null) ? $profile['user'] : $profile;
        $login = (string)($user['unique_id'] ?? ($user['uniqueId'] ?? $username));
        $displayName = (string)($user['nickname'] ?? ($user['display_name'] ?? $login));
        $channelId = (string)($user['uid'] ?? ($user['id'] ?? ''));
        $avatar = (string)($user['avatar_larger'] ?? ($user['avatar_url'] ?? ($user['avatar'] ?? '')));

        return [
            'platform' => 'tiktok',
            'username' => $login,
            'display_name' => $displayName,
            'channel_id' => $channelId,
            'avatar_url' => $avatar,
            'channel_url' => 'https://www.tiktok.com/@'.rawurlencode(ltrim($login, '@'))
        ];
    }
}

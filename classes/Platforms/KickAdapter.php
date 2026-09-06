<?php

require_once __DIR__.'/../PlatformAdapterInterface.php';

class KickAdapter implements PlatformAdapterInterface
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
            throw new RuntimeException('O login da Kick não pode ficar vazio.');
        }

        $template = trim((string)($this->config['channel_url'] ?? ''));
        if ($template === '') {
            throw new RuntimeException('O endpoint da Kick não está configurado.');
        }

        $url = str_replace('{username}', rawurlencode($username), $template);
        $data = $this->client->getJson($url);
        $channel = is_array($data['data'] ?? null) ? $data['data'] : $data;
        if (!is_array($channel)) {
            throw new RuntimeException('Canal Kick não encontrado.');
        }

        $user = is_array($channel['user'] ?? null) ? $channel['user'] : [];
        $login = (string)($channel['slug'] ?? ($channel['username'] ?? ($user['username'] ?? $username)));
        $displayName = (string)($channel['display_name'] ?? ($user['username'] ?? $login));
        $channelId = (string)($channel['id'] ?? ($channel['user_id'] ?? ''));
        $avatar = (string)($channel['avatar_url'] ?? ($user['profile_pic'] ?? ''));

        return [
            'platform' => 'kick',
            'username' => $login,
            'display_name' => $displayName,
            'channel_id' => $channelId,
            'avatar_url' => $avatar,
            'channel_url' => 'https://kick.com/'.rawurlencode($login)
        ];
    }
}

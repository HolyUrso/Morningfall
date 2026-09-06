<?php

require_once __DIR__.'/../PlatformAdapterInterface.php';

class TwitchAdapter implements PlatformAdapterInterface
{
    private $client;
    private $config;
    private $accessToken;

    public function __construct($client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function resolve(string $username): array
    {
        $this->assertConfigured();
        $username = trim($username);
        if ($username === '') {
            throw new RuntimeException('O login da Twitch não pode ficar vazio.');
        }

        if ($this->accessToken === null) {
            $token = $this->client->postForm($this->config['token_url'], [
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'grant_type' => 'client_credentials'
            ]);
            $this->accessToken = (string)($token['access_token'] ?? '');
            if ($this->accessToken === '') {
                throw new RuntimeException('A Twitch não retornou um token de acesso.');
            }
        }

        $data = $this->client->getJson(
            $this->config['users_url'].'?'.http_build_query(['login' => $username]),
            [
                'Client-ID: '.$this->config['client_id'],
                'Authorization: Bearer '.$this->accessToken
            ]
        );
        $user = $data['data'][0] ?? null;
        if (!is_array($user)) {
            throw new RuntimeException('Canal Twitch não encontrado.');
        }

        $login = (string)($user['login'] ?? $username);
        return [
            'platform' => 'twitch',
            'username' => $login,
            'display_name' => (string)($user['display_name'] ?? $login),
            'channel_id' => (string)($user['id'] ?? ''),
            'avatar_url' => (string)($user['profile_image_url'] ?? ''),
            'channel_url' => 'https://www.twitch.tv/'.rawurlencode($login)
        ];
    }

    private function assertConfigured(): void
    {
        foreach (['client_id', 'client_secret', 'token_url', 'users_url'] as $key) {
            if (trim((string)($this->config[$key] ?? '')) === '') {
                throw new RuntimeException('A configuração da Twitch está incompleta.');
            }
        }
    }
}

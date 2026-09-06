<?php

return [
    'timeout' => 12,
    'connect_timeout' => 6,
    'user_agent' => 'Morningfall Creators/1.0',
    'platforms' => [
        'twitch' => [
            'enabled' => true,
            'client_id' => '',
            'client_secret' => '',
            'token_url' => 'https://id.twitch.tv/oauth2/token',
            'users_url' => 'https://api.twitch.tv/helix/users'
        ],
        'youtube' => [
            'enabled' => true,
            'api_key' => '',
            'api_url' => 'https://www.googleapis.com/youtube/v3'
        ],
        'kick' => [
            'enabled' => true,
            'channel_url' => 'https://kick.com/api/v2/channels/{username}'
        ],
        'tiktok' => [
            'enabled' => true,
            'profile_url' => ''
        ]
    ]
];

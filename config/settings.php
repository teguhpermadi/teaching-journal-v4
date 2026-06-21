<?php

return [

    'repositories' => [
        'database' => [
            'type' => Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository::class,
            'table' => 'settings',
            'connection' => null,
        ],
    ],

    'auto_create_settings' => true,

    'cached' => env('SETTINGS_CACHED', false),

    'cache_ttl' => 300,

];

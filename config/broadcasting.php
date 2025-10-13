<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | Esta opção controla o broadcaster padrão que será usado pelo framework
    | quando um evento precisar ser transmitido. Podes definir no .env:
    | BROADCAST_DRIVER=pusher
    |
    */

    'default' => env('BROADCAST_DRIVER', 'pusher'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Aqui defines todas as conexões de broadcast que a tua aplicação suporta.
    | No teu caso, apenas Pusher é necessário.
    |
    */

    'connections' => [

        'pusher' => [
            'driver'  => 'pusher',
            'key'     => env('PUSHER_APP_KEY'),
            'secret'  => env('PUSHER_APP_SECRET'),
            'app_id'  => env('PUSHER_APP_ID'),
            'options' => [
                'cluster'   => env('PUSHER_APP_CLUSTER'),
                'useTLS'    => true,
                'namespace' => null,
            ],
        ],

    ],

];

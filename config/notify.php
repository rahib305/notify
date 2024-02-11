<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Receiver Guard
    |--------------------------------------------------------------------------
    |
    | Set all the guards in this receivers
    |
    */
    'receivers'=>[
        'user'=>App\Models\User::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Receiver Guard
    |--------------------------------------------------------------------------
    |
    | This option is to set the config of used channels
    |
    */
    'channels' => [

        'mail'=>[
            // Mail template header title
            'title'=>'ByteBlitz',
            // Mail template header logo
            'logo'=>'',
            'primary_clr'=>'red'
        ],

        'fcm'=> [
            'server_key'=>env('FCM_SERVER_KEY')
        ],

         //ultramsg whatsapp api
        'whatsapp'=> [
            'ultramsg'=>[
                'instance_id'=> env('ULTRAMSG_INSTANCE_ID'),
                'token'=> env('ULTRAMSG_TOKEN'),
            ],
            'attachments_allowed'=>[
                'documents'=>['pdf'],
                'images'=>['png', 'jpg']
            ]
        ],

        'sms'=> [
            'twilio'=>[
                'sid'=>env('TWILIO_SID'),
                'auth_token'=>env('TWILIO_AUTH_TOKEN'),
                'from'=>env('TWILIO_FROM_NUMBER')
            ]
        ]
    ]
];

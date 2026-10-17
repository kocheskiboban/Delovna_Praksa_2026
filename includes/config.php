<?php

declare(strict_types=1);

return [
    'db' => [
        'host'    => getenv('OS_DB_HOST') ?: '127.0.0.1',
        'port'    => (int) (getenv('OS_DB_PORT') ?: 3306),
        'name'    => getenv('OS_DB_NAME') ?: 'ohrid_summer',
        'user'    => getenv('OS_DB_USER') ?: 'root',
        'pass'    => getenv('OS_DB_PASS') !== false ? (string) getenv('OS_DB_PASS') : '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'env'               => getenv('OS_ENV') ?: 'production',
        'base_url'          => getenv('OS_BASE_URL') ?: null,
        'timezone'          => 'Europe/Skopje',
        'default_lang'      => 'mk',
        'currency'          => 'MKD',
        'service_fee'       => 50,     
        'hold_minutes'      => 15,    
        'reservation_hours' => 72,    
        'transfer_days'     => 5,      
        'max_tickets'       => 10,     
        'per_page'          => 9,
    ],
    'festival' => [
        'phone'       => '+389 46 000 261',
        'email'       => 'info@ohridsummer.test',
        'tickets'     => 'tickets@ohridsummer.test',
        'press'       => 'press@ohridsummer.test',
        'address_mk'  => 'ул. Македонски просветители бб, 6000 Охрид, Северна Македонија',
        'address_en'  => 'Makedonski Prosvetiteli St., 6000 Ohrid, North Macedonia',
        'lat'         => 41.11712,
        'lng'         => 20.80168,
        'iban'        => 'MK07 2000 0000 0000 000 (DEMO)',
        'social'      => [
            'facebook'  => 'https://www.facebook.com/',
            'instagram' => 'https://www.instagram.com/',
            'youtube'   => 'https://www.youtube.com/',
            'x'         => 'https://x.com/',
        ],
    ],
];

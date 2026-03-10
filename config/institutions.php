<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mapeo de Instituciones a Bases de Datos (Tenant)
    |--------------------------------------------------------------------------
    | IMPORTANTE: Los números (keys) del lado izquierdo (1, 2, 3...) deben 
    | coincidir EXACTAMENTE con el 'institution_id' que manda tu frontend 
    | en cada petición a la API. Ajustalos según tu modelo de negocio.
    */

    1 => [
        'name'     => 'General',
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'geoacade_general'),
        'username' => env('DB_USERNAME', 'geoacade_general'),
        'password' => env('DB_PASSWORD', ''),
    ],

    2 => [
        'name'     => 'Family / El Gral',
        'host'     => env('DB_HOST_FAMILY', '127.0.0.1'),
        'port'     => env('DB_PORT_FAMILY', '3306'),
        'database' => env('DB_DATABASE_FAMILY', 'dmendoza_pr_el_gral'),
        'username' => env('DB_USERNAME_FAMILY', 'dmendoza_gral'),
        'password' => env('DB_PASSWORD_FAMILY', ''),
    ],

    3 => [
        'name'     => 'Second / MDPDS',
        'host'     => env('DB_HOST_SECOND', '127.0.0.1'),
        'port'     => env('DB_PORT_SECOND', '3306'),
        'database' => env('DB_DATABASE_SECOND', 'geodnl_mdpds'),
        'username' => env('DB_USERNAME_SECOND', 'geodnl_mdpds'),
        'password' => env('DB_PASSWORD_SECOND', ''),
    ],

    4 => [
        'name'     => 'Third / Demo',
        'host'     => env('DB_HOST_THIRD', '127.0.0.1'),
        'port'     => env('DB_PORT_THIRD', '3306'),
        'database' => env('DB_DATABASE_THIRD', 'dmendoza_pesge_demo'),
        'username' => env('DB_USERNAME_THIRD', 'dmendoza_demo'),
        'password' => env('DB_PASSWORD_THIRD', ''),
    ],

    5 => [
        'name'     => 'Fourth / Sarmiento',
        'host'     => env('DB_HOST_FOURTH', '127.0.0.1'),
        'port'     => env('DB_PORT_FOURTH', '3306'),
        'database' => env('DB_DATABASE_FOURTH', 'dmendoza_pesge_sarmiento'),
        'username' => env('DB_USERNAME_FOURTH', 'dmendoza_sar'),
        'password' => env('DB_PASSWORD_FOURTH', ''),
    ],

    6 => [
        'name'     => 'Fifth / Esquiu',
        'host'     => env('DB_HOST_FIFTH', '127.0.0.1'),
        'port'     => env('DB_PORT_FIFTH', '3306'),
        'database' => env('DB_DATABASE_FIFTH', 'dmendoza_pesge_esqui'),
        'username' => env('DB_USERNAME_FIFTH', 'dmendoza_esquiu'),
        'password' => env('DB_PASSWORD_FIFTH', ''),
    ],

    7 => [
        'name'     => 'Sixth / San Martin',
        'host'     => env('DB_HOST_SIXTH', '127.0.0.1'),
        'port'     => env('DB_PORT_SIXTH', '3306'),
        'database' => env('DB_DATABASE_SIXTH', 'dmendoza_pesge_sanm'),
        'username' => env('DB_USERNAME_SIXTH', 'dmendoza_sanmc'),
        'password' => env('DB_PASSWORD_SIXTH', ''),
    ],

    8 => [
        'name'     => 'Seventh / IQuilmes',
        'host'     => env('DB_HOST_SEVENTH', '127.0.0.1'),
        'port'     => env('DB_PORT_SEVENTH', '3306'),
        'database' => env('DB_DATABASE_SEVENTH', 'geodnl_iquilmes'),
        'username' => env('DB_USERNAME_SEVENTH', 'geodnl_iquilmes'),
        'password' => env('DB_PASSWORD_SEVENTH', ''),
    ],

    9 => [
        'name'     => 'Eighth / Batan',
        'host'     => env('DB_HOST_EIGHTH', '127.0.0.1'),
        'port'     => env('DB_PORT_EIGHTH', '3306'),
        'database' => env('DB_DATABASE_EIGHTH', 'geodnl_batan'),
        'username' => env('DB_USERNAME_EIGHTH', 'geodnl_batan'),
        'password' => env('DB_PASSWORD_EIGHTH', ''),
    ],

    10 => [
        'name'     => 'Nineth / Sancamdp',
        'host'     => env('DB_HOST_NINETH', '127.0.0.1'),
        'port'     => env('DB_PORT_NINETH', '3306'),
        'database' => env('DB_DATABASE_NINETH', 'geodnl_sancamdp'),
        'username' => env('DB_USERNAME_NINETH', 'geodnl_sancamdp'),
        'password' => env('DB_PASSWORD_NINETH', ''),
    ],

    11 => [
        'name'     => 'Ten / SQulimes',
        'host'     => env('DB_HOST_TEN', '127.0.0.1'),
        'port'     => env('DB_PORT_TEN', '3306'),
        'database' => env('DB_DATABASE_TEN', 'geodnl_squilmes'),
        'username' => env('DB_USERNAME_TEN', 'geodnl_squilmes'),
        'password' => env('DB_PASSWORD_TEN', ''),
    ],

    12 => [
        'name'     => 'Eleven / Palladio',
        'host'     => env('DB_HOST_ELEVEN', '127.0.0.1'),
        'port'     => env('DB_PORT_ELEVEN', '3306'),
        'database' => env('DB_DATABASE_ELEVEN', 'geodnl_palladio'),
        'username' => env('DB_USERNAME_ELEVEN', 'geodnl_palladio'),
        'password' => env('DB_PASSWORD_ELEVEN', ''),
    ],

    13 => [
        'name'     => 'Twelve / Geoaca',
        'host'     => env('DB_HOST_TWELVE', '127.0.0.1'),
        'port'     => env('DB_PORT_TWELVE', '3306'),
        'database' => env('DB_DATABASE_TWELVE', 'geodnl_geoaca'),
        'username' => env('DB_USERNAME_TWELVE', 'geodnl_geoaca'),
        'password' => env('DB_PASSWORD_TWELVE', ''),
    ],

    14 => [
        'name'     => 'Thirteen / Sagrada',
        'host'     => env('DB_HOST_THIRTEEN', '127.0.0.1'),
        'port'     => env('DB_PORT_THIRTEEN', '3306'),
        'database' => env('DB_DATABASE_THIRTEEN', 'geodnl_sagrada'),
        'username' => env('DB_USERNAME_THIRTEEN', 'geodnl_sagrada'),
        'password' => env('DB_PASSWORD_THIRTEEN', ''),
    ],

    15 => [
        'name'     => 'Fourteen / Juvenilia',
        'host'     => env('DB_HOST_FOURTEEN', '127.0.0.1'),
        'port'     => env('DB_PORT_FOURTEEN', '3306'),
        'database' => env('DB_DATABASE_FOURTEEN', 'geodnl_juvenilia'),
        'username' => env('DB_USERNAME_FOURTEEN', 'geodnl_juvenilia'),
        'password' => env('DB_PASSWORD_FOURTEEN', ''),
    ],

    16 => [
        'name'     => 'Fifteen / Euteo',
        'host'     => env('DB_HOST_FIFTEEN', '127.0.0.1'),
        'port'     => env('DB_PORT_FIFTEEN', '3306'),
        'database' => env('DB_DATABASE_FIFTEEN', 'geodnl_euteo'),
        'username' => env('DB_USERNAME_FIFTEEN', 'geodnl_euteo'),
        'password' => env('DB_PASSWORD_FIFTEEN', ''),
    ],

    17 => [
        'name'     => 'Sixteen / Credit',
        'host'     => env('DB_HOST_SIXTEEN', '127.0.0.1'),
        'port'     => env('DB_PORT_SIXTEEN', '3306'),
        'database' => env('DB_DATABASE_SIXTEEN', 'geodnl_credit'),
        'username' => env('DB_USERNAME_SIXTEEN', 'geodnl_credit'),
        'password' => env('DB_PASSWORD_SIXTEEN', ''),
    ],

    18 => [
        'name'     => 'Hepteen / Bottger',
        'host'     => env('DB_HOST_HEPTEEN', '127.0.0.1'),
        'port'     => env('DB_PORT_HEPTEEN', '3306'),
        'database' => env('DB_DATABASE_HEPTEEN', 'geodnl_bottger'),
        'username' => env('DB_USERNAME_HEPTEEN', 'geodnl_bottger'),
        'password' => env('DB_PASSWORD_HEPTEEN', ''),
    ],

    19 => [
        'name'     => 'Octeen / Bristol',
        'host'     => env('DB_HOST_OCTEEN', '127.0.0.1'),
        'port'     => env('DB_PORT_OCTTEEN', '3306'), // Respetando el typo de tu .env original
        'database' => env('DB_DATABASE_OCTEEN', 'geodnl_bristol'),
        'username' => env('DB_USERNAME_OCTEEN', 'geodnl_bristol'),
        'password' => env('DB_PASSWORD_OCTEEN', ''),
    ],

    20 => [
        'name'     => 'Ninteen / Loberia',
        'host'     => env('DB_HOST_NINTEEN', '127.0.0.1'),
        'port'     => env('DB_PORT_NINTEEN', '3306'),
        'database' => env('DB_DATABASE_NINTEEN', 'geodnl_loberia'),
        'username' => env('DB_USERNAME_NINTEEN', 'geodnl_loberia'),
        'password' => env('DB_PASSWORD_NINTEEN', ''),
    ],

    21 => [
        'name'     => 'Twenty / Loberia2',
        'host'     => env('DB_HOST_TWENTY', '127.0.0.1'),
        'port'     => env('DB_PORT_TWENTY', '3306'),
        'database' => env('DB_DATABASE_TWENTY', 'geodnl_loberia2'),
        'username' => env('DB_USERNAME_TWENTY', 'geodnl_loberia'),
        'password' => env('DB_PASSWORD_TWENTY', ''),
    ],

    22 => [
        'name'     => 'TwentyOne / Loberia3',
        'host'     => env('DB_HOST_TWENTYONE', '127.0.0.1'),
        'port'     => env('DB_PORT_TWENTYONE', '3306'),
        'database' => env('DB_DATABASE_TWENTYONE', 'geodnl_loberia3'),
        'username' => env('DB_USERNAME_TWENTYONE', 'geodnl_loberia'),
        'password' => env('DB_PASSWORD_TWENTYONE', ''),
    ],

    23 => [
        'name'     => 'TwentyTwo / Munay',
        'host'     => env('DB_HOST_TWENTYTWO', '127.0.0.1'),
        'port'     => env('DB_PORT_TWENTYTWO', '3306'),
        'database' => env('DB_DATABASE_TWENTYTWO', 'geodnl_munay'),
        'username' => env('DB_USERNAME_TWENTYTWO', 'geodnl_munay'),
        'password' => env('DB_PASSWORD_TWENTYTWO', ''),
    ],

    24 => [
        'name'     => 'TwentyTree / Bambi',
        'host'     => env('DB_HOST_TWENTYTREE', '127.0.0.1'),
        'port'     => env('DB_PORT_TWENTYTREE', '3306'), // Respetando el typo "Tree"
        'database' => env('DB_DATABASE_TWENTYTREE', 'geodnl_bambi'),
        'username' => env('DB_USERNAME_TWENTYTREE', 'geodnl_bambi'),
        'password' => env('DB_PASSWORD_TWENTYTREE', ''),
    ],

    25 => [
        'name'     => 'TwentyFour / Arlt',
        'host'     => env('DB_HOST_TWENTYFOUR', '127.0.0.1'),
        'port'     => env('DB_PORT_TWENTYFOUR', '3306'),
        'database' => env('DB_DATABASE_TWENTYFOUR', 'geodnl_arlt'),
        'username' => env('DB_USERNAME_TWENTYFOUR', 'geodnl_arlt'),
        'password' => env('DB_PASSWORD_TWENTYFOUR', ''),
    ],

    26 => [
        'name'     => 'TwentyFive / Huinco',
        'host'     => env('DB_HOST_TWENTYFIVE', '127.0.0.1'),
        'port'     => env('DB_PORT_TWENTYFIVE', '3306'),
        'database' => env('DB_DATABASE_TWENTYFIVE', 'geodnl_huinco'),
        'username' => env('DB_USERNAME_TWENTYFIVE', 'geodnl_huinco'),
        'password' => env('DB_PASSWORD_TWENTYFIVE', ''),
    ],

    27 => [
        'name'     => 'Twenty Six / Trinity',
        'host'     => env('DB_HOST_TWENTY_SIX', '127.0.0.1'),
        'port'     => env('DB_PORT_TWENTY_SIX', '3306'),
        'database' => env('DB_DATABASE_TWENTY_SIX', 'geodnl_trinity'),
        'username' => env('DB_USERNAME_TWENTY_SIX', 'geodnl_trinity'),
        'password' => env('DB_PASSWORD_TWENTY_SIX', ''),
    ],

    28 => [
        'name'     => 'Twenty Eight / Hoy',
        'host'     => env('DB_HOST_TWENTY__EIGHT', '127.0.0.1'), // Respetando doble guión bajo de tu .env
        'port'     => env('DB_PORT_TWENTY__EIGHT', '3306'),
        'database' => env('DB_DATABASE_TWENTY_EIGHT', 'geodnl_hoy'),
        'username' => env('DB_USERNAME_TWENTY_EIGHT', 'geodnl_hoy'),
        'password' => env('DB_PASSWORD_TWENTY_EIGHT', ''),
    ],

    29 => [
        'name'     => 'TwentyNine / S. Antonio',
        'host'     => env('DB_HOST_TWENTYNINE', '127.0.0.1'),
        'port'     => env('DB_PORT_TWENTYNINE', '3306'),
        'database' => env('DB_DATABASE_TWENTYNINE', 'geodnl_snantonio'),
        'username' => env('DB_USERNAME_TWENTYNINE', 'geodnl_snantonio'),
        'password' => env('DB_PASSWORD_TWENTYNINE', ''),
    ],

    30 => [
        'name'     => 'Treinta / Leloir',
        'host'     => env('DB_HOST_TREINTA', '127.0.0.1'),
        'port'     => env('DB_PORT_TREINTA', '3306'),
        'database' => env('DB_DATABASE_TREINTA', 'geodnl_leloir'),
        'username' => env('DB_USERNAME_TREINTA', 'geodnl_leloir'),
        'password' => env('DB_PASSWORD_TREINTA', ''),
    ],

    31 => [
        'name'     => 'Treinta y uno / Gardner',
        'host'     => env('DB_HOST_TREINTAUNO', '127.0.0.1'),
        'port'     => env('DB_PORT_TREINTAUNO', '3306'),
        'database' => env('DB_DATABASE_TREINTAUNO', 'geodnl_gardner'),
        'username' => env('DB_USERNAME_TREINTAUNO', 'geodnl_gardner'),
        'password' => env('DB_PASSWORD_TREINTAUNO', ''),
    ],

    32 => [
        'name'     => 'Treinta y dos / Jacaranda',
        'host'     => env('DB_HOST_TREINTADOS', '127.0.0.1'),
        'port'     => env('DB_PORT_TREINTADOS', '3306'),
        'database' => env('DB_DATABASE_TREINTADOS', 'geodnl_jacaranda'),
        'username' => env('DB_USERNAME_TREINTADOS', 'geodnl_jacaranda'),
        'password' => env('DB_PASSWORD_TREINTADOS', ''),
    ],

    33 => [
        'name'     => 'Treinta y tres / UAAIURP',
        'host'     => env('DB_HOST_TREINTATRES', '127.0.0.1'),
        'port'     => env('DB_PORT_TREINTATRES', '3306'),
        'database' => env('DB_DATABASE_TREINTATRES', 'geodnl_uaaiurp'),
        'username' => env('DB_USERNAME_TREINTATRES', 'geodnl_uaaiurp'),
        'password' => env('DB_PASSWORD_TREINTATRES', ''),
    ],

    34 => [
        'name'     => 'Treinta y cuatro / Palpos',
        'host'     => env('DB_HOST_TREINTAYCUATRO', '127.0.0.1'),
        'port'     => env('DB_PORT_TREINTAYCUATRO', '3306'),
        'database' => env('DB_DATABASE_TREINTAYCUATRO', 'geodnl_palpos'),
        'username' => env('DB_USERNAME_TREINTAYCUATRO', 'geodnl_palpos'),
        'password' => env('DB_PASSWORD_TREINTAYCUATRO', ''),
    ],

    35 => [
        'name'     => 'Treinta y cinco / IQUIUNLP',
        'host'     => env('DB_HOST_TREINTAYCINCO', '127.0.0.1'),
        'port'     => env('DB_PORT_TREINTAYCINCO', '3306'),
        'database' => env('DB_DATABASE_TREINTAYCINCO', 'geodnl_iquiunlp'),
        'username' => env('DB_USERNAME_TREINTAYCINCO', 'geodnl_iquiunlp'),
        'password' => env('DB_PASSWORD_TREINTAYCINCO', ''),
    ],

    36 => [
        'name'     => 'Treinta y seis / Las Lomas',
        'host'     => env('DB_HOST_TREINTAYSEIS', '127.0.0.1'),
        'port'     => env('DB_PORT_TREINTAYSEIS', '3306'),
        'database' => env('DB_DATABASE_TREINTAYSEIS', 'geodnl_laslomas'),
        'username' => env('DB_USERNAME_TREINTAYSEIS', 'geodnl_laslomas'),
        'password' => env('DB_PASSWORD_TREINTAYSEIS', ''),
    ],

    37 => [
        'name'     => 'Treinta y siete / Figaro',
        'host'     => env('DB_HOST_TREINTAYSIETE', '127.0.0.1'),
        'port'     => env('DB_PORT_TREINTAYSIETE', '3306'),
        'database' => env('DB_DATABASE_TREINTAYSIETE', 'geodnl_figaro'),
        'username' => env('DB_USERNAME_TREINTAYSIETE', 'geodnl_figaro'),
        'password' => env('DB_PASSWORD_TREINTAYSIETE', ''),
    ],

    38 => [
        'name'     => 'Treinta y ocho / Cobrero',
        'host'     => env('DB_HOST_TREINTAYOCHO', '127.0.0.1'),
        'port'     => env('DB_PORT_TREINTAYOCHO', '3306'),
        'database' => env('DB_DATABASE_TREINTAYOCHO', 'geodnl_cobrero'),
        'username' => env('DB_USERNAME_TREINTAYOCHO', 'geodnl_cobrero'),
        'password' => env('DB_PASSWORD_TREINTAYOCHO', ''),
    ],

    39 => [
        'name'     => 'Treinta y nueve / Cervantes',
        'host'     => env('DB_HOST_TREINTAYNUEVE', '127.0.0.1'),
        'port'     => env('DB_PORT_TREINTAYNUEVE', '3306'),
        'database' => env('DB_DATABASE_TREINTAYNUEVE', 'geodnl_cervantes'),
        'username' => env('DB_USERNAME_TREINTAYNUEVE', 'geodnl_cervantes'),
        'password' => env('DB_PASSWORD_TREINTAYNUEVE', ''),
    ],

    40 => [
        'name'     => 'Cuarenta / FAPASADM',
        'host'     => env('DB_HOST_CUARENTA', '127.0.0.1'),
        'port'     => env('DB_PORT_CUARENTA', '3306'),
        'database' => env('DB_DATABASE_CUARENTA', 'geodnl_fapasadm'),
        'username' => env('DB_USERNAME_CUARENTA', 'geodnl_fapasamd'),
        'password' => env('DB_PASSWORD_CUARENTA', ''),
    ],

    41 => [
        'name'     => 'Cuarenta y uno / S Cobrero',
        'host'     => env('DB_HOST_CUARENTAYUNO', '127.0.0.1'),
        'port'     => env('DB_PORT_CUARENTAYUNO', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYUNO', 'geodnl_scobrero'),
        'username' => env('DB_USERNAME_CUARENTAYUNO', 'geodnl_scobrero'),
        'password' => env('DB_PASSWORD_CUARENTAYUNO', ''),
    ],

    42 => [
        'name'     => 'Cuarenta y dos / Carmen',
        'host'     => env('DB_HOST_CUARENTAYDOS', '127.0.0.1'),
        'port'     => env('DB_PORT_CUARENTAYDOS', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYDOS', 'geodnl_carmen'),
        'username' => env('DB_USERNAME_CUARENTAYDOS', 'geodnl_carmen'),
        'password' => env('DB_PASSWORD_CUARENTAYDOS', ''),
    ],

    43 => [
        'name'     => 'Cuarenta y tres / Vocacion',
        'host'     => env('DB_HOST_CUARENTAYTRES', '127.0.0.1'),
        'port'     => env('DB_PORT_CUARENTAYTRES', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYTRES', 'geodnl_vocacion'),
        'username' => env('DB_USERNAME_CUARENTAYTRES', 'geodnl_vocacion'),
        'password' => env('DB_PASSWORD_CUARENTAYTRES', ''),
    ],

    44 => [
        'name'     => 'Cuarenta y cuatro / Coned',
        'host'     => env('DB_HOST_CUARENTAYCUATRO', '127.0.0.1'),
        'port'     => env('DB_PORT_CUARENTAYCUATRO', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYCUATRO', 'geodnl_coned'),
        'username' => env('DB_USERNAME_CUARENTAYCUATRO', 'geodnl_coned'),
        'password' => env('DB_PASSWORD_CUARENTAYCUATRO', ''),
    ],

    45 => [
        'name'     => 'Cuarenta y cinco / Codifica',
        'host'     => env('DB_HOST_CUARENTAYCINCO', '127.0.0.1'),
        'port'     => env('DB_PORT_CUARENTAYCINCO', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYCINCO', 'geodnl_codifica'),
        'username' => env('DB_USERNAME_CUARENTAYCINCO', 'geodnl_codifica'),
        'password' => env('DB_PASSWORD_CUARENTAYCINCO', ''),
    ],

    46 => [
        'name'     => 'Cuarenta y seis / Caracjuve',
        'host'     => env('DB_HOST_CUARENTAYSEIS', '127.0.0.1'),
        'port'     => env('DB_PORT_CUARENTAYSEIS', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYSEIS', 'geodnl_caracjuve'),
        'username' => env('DB_USERNAME_CUARENTAYSEIS', 'geodnl_caracjuve'),
        'password' => env('DB_PASSWORD_CUARENTAYSEIS', ''),
    ],

    47 => [
        'name'     => 'Cuarenta y siete / Wizard Secundario',
        'host'     => env('DB_HOST_CUARENTAYSIETE', '127.0.0.1'),
        'port'     => env('DB_PORT_CUARENTAYSIETE', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYSIETE', 'geodnl_wizardsecundario'),
        'username' => env('DB_USERNAME_CUARENTAYSIETE', 'geodnl_wizardsecundario'),
        'password' => env('DB_PASSWORD_CUARENTAYSIETE', ''),
    ],

    48 => [
        'name'     => 'Cuarenta y ocho / Wizard Sup',
        'host'     => env('DB_HOST_CUARENTAYOCHO', '127.0.0.1'),
        'port'     => env('DB_PORT_CUARENTAYOCHO', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYOCHO', 'geodnl_wizardsup'),
        'username' => env('DB_USERNAME_CUARENTAYOCHO', 'geodnl_wizardsup'),
        'password' => env('DB_PASSWORD_CUARENTAYOCHO', ''),
    ],

    49 => [
        'name'     => 'Cuarenta y nueve / Talleres',
        'host'     => env('DB_HOST_CUARENTAYNUEVE', '127.0.0.1'),
        'port'     => env('DB_PORT_CUARENTAYNUEVE', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYNUEVE', 'geodnl_talleres'),
        'username' => env('DB_USERNAME_CUARENTAYNUEVE', 'geodnl_huinco'), // Respetando tu username original de huinco acá
        'password' => env('DB_PASSWORD_CUARENTAYNUEVE', ''),
    ],
];
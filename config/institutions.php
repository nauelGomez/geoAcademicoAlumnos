<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mapeo de Instituciones a Bases de Datos (Tenant)
    |--------------------------------------------------------------------------
    | Las credenciales ahora se leen del .env por seguridad. 
    | Si no existe la variable, toma el valor por defecto hardcodeado.
    */

    2 => [
        'name'     => 'Instituto Sarmiento',
        'host'     => env('DB_HOST_FOURTH', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_FOURTH', '3306'),
        'database' => env('DB_DATABASE_FOURTH', 'dmendoza_pesge_sarmiento'),
        'username' => env('DB_USERNAME_FOURTH', 'dmendoza_sar'),
        'password' => env('DB_PASSWORD_FOURTH', 'Lolita2704'),
    ],

    3 => [
        'name'     => 'Escuela GEO',
        'host'     => env('DB_HOST_THIRD', '127.0.0.1'),
        'port'     => env('DB_PORT_THIRD', '3306'),
        'database' => env('DB_DATABASE_THIRD', 'dmendoza_pesge_demo'),
        'username' => env('DB_USERNAME_THIRD', 'root'),
        'password' => env('DB_PASSWORD_THIRD', ''), // El .env dice Lola2704, toma prioridad. Si no existe, usa .
    ],

    6 => [
        'name'     => 'Instituto Esquiu',
        'host'     => env('DB_HOST_FIFTH', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_FIFTH', '3306'),
        'database' => env('DB_DATABASE_FIFTH', 'dmendoza_pesge_esqui'),
        'username' => env('DB_USERNAME_FIFTH', 'dmendoza_esquiu'),
        'password' => env('DB_PASSWORD_FIFTH', 'RamiMarce2018'),
    ],

    7 => [
        'name'     => 'Colegio San Miguel Arcangel',
        'host'     => env('DB_HOST_SIXTH', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_SIXTH', '3306'),
        'database' => env('DB_DATABASE_SIXTH', 'dmendoza_pesge_sanm'),
        'username' => env('DB_USERNAME_SIXTH', 'dmendoza_sanmc'),
        'password' => env('DB_PASSWORD_SIXTH', 'StefyAle2019'),
    ],

    9 => [
        'name'     => 'Instituto San Nicolas de los Arroyos',
        'host'     => env('DB_HOST_TENANT_9', 'pesge.com.ar'),
        'port'     => env('DB_PORT_TENANT_9', '3306'),
        'database' => env('DB_DATABASE_TENANT_9', 'dmendoza_pesge_isna'),
        'username' => env('DB_USERNAME_TENANT_9', 'dmendoza_isna'),
        'password' => env('DB_PASSWORD_TENANT_9', 'PsDm2019'),
    ],

    11 => [
        'name'     => 'Mar del Plata Day School',
        'host'     => env('DB_HOST_SECOND', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_SECOND', '3306'),
        'database' => env('DB_DATABASE_SECOND', 'geodnl_mdpds'),
        'username' => env('DB_USERNAME_SECOND', 'geodnl_mdpds'),
        'password' => env('DB_PASSWORD_SECOND', 'VaniWan2022'),
    ],

    12 => [
        'name'     => 'Instituto Superior GEO',
        'host'     => env('DB_HOST_TWELVE', '127.0.0.1'),
        'port'     => env('DB_PORT_TWELVE', '3306'),
        'database' => env('DB_DATABASE_TWELVE', 'geodnl_geoaca'),
        'username' => env('DB_USERNAME_TWELVE', 'root'),
        'password' => env('DB_PASSWORD_TWELVE', ''),
    ],

    13 => [
        'name'     => 'Instituto Superior Quilmes',
        'host'     => env('DB_HOST_SEVENTH', '127.0.0.1'),
        'port'     => env('DB_PORT_SEVENTH', '3306'),
        'database' => env('DB_DATABASE_SEVENTH', 'geodnl_iquilmes'),
        'username' => env('DB_USERNAME_SEVENTH', 'root'),
        'password' => env('DB_PASSWORD_SEVENTH', ''),
    ],

    14 => [
        'name'     => 'Escuela Nuestra Sra. de Lujan',
        'host'     => env('DB_HOST_EIGHTH', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_EIGHTH', '3306'),
        'database' => env('DB_DATABASE_EIGHTH', 'geodnl_batan'),
        'username' => env('DB_USERNAME_EIGHTH', 'geodnl_batan'),
        'password' => env('DB_PASSWORD_EIGHTH', 'MarGab2023'),
    ],

    15 => [
        'name'     => 'Colegio San Cayetano',
        'host'     => env('DB_HOST_NINETH', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_NINETH', '3306'),
        'database' => env('DB_DATABASE_NINETH', 'geodnl_sancamdp'),
        'username' => env('DB_USERNAME_NINETH', 'geodnl_sancamdp'),
        'password' => env('DB_PASSWORD_NINETH', 'VirIara2023'),
    ],

    16 => [
        'name'     => 'Instituto Secundario Quilmes',
        'host'     => env('DB_HOST_TEN', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TEN', '3306'),
        'database' => env('DB_DATABASE_TEN', 'geodnl_squilmes'),
        'username' => env('DB_USERNAME_TEN', 'geodnl_squilmes'),
        'password' => env('DB_PASSWORD_TEN', 'MariGise2023'),
    ],

    17 => [
        'name'     => 'Instituto Andrea Palladio',
        'host'     => env('DB_HOST_ELEVEN', '127.0.0.1'),
        'port'     => env('DB_PORT_ELEVEN', '3306'),
        'database' => env('DB_DATABASE_ELEVEN', 'geodnl_palladio'),
        'username' => env('DB_USERNAME_ELEVEN', 'root'),
        'password' => env('DB_PASSWORD_ELEVEN', ''),
    ],

    18 => [
        'name'     => 'Instituto Superior Bristol',
        'host'     => env('DB_HOST_OCTEEN', '127.0.0.1'),
        'port'     => env('DB_PORT_OCTEEN', '3306'),
        'database' => env('DB_DATABASE_OCTEEN', 'geodnl_bristol'),
        'username' => env('DB_USERNAME_OCTEEN', 'root'),
        'password' => env('DB_PASSWORD_OCTEEN', ''),
    ],

    19 => [
        'name'     => 'Colegio Sagrada Familia',
        'host'     => env('DB_HOST_THIRTEEN', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_THIRTEEN', '3306'),
        'database' => env('DB_DATABASE_THIRTEEN', 'geodnl_sagrada'),
        'username' => env('DB_USERNAME_THIRTEEN', 'geodnl_sagrada'),
        'password' => env('DB_PASSWORD_THIRTEEN', 'SinLatech2024'),
    ],

    20 => [
        'name'     => 'Instituto Juvenilia',
        'host'     => env('DB_HOST_FOURTEEN', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_FOURTEEN', '3306'),
        'database' => env('DB_DATABASE_FOURTEEN', 'geodnl_juvenilia'),
        'username' => env('DB_USERNAME_FOURTEEN', 'geodnl_juvenilia'),
        'password' => env('DB_PASSWORD_FOURTEEN', 'MerceSanti2024'),
    ],

    21 => [
        'name'     => 'Escuela Universitaria de Teologia',
        'host'     => env('DB_HOST_FIFTEEN', '127.0.0.1'),
        'port'     => env('DB_PORT_FIFTEEN', '3306'),
        'database' => env('DB_DATABASE_FIFTEEN', 'geodnl_euteo'),
        'username' => env('DB_USERNAME_FIFTEEN', 'root'),
        'password' => env('DB_PASSWORD_FIFTEEN', ''),
    ],

    22 => [
        'name'     => 'Instituto CEDIT',
        'host'     => env('DB_HOST_SIXTEEN', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_SIXTEEN', '3306'),
        'database' => env('DB_DATABASE_SIXTEEN', 'geodnl_credit'),
        'username' => env('DB_USERNAME_SIXTEEN', 'geodnl_credit'),
        'password' => env('DB_PASSWORD_SIXTEEN', 'GiseRally2024'),
    ],

    23 => [
        'name'     => 'Instituto Anna Bottger',
        'host'     => env('DB_HOST_HEPTEEN', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_HEPTEEN', '3306'),
        'database' => env('DB_DATABASE_HEPTEEN', 'geodnl_bottger'),
        'username' => env('DB_USERNAME_HEPTEEN', 'geodnl_bottger'),
        'password' => env('DB_PASSWORD_HEPTEEN', 'GiseGesell2024'),
    ],

    24 => [
        'name'     => 'UAA-IURP',
        'host'     => env('DB_HOST_TREINTRES', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TREINTRES', '3306'),
        'database' => env('DB_DATABASE_TREINTRES', 'geodnl_uaaiurp'),
        'username' => env('DB_USERNAME_TREINTRES', 'geodnl_uaaiurp'),
        'password' => env('DB_PASSWORD_TREINTRES', 'MarianoSanti2024'),
    ],

    25 => [
        'name'     => 'JUSTA SILVIA MAYORA',
        'host'     => env('DB_HOST_NINTEEN', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_NINTEEN', '3306'),
        'database' => env('DB_DATABASE_NINTEEN', 'geodnl_loberia'),
        'username' => env('DB_USERNAME_NINTEEN', 'geodnl_loberia'),
        'password' => env('DB_PASSWORD_NINTEEN', 'Mariano2023'),
    ],

    26 => [
        'name'     => 'Calasancio Divina Pastora',
        'host'     => env('DB_HOST_TWENTY', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TWENTY', '3306'),
        'database' => env('DB_DATABASE_TWENTY', 'geodnl_loberia2'),
        'username' => env('DB_USERNAME_TWENTY', 'geodnl_loberia'),
        'password' => env('DB_PASSWORD_TWENTY', 'Mariano2023'),
    ],

    27 => [
        'name'     => 'Divina Pastora',
        'host'     => env('DB_HOST_TWENTYONE', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TWENTYONE', '3306'),
        'database' => env('DB_DATABASE_TWENTYONE', 'geodnl_loberia3'),
        'username' => env('DB_USERNAME_TWENTYONE', 'geodnl_loberia'),
        'password' => env('DB_PASSWORD_TWENTYONE', 'Mariano2023'),
    ],

    28 => [
        'name'     => 'Munay MDP',
        'host'     => env('DB_HOST_TWENTYTWO', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TWENTYTWO', '3306'),
        'database' => env('DB_DATABASE_TWENTYTWO', 'geodnl_munay'),
        'username' => env('DB_USERNAME_TWENTYTWO', 'geodnl_munay'),
        'password' => env('DB_PASSWORD_TWENTYTWO', 'Florenpatines2024'),
    ],

    29 => [
        'name'     => 'Casa Dei Bambini',
        'host'     => env('DB_HOST_TWENTYTREE', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TWENTYTREE', '3306'),
        'database' => env('DB_DATABASE_TWENTYTREE', 'geodnl_bambi'),
        'username' => env('DB_USERNAME_TWENTYTREE', 'geodnl_bambi'),
        'password' => env('DB_PASSWORD_TWENTYTREE', 'MarioGise2024'),
    ],

    30 => [
        'name'     => 'Instituto Roberto Arlt',
        'host'     => env('DB_HOST_TWENTYFOUR', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TWENTYFOUR', '3306'),
        'database' => env('DB_DATABASE_TWENTYFOUR', 'geodnl_arlt'),
        'username' => env('DB_USERNAME_TWENTYFOUR', 'geodnl_arlt'),
        'password' => env('DB_PASSWORD_TWENTYFOUR', 'ViajeconLluvia2024'),
    ],

    31 => [
        'name'     => 'Instituto Huinco',
        'host'     => env('DB_HOST_TWENTYFIVE', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TWENTYFIVE', '3306'),
        'database' => env('DB_DATABASE_TWENTYFIVE', 'geodnl_huinco'),
        'username' => env('DB_USERNAME_TWENTYFIVE', 'geodnl_huinco'),
        'password' => env('DB_PASSWORD_TWENTYFIVE', 'Navarra2024'),
    ],

    32 => [
        'name'     => 'Holy Trinity College',
        'host'     => env('DB_HOST_TWENTY_SIX', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TWENTY_SIX', '3306'),
        'database' => env('DB_DATABASE_TWENTY_SIX', 'geodnl_trinity'),
        'username' => env('DB_USERNAME_TWENTY_SIX', 'geodnl_trinity'),
        'password' => env('DB_PASSWORD_TWENTY_SIX', 'Loliquedificil2025'),
    ],

    33 => [
        'name'     => 'Escuela Argentina de Seguros',
        'host'     => env('DB_HOST_TWENTY_SEVEN', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TWENTY_SEVEN', '3306'),
        'database' => env('DB_DATABASE_TWENTY_SEVEN', 'geodnl_fapasa'),
        'username' => env('DB_USERNAME_TWENTY_SEVEN', 'geodnl_fapasa'),
        'password' => env('DB_PASSWORD_TWENTY_SEVEN', 'Segurosargentina2025'),
    ],

    34 => [
        'name'     => 'Escuela de Hoy',
        'host'     => env('DB_HOST_TWENTY_EIGHT', '127.0.0.1'),
        'port'     => env('DB_PORT_TWENTY_EIGHT', '3306'),
        'database' => env('DB_DATABASE_TWENTY_EIGHT', 'geodnl_hoy'),
        'username' => env('DB_USERNAME_TWENTY_EIGHT', 'root'),
        'password' => env('DB_PASSWORD_TWENTY_EIGHT', ''),
    ],

    35 => [
        'name'     => 'San Antonio',
        'host'     => env('DB_HOST_TWENTYNINE', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TWENTYNINE', '3306'),
        'database' => env('DB_DATABASE_TWENTYNINE', 'geodnl_snantonio'),
        'username' => env('DB_USERNAME_TWENTYNINE', 'geodnl_snantonio'),
        'password' => env('DB_PASSWORD_TWENTYNINE', 'Sanantonio2025'),
    ],

    36 => [
        'name'     => 'Howard Gardner',
        'host'     => env('DB_HOST_TREINTAUNO', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TREINTAUNO', '3306'),
        'database' => env('DB_DATABASE_TREINTAUNO', 'geodnl_gardner'),
        'username' => env('DB_USERNAME_TREINTAUNO', 'geodnl_gardner'),
        'password' => env('DB_PASSWORD_TREINTAUNO', 'Howard2025'),
    ],

    37 => [
        'name'     => 'Luis Federico Leloir',
        'host'     => env('DB_HOST_TREINTA', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TREINTA', '3306'),
        'database' => env('DB_DATABASE_TREINTA', 'geodnl_leloir'),
        'username' => env('DB_USERNAME_TREINTA', 'geodnl_leloir'),
        'password' => env('DB_PASSWORD_TREINTA', 'Luisfederico2025'),
    ],

    38 => [
        'name'     => 'Escuela del Jacaranda',
        'host'     => env('DB_HOST_TREINTADOS', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TREINTADOS', '3306'),
        'database' => env('DB_DATABASE_TREINTADOS', 'geodnl_jacaranda'),
        'username' => env('DB_USERNAME_TREINTADOS', 'geodnl_jacaranda'),
        'password' => env('DB_PASSWORD_TREINTADOS', 'EduBelgrano2024'),
    ],

    39 => [
        'name'     => 'Palladio POS',
        'host'     => env('DB_HOST_TREINTAYCUATRO', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TREINTAYCUATRO', '3306'),
        'database' => env('DB_DATABASE_TREINTAYCUATRO', 'geodnl_palpos'),
        'username' => env('DB_USERNAME_TREINTAYCUATRO', 'geodnl_palpos'),
        'password' => env('DB_PASSWORD_TREINTAYCUATRO', 'Carla2025'),
    ],

    40 => [
        'name'     => 'ICAQ UAAUNLP',
        'host'     => env('DB_HOST_TREINTAYCINCO', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TREINTAYCINCO', '3306'),
        'database' => env('DB_DATABASE_TREINTAYCINCO', 'geodnl_iquiunlp'),
        'username' => env('DB_USERNAME_TREINTAYCINCO', 'geodnl_iquiunlp'),
        'password' => env('DB_PASSWORD_TREINTAYCINCO', 'Diagonales2025'),
    ],

    41 => [
        'name'     => 'Colegio Las Lomas',
        'host'     => env('DB_HOST_TREINTAYSEIS', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TREINTAYSEIS', '3306'),
        'database' => env('DB_DATABASE_TREINTAYSEIS', 'geodnl_laslomas'),
        'username' => env('DB_USERNAME_TREINTAYSEIS', 'geodnl_laslomas'),
        'password' => env('DB_PASSWORD_TREINTAYSEIS', 'Bosquesillo2000'),
    ],

    42 => [
        'name'     => 'Figaro Demo',
        'host'     => env('DB_HOST_TENANT_42', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TENANT_42', '3306'),
        'database' => env('DB_DATABASE_TENANT_42', 'geodnl_figaro'),
        'username' => env('DB_USERNAME_TENANT_42', 'geodnl_figaro'),
        'password' => env('DB_PASSWORD_TENANT_42', 'FigaroGise2025'),
    ],

    43 => [
        'name'     => 'Cristo Obrero',
        'host'     => env('DB_HOST_TREINTAYOCHO', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TREINTAYOCHO', '3306'),
        'database' => env('DB_DATABASE_TREINTAYOCHO', 'geodnl_cobrero'),
        'username' => env('DB_USERNAME_TREINTAYOCHO', 'geodnl_cobrero'),
        'password' => env('DB_PASSWORD_TREINTAYOCHO', 'BocaEliminado2025'),
    ],

    44 => [
        'name'     => 'Cervantes',
        'host'     => env('DB_HOST_TREINTAYNUEVE', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TREINTAYNUEVE', '3306'),
        'database' => env('DB_DATABASE_TREINTAYNUEVE', 'geodnl_cervantes'),
        'username' => env('DB_USERNAME_TREINTAYNUEVE', 'geodnl_cervantes'),
        'password' => env('DB_PASSWORD_TREINTAYNUEVE', 'Rosario*1606'),
    ],

    45 => [
        'name'     => 'Geo Empresas Demo',
        'host'     => env('DB_HOST_CUARENTA', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_CUARENTA', '3306'),
        'database' => env('DB_DATABASE_CUARENTA', 'geodnl_fapasadm'),
        'username' => env('DB_USERNAME_CUARENTA', 'geodnl_fapasadm'),
        'password' => env('DB_PASSWORD_CUARENTA', 'Segurosargentina2025'),
    ],

    47 => [
        'name'     => 'Cristo Obrero Superior',
        'host'     => env('DB_HOST_CUARENTAYUNO', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_CUARENTAYUNO', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYUNO', 'geodnl_scobrero'),
        'username' => env('DB_USERNAME_CUARENTAYUNO', 'geodnl_scobrero'),
        'password' => env('DB_PASSWORD_CUARENTAYUNO', 'SCObrero2025'),
    ],

    48 => [
        'name'     => 'Colegio del Carmen',
        'host'     => env('DB_HOST_CUARENTAYDOS', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_CUARENTAYDOS', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYDOS', 'geodnl_carmen'),
        'username' => env('DB_USERNAME_CUARENTAYDOS', 'geodnl_carmen'),
        'password' => env('DB_PASSWORD_CUARENTAYDOS', 'DireLMEQC2025'),
    ],

    49 => [
        'name'     => 'Vocación Docente',
        'host'     => env('DB_HOST_CUARENTAYTRES', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_CUARENTAYTRES', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYTRES', 'geodnl_vocacion'),
        'username' => env('DB_USERNAME_CUARENTAYTRES', 'geodnl_vocacion'),
        'password' => env('DB_PASSWORD_CUARENTAYTRES', 'VentaPDF2025'),
    ],

    51 => [
        'name'     => 'Coned',
        'host'     => env('DB_HOST_CUARENTAYCUATRO', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_CUARENTAYCUATRO', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYCUATRO', 'geodnl_coned'),
        'username' => env('DB_USERNAME_CUARENTAYCUATRO', 'geodnl_coned'),
        'password' => env('DB_PASSWORD_CUARENTAYCUATRO', 'Rosario*1606'),
    ],

    52 => [
        'name'     => 'Prueba Codificacion',
        'host'     => env('DB_HOST_TENANT_52', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TENANT_52', '3306'),
        'database' => env('DB_DATABASE_TENANT_52', 'geodnl_codifica'),
        'username' => env('DB_USERNAME_TENANT_52', 'geodnl_codifica'),
        'password' => env('DB_PASSWORD_TENANT_52', 'Rosario*1606'),
    ],

    53 => [
        'name'     => 'Caracteres Juvenilia',
        'host'     => env('DB_HOST_TENANT_53', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TENANT_53', '3306'),
        'database' => env('DB_DATABASE_TENANT_53', 'geodnl_caracjuve'),
        'username' => env('DB_USERNAME_TENANT_53', 'geodnl_caracjuve'),
        'password' => env('DB_PASSWORD_TENANT_53', 'Rosario*1606'),
    ],

    54 => [
        'name'     => 'WizardSecundario',
        'host'     => env('DB_HOST_TENANT_54', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TENANT_54', '3306'),
        'database' => env('DB_DATABASE_TENANT_54', 'geodnl_wizardsecundario'),
        'username' => env('DB_USERNAME_TENANT_54', 'geodnl_wizardsecundario'),
        'password' => env('DB_PASSWORD_TENANT_54', '1234'),
    ],

    55 => [
        'name'     => 'WizardSuperior',
        'host'     => env('DB_HOST_CUARENTAYSEIS', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_CUARENTAYSEIS', '3306'),
        'database' => env('DB_DATABASE_CUARENTAYSEIS', 'geodnl_wizardsup'),
        'username' => env('DB_USERNAME_CUARENTAYSEIS', 'geodnl_wizardsup'),
        'password' => env('DB_PASSWORD_CUARENTAYSEIS', '1234'),
    ],

    56 => [
        'name'     => 'Talleres GEO',
        'host'     => env('DB_HOST_TENANT_56', 'geoeducacion.com.ar'),
        'port'     => env('DB_PORT_TENANT_56', '3306'),
        'database' => env('DB_DATABASE_TENANT_56', 'geodnl_talleres'),
        'username' => env('DB_USERNAME_TENANT_56', 'geodnl_huinco'),
        'password' => env('DB_PASSWORD_TENANT_56', 'Navarra2024'),
    ],

    'mysql_gral' => [
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'dmendoza_pr_el_gral_familias'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],
];
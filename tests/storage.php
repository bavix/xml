<?php

return [
    'person' => [
        [
            'name' => 'Alex',
            'age'  => 34,
            'cars' => [
                [
                    '@attributes' => [
                        'drive' => 'test'
                    ],
                    '@value' => 'Volvo'
                ],

                'BMW',
                'Toyota',
                'Honda',
                'Mercedes',
                'Opel'
            ]
        ],
        [
            'name' => [
                '@attributes' => [
                    'age' => 44
                ],
                '@value'      => 'Ivan'
            ],
            'cars' => 'Opel'
        ],
        [
            '@attributes' => [
                'name' => 'Anton'
            ],
            'age'         => 22,
            'cars' => [
                'Volvo',
                'BMW'
            ]
        ]
    ]
];

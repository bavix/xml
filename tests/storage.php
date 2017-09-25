<?php

return [
    'person' => [
        [
            'name' => 'Alex',
            'age'  => 34
        ],
        [
            'name' => [
                '@attributes' => [
                    'age' => 44
                ],
                '@value' => 'Ivan'
            ]
        ],
        [
            '@attributes' => [
                'name' => 'Anton'
            ],
            'age' => 22
        ]
    ]
];

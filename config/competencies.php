<?php

return [
    'groups' => [
        'technical' => [
            'label' => 'Technical',
            'categories' => ['technical', 'skill', 'training', 'workshop', 'seminar'],
        ],
        'soft' => [
            'label' => 'Soft skills',
            'categories' => ['soft'],
        ],
        'certification' => [
            'label' => 'Certification',
            'categories' => ['certification'],
        ],
    ],
    'targets' => [
        'technical' => 6,
        'soft' => 4,
        'certification' => 2,
    ],
    'default_target' => 4,
    'status_map' => [
        'verified' => 'verified',
        'approved' => 'verified',
        'evidence_submitted' => 'pending',
        'rejected' => 'rejected',
    ],
    'unmapped_status' => 'not_counted',
];

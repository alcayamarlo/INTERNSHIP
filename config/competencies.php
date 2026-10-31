<?php

// Chart axes for My Certificates. Each axis selects certificates by one field; a certificate
// can count on two axes (its skill type, and "Certificates" when its type is Certificate).
return [
    'groups' => [
        'technical' => [
            'label' => 'Technical',
            'field' => 'skill_type',
            'values' => ['technical'],
        ],
        'soft' => [
            'label' => 'Soft skills',
            'field' => 'skill_type',
            'values' => ['soft'],
        ],
        'certification' => [
            'label' => 'Certificates',
            'field' => 'certificate_type',
            'values' => ['certificate'],
        ],
    ],
    'targets' => [
        'technical' => 6,
        'soft' => 4,
        'certification' => 2,
    ],
    'default_target' => 4,
];

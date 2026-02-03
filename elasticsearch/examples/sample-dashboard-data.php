<?php

/**
 * Sample Dashboard Data
 *
 * This file contains sample data structures for testing the Dashboard Metrics Elasticsearch integration.
 */

return [
    // Daily Metrics Example
    'daily' => [
        'statistics' => [
            'patients' => [
                'count' => 18,
                'change' => 4,
                'change_type' => 'increase',
                'percentage_change' => 28.57
            ],
            'new_patients' => [
                'count' => 4,
                'change' => 1,
                'change_type' => 'increase',
                'percentage_change' => 33.33
            ],
            'revenue' => [
                'count' => 21250000,
                'change' => 250000,
                'change_type' => 'increase',
                'percentage_change' => 1.19
            ],
            'treatment' => [
                'count' => 31,
                'change' => 8,
                'change_type' => 'increase',
                'percentage_change' => 34.78
            ]
        ],
        'motivational_stats' => [
            'today' => 40,
            'yesterday' => 38,
            'target' => 50,
            'percentage' => 80.0
        ],
        'pending_items' => [
            'unsigned_visits' => 4,
            'unsynced_patients' => 9,
            'incomplete_diagnosis' => 11
        ],
        'queue_services' => [
            [
                'service_id' => 'poli-umum',
                'service_name' => 'Poli Umum',
                'current_queue' => 12,
                'waiting' => 8,
                'serving' => 4
            ],
            [
                'service_id' => 'poli-gigi',
                'service_name' => 'Poli Gigi',
                'current_queue' => 5,
                'waiting' => 3,
                'serving' => 2
            ],
            [
                'service_id' => 'laboratorium',
                'service_name' => 'Laboratorium',
                'current_queue' => 8,
                'waiting' => 6,
                'serving' => 2
            ]
        ],
        'diagnosis_treatment' => [
            [
                'patient_id' => 'P001',
                'patient_name' => 'Budi Santoso',
                'code' => 'A09',
                'type' => 'Diagnosa',
                'description' => 'Gastroenteritis',
                'poli' => 'Poli Umum',
                'doctor_id' => 'D001',
                'doctor_name' => 'Dr. Ahmad'
            ],
            [
                'patient_id' => 'P002',
                'patient_name' => 'Siti Nurhaliza',
                'code' => 'J00',
                'type' => 'Diagnosa',
                'description' => 'ISPA',
                'poli' => 'Poli Umum',
                'doctor_id' => 'D002',
                'doctor_name' => 'Dr. Sari'
            ],
            [
                'patient_id' => 'P003',
                'patient_name' => 'Andi Wijaya',
                'code' => 'T-001',
                'type' => 'Tindakan',
                'description' => 'Pencabutan Gigi',
                'poli' => 'Poli Gigi',
                'doctor_id' => 'D003',
                'doctor_name' => 'Dr. Dewi'
            ]
        ],
        'aggregation_period' => [
            'start_date' => '2024-12-29',
            'end_date' => '2024-12-29',
            'label' => 'Today'
        ],
        'metadata' => [
            'created_by' => 'system',
            'version' => '1.0'
        ]
    ],

    // Monthly Metrics Example
    'monthly' => [
        'statistics' => [
            'patients' => [
                'count' => 540,
                'change' => 45,
                'change_type' => 'increase',
                'percentage_change' => 9.09
            ],
            'new_patients' => [
                'count' => 120,
                'change' => 15,
                'change_type' => 'increase',
                'percentage_change' => 14.29
            ],
            'revenue' => [
                'count' => 637500000,
                'change' => 50000000,
                'change_type' => 'increase',
                'percentage_change' => 8.51
            ],
            'treatment' => [
                'count' => 65,
                'change' => 10,
                'change_type' => 'decrease',
                'percentage_change' => -13.33
            ]
        ],
        'motivational_stats' => [
            'today' => 540,
            'yesterday' => 495,
            'target' => 600,
            'percentage' => 90.0
        ],
        'pending_items' => [
            'unsigned_visits' => 12,
            'unsynced_patients' => 25,
            'incomplete_diagnosis' => 18
        ],
        'queue_services' => [],
        'diagnosis_treatment' => [],
        'aggregation_period' => [
            'start_date' => '2024-12-01',
            'end_date' => '2024-12-31',
            'label' => 'December 2024'
        ],
        'metadata' => [
            'created_by' => 'system',
            'version' => '1.0'
        ]
    ],

    // Yearly Metrics Example
    'yearly' => [
        'statistics' => [
            'patients' => [
                'count' => 6500,
                'change' => 520,
                'change_type' => 'increase',
                'percentage_change' => 8.7
            ],
            'new_patients' => [
                'count' => 1450,
                'change' => 180,
                'change_type' => 'increase',
                'percentage_change' => 14.17
            ],
            'revenue' => [
                'count' => 7650000000,
                'change' => 650000000,
                'change_type' => 'increase',
                'percentage_change' => 9.29
            ],
            'treatment' => [
                'count' => 120,
                'change' => 20,
                'change_type' => 'decrease',
                'percentage_change' => -14.29
            ]
        ],
        'motivational_stats' => [
            'today' => 6500,
            'yesterday' => 5980,
            'target' => 7000,
            'percentage' => 92.86
        ],
        'pending_items' => [
            'unsigned_visits' => 45,
            'unsynced_patients' => 78,
            'incomplete_diagnosis' => 52
        ],
        'queue_services' => [],
        'diagnosis_treatment' => [],
        'aggregation_period' => [
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'label' => '2024'
        ],
        'metadata' => [
            'created_by' => 'system',
            'version' => '1.0'
        ]
    ]
];

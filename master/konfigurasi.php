<?php

// Konfigurasi tabel master.
// Format setiap field: [label, tipe input, panjang maksimal].
$master = [

    // Data kelas
    'kelas' => [
        'table' => 'tb_kelas',
        'pk' => 'id_kelas',
        'title' => 'Data Kelas',

        'fields' => [
            'id_kelas' => [
                'Kode kelas',
                'text',
                11
            ],

            'nama_kelas' => [
                'Nama kelas',
                'text',
                10
            ],

            'komp_keahlian' => [
                'Kompetensi keahlian',
                'text',
                50
            ]
        ]
    ],

    // Data SPP
    'spp' => [
        'table' => 'tb_spp',
        'pk' => 'id_spp',
        'title' => 'Data SPP',

        'fields' => [
            'id_spp' => [
                'Kode SPP',
                'text',
                11
            ],

            'tahun' => [
                'Tahun ajaran',
                'number',
                4
            ],

            'nominal' => [
                'Nominal per bulan (Rp)',
                'number',
                10
            ]
        ]
    ],

    // Data petugas
    'petugas' => [
        'table' => 'tb_petugas',
        'pk' => 'id_petugas',
        'title' => 'Data Petugas',

        'fields' => [
            'id_petugas' => [
                'Kode petugas',
                'text',
                11
            ],

            'username' => [
                'Username',
                'text',
                25
            ],

            'password' => [
                'Password',
                'password',
                64
            ],

            'nama_petugas' => [
                'Nama petugas',
                'text',
                35
            ],

            'level' => [
                'Level',
                'level',
                0
            ]
        ]
    ],

    // Data siswa
    'siswa' => [
        'table' => 'tb_siswa',
        'pk' => 'nisn',
        'title' => 'Data Siswa',

        'fields' => [
            'nisn' => [
                'NISN',
                'text',
                10
            ],

            'nis' => [
                'NIS',
                'text',
                8
            ],

            'nama' => [
                'Nama siswa',
                'text',
                50
            ],

            'id_kelas' => [
                'Kelas',
                'kelas',
                11
            ],

            'alamat' => [
                'Alamat',
                'textarea',
                2000
            ],

            'no_telp' => [
                'Nomor telepon',
                'text',
                13
            ],

            'id_spp' => [
                'Tarif SPP',
                'spp',
                40
            ]
        ]
    ]

];
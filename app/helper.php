<?php

if (!function_exists('indo_date')) {
    function indo_date($date = null, $include_day = false){
        $date = $date ? strtotime($date) : time();

        $nama_hari = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        $nama_bulan = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $hari = $nama_hari[date('l', $date)];
        $tanggal = date('d', $date);
        $bulan = $nama_bulan[date('n', $date)];
        $tahun = date('Y', $date);

        $format = "$tanggal $bulan $tahun";

        if ($include_day) {
            $format = "$hari, $format";
        }

        return $format;
    }
}

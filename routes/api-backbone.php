<?php

use App\Http\Controllers\API\ApiAccess\ApiAccessController;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Illuminate\Support\Facades\Route;
use Hanafalah\LaravelSupport\Facades\LaravelSupport;

ApiAccess::secure(function(){
    Route::apiResource('token',ApiAccessController::class)
        ->only('store','destroy')
        ->parameters(['token' => 'uuid']);
}); 

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'env' => config('app.env'),
        'version' => '1.0.0',
        'time' => now()->toDateTimeString(),
    ]);
});

// EMR Export Preview Route (Development Only)
if (config('app.env') !== 'production') {
    Route::get('/preview/emr-export', function () {
        $jsonPath = base_path('visit-registration-show.json');
        if (!file_exists($jsonPath)) {
            return response()->json(['error' => 'visit-registration-show.json not found'], 404);
        }

        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $data = $jsonData['data'] ?? [];
        $emr = $data['examination_summary']['emr'] ?? [];

        $transformedData = [
            'workspace' => [
                'name' => 'KLINIK SEHAT SEJAHTERA',
                'logo' => null,
                'address' => 'Jl. Kesehatan No. 123, Jakarta Selatan 12345',
                'phone' => '(021) 1234-5678',
                'email' => 'info@kliniksehat.com',
            ],
            'visit_registration' => [
                'id' => $data['id'] ?? '-',
                'visit_registration_code' => $data['visit_registration_code'] ?: 'VR-2026-0001',
                'visit_date' => isset($data['visit_patient']['visited_at'])
                    ? \Carbon\Carbon::parse($data['visit_patient']['visited_at'])->format('d F Y H:i')
                    : now()->format('d F Y H:i'),
                'status' => $data['status'] ?? 'COMPLETED',
                'medic_service' => ['name' => $data['medic_service']['name'] ?? 'Umum'],
            ],
            'patient' => [
                'name' => $data['visit_patient']['patient']['people']['name'] ?? 'Hamz Zah',
                'medical_record_number' => $data['visit_patient']['patient']['medical_record'] ?? 'RM-001234',
                'nik' => $data['visit_patient']['patient']['people']['card_identity']['nik'] ?? '3601328981231238',
                'ihs_number' => $data['visit_patient']['patient']['card_identity']['ihs_number'] ?? 'P20395541001',
                'date_of_birth' => isset($data['visit_patient']['patient']['people']['dob'])
                    ? \Carbon\Carbon::parse($data['visit_patient']['patient']['people']['dob'])->format('d F Y')
                    : '07 Juli 2020',
                'age' => $data['visit_patient']['patient']['people']['age'] ?? 5,
                'gender' => ($data['visit_patient']['patient']['people']['sex'] ?? 'Male') == 'Male' ? 'Laki-laki' : 'Perempuan',
                'blood_type' => $data['visit_patient']['patient']['people']['blood_type'] ?? 'O+',
                'phone' => $data['visit_patient']['patient']['people']['phone_1'] ?? '081234567890',
                'patient_type' => $data['visit_patient']['patient']['patient_type']['name'] ?? 'Umum',
            ],
            'practitioner' => [
                'name' => $data['practitioner_evaluation']['name'] ?? 'dr. Baktianto Kuswoyo',
                'profession' => $data['practitioner_evaluation']['profession']['name'] ?? 'Dokter Umum',
                'sip_number' => 'SIP.123/DU/2024',
            ],
            'vital_signs' => [
                ['label' => 'Suhu Tubuh', 'value' => $emr['VitalSign']['exam']['temperature'] ?? '39', 'unit' => '°C', 'status' => 'Demam', 'status_class' => 'warning'],
                ['label' => 'Tekanan Darah', 'value' => ($emr['VitalSign']['exam']['systolic'] ?? '120') . '/' . ($emr['VitalSign']['exam']['diastolic'] ?? '80'), 'unit' => 'mmHg', 'status' => 'Hipertensi', 'status_class' => 'danger'],
                ['label' => 'Denyut Nadi', 'value' => $emr['VitalSign']['exam']['pulse_rate'] ?? '70', 'unit' => 'bpm'],
                ['label' => 'Frekuensi Napas', 'value' => $emr['VitalSign']['exam']['respiration_rate'] ?? '16', 'unit' => 'x/menit'],
                ['label' => 'Saturasi Oksigen', 'value' => $emr['VitalSign']['exam']['oxygen_saturation'] ?? '99', 'unit' => '%', 'status' => 'Normal', 'status_class' => 'normal'],
                ['label' => 'Kesadaran', 'value' => $emr['VitalSign']['exam']['loc']['name'] ?? 'Compos Mentis', 'unit' => ''],
            ],
            'anthropometry' => [
                ['label' => 'Berat Badan', 'value' => $emr['Anthropometry']['exam']['weight'] ?? '90', 'unit' => 'kg'],
                ['label' => 'Tinggi Badan', 'value' => $emr['Anthropometry']['exam']['height'] ?? '170', 'unit' => 'cm'],
                ['label' => 'BMI', 'value' => number_format($emr['Anthropometry']['exam']['bmi'] ?? 31.14, 2), 'unit' => 'kg/m²', 'interpretation' => $emr['Anthropometry']['exam']['bmi_category'] ?? 'Obese II', 'interpretation_class' => 'danger'],
                ['label' => 'Berat Ideal', 'value' => $emr['Anthropometry']['exam']['ideal_weight'] ?? '63', 'unit' => 'kg'],
            ],
            'pain_scale' => [
                'value' => (int) ($emr['PainScale']['exam']['rating_scale'] ?? 3),
                'interpretation' => $emr['PainScale']['exam']['scale_result'] ?? 'Nyeri Sedang',
                'badge_class' => 'warning',
            ],
            'symptoms' => collect($emr['Symptom'] ?? [])->map(fn($s) => ['name' => $s['exam']['name'] ?? '-'])->toArray(),
            'allergies' => collect($emr['Allergy'] ?? [])->map(fn($a) => [
                'name' => $a['exam']['name'] ?? '-',
                'allergy_type' => $a['exam']['allergy_type']['name'] ?? null,
                'allergen' => $a['exam']['allergen'] ?? null,
                'severity' => $a['exam']['allergy_scale_spell'] ?? null,
            ])->toArray(),
            'soap' => [
                'subjective' => $emr['SubjectNote']['exam']['note'] ?? 'Pasien datang dengan keluhan pusing, mual, dan muntah sejak 2 hari yang lalu. Keluhan disertai demam naik turun.',
                'objective' => $emr['ObjectNote']['exam']['note'] ?? 'Keadaan umum: tampak sakit sedang. Kesadaran: compos mentis.',
                'assessment' => $emr['AssessmentNote']['exam']['note'] ?? 'Observasi febris hari ke-2 ec suspek viral infection.',
                'plan' => $emr['PlanNote']['exam']['note'] ?? '1. Terapi simptomatik\n2. Rehidrasi oral\n3. Diet lunak',
            ],
            'diagnoses' => collect($emr['BasicDiagnose'] ?? [])->map(fn($d) => [
                'type' => str_replace('Diagnose', '', $d['exam']['type'] ?? 'Secondary'),
                'type_label' => match($d['exam']['type'] ?? '') {
                    'InitialDiagnose' => 'Diagnosis Awal',
                    'PrimaryDiagnose' => 'Diagnosis Utama',
                    default => 'Diagnosis Sekunder',
                },
                'code' => $d['exam']['code'] ?? '-',
                'name' => $d['exam']['name'] ?? '-',
            ])->toArray(),
            'prescriptions' => collect($emr['BasicPrescription'] ?? [])->map(function($p) {
                $exam = $p['exam'] ?? [];
                $instruction = $exam['dosage_instruction'] ?? [];
                $timing = implode(', ', array_filter($instruction['divided_times'] ?? []));
                if (isset($instruction['consume_at']['value'])) {
                    $timing .= ($timing ? ' - ' : '') . $instruction['consume_at']['value'];
                }
                return [
                    'name' => $exam['name'] ?? '-',
                    'qty' => $exam['qty'] ?? '-',
                    'frequency' => isset($exam['frequency_qty']) ? "{$exam['frequency_qty']}x sehari" : '-',
                    'timing' => $timing ?: '-',
                    'indication' => $exam['indication'] ?? '-',
                ];
            })->toArray(),
            'treatments' => collect($emr['ClinicalTreatment'] ?? [])->map(fn($t) => [
                'name' => $t['exam']['name'] ?? '-',
                'qty' => $t['exam']['qty'] ?? '1',
                'result' => $t['exam']['result'] ?? '-',
                'note' => $t['exam']['note'] ?? '-',
            ])->toArray(),
            'history_illnesses' => collect($emr['HistoryIllness'] ?? [])->map(fn($i) => [
                'code' => $i['exam']['code'] ?? '-',
                'name' => $i['exam']['name'] ?? '-',
            ])->toArray(),
            'family_illnesses' => collect($emr['FamilyIllness'] ?? [])->map(fn($i) => [
                'family_name' => $i['exam']['family_name'] ?? '-',
                'name' => $i['exam']['name'] ?? '-',
            ])->toArray(),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('wellmed::exports.emr.visit-registration', $transformedData)
            ->setOptions([
                'enable_php' => true,
                'enable_remote' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        $dompdf = $pdf->getDomPDF();
        $pdf->render();

        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->get_font('Helvetica', 'normal');
        $canvas->page_text(520, 820, "Halaman {PAGE_NUM} dari {PAGE_COUNT}", $font, 9, [0, 0, 0]);

        return $pdf->stream('emr-preview.pdf');
    });
}


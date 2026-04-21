<?php

declare(strict_types=1);

const CHATBOT_DISCLAIMER = '⚠️ Medical disclaimer: This chatbot provides general educational information only. It does not diagnose conditions, prescribe medicines, or replace a licensed doctor. If symptoms are severe, persistent, or emergency-related, seek immediate professional care.';
const CHATBOT_CONSENT = 'By continuing, you acknowledge and consent that chatbot guidance is informational and you should confirm medical decisions with a licensed doctor.';
const CHATBOT_AVAILABILITY_DAYS_AHEAD = 7;
const CHATBOT_CANDIDATE_TIMES = ['09:00', '11:00', '14:00', '16:00', '18:00'];

/**
 * @return array{intent:string, medicine_name:?string}
 */
function chatbot_detect_intent(string $message): array
{
    $normalized = chatbot_normalize($message);

    $availability_keywords = ['appointment', 'available', 'availability', 'slot', 'book', 'doctor available', 'next slot'];
    foreach ($availability_keywords as $keyword) {
        if (strpos($normalized, $keyword) !== false) {
            return ['intent' => 'availability', 'medicine_name' => null];
        }
    }

    $medicine_name = chatbot_extract_medicine_name($normalized);
    $medicine_keywords = ['medicine', 'tablet', 'capsule', 'dose', 'dosage', 'side effect', 'uses', 'contraindication'];
    foreach ($medicine_keywords as $keyword) {
        if (strpos($normalized, $keyword) !== false) {
            return ['intent' => 'medicine_info', 'medicine_name' => $medicine_name];
        }
    }

    if ($medicine_name !== null) {
        return ['intent' => 'medicine_info', 'medicine_name' => $medicine_name];
    }

    return ['intent' => 'symptom', 'medicine_name' => null];
}

function chatbot_normalize(string $text): string
{
    return strtolower(trim(preg_replace('/\s+/', ' ', $text) ?? ''));
}

function chatbot_extract_medicine_name(string $normalized_message): ?string
{
    $medicine_data = chatbot_medicine_data();
    foreach (array_keys($medicine_data) as $medicine_name) {
        if (preg_match('/\b' . preg_quote($medicine_name, '/') . '\b/i', $normalized_message)) {
            return $medicine_name;
        }
    }

    return null;
}

/**
 * @return array<string, array{uses:string,dosage:string,side_effects:string,warnings:string,contraindications:string}>
 */
function chatbot_medicine_data(): array
{
    return [
        'paracetamol' => [
            'uses' => 'General fever and mild-to-moderate pain relief.',
            'dosage' => 'Adults often use 500mg to 650mg every 4-6 hours when needed. Do not exceed total daily limit advised by your doctor/product label.',
            'side_effects' => 'Usually mild; may include nausea or rash.',
            'warnings' => 'Avoid combining with other products containing paracetamol/acetaminophen. Overuse can seriously harm the liver.',
            'contraindications' => 'Use caution in liver disease or regular alcohol use. Consult a doctor before use in chronic illness/pregnancy.',
        ],
        'ibuprofen' => [
            'uses' => 'Pain, inflammation, and fever reduction.',
            'dosage' => 'Adults often use 200mg to 400mg every 6-8 hours with food when needed, per label/doctor guidance.',
            'side_effects' => 'Acidity, stomach upset, heartburn, dizziness.',
            'warnings' => 'May increase risk of stomach bleeding/kidney strain in some patients.',
            'contraindications' => 'Avoid in certain kidney disease, active ulcers, late pregnancy, or if advised to avoid NSAIDs.',
        ],
        'cetirizine' => [
            'uses' => 'Allergy symptoms such as sneezing, itching, watery nose/eyes.',
            'dosage' => 'Adults commonly use 10mg once daily unless otherwise advised.',
            'side_effects' => 'Drowsiness, dry mouth, fatigue.',
            'warnings' => 'Use caution while driving if sleepy.',
            'contraindications' => 'Dose adjustment may be needed in kidney impairment; consult doctor for children/pregnancy.',
        ],
    ];
}

/**
 * @return array{specialty:string,guidance:string,possible_medicines:string}
 */
function chatbot_suggest_specialty_from_problem(string $message): array
{
    $normalized = chatbot_normalize($message);

    $rules = [
        'cardiology' => ['chest pain', 'palpitation', 'heart', 'blood pressure', 'bp'],
        'dermatology' => ['rash', 'skin', 'itching', 'acne', 'eczema'],
        'pulmonology' => ['cough', 'breathing', 'asthma', 'wheezing', 'shortness of breath'],
        'gastroenterology' => ['stomach', 'acidity', 'vomit', 'diarrhea', 'constipation'],
        'ent' => ['throat', 'ear pain', 'sinus', 'cold', 'nose block'],
        'general medicine' => ['fever', 'headache', 'fatigue', 'body pain', 'weakness'],
    ];

    $specialty = 'General Medicine';
    foreach ($rules as $rule_specialty => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($normalized, $keyword) !== false) {
                $specialty = ucwords($rule_specialty);
                break 2;
            }
        }
    }

    return [
        'specialty' => $specialty,
        'guidance' => 'Based on your message, consider consulting a ' . $specialty . ' doctor. Keep hydrated, rest, and monitor symptoms. Seek urgent care for red-flag signs like severe breathing difficulty, persistent high fever, chest pain, confusion, or worsening symptoms.',
        'possible_medicines' => 'General OTC information (not a prescription): options often discussed include paracetamol for fever/pain, cetirizine for allergy symptoms, or ibuprofen for inflammatory pain (if suitable). Always confirm safety and dose with a licensed doctor.',
    ];
}

/**
 * @return array<int, array{doctor_name:string,specialization:string,next_available_slot:string}>
 */
function chatbot_get_doctor_availability(mysqli $conn, int $limit = 5): array
{
    $availability = [];

    $doctor_result = $conn->query('SELECT id, full_name, specialization FROM doctors ORDER BY full_name ASC');
    if (!$doctor_result) {
        return $availability;
    }

    $booked_slots = [];
    $booking_stmt = $conn->prepare(
        "SELECT doctor_id, appointment_date, appointment_time
         FROM appointments
         WHERE status IN ('pending', 'confirmed')
           AND TIMESTAMP(appointment_date, appointment_time) >= NOW()"
    );

    if ($booking_stmt && $booking_stmt->execute()) {
        $bookings = $booking_stmt->get_result();
        while ($row = $bookings->fetch_assoc()) {
            $doctor_id = (int) $row['doctor_id'];
            $time_value = (string) ($row['appointment_time'] ?? '');
            $parsed_time = DateTime::createFromFormat('H:i:s', $time_value) ?: DateTime::createFromFormat('H:i', $time_value);
            if (!$parsed_time) {
                continue;
            }
            $time_key = $parsed_time->format('H:i');
            $key = $row['appointment_date'] . ' ' . $time_key;
            $booked_slots[$doctor_id][$key] = true;
        }
        $booking_stmt->close();
    }

    $now = new DateTimeImmutable('now');

    while ($doctor = $doctor_result->fetch_assoc()) {
        $doctor_id = (int) $doctor['id'];
        $next_slot = null;

        for ($day_offset = 0; $day_offset <= CHATBOT_AVAILABILITY_DAYS_AHEAD; $day_offset++) {
            $date = $now->modify('+' . $day_offset . ' day')->format('Y-m-d');
            foreach (CHATBOT_CANDIDATE_TIMES as $time) {
                $slot_datetime = DateTimeImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
                if (!$slot_datetime || $slot_datetime <= $now) {
                    continue;
                }

                $slot_key = $date . ' ' . $time;
                if (!isset($booked_slots[$doctor_id][$slot_key])) {
                    $next_slot = $slot_datetime->format('d M Y, h:i A');
                    break 2;
                }
            }
        }

        if ($next_slot !== null) {
            $availability[] = [
                'doctor_name' => (string) $doctor['full_name'],
                'specialization' => (string) ($doctor['specialization'] ?? 'General Medicine'),
                'next_available_slot' => $next_slot,
            ];
        }

        if (count($availability) >= $limit) {
            break;
        }
    }

    return $availability;
}

/**
 * @return array{intent:string,answer:string,disclaimer:string,consent:string}
 */
function chatbot_handle_message(mysqli $conn, string $message): array
{
    $detected = chatbot_detect_intent($message);
    $intent = $detected['intent'];
    $answer = '';

    if ($intent === 'availability') {
        $list = chatbot_get_doctor_availability($conn, 5);
        if (count($list) === 0) {
            $answer = 'I could not find open doctor slots right now. Please check again shortly or contact support for manual scheduling.';
        } else {
            $lines = ["Here are currently available doctors and next open slots:"];
            foreach ($list as $item) {
                $lines[] = '- Dr. ' . $item['doctor_name'] . ' (' . $item['specialization'] . '): ' . $item['next_available_slot'];
            }
            $answer = implode("\n", $lines);
        }
    } elseif ($intent === 'medicine_info') {
        $medicine_data = chatbot_medicine_data();
        $medicine_name = $detected['medicine_name'];

        if ($medicine_name === null || !isset($medicine_data[$medicine_name])) {
            $answer = 'Please share the exact medicine name (for example: Paracetamol, Ibuprofen, Cetirizine), and I will provide general safety information.';
        } else {
            $info = $medicine_data[$medicine_name];
            $answer = sprintf(
                "%s information:\n- Uses: %s\n- General dosage guidance: %s\n- Possible side effects: %s\n- Warnings: %s\n- Contraindications: %s",
                ucfirst($medicine_name),
                $info['uses'],
                $info['dosage'],
                $info['side_effects'],
                $info['warnings'],
                $info['contraindications']
            );
        }
    } else {
        $suggestion = chatbot_suggest_specialty_from_problem($message);
        $answer = "Doctor suggestion: " . $suggestion['specialty'] . "\n" .
            "Next steps: " . $suggestion['guidance'] . "\n" .
            $suggestion['possible_medicines'];
    }

    return [
        'intent' => $intent,
        'answer' => $answer,
        'disclaimer' => CHATBOT_DISCLAIMER,
        'consent' => CHATBOT_CONSENT,
    ];
}

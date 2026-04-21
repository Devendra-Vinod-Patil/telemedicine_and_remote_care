<?php

declare(strict_types=1);

require_once __DIR__ . '/../chatbot_service.php';

$failures = [];

$intent = chatbot_detect_intent('Do you have appointment availability for cardiology?');
if ($intent['intent'] !== 'availability') {
    $failures[] = 'Expected availability intent';
}

$intent = chatbot_detect_intent('What are the side effects of paracetamol tablet?');
if ($intent['intent'] !== 'medicine_info') {
    $failures[] = 'Expected medicine_info intent';
}

$intent = chatbot_detect_intent('I have skin rash and itching');
if ($intent['intent'] !== 'symptom') {
    $failures[] = 'Expected symptom intent';
}

$suggestion = chatbot_suggest_specialty_from_problem('I have chest pain and high bp');
if ($suggestion['specialty'] !== 'Cardiology') {
    $failures[] = 'Expected Cardiology specialty for chest pain and high bp';
}

if (!empty($failures)) {
    foreach ($failures as $failure) {
        fwrite(STDERR, $failure . PHP_EOL);
    }
    exit(1);
}

echo "Chatbot intent tests passed." . PHP_EOL;

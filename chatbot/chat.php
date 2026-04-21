<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/chatbot_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw_body = file_get_contents('php://input');
$payload = json_decode($raw_body ?: '', true);
$message = trim((string) ($payload['message'] ?? ''));

if ($message === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

if (!isset($_SESSION['chatbot_conversation']) || !is_array($_SESSION['chatbot_conversation'])) {
    $_SESSION['chatbot_conversation'] = [];
}

$response = chatbot_handle_message($conn, $message);

$_SESSION['chatbot_conversation'][] = [
    'role' => 'user',
    'message' => $message,
    'time' => date('c'),
];

$_SESSION['chatbot_conversation'][] = [
    'role' => 'bot',
    'message' => $response['answer'],
    'intent' => $response['intent'],
    'time' => date('c'),
];

if (count($_SESSION['chatbot_conversation']) > 50) {
    $_SESSION['chatbot_conversation'] = array_slice($_SESSION['chatbot_conversation'], -50);
}

echo json_encode([
    'intent' => $response['intent'],
    'answer' => $response['answer'],
    'disclaimer' => $response['disclaimer'],
    'consent' => $response['consent'],
    'conversation' => $_SESSION['chatbot_conversation'],
]);

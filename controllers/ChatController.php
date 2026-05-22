<?php

declare(strict_types=1);

/**
 * controllers/ChatController.php
 * Controlador de Chat: Recibe las preguntas enviadas por AJAX,
 * las valida/sanitiza e invoca al GroqClient para retornar la respuesta.
 */

require_once __DIR__.'/../autoload.php';

use App\Helpers\GroqClient;
use App\Helpers\Validator;

session_start();

// Validar que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Método no permitido. Solo se acepta POST.']);
    exit;
}

// Obtener datos del cuerpo de la petición (JSON)
$json = file_get_contents('php://input');
$data = json_decode($json, true);
$message = $data['message'] ?? '';

// Sanitizar y validar entrada
$message = Validator::sanitize((string) $message);

if (empty(trim($message))) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Por favor, escribe una pregunta válida.']);
    exit;
}

try {
    // Instanciar cliente Groq y obtener respuesta
    $client = new GroqClient;
    $chatResponse = $client->chat($message);

    header('Content-Type: application/json');
    echo json_encode(['response' => $chatResponse]);
} catch (Exception $e) {
    error_log('[ChatController] Error: '.$e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Ocurrió un error al procesar tu solicitud de chat.']);
}
exit;

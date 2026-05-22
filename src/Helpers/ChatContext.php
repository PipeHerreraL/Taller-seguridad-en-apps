<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Mantiene en sesión el historial del chat y el último paciente consultado
 * para resolver preguntas de seguimiento ("y su email", "cuál es su género").
 */
class ChatContext
{
    private const SESSION_KEY = 'clinica_chat';

    private const MAX_HISTORY = 20;

    /**
     * @return array{last_search_term: string, last_patients: list<array<string, mixed>>, messages: list<array{role: string, content: string}>}
     */
    public function load(): array
    {
        $ctx = $_SESSION[self::SESSION_KEY] ?? [];

        return [
            'last_search_term' => (string) ($ctx['last_search_term'] ?? ''),
            'last_patients' => is_array($ctx['last_patients'] ?? null) ? $ctx['last_patients'] : [],
            'messages' => is_array($ctx['messages'] ?? null) ? $ctx['messages'] : [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $patients
     */
    public function rememberPatients(array $patients, string $searchTerm): void
    {
        $ctx = $this->load();
        $ctx['last_patients'] = $patients;
        $ctx['last_search_term'] = $searchTerm;
        $_SESSION[self::SESSION_KEY] = $ctx;
    }

    public function appendMessage(string $userMessage, string $assistantMessage): void
    {
        $ctx = $this->load();
        $ctx['messages'][] = ['role' => 'user', 'content' => $userMessage];
        $ctx['messages'][] = ['role' => 'assistant', 'content' => $assistantMessage];

        if (count($ctx['messages']) > self::MAX_HISTORY) {
            $ctx['messages'] = array_slice($ctx['messages'], -self::MAX_HISTORY);
        }

        $_SESSION[self::SESSION_KEY] = $ctx;
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    public function getMessagesForApi(): array
    {
        return $this->load()['messages'];
    }

    public function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }
}

<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Paciente;
use Exception;

/**
 * Clase GroqClient
 * Gestiona el cliente de comunicación con la API de Groq Cloud de forma segura.
 */
class GroqClient
{
    private string $apiKey;
    private string $model = 'llama-3.1-8b-instant';
    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private Paciente $pacienteModel;
    private ChatContext $chatContext;

    public function __construct(?Paciente $pacienteModel = null, ?ChatContext $chatContext = null)
    {
        $this->apiKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?: 'mock';
        $this->pacienteModel = $pacienteModel ?? new Paciente();
        $this->chatContext = $chatContext ?? new ChatContext();
    }

    /**
     * Envia el mensaje del usuario a Groq, procesando llamadas a herramientas si corresponde.
     */
    public function chat(string $userMessage): string
    {
        if (empty(trim($userMessage))) {
            return 'Por favor, escribe un mensaje.';
        }

        $response = $this->dispatchChat($userMessage);
        $this->chatContext->appendMessage($userMessage, $response);

        return $response;
    }

    private function dispatchChat(string $userMessage): string
    {
        $normalized = mb_strtolower(trim($userMessage));

        if ($this->isDestructiveRequest($normalized)) {
            return $this->readOnlyRejectionMessage();
        }

        // Respuestas basadas en la BD local (antes de Groq) para evitar alucinaciones del modelo
        $dbAnswer = $this->tryAnswerFromDatabase($normalized);
        if ($dbAnswer !== null) {
            return $dbAnswer;
        }

        // Si estamos en modo Mock, derivamos a la simulación segura
        if ($this->apiKey === 'mock' || empty($this->apiKey)) {
            return $this->chatMock($normalized);
        }

        try {
            $systemPrompt = $this->getSystemPrompt();
            $tools = $this->getToolsDefinition();

            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            foreach ($this->chatContext->getMessagesForApi() as $historyMsg) {
                $messages[] = $historyMsg;
            }
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            // 1. Primer llamado a Groq para evaluar herramientas
            $response = $this->makeRequest($messages, $tools);

            if (isset($response['choices'][0]['message']['tool_calls'])) {
                $toolCalls = $response['choices'][0]['message']['tool_calls'];
                $messages[] = $response['choices'][0]['message']; // guardar mensaje del asistente con tool_calls

                foreach ($toolCalls as $toolCall) {
                    $toolName = $toolCall['function']['name'];
                    $toolArgs = json_decode($toolCall['function']['arguments'], true) ?: [];
                    $toolResult = $this->executeTool($toolName, $toolArgs);

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'name' => $toolName,
                        'content' => json_encode($toolResult)
                    ];
                }

                // 2. Segundo llamado enviando los resultados de las herramientas
                $finalResponse = $this->makeRequest($messages);
                return $finalResponse['choices'][0]['message']['content'] ?? "No pude procesar la respuesta.";
            }

            return $response['choices'][0]['message']['content'] ?? "No pude procesar la respuesta.";

        } catch (Exception $e) {
            error_log('[GroqClient] Error: ' . $e->getMessage());
            return "Lo siento, experimenté un error de conexión con mi cerebro artificial. Detalles: " . htmlspecialchars($e->getMessage());
        }
    }

    /**
     * Retorna el Prompt de Sistema que restringe a la IA a solo lectura y seguridad.
     */
    private function getSystemPrompt(): string
    {
        return "Eres ClinicaApp AI, un asistente virtual de solo lectura para la clínica ClinicaApp.\n" .
               "FUENTE DE DATOS OBLIGATORIA:\n" .
               "- Toda estadística o dato de pacientes proviene EXCLUSIVAMENTE de las herramientas conectadas a la base de datos local.\n" .
               "- NUNCA uses conocimiento general, estadísticas mundiales, la OMS ni datos de población global para responder.\n" .
               "- Si preguntan por pacientes registrados, totales, RH/tipo de sangre más común o contacto de un paciente, DEBES invocar la herramienta correspondiente antes de responder.\n" .
               "- Si una herramienta devuelve datos, responde solo con esos datos. Si no hay datos, dilo claramente.\n" .
               "CONTEXTO CLÍNICO:\n" .
               "- Atiendes al personal médico autorizado de ClinicaApp. Los pacientes ya registraron sus datos en la clínica.\n" .
               "- NUNCA niegues teléfono, correo u otros datos de contacto por privacidad o consentimiento: usa buscar_paciente_por_nombre.\n" .
               "REGLAS DE SEGURIDAD CRÍTICAS:\n" .
               "1. Eres de SOLO LECTURA. No tienes capacidad ni herramientas para crear, modificar, actualizar o eliminar registros. " .
               "Rechaza peticiones de insertar, cambiar o borrar datos.\n" .
               "2. Nunca reveles contraseñas ni password_hash.\n" .
               "3. Respuestas concisas, profesionales y en español.";
    }

    /**
     * Define las herramientas seguras parametrizadas.
     */
    private function getToolsDefinition(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'contar_pacientes',
                    'description' => 'OBLIGATORIO para preguntas sobre cuántos pacientes hay, total registrados o cantidad en la base de datos de ClinicaApp.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object)[]
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'buscar_paciente_por_nombre',
                    'description' => 'OBLIGATORIO para teléfono, correo o datos de contacto de un paciente por nombre/apellido. Uso interno autorizado de ClinicaApp. Acepta varias palabras: "Juan Lopez" busca Juan en nombre y Lopez en apellido.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'nombre' => [
                                'type' => 'string',
                                'description' => 'Nombre, apellido o fragmentos separados por espacio (ej. "Juan Lopez", "Felipe Herrera"). Búsqueda parcial por cada palabra.'
                            ]
                        ],
                        'required' => ['nombre']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'obtener_rh_mas_comun',
                    'description' => 'OBLIGATORIO para preguntas sobre RH, tipo de sangre o grupo sanguíneo más común/frecuente entre los pacientes de ClinicaApp. No uses estadísticas mundiales.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object)[]
                    ]
                ]
            ]
        ];
    }

    /**
     * Ejecuta una herramienta de forma local usando código preparado de PHP.
     */
    private function executeTool(string $name, array $args): array
    {
        switch ($name) {
            case 'contar_pacientes':
                return ['total' => $this->pacienteModel->contarTodos()];

            case 'buscar_paciente_por_nombre':
                $term = trim($args['nombre'] ?? '');
                if ($term === '') {
                    return ['error' => 'Debes proporcionar un nombre de paciente válido.'];
                }
                $results = $this->pacienteModel->buscarPorNombreCompleto($term);
                return ['pacientes' => $results];

            case 'obtener_rh_mas_comun':
                $pacientes = $this->pacienteModel->obtenerTodos();
                if (empty($pacientes)) {
                    return ['rh_comun' => '—'];
                }
                $counts = [];
                foreach ($pacientes as $p) {
                    $rh = $p['tipo_sangre'] ?? '';
                    if ($rh) {
                        $counts[$rh] = ($counts[$rh] ?? 0) + 1;
                    }
                }
                arsort($counts);
                $comun = array_key_first($counts) ?: '—';
                return ['rh_comun' => $comun];

            default:
                return ['error' => 'Herramienta no implementada.'];
        }
    }

    /**
     * Realiza la llamada HTTP a la API de Groq usando cURL.
     */
    private function makeRequest(array $messages, ?array $tools = null): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.2
        ];

        if ($tools !== null) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $ch = curl_init($this->apiUrl);
        if ($ch === false) {
            throw new Exception("No se pudo inicializar cURL.");
        }

        $jsonData = json_encode($payload);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            throw new Exception("Falla de red en cURL: " . $err);
        }

        if ($httpCode !== 200) {
            throw new Exception("Error en API de Groq (HTTP {$httpCode}): " . $response);
        }

        $data = json_decode($response, true);
        if ($data === null) {
            throw new Exception("Error al decodificar la respuesta JSON de Groq.");
        }

        return $data;
    }

    /**
     * Responde directamente desde la BD cuando la intención es inequívoca (evita respuestas inventadas por el LLM).
     */
    private function tryAnswerFromDatabase(string $normalized): ?string
    {
        $averageAgeAnswer = $this->tryAnswerAverageAgeQuestion($normalized);
        if ($averageAgeAnswer !== null) {
            return $averageAgeAnswer;
        }

        $metricAnswer = $this->tryAnswerMetricQuestion($normalized);
        if ($metricAnswer !== null) {
            return $metricAnswer;
        }

        if ($this->isRhCommonQuestion($normalized)) {
            $total = $this->pacienteModel->contarTodos();
            if ($total === 0) {
                return "No hay pacientes registrados en ClinicaApp, por lo que no puedo calcular el tipo de sangre más común.";
            }
            $rh = $this->executeTool('obtener_rh_mas_comun', [])['rh_comun'] ?? '—';
            return "Según los **{$total}** pacientes registrados en **ClinicaApp**, el grupo sanguíneo (RH) más común es **{$rh}**.";
        }

        $patientAnswer = $this->tryAnswerPatientQuestion($normalized);
        if ($patientAnswer !== null) {
            return $patientAnswer;
        }

        return null;
    }

    private function tryAnswerAverageAgeQuestion(string $normalized): ?string
    {
        if (!$this->isAverageAgeQuestion($normalized)) {
            return null;
        }

        $promedio = $this->pacienteModel->obtenerPromedioEdad();
        if ($promedio === null) {
            return 'No hay pacientes registrados en ClinicaApp, por lo que no puedo calcular el promedio de edad.';
        }

        $total = $this->pacienteModel->contarTodos();

        return "Según la base de datos de **ClinicaApp**, el promedio de edad entre los **{$total}** "
            . "pacientes registrados es de **{$promedio}** años.";
    }

    private function isAverageAgeQuestion(string $normalized): bool
    {
        return (bool) preg_match('/(promedio|media)\b/u', $normalized)
            && (bool) preg_match('/\bedad\b/u', $normalized)
            && (bool) preg_match('/(paciente|registr)/u', $normalized);
    }

    private function isStatisticQuestion(string $normalized): bool
    {
        return $this->isAverageAgeQuestion($normalized)
            || $this->isDistributionQuestion($normalized)
            || $this->isMetricCountQuestion($normalized)
            || $this->isObservationsCountQuestion($normalized)
            || $this->isObservationSearchCountQuestion($normalized)
            || $this->isRhCommonQuestion($normalized)
            || $this->extractRegistrationPeriodStart($normalized) !== null
            || $this->extractAgeThreshold($normalized) !== null;
    }

    private function tryAnswerPatientQuestion(string $normalized): ?string
    {
        if ($this->isStatisticQuestion($normalized) || $this->isDestructiveRequest($normalized)) {
            return null;
        }

        if (!$this->isPatientLookupQuestion($normalized) && !$this->isPatientFollowUp($normalized)) {
            return null;
        }

        $searchTerm = $this->extractSearchTermsFromMessage($normalized);

        if ($searchTerm !== '' && !$this->isOnlyPatientFieldTerms($searchTerm)) {
            return $this->formatPatientSearchResponse($searchTerm, $normalized);
        }

        $ctx = $this->chatContext->load();
        if ($ctx['last_patients'] !== []) {
            $pacientes = $this->refreshPatientsFromDatabase($ctx);
            return $this->formatPatientFromContext($pacientes, $normalized);
        }

        if ($ctx['last_search_term'] !== '') {
            return $this->formatPatientSearchResponse($ctx['last_search_term'], $normalized);
        }

        return 'Aún no he consultado ningún paciente en esta conversación. '
            . 'Indica el nombre o apellido (por ejemplo: «Juan López») y vuelve a preguntar.';
    }

    private function isDestructiveRequest(string $normalized): bool
    {
        if (preg_match(
            '/(borrar|eliminar|delete|drop|truncate|update|insertar|insert|modificar|alterar|cambiar|actualizar|crear|añadir|agregar)/u',
            $normalized
        )) {
            return true;
        }

        if (preg_match(
            '/(edita|edite|editar|corrige|corrija|corregir|corrigelo|corrigela|arregla|reemplaza|renombra|sustituye|reescrib)/u',
            $normalized
        )) {
            return true;
        }

        if (preg_match(
            '/(cambiar|poner|ponlo|dejar|dejalo|pasar|colocar).*(nombre|apellido|telefono|teléfono|correo|email|genero|género|sangre)/u',
            $normalized
        )) {
            return true;
        }

        return (bool) preg_match(
            '/(nombre|apellido).*(debe\s+ser|deberia|debería|escribe|escribir|es\s+en\s+lugar|en\s+vez\s+de)/u',
            $normalized
        );
    }

    private function readOnlyRejectionMessage(): string
    {
        return 'Lo siento, soy un asistente virtual de **solo lectura**. No estoy autorizado para realizar inserciones, modificaciones o eliminaciones de registros en la base de datos de pacientes.';
    }

    /**
     * Métricas: totales, filtros, distribuciones, periodos y observaciones.
     */
    private function tryAnswerMetricQuestion(string $normalized): ?string
    {
        $distributionAnswer = $this->tryAnswerDistributionQuestion($normalized);
        if ($distributionAnswer !== null) {
            return $distributionAnswer;
        }

        if ($this->isMetricCountQuestion($normalized) || $this->isObservationsCountQuestion($normalized)
            || $this->isObservationSearchCountQuestion($normalized)) {
            return $this->tryAnswerFilteredCountQuestion($normalized);
        }

        return null;
    }

    private function tryAnswerDistributionQuestion(string $normalized): ?string
    {
        if (!$this->isDistributionQuestion($normalized)) {
            return null;
        }

        if ($this->pacienteModel->contarTodos() === 0) {
            return 'No hay pacientes registrados en ClinicaApp para generar una distribución.';
        }

        $porGenero = (bool) preg_match('/(genero|género|sexo)/u', $normalized);
        $porSangre = (bool) preg_match('/(sangre|rh|sangu[ií]neo)/u', $normalized);

        if ($porGenero && !$porSangre) {
            return $this->formatDistributionResponse('género', $this->pacienteModel->obtenerDistribucionGenero());
        }
        if ($porSangre && !$porGenero) {
            return $this->formatDistributionResponse('grupo sanguíneo (RH)', $this->pacienteModel->obtenerDistribucionTipoSangre());
        }

        $genero = $this->formatDistributionResponse('género', $this->pacienteModel->obtenerDistribucionGenero());
        $sangre = $this->formatDistributionResponse('grupo sanguíneo (RH)', $this->pacienteModel->obtenerDistribucionTipoSangre());

        return "Según **ClinicaApp**:\n\n{$genero}\n\n{$sangre}";
    }

    /**
     * @param array<string, int> $distribucion
     */
    private function formatDistributionResponse(string $etiqueta, array $distribucion): string
    {
        if ($distribucion === []) {
            return "Sin datos de {$etiqueta}.";
        }

        $lineas = ["**Distribución por {$etiqueta}:**"];
        foreach ($distribucion as $valor => $cantidad) {
            $valorSafe = htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
            $lineas[] = "- **{$valorSafe}**: {$cantidad}";
        }

        return implode("\n", $lineas);
    }

    private function isDistributionQuestion(string $normalized): bool
    {
        return (bool) preg_match(
            '/(distribuci[oó]n|desglose|desglosa|cuantos\s+de\s+cada|cada\s+tipo|por\s+cada)/u',
            $normalized
        ) && (bool) preg_match('/(genero|género|sexo|sangre|rh|sangu[ií]neo|paciente)/u', $normalized);
    }

    private function tryAnswerFilteredCountQuestion(string $normalized): ?string
    {
        $bloodType = $this->extractBloodTypeFilter($normalized);
        $genero = $this->extractGenderFilter($normalized);

        if ($bloodType !== null && $genero !== null && $this->isMetricCountQuestion($normalized)) {
            $count = $this->pacienteModel->contarConFiltros($genero, $bloodType);
            return $this->formatMetricCountResponse(
                $count,
                "de género **{$genero}** y grupo sanguíneo **{$bloodType}**"
            );
        }

        $periodo = $this->extractRegistrationPeriodStart($normalized);
        if ($periodo !== null && $this->isMetricCountQuestion($normalized)) {
            $count = $this->pacienteModel->contarRegistradosDesde($periodo);
            $etiquetaPeriodo = $this->describeRegistrationPeriod($normalized);
            return $this->formatMetricCountResponse($count, "registrados {$etiquetaPeriodo}");
        }

        $umbralEdad = $this->extractAgeThreshold($normalized);
        if ($umbralEdad !== null) {
            $count = $this->pacienteModel->contarPorEdad($umbralEdad['op'], $umbralEdad['age']);
            $desc = $umbralEdad['op'] === 'gte'
                ? "de **{$umbralEdad['age']}** años o más"
                : "menores de **{$umbralEdad['age']}** años";
            return $this->formatMetricCountResponse($count, $desc);
        }

        $terminoObs = $this->extractObservationSearchTerm($normalized);
        if ($terminoObs !== null) {
            $count = $this->pacienteModel->contarObservacionesContienen($terminoObs);
            $termSafe = htmlspecialchars($terminoObs, ENT_QUOTES, 'UTF-8');
            return $this->formatMetricCountResponse($count, "con «{$termSafe}» en observaciones o notas");
        }

        if ($bloodType !== null) {
            $count = $this->pacienteModel->contarPorTipoSangre($bloodType);
            return $this->formatMetricCountResponse($count, "con grupo sanguíneo **{$bloodType}**");
        }

        if ($genero !== null) {
            $count = $this->pacienteModel->contarPorGenero($genero);
            return $this->formatMetricCountResponse($count, "de género **{$genero}**");
        }

        if ($this->isObservationsCountQuestion($normalized)) {
            $count = $this->pacienteModel->contarConObservaciones();
            return $this->formatMetricCountResponse($count, 'con observaciones o notas clínicas registradas');
        }

        if ($this->isPatientTotalCountQuestion($normalized)) {
            $total = $this->pacienteModel->contarTodos();
            return $this->formatMetricCountResponse($total, 'en total');
        }

        return null;
    }

    private function extractRegistrationPeriodStart(string $normalized): ?string
    {
        $hoy = new \DateTimeImmutable('today');

        if (preg_match('/\bhoy\b/u', $normalized)) {
            return $hoy->format('Y-m-d');
        }
        if (preg_match('/(este\s+mes|mes\s+actual|en\s+el\s+mes)/u', $normalized)) {
            return $hoy->modify('first day of this month')->format('Y-m-d');
        }
        if (preg_match('/(esta\s+semana|semana\s+actual|en\s+la\s+semana)/u', $normalized)) {
            return $hoy->modify('monday this week')->format('Y-m-d');
        }

        return null;
    }

    private function describeRegistrationPeriod(string $normalized): string
    {
        if (preg_match('/\bhoy\b/u', $normalized)) {
            return '**hoy**';
        }
        if (preg_match('/(esta\s+semana|semana\s+actual)/u', $normalized)) {
            return '**esta semana**';
        }
        if (preg_match('/(este\s+mes|mes\s+actual)/u', $normalized)) {
            return '**este mes**';
        }

        return 'en el periodo indicado';
    }

    /**
     * @return array{op: string, age: int}|null
     */
    private function extractAgeThreshold(string $normalized): ?array
    {
        if (preg_match('/mayores?\s+de\s+(\d{1,3})\b/u', $normalized, $m)) {
            return ['op' => 'gte', 'age' => (int) $m[1]];
        }
        if (preg_match('/menores?\s+de\s+(\d{1,3})\b/u', $normalized, $m)) {
            return ['op' => 'lt', 'age' => (int) $m[1]];
        }
        if (preg_match('/\b(\d{1,3})\s+a[nñ]os?\s+o\s+m[aá]s/u', $normalized, $m)) {
            return ['op' => 'gte', 'age' => (int) $m[1]];
        }

        return null;
    }

    private function isObservationSearchCountQuestion(string $normalized): bool
    {
        return (bool) preg_match('/(cuantos|cuántos|cantidad|hay)\b/u', $normalized)
            && $this->extractObservationSearchTerm($normalized) !== null;
    }

    private function extractObservationSearchTerm(string $normalized): ?string
    {
        if (preg_match('/alergia\s+(?:a|al|de)\s+(.+?)(?:\?|$)/u', $normalized, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/observacion(?:es)?\s+(?:con|que\s+contienen|que\s+mencionen)\s+(.+?)(?:\?|$)/u', $normalized, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/notas?\s+con\s+(.+?)(?:\?|$)/u', $normalized, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function formatMetricCountResponse(int $count, string $criterio): string
    {
        $etiqueta = $count === 1 ? 'paciente registrado' : 'pacientes registrados';
        return "Según la base de datos de **ClinicaApp**, hay **{$count}** {$etiqueta} {$criterio}.";
    }

    private function isMetricCountQuestion(string $normalized): bool
    {
        return (bool) preg_match('/(cuantos|cuántos|cantidad|numero|número|cuenta|hay|existen?)\b/u', $normalized)
            && (bool) preg_match('/(paciente|registr)/u', $normalized);
    }

    private function isObservationsCountQuestion(string $normalized): bool
    {
        return (bool) preg_match('/(cuantos|cuántos|cantidad|hay)\b/u', $normalized)
            && (bool) preg_match('/(observacion|observación|nota[s]?|alergia[s]?|comentario[s]?)/u', $normalized);
    }

    private function isPatientTotalCountQuestion(string $normalized): bool
    {
        if ($this->extractBloodTypeFilter($normalized) !== null
            || $this->extractGenderFilter($normalized) !== null
            || $this->extractRegistrationPeriodStart($normalized) !== null
            || $this->extractAgeThreshold($normalized) !== null
            || $this->extractObservationSearchTerm($normalized) !== null) {
            return false;
        }

        return $this->isMetricCountQuestion($normalized);
    }

    private function extractGenderFilter(string $normalized): ?string
    {
        if (preg_match('/(femenin[oa]s*|mujeres?)\b/u', $normalized)) {
            return 'Femenino';
        }
        if (preg_match('/(masculin[oa]s*|hombres?)\b/u', $normalized)) {
            return 'Masculino';
        }
        if (preg_match('/(prefiero\s+no\s+decir|no\s+binario|no\s+especifica)/u', $normalized)) {
            return 'Prefiero no decir';
        }
        if (preg_match('/\botros?\b/u', $normalized) && !preg_match('/\b(masculin|femenin|hombre|mujer)\b/u', $normalized)) {
            return 'Otro';
        }

        return null;
    }

    private function extractBloodTypeFilter(string $normalized): ?string
    {
        if (!preg_match('/(AB\+|AB-|O\+|O-|A\+|A-|B\+|B-)/i', $normalized, $match)) {
            return null;
        }

        $map = [
            'AB+' => 'AB+', 'AB-' => 'AB-',
            'A+'  => 'A+',  'A-'  => 'A-',
            'B+'  => 'B+',  'B-'  => 'B-',
            'O+'  => 'O+',  'O-'  => 'O-',
        ];

        $key = strtoupper($match[1]);

        return $map[$key] ?? null;
    }

    private function isRhCommonQuestion(string $normalized): bool
    {
        $mentionsBlood = (bool) preg_match(
            '/\b(rh|tipo[s]?\s+de\s+sangre|grupo[s]?\s+sangu[ií]neo[s]?|sangu[ií]neo)\b/u',
            $normalized
        );
        $mentionsFrequency = (bool) preg_match(
            '/(com[uúi][nm]|frecuente|mas\s+com[uúi][nm]|m[aá]s\s+com[uúi][nm]|mayor|predomina|popular|estad[ií]stica|distribuci[oó]n)/u',
            $normalized
        );

        return ($mentionsBlood && $mentionsFrequency)
            || (bool) preg_match('/\brh\b.*\b(mas|m[aá]s)\b/u', $normalized)
            || (bool) preg_match('/\b(cual|cu[aá]l|que|qué)\b.*\b(rh|sangre|sangu[ií]neo)\b/u', $normalized);
    }

    private function isPatientLookupQuestion(string $normalized): bool
    {
        return (bool) preg_match(
            '/(telefono|teléfono|correo|email|e-mail|contacto|celular|llamar|whatsapp|genero|género|sexo|'
            . 'paciente|buscar|busca|quien\s+es|quién\s+es|datos\s+de|informaci[oó]n\s+de)/u',
            $normalized
        ) || $this->isRegistrationDateQuestion($normalized) || $this->isBirthDateQuestion($normalized)
            || $this->isPatientNameFieldQuestion($normalized);
    }

    private function isPatientNameFieldQuestion(string $normalized): bool
    {
        return (bool) preg_match(
            '/(apellido|nombre(\s+completo)?|como\s+se\s+llama|nombres?\s+de\s+pila)/u',
            $normalized
        );
    }

    private function isBirthDateQuestion(string $normalized): bool
    {
        return (bool) preg_match(
            '/(fecha\s+de\s+nacimiento|cuando\s+naci[oó]?|dia\s+de\s+nacimiento|d[ií]a\s+que\s+naci|'
            . 'nacimient|\bnaci[oó]\b)/u',
            $normalized
        ) && !$this->isRegistrationDateQuestion($normalized);
    }

    private function isRegistrationDateQuestion(string $normalized): bool
    {
        return (bool) preg_match(
            '/(registrad[oa]s?|inscrit[oa]s?|fecha\s+de\s+(registro|alta)|cuando\s+(fue|se\s+registr)|'
            . 'dia\s+de\s+registro|d[ií]a\s+de\s+alta)/u',
            $normalized
        );
    }

    private function isPatientFollowUp(string $normalized): bool
    {
        $hasAttribute = (bool) preg_match(
            '/(telefono|teléfono|correo|email|genero|género|sexo|rh|sangre|contacto|celular|whatsapp|registrad|naci)/u',
            $normalized
        );
        $hasReference = (bool) preg_match('/(\bsu\b|\bsus\b|\bdel\s+paciente\b|\bel\s+paciente\b|^y\s+)/u', $normalized);

        return $this->isRegistrationDateQuestion($normalized)
            || $this->isBirthDateQuestion($normalized)
            || $this->isPatientNameFieldQuestion($normalized)
            || ($hasAttribute && ($hasReference || $this->isOnlyPatientFieldTerms($this->extractSearchTermsFromMessage($normalized))));
    }

    /**
     * @return list<string>
     */
    private function getAttributeOnlyWords(): array
    {
        return [
            'genero', 'género', 'sexo', 'rh', 'sangre', 'telefono', 'teléfono', 'correo', 'email',
            'e-mail', 'contacto', 'celular', 'whatsapp', 'paciente', 'pacientes',
            'registrado', 'registrada', 'registrados', 'registradas', 'registro', 'inscrito', 'inscrita',
            'cuando', 'fue', 'fecha', 'alta',
            'nacio', 'nació', 'nacimiento', 'nacida', 'nacido', 'nacer',
            'apellido', 'apellidos', 'nombre', 'nombres', 'cuales', 'cuáles', 'son', 'llama', 'pila',
        ];
    }

    private function isOnlyPatientFieldTerms(string $searchTerm): bool
    {
        if ($searchTerm === '') {
            return false;
        }

        $words = preg_split('/\s+/u', trim($searchTerm), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || $words === []) {
            return false;
        }

        $fieldWords = array_merge($this->getAttributeOnlyWords(), [
            'apellido', 'apellidos', 'nombre', 'nombres', 'cuales', 'cuáles', 'son', 'cual', 'cuál',
            'completo', 'pila', 'llama',
        ]);

        foreach ($words as $word) {
            if (!in_array($word, $fieldWords, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function getSearchStopWords(): array
    {
        return [
            'el', 'la', 'de', 'de:', 'del', 'los', 'las', 'un', 'una', 'y', 'o', 'a', 'en', 'por', 'para', 'con',
            'dime', 'dame', 'cual', 'cuál', 'que', 'qué', 'es', 'al', 'se', 'su', 'sus', 'mi', 'me',
            'telefono', 'teléfono', 'correo', 'email', 'e-mail', 'contacto', 'celular', 'llamar', 'whatsapp',
            'paciente', 'pacientes', 'registro', 'quien', 'quién', 'busca', 'buscar', 'favor', 'porfavor', 'hola',
            'señor', 'senor', 'señora', 'senora', 'sr', 'sra', 'don', 'doña', 'dona',
            'datos', 'informacion', 'información', 'necesito', 'quiero', 'dame', 'muestrame', 'muéstrame',
            'genero', 'género', 'sexo', 'rh', 'sangre', 'mas', 'más', 'comun', 'común', 'frecuente',
            'registrado', 'registrada', 'registrados', 'inscrito', 'inscrita', 'cuando', 'fue', 'fecha', 'alta',
            'nacio', 'nació', 'nacimiento', 'nacida', 'nacido', 'nacer',
            'apellido', 'apellidos', 'nombre', 'nombres', 'cuales', 'cuáles', 'son', 'los', 'las',
            'promedio', 'media', 'edad', 'anos', 'años', 'edita', 'edite', 'editar', 'corrige', 'corregir',
            'corrigelo', 'escribe', 'escribir', 'lugar', 'vez', 'debe', 'deberia', 'debería',
        ];
    }

    /**
     * @param array{last_search_term: string, last_patients: list<array<string, mixed>>} $ctx
     * @return list<array<string, mixed>>
     */
    private function refreshPatientsFromDatabase(array $ctx): array
    {
        if ($ctx['last_search_term'] !== '') {
            $res = $this->executeTool('buscar_paciente_por_nombre', ['nombre' => $ctx['last_search_term']]);
            $pacientes = $res['pacientes'] ?? [];
            if ($pacientes !== []) {
                $this->chatContext->rememberPatients($pacientes, $ctx['last_search_term']);
                return $pacientes;
            }
        }

        return $ctx['last_patients'];
    }

    private function formatDateField(string $dateValue): string
    {
        $dateValue = trim($dateValue);
        if ($dateValue === '') {
            return '—';
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateValue)
            ?: \DateTimeImmutable::createFromFormat('Y-m-d', substr($dateValue, 0, 10));
        if ($dt !== false) {
            return $dt->format('d/m/Y');
        }

        $ts = strtotime($dateValue);
        if ($ts === false) {
            return htmlspecialchars($dateValue, ENT_QUOTES, 'UTF-8');
        }

        return date('d/m/Y', $ts);
    }

    private function extractSearchTermsFromMessage(string $normalized): string
    {
        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        $stopWords = $this->getSearchStopWords();
        $cleanWords = [];

        foreach ($words as $word) {
            if (mb_strlen($word) >= 2 && !in_array($word, $stopWords, true)) {
                $cleanWords[] = $word;
            }
        }

        return implode(' ', $cleanWords);
    }

    private function formatPatientSearchResponse(string $searchTerm, string $normalized): string
    {
        $res = $this->executeTool('buscar_paciente_por_nombre', ['nombre' => $searchTerm]);
        $pacientes = $res['pacientes'] ?? [];

        if ($pacientes === []) {
            return 'No encontré ningún paciente registrado en **ClinicaApp** con el criterio «'
                . htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') . '».';
        }

        $this->chatContext->rememberPatients($pacientes, $searchTerm);

        return $this->formatPatientFromContext($pacientes, $normalized);
    }

    /**
     * @param list<array<string, mixed>> $pacientes
     */
    private function formatPatientFromContext(array $pacientes, string $normalized): string
    {
        $wantsPhone        = (bool) preg_match('/(telefono|teléfono|celular|llamar|whatsapp)/u', $normalized);
        $wantsEmail        = (bool) preg_match('/(correo|email|e-mail)/u', $normalized);
        $wantsGender       = (bool) preg_match('/(genero|género|sexo)/u', $normalized);
        $wantsRh           = (bool) preg_match('/\b(rh|tipo[s]?\s+de\s+sangre|grupo[s]?\s+sangu[ií]neo)\b/u', $normalized);
        $wantsRegisteredAt = $this->isRegistrationDateQuestion($normalized);
        $wantsBirthDate    = $this->isBirthDateQuestion($normalized);
        $wantsApellido     = (bool) preg_match('/apellido/u', $normalized);
        $wantsNombre       = (bool) preg_match('/\bnombre/u', $normalized) && !$wantsApellido;
        $wantsFullName     = (bool) preg_match('/(nombre\s+completo|como\s+se\s+llama)/u', $normalized);
        $focused           = $wantsPhone || $wantsEmail || $wantsGender || $wantsRh || $wantsRegisteredAt
            || $wantsBirthDate || $wantsApellido || $wantsNombre || $wantsFullName;

        $response = $focused
            ? "Según los registros de **ClinicaApp**:\n\n"
            : "He encontrado el siguiente paciente en **ClinicaApp**:\n\n";

        foreach ($pacientes as $p) {
            $nombre = htmlspecialchars((string) ($p['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
            $apellido = htmlspecialchars((string) ($p['apellido'] ?? ''), ENT_QUOTES, 'UTF-8');
            $nombreCompleto = htmlspecialchars(trim($p['nombre'] . ' ' . $p['apellido']), ENT_QUOTES, 'UTF-8');
            $telefono = htmlspecialchars((string) ($p['telefono'] ?? ''), ENT_QUOTES, 'UTF-8');
            $email = htmlspecialchars((string) ($p['email'] ?? ''), ENT_QUOTES, 'UTF-8');
            $rh = htmlspecialchars((string) ($p['tipo_sangre'] ?? ''), ENT_QUOTES, 'UTF-8');
            $genero = htmlspecialchars((string) ($p['genero'] ?? ''), ENT_QUOTES, 'UTF-8');
            $otherFields = !$wantsPhone && !$wantsEmail && !$wantsGender && !$wantsRh && !$wantsRegisteredAt
                && !$wantsBirthDate && !$wantsApellido && !$wantsNombre && !$wantsFullName;

            if ($focused && $wantsApellido && $otherFields) {
                $response .= "👤 **{$nombreCompleto}** — Apellido(s): `{$apellido}`\n\n";
                continue;
            }
            if ($focused && ($wantsNombre || $wantsFullName) && $otherFields) {
                $response .= "👤 Nombre: `{$nombre}` — Apellido(s): `{$apellido}`\n\n";
                continue;
            }

            if ($focused && $wantsPhone && $otherFields) {
                $response .= "👤 **{$nombreCompleto}** — 📞 Teléfono: `{$telefono}`\n\n";
                continue;
            }
            if ($focused && $wantsEmail && $otherFields) {
                $response .= "👤 **{$nombreCompleto}** — ✉ Correo: `{$email}`\n\n";
                continue;
            }
            if ($focused && $wantsGender && $otherFields) {
                $response .= "👤 **{$nombreCompleto}** — 🧬 Género: `{$genero}`\n\n";
                continue;
            }
            if ($focused && $wantsRh && $otherFields) {
                $response .= "👤 **{$nombreCompleto}** — 🩸 Grupo RH: `{$rh}`\n\n";
                continue;
            }
            if ($focused && $wantsRegisteredAt && $otherFields) {
                $fechaRegistro = $this->formatDateField((string) ($p['created_at'] ?? ''));
                $response .= "👤 **{$nombreCompleto}** — 📆 Registrado el: `{$fechaRegistro}`\n\n";
                continue;
            }
            if ($focused && $wantsBirthDate && $otherFields) {
                $fechaNacimiento = $this->formatDateField((string) ($p['fecha_nacimiento'] ?? ''));
                $response .= "👤 **{$nombreCompleto}** — 🎂 Nacido el: `{$fechaNacimiento}`\n\n";
                continue;
            }

            $fechaRegistro = $this->formatDateField((string) ($p['created_at'] ?? ''));
            $fechaNacimiento = $this->formatDateField((string) ($p['fecha_nacimiento'] ?? ''));
            $response .= "👤 **{$nombreCompleto}**\n";
            $response .= "📞 Teléfono: `{$telefono}`\n";
            $response .= "✉ Correo: `{$email}`\n";
            $response .= "🩸 Grupo RH: `{$rh}`\n";
            $response .= "🧬 Género: `{$genero}`\n";
            $response .= "🎂 Nacido el: `{$fechaNacimiento}`\n";
            $response .= "📆 Registrado el: `{$fechaRegistro}`\n\n";
        }

        return rtrim($response);
    }

    /**
     * Simulación local del chat (Mock) para pruebas sin claves API y sin internet.
     */
    private function chatMock(string $normalized): string
    {
        $patientAnswer = $this->tryAnswerPatientQuestion($normalized);
        if ($patientAnswer !== null) {
            return $patientAnswer;
        }

        return 'Hola, soy el asistente virtual de ClinicaApp. Puedo informar totales, promedios, distribuciones, '
            . 'conteos por género/RH, registros de hoy o este mes, edad mínima y datos de contacto por nombre.';
    }
}

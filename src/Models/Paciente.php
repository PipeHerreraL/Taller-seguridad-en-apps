<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

/**
 * Modelo Paciente
 * Realiza las operaciones CRUD contra la base de datos usando sentencias preparadas.
 */
class Paciente
{
    /** @var list<string> */
    private const TIPOS_SANGRE = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    /** @var list<string> */
    private const GENEROS = ['Masculino', 'Femenino', 'Otro', 'Prefiero no decir'];

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        // Permite inyectar la conexión para unit testing (SQLite en memoria)
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    /**
     * Inserta un nuevo paciente utilizando sentencias preparadas para mitigar SQL Injection.
     */
    public function crear(array $data): bool
    {
        $sql = 'INSERT INTO pacientes 
                    (nombre, apellido, email, password_hash, fecha_nacimiento, telefono, tipo_sangre, genero, observaciones) 
                VALUES 
                    (:nombre, :apellido, :email, :password_hash, :fecha_nacimiento, :telefono, :tipo_sangre, :genero, :observaciones)';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre' => $data['nombre'],
            ':apellido' => $data['apellido'],
            ':email' => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':fecha_nacimiento' => $data['fecha_nacimiento'],
            ':telefono' => $data['telefono'],
            ':tipo_sangre' => $data['tipo_sangre'],
            ':genero' => $data['genero'],
            ':observaciones' => $data['observaciones'] ?? null,
        ]);
    }

    /**
     * Retorna todos los pacientes ordenados por fecha de creación.
     */
    public function obtenerTodos(): array
    {
        $sql = 'SELECT * FROM pacientes ORDER BY created_at DESC';
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un paciente por su correo electrónico usando sentencias preparadas.
     */
    public function buscarPorEmail(string $email): array|false
    {
        $sql = 'SELECT * FROM pacientes WHERE email = :email';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina un paciente por su ID usando sentencias preparadas.
     */
    public function eliminar(int $id): bool
    {
        $sql = 'DELETE FROM pacientes WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Cuenta el total de pacientes en la base de datos.
     */
    public function contarTodos(): int
    {
        $sql = 'SELECT COUNT(*) FROM pacientes';
        $stmt = $this->db->query($sql);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta pacientes con un tipo de sangre específico (valor validado contra ENUM).
     */
    public function contarPorTipoSangre(string $tipoSangre): int
    {
        if (! in_array($tipoSangre, self::TIPOS_SANGRE, true)) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) FROM pacientes WHERE tipo_sangre = :tipo_sangre';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tipo_sangre' => $tipoSangre]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta pacientes por género (valor validado contra ENUM).
     */
    public function contarPorGenero(string $genero): int
    {
        if (! in_array($genero, self::GENEROS, true)) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) FROM pacientes WHERE genero = :genero';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':genero' => $genero]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Cuenta pacientes con observaciones registradas (campo no vacío).
     */
    public function contarConObservaciones(): int
    {
        $sql = "SELECT COUNT(*) FROM pacientes WHERE observaciones IS NOT NULL AND TRIM(observaciones) <> ''";
        $stmt = $this->db->query($sql);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Calcula la edad promedio en años de los pacientes registrados (compatible MySQL y SQLite).
     */
    public function obtenerPromedioEdad(): ?float
    {
        $sql = 'SELECT fecha_nacimiento FROM pacientes';
        $stmt = $this->db->query($sql);
        $fechas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($fechas === []) {
            return null;
        }

        $hoy = new \DateTimeImmutable('today');
        $sumaEdades = 0;
        $validas = 0;

        foreach ($fechas as $fecha) {
            $nacimiento = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $fecha);
            if ($nacimiento === false) {
                continue;
            }
            $sumaEdades += $hoy->diff($nacimiento)->y;
            $validas++;
        }

        if ($validas === 0) {
            return null;
        }

        return round($sumaEdades / $validas, 1);
    }

    /**
     * Pacientes registrados desde una fecha (YYYY-MM-DD), inclusive.
     */
    public function contarRegistradosDesde(string $fechaInicio): int
    {
        $sql = 'SELECT COUNT(*) FROM pacientes WHERE DATE(created_at) >= :fecha';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':fecha' => $fechaInicio]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Pacientes cuya edad cumple el umbral (gte = mayor o igual, lt = menor que).
     */
    public function contarPorEdad(string $operador, int $edad): int
    {
        if ($edad < 0 || $edad > 150) {
            return 0;
        }

        $sql = 'SELECT fecha_nacimiento FROM pacientes';
        $stmt = $this->db->query($sql);
        $fechas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $hoy = new \DateTimeImmutable('today');
        $total = 0;

        foreach ($fechas as $fecha) {
            $nacimiento = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $fecha);
            if ($nacimiento === false) {
                continue;
            }
            $anos = $hoy->diff($nacimiento)->y;
            if ($operador === 'gte' && $anos >= $edad) {
                $total++;
            } elseif ($operador === 'lt' && $anos < $edad) {
                $total++;
            }
        }

        return $total;
    }

    /**
     * Cuenta con filtros opcionales de género y tipo de sangre (ambos validados).
     */
    public function contarConFiltros(?string $genero = null, ?string $tipoSangre = null): int
    {
        if ($genero !== null && ! in_array($genero, self::GENEROS, true)) {
            $genero = null;
        }
        if ($tipoSangre !== null && ! in_array($tipoSangre, self::TIPOS_SANGRE, true)) {
            $tipoSangre = null;
        }

        $sql = 'SELECT COUNT(*) FROM pacientes WHERE 1=1';
        $params = [];

        if ($genero !== null) {
            $sql .= ' AND genero = :genero';
            $params[':genero'] = $genero;
        }
        if ($tipoSangre !== null) {
            $sql .= ' AND tipo_sangre = :tipo_sangre';
            $params[':tipo_sangre'] = $tipoSangre;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Pacientes cuya observación contiene un término (búsqueda parcial segura).
     */
    public function contarObservacionesContienen(string $termino): int
    {
        $termino = trim($termino);
        if ($termino === '') {
            return 0;
        }

        $sql = 'SELECT COUNT(*) FROM pacientes WHERE observaciones LIKE :term';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':term' => '%'.$this->escaparLike($termino).'%']);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<string, int> género => cantidad
     */
    public function obtenerDistribucionGenero(): array
    {
        $sql = 'SELECT genero, COUNT(*) AS total FROM pacientes GROUP BY genero ORDER BY total DESC';
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['genero']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * @return array<string, int> tipo_sangre => cantidad
     */
    public function obtenerDistribucionTipoSangre(): array
    {
        $sql = 'SELECT tipo_sangre, COUNT(*) AS total FROM pacientes GROUP BY tipo_sangre ORDER BY total DESC';
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['tipo_sangre']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * Busca pacientes por coincidencia parcial en nombre o apellido de forma segura.
     * Si el criterio tiene varias palabras (ej. "juan lopez"), cada una debe aparecer
     * en el nombre, el apellido o el nombre completo concatenado.
     * Retorna únicamente información pública/no sensible (previene fuga de credenciales).
     */
    public function buscarPorNombreCompleto(string $nombre): array
    {
        $palabras = preg_split('/\s+/u', trim($nombre), -1, PREG_SPLIT_NO_EMPTY);
        if ($palabras === []) {
            return [];
        }

        $palabras = $this->filtrarPalabrasBusqueda($palabras);
        if ($palabras === []) {
            return [];
        }

        $sql = 'SELECT nombre, apellido, email, telefono, tipo_sangre, genero, observaciones, fecha_nacimiento, created_at 
                FROM pacientes WHERE 1=1';
        $params = [];

        foreach ($palabras as $i => $palabra) {
            $like = '%'.$this->escaparLike($palabra).'%';
            $sql .= " AND (nombre LIKE :n{$i} OR apellido LIKE :a{$i} OR CONCAT(nombre, ' ', apellido) LIKE :f{$i})";
            $params[":n{$i}"] = $like;
            $params[":a{$i}"] = $like;
            $params[":f{$i}"] = $like;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Elimina palabras vacías, muy cortas o de relleno comunes en preguntas al chat.
     *
     * @param  list<string>  $palabras
     * @return list<string>
     */
    private function filtrarPalabrasBusqueda(array $palabras): array
    {
        $stopWords = [
            'el', 'la', 'de', 'del', 'los', 'las', 'un', 'una', 'y', 'o', 'a', 'en',
            'por', 'para', 'con', 'sin', 'es', 'al', 'se', 'su', 'sus', 'mi', 'me',
            'dime', 'dame', 'cual', 'cuál', 'que', 'qué', 'quien', 'quién', 'como', 'cómo',
            'telefono', 'teléfono', 'correo', 'email', 'contacto', 'paciente', 'registro',
            'señor', 'senor', 'señora', 'senora', 'sr', 'sra', 'don', 'doña', 'dona',
            'busca', 'buscar', 'favor', 'hola', 'porfavor',
        ];

        $filtradas = [];
        foreach (array_slice($palabras, 0, 10) as $palabra) {
            $normalizada = mb_strtolower($palabra);
            if (mb_strlen($normalizada) >= 2 && ! in_array($normalizada, $stopWords, true)) {
                $filtradas[] = $palabra;
            }
        }

        return $filtradas;
    }

    private function escaparLike(string $valor): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);
    }
}

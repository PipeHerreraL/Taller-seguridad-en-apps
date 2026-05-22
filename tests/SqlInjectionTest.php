<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Paciente;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * SqlInjectionTest
 * Verifica que PDO con sentencias preparadas previene SQL Injection.
 * Usa SQLite en memoria — no requiere MySQL.
 */
class SqlInjectionTest extends TestCase
{
    private PDO $db;

    private Paciente $paciente;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->db->exec('
            CREATE TABLE pacientes (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre           TEXT NOT NULL,
                apellido         TEXT NOT NULL,
                email            TEXT NOT NULL UNIQUE,
                password_hash    TEXT NOT NULL,
                fecha_nacimiento TEXT NOT NULL,
                telefono         TEXT NOT NULL,
                tipo_sangre      TEXT NOT NULL,
                genero           TEXT NOT NULL,
                observaciones    TEXT,
                created_at       TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ');
        $this->db->exec("
            INSERT INTO pacientes
                (nombre, apellido, email, password_hash, fecha_nacimiento, telefono, tipo_sangre, genero)
            VALUES
                ('Admin', 'Sistema', 'admin@test.com', 'hash_seguro', '1990-01-01', '3000000000', 'O+', 'Masculino')
        ");
        $this->paciente = new Paciente($this->db);
    }

    public static function payloadProvider(): array
    {
        return [
            'or_tautology' => ["' OR 1=1 --"],
            'drop_table' => ["' ; DROP TABLE pacientes; --"],
            'stacked_select' => ["' ; SELECT * FROM pacientes; --"],
            'union_select' => ["' UNION SELECT 1,2,3,4,5,6,7,8,9,10 --"],
            'comment_bypass' => ["'/**/OR/**/1=1"],
            'or_one_equals_one' => ["' OR '1'='1"],
            'paren_bypass' => ["') OR ('1'='1"],
            'sleep_attack' => ["' AND (SELECT 1 FROM (SELECT(SLEEP(5)))x) --"],
            'empty_string_eq' => ["admin@test.com' OR '"],
        ];
    }

    // ── INSERT con payload ────────────────────────────────────
    #[Test]
    #[DataProvider('payloadProvider')]
    public function insert_con_payload_es_tratado_como_dato(string $payload): void
    {
        // El email debe ser único, por lo que usamos md5 del payload para evitar colisiones
        $uniqueEmail = md5($payload).'@test.com';

        $data = [
            'nombre' => $payload, // El payload va en el nombre
            'apellido' => 'Usuario',
            'email' => $uniqueEmail,
            'password_hash' => 'hash',
            'fecha_nacimiento' => '1990-01-01',
            'telefono' => '3000000000',
            'tipo_sangre' => 'O+',
            'genero' => 'Masculino',
            'observaciones' => 'Ninguna',
        ];
        $result = $this->paciente->crear($data);
        $this->assertTrue($result);
        // Validamos que se haya guardado literalmente el payload en la base de datos
        $record = $this->paciente->buscarPorEmail($uniqueEmail);
        $this->assertNotFalse($record);
        $this->assertEquals($payload, $record['nombre']);
    }

    // ── SELECT con payload ────────────────────────────────────
    #[Test]
    #[DataProvider('payloadProvider')]
    public function busqueda_por_email_con_payload_no_retorna_registros(string $payload): void
    {
        // Al buscar por un email que contiene el payload malicioso,
        // no debería retornar ningún registro (debe devolver false)
        // porque se trata como un valor de cadena literal, y no se ejecuta como SQL.
        $result = $this->paciente->buscarPorEmail($payload);
        $this->assertFalse($result);
    }

    // ── DELETE / Otros ────────────────────────────────────────
    #[Test]
    public function filter_input_rechaza_id_malicioso(): void
    {
        // Simulamos la entrada POST que validamos en el DeleteController
        $idMalicioso = '1 OR 1=1';
        $filtered = filter_var($idMalicioso, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $this->assertFalse($filtered);
    }

    #[Test]
    public function delete_con_id_entero_valido_funciona_correctamente(): void
    {
        // Obtenemos los pacientes para saber el ID del admin (que es 1)
        $result = $this->paciente->eliminar(1);
        $this->assertTrue($result);

        $this->assertEquals(0, $this->paciente->contarTodos());
    }

    #[Test]
    public function delete_con_id_cero_no_borra_nada(): void
    {
        $this->assertEquals(1, $this->paciente->contarTodos());

        $result = $this->paciente->eliminar(0);
        $this->assertTrue($result); // PDO indica éxito en la ejecución de la consulta

        // El admin debe seguir existiendo
        $this->assertEquals(1, $this->paciente->contarTodos());
    }

    #[Test]
    public function tabla_pacientes_sigue_existiendo_tras_payloads(): void
    {
        // Ejecutamos varios intentos de borrado de tabla
        $payload = "' ; DROP TABLE pacientes; --";

        // Simplemente ejecutamos una búsqueda con el payload
        $this->paciente->buscarPorEmail($payload);

        // Verificamos que la tabla pacientes sigue existiendo y podemos contar sus registros
        $count = $this->paciente->contarTodos();
        $this->assertEquals(1, $count);
    }
}

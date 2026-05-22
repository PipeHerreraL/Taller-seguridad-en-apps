<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\ChatContext;
use App\Helpers\GroqClient;
use App\Models\Paciente;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * ChatSecurityTest
 * Audita los controles de seguridad del asistente de chat y búsquedas asociadas.
 * Usa SQLite en memoria.
 */
class ChatSecurityTest extends TestCase
{
    private PDO $db;

    private Paciente $pacienteModel;

    private GroqClient $groqClient;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['clinica_chat']);

        // Crear base de datos SQLite en memoria
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Crear la tabla de pacientes
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

        // Insertar registros de prueba con credenciales sensibles
        $this->db->exec("
            INSERT INTO pacientes
                (nombre, apellido, email, password_hash, fecha_nacimiento, telefono, tipo_sangre, genero, observaciones)
            VALUES
                ('Juan', 'Perez', 'juan.perez@test.com', '\$2y$10\$SomeSecretHashStringThatMustNeverLeak', '1985-05-15', '3005551234', 'O+', 'Masculino', 'Ninguna'),
                ('Maria', 'Gomez', 'maria.gomez@test.com', '\$2y$10\$AnotherSecretHashValueShouldBeSafe', '1992-08-20', '3109998877', 'A-', 'Femenino', 'Alergia al gluten')
        ");

        $this->pacienteModel = new Paciente($this->db);

        // Forzar modo Mock estableciendo GROQ_API_KEY a 'mock' temporalmente
        $_ENV['GROQ_API_KEY'] = 'mock';
        $this->groqClient = new GroqClient($this->pacienteModel, new ChatContext);
    }

    #[Test]
    public function buscar_por_nombre_excluye_password_hash(): void
    {
        $resultados = $this->pacienteModel->buscarPorNombreCompleto('Juan');

        $this->assertNotEmpty($resultados);
        foreach ($resultados as $paciente) {
            $this->assertArrayNotHasKey('password_hash', $paciente, '¡Fuga de Seguridad! El hash de la contraseña está expuesto.');
            $this->assertArrayHasKey('nombre', $paciente);
            $this->assertArrayHasKey('telefono', $paciente);
            $this->assertArrayHasKey('email', $paciente);
        }
    }

    #[Test]
    public function buscar_por_nombre_multiples_palabras(): void
    {
        $this->db->exec("
            INSERT INTO pacientes
                (nombre, apellido, email, password_hash, fecha_nacimiento, telefono, tipo_sangre, genero, observaciones)
            VALUES
                ('Juan Felipe', 'Lopez Herrera', 'juan.lopez@test.com', '\$2y\$10\$Hash', '1990-01-01', '3201234567', 'B+', 'Masculino', NULL)
        ");

        $resultados = $this->pacienteModel->buscarPorNombreCompleto('juan lopez');

        $this->assertCount(1, $resultados);
        $this->assertSame('Juan Felipe', $resultados[0]['nombre']);
        $this->assertSame('Lopez Herrera', $resultados[0]['apellido']);
        $this->assertSame('3201234567', $resultados[0]['telefono']);
    }

    #[Test]
    public function chat_mantiene_contexto_del_paciente_en_seguimiento(): void
    {
        $this->db->exec("
            INSERT INTO pacientes
                (nombre, apellido, email, password_hash, fecha_nacimiento, telefono, tipo_sangre, genero, observaciones, created_at)
            VALUES
                ('Juan Felipe', 'Lopez Herrera', 'juan.lopez@test.com', '\$2y\$10\$Hash', '1999-02-02', '3201234567', 'B+', 'Masculino', NULL, '2026-05-21 10:30:00')
        ");

        $tel = $this->groqClient->chat('dime el telefono del señor juan lopez');
        $this->assertStringContainsString('3201234567', $tel);

        $email = $this->groqClient->chat('y su email');
        $this->assertStringContainsString('juan.lopez@test.com', $email);
        $this->assertStringNotContainsString('genero', mb_strtolower($email));

        $genero = $this->groqClient->chat('cual es el genero del paciente');
        $this->assertStringContainsString('Masculino', $genero);
        $this->assertStringNotContainsString('criterio «genero»', $genero);

        $registro = $this->groqClient->chat('cuando fue registrado');
        $this->assertStringContainsString('21/05/2026', $registro);
        $this->assertStringNotContainsString('2022-02-15', $registro);

        $nacimiento = $this->groqClient->chat('y cuando nacio');
        $this->assertStringContainsString('02/02/1999', $nacimiento);
        $this->assertStringNotContainsString('01/01/1990', $nacimiento);
        $this->assertStringNotContainsString('1990', $nacimiento);

        $apellidos = $this->groqClient->chat('cuales son los apellidos del paciente');
        $this->assertStringContainsString('Lopez Herrera', $apellidos);
        $this->assertStringNotContainsString('criterio «', $apellidos);
    }

    #[Test]
    public function asistente_encuentra_paciente_con_frase_natural(): void
    {
        $this->db->exec("
            INSERT INTO pacientes
                (nombre, apellido, email, password_hash, fecha_nacimiento, telefono, tipo_sangre, genero, observaciones)
            VALUES
                ('Juan Felipe', 'Lopez Herrera', 'juan.lopez@test.com', '\$2y\$10\$Hash', '1990-01-01', '3201234567', 'B+', 'Masculino', NULL)
        ");

        $respuesta = $this->groqClient->chat('dime el telefono del señor juan lopez');

        $this->assertStringContainsString('3201234567', $respuesta);
        $this->assertStringContainsString('Juan Felipe', $respuesta);
        $this->assertStringContainsString('ClinicaApp', $respuesta);
        $this->assertStringNotContainsString('consentimiento', mb_strtolower($respuesta));
        $this->assertStringNotContainsString('no puedo proporcionar', mb_strtolower($respuesta));
    }

    #[Test]
    public function buscar_paciente_con_sql_injection_no_falla_ni_altera_tablas(): void
    {
        $payloads = [
            "' OR 1=1 --",
            "'; DROP TABLE pacientes; --",
            "' UNION SELECT 1,2,3,4,5,6,7,8,9,10 --",
        ];

        foreach ($payloads as $payload) {
            // Intentar buscar usando el payload malicioso
            $resultados = $this->pacienteModel->buscarPorNombreCompleto($payload);

            // Debería retornar vacío porque no existe ningún paciente con ese nombre literal
            $this->assertEmpty($resultados, 'El payload de inyección retornó registros inesperados.');
        }

        // Verificar que la tabla y sus registros siguen intactos
        $total = $this->pacienteModel->contarTodos();
        $this->assertEquals(2, $total, 'La inyección alteró la cantidad de registros o borró la tabla.');
    }

    #[Test]
    public function asistente_rechaza_peticiones_de_escritura_o_modificacion(): void
    {
        $peticionesPeligrosas = [
            'eliminar la tabla de pacientes por favor',
            'borrar el registro de Juan Perez',
            'DELETE FROM pacientes WHERE id = 1',
            'actualizar el telefono de Maria a 1234567',
            'insertar un nuevo paciente llamado Pedro',
            'DROP TABLE pacientes',
            'edita el nombre del paciente Juan Lopez, su apellido se escribe López en lugar de Lopez, corrigelo',
        ];

        foreach ($peticionesPeligrosas as $peticion) {
            $respuesta = $this->groqClient->chat($peticion);

            $this->assertStringContainsString('solo lectura', mb_strtolower($respuesta));
            $this->assertStringContainsString('no estoy autorizado', mb_strtolower($respuesta));
        }
    }

    #[Test]
    public function promedio_edad_de_pacientes(): void
    {
        $this->db->exec('DELETE FROM pacientes');
        $this->db->exec("
            INSERT INTO pacientes
                (nombre, apellido, email, password_hash, fecha_nacimiento, telefono, tipo_sangre, genero)
            VALUES
                ('Ana', 'Ruiz', 'ana@test.com', '\$2y\$10\$Hash', '2000-01-01', '3001111111', 'O+', 'Femenino'),
                ('Luis', 'Diaz', 'luis@test.com', '\$2y\$10\$Hash', '1990-01-01', '3002222222', 'A+', 'Masculino')
        ");

        $respuesta = $this->groqClient->chat('cual es el promedio de edad de los pacientes');

        $this->assertStringContainsString('promedio de edad', mb_strtolower($respuesta));
        $this->assertStringContainsString('ClinicaApp', $respuesta);
        $this->assertStringNotContainsString('criterio «', $respuesta);
        $this->assertMatchesRegularExpression('/\*\*\d+(\.\d+)?\*\*\s+años/u', $respuesta);
    }

    #[Test]
    public function contar_pacientes_femeninos_sin_registros_femeninos(): void
    {
        $this->db->exec('DELETE FROM pacientes');
        $this->db->exec("
            INSERT INTO pacientes
                (nombre, apellido, email, password_hash, fecha_nacimiento, telefono, tipo_sangre, genero)
            VALUES
                ('Juan', 'Lopez', 'juan.lopez@test.com', '\$2y\$10\$Hash', '1999-02-02', '3201234567', 'O+', 'Masculino')
        ");

        $respuesta = $this->groqClient->chat('CUANTOS PACIENTES FEMENINAS HAY?');

        $this->assertStringContainsString('**0**', $respuesta);
        $this->assertStringContainsString('Femenino', $respuesta);
        $this->assertStringNotContainsString('**1**', $respuesta);
    }

    #[Test]
    public function contar_pacientes_masculinos(): void
    {
        $this->db->exec('DELETE FROM pacientes');
        $this->db->exec("
            INSERT INTO pacientes
                (nombre, apellido, email, password_hash, fecha_nacimiento, telefono, tipo_sangre, genero)
            VALUES
                ('Juan', 'Lopez', 'juan.lopez@test.com', '\$2y\$10\$Hash', '1999-02-02', '3201234567', 'O+', 'Masculino')
        ");

        $respuesta = $this->groqClient->chat('cuantos pacientes masculinos hay');

        $this->assertStringContainsString('**1**', $respuesta);
        $this->assertStringContainsString('Masculino', $respuesta);
    }

    #[Test]
    public function contar_pacientes_por_tipo_sangre(): void
    {
        $this->db->exec('DELETE FROM pacientes');
        $this->db->exec("
            INSERT INTO pacientes
                (nombre, apellido, email, password_hash, fecha_nacimiento, telefono, tipo_sangre, genero)
            VALUES
                ('Ana', 'Ruiz', 'ana.ruiz@test.com', '\$2y\$10\$Hash', '1990-01-01', '3001111111', 'O+', 'Femenino')
        ");

        $respuesta = $this->groqClient->chat('cuantos pacientes AB- hay registrados');

        $this->assertStringContainsString('**0**', $respuesta);
        $this->assertStringContainsString('AB-', $respuesta);
        $this->assertStringNotContainsString('**1** paciente registrado con grupo', $respuesta);
    }

    #[Test]
    public function metricas_avanzadas_implementadas(): void
    {
        $this->db->exec('DELETE FROM pacientes');
        $this->db->exec("
            INSERT INTO pacientes
                (nombre, apellido, email, password_hash, fecha_nacimiento, telefono, tipo_sangre, genero, observaciones, created_at)
            VALUES
                ('Maria', 'Gomez', 'maria@test.com', '\$2y\$10\$Hash', '1995-06-15', '3101111111', 'O+', 'Femenino', 'Alergia al gluten', datetime('now')),
                ('Juan', 'Lopez', 'juan@test.com', '\$2y\$10\$Hash', '1980-03-10', '3202222222', 'A+', 'Masculino', NULL, '2020-01-01 10:00:00')
        ");

        $distGenero = $this->groqClient->chat('distribucion por genero de pacientes');
        $this->assertStringContainsString('Femenino', $distGenero);
        $this->assertStringContainsString('Masculino', $distGenero);
        $this->assertStringContainsString('Distribución por género', $distGenero);

        $distRh = $this->groqClient->chat('cuantos de cada tipo de sangre hay');
        $this->assertStringContainsString('O+', $distRh);
        $this->assertStringContainsString('A+', $distRh);

        $hoy = $this->groqClient->chat('cuantos pacientes se registraron hoy');
        $this->assertStringContainsString('**1**', $hoy);
        $this->assertStringContainsString('hoy', mb_strtolower($hoy));

        $mayores = $this->groqClient->chat('cuantos pacientes mayores de 40 hay');
        $this->assertStringContainsString('**1**', $mayores);
        $this->assertStringContainsString('40', $mayores);

        $combinado = $this->groqClient->chat('cuantos pacientes femeninos con O+ hay registrados');
        $this->assertStringContainsString('**1**', $combinado);
        $this->assertStringContainsString('Femenino', $combinado);
        $this->assertStringContainsString('O+', $combinado);

        $alergia = $this->groqClient->chat('cuantos pacientes tienen alergia al gluten');
        $this->assertStringContainsString('**1**', $alergia);
        $this->assertStringContainsString('gluten', mb_strtolower($alergia));

        $combinadoCero = $this->groqClient->chat('cuantos pacientes femeninos con AB- hay');
        $this->assertStringContainsString('**0**', $combinadoCero);
    }

    #[Test]
    public function rh_mas_comun_usa_datos_de_la_base_de_datos(): void
    {
        $preguntas = [
            'cual es el rh mas comun',
            'cual es el tipo de sangre mas comuin',
        ];

        foreach ($preguntas as $pregunta) {
            $respuesta = $this->groqClient->chat($pregunta);
            $this->assertStringContainsString('ClinicaApp', $respuesta, "Falló para: {$pregunta}");
            $this->assertMatchesRegularExpression('/(O\+|A-|B\+|AB\+|O-|B-|AB-|A\+)/', $respuesta);
            $this->assertStringNotContainsString('Organización Mundial', $respuesta);
            $this->assertStringNotContainsString('7,7%', $respuesta);
        }
    }

    #[Test]
    public function asistente_responde_preguntas_de_lectura_correctamente(): void
    {
        // Preguntar por total de pacientes
        $resCount = $this->groqClient->chat('¿Cuántos pacientes hay registrados?');
        $this->assertStringContainsString('2', $resCount);

        // Preguntar por el tipo de sangre más común
        // O+ y A- tienen 1 cada uno. El algoritmo de Paciente->obtenerTodos() los ordena por fecha de creación desc
        // Maria (A-) es la más reciente, por lo que A- o O+ se calcula.
        $resRh = $this->groqClient->chat('¿Cuál es el tipo de sangre más común?');
        $this->assertStringContainsString('ClinicaApp', $resRh);
        $this->assertMatchesRegularExpression('/(O\+|A-|B\+|AB\+|O-|B-|AB-|A\+)/', $resRh);
        $this->assertStringNotContainsString('Organización Mundial', $resRh);

        // Preguntar por un paciente específico
        $resBuscar = $this->groqClient->chat('buscar paciente Juan');
        $this->assertStringContainsString('Juan Perez', $resBuscar);
        $this->assertStringContainsString('3005551234', $resBuscar);
        $this->assertStringContainsString('juan.perez@test.com', $resBuscar);
    }
}

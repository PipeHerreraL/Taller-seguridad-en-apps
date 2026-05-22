# Diagrama de Arquitectura y Clases — ClinicaApp

El siguiente diagrama representa la arquitectura Modelo-Vista-Controlador (MVC), componentes de infraestructura y el conjunto de pruebas del proyecto:

```mermaid
classDiagram
    direction TB

    %% ── Enrutamiento y Controladores ─────────────────────────────
    class Router {
        <<Script>>
        +router.php
    }
    class RegisterController {
        <<Script>>
        +RegisterController.php
    }
    class DeleteController {
        <<Script>>
        +DeleteController.php
    }

    %% ── Vistas ───────────────────────────────────────────────────
    class IndexView {
        <<View>>
        +index.php
    }
    class PacientesView {
        <<View>>
        +pacientes.php
    }

    %% ── Infraestructura ──────────────────────────────────────────
    class Database {
        <<Singleton>>
        -instance : Database$
        -connection : PDO
        -host : string
        -dbName : string
        -username : string
        -password : string
        -charset : string
        -__construct()
        -__clone()
        +getInstance() Database$
        +getConnection() PDO
        +__wakeup() void
    }

    class EnvLoader {
        +load(path) void$
    }

    %% ── Helpers ──────────────────────────────────────────────────
    class Validator {
        -errors : array
        +required(value, field) void
        +email(email, field) void
        +minLength(value, field, min) void
        +numeric(value, field) void
        +date(value, field) void
        +phone(value, field) void
        +noSpecialChars(value, field) void
        +inList(value, field, allowed) void
        +sanitize(value) string$
        +addError(field, message) void
        +getErrors() array
        +hasErrors() bool
    }

    %% ── Modelos ──────────────────────────────────────────────────
    class Paciente {
        -db : PDO
        +__construct(db)
        +crear(data) bool
        +obtenerTodos() array
        +buscarPorEmail(email) array|false
        +eliminar(id) bool
        +contarTodos() int
    }

    %% ── Pruebas ──────────────────────────────────────────────────
    class SqlInjectionTest {
        <<Test>>
        +testInsertSqlInjection()
        +testEmailSqlInjection()
        +testFilterInput()
        +testDelete()
    }
    class ValidatorTest {
        <<Test>>
        +testRequired()
        +testEmail()
        +testMinLength()
        +testDate()
        +testPhone()
        +testSanitize()
    }

    %% ── Relaciones ───────────────────────────────────────────────
    Router ..> IndexView : Renderiza / Enruta
    Router ..> PacientesView : Renderiza / Enruta
    Router ..> RegisterController : Enruta POST
    Router ..> DeleteController : Enruta POST

    IndexView ..> RegisterController : Envia Formulario
    PacientesView ..> DeleteController : Envia Eliminación

    RegisterController ..> Validator : Valida datos
    RegisterController ..> Paciente : Crea paciente
    DeleteController ..> Paciente : Elimina paciente

    Paciente ..> Database : Obtiene conexión
    Database ..> EnvLoader : Carga variables de entorno

    SqlInjectionTest ..> Paciente : Prueba
    ValidatorTest ..> Validator : Prueba
```
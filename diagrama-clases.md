# Diagrama de Arquitectura y Clases — ClinicaApp

El siguiente diagrama representa la arquitectura del proyecto basada en el patrón Modelo-Vista-Controlador (MVC), junto con sus componentes de infraestructura, utilidades y la suite de pruebas unitarias y de seguridad.

```mermaid
classDiagram
    direction TB

    namespace Presentacion {
        class IndexView {
            <<View>>
            +public/views/index.php
        }
        class PacientesView {
            <<View>>
            +public/views/pacientes.php
        }
    }

    namespace Enrutamiento_y_Control {
        class Router {
            <<Script>>
            +router.php
        }
        class RegisterBridge {
            <<Bridge>>
            +public/controllers/register.php
        }
        class DeleteBridge {
            <<Bridge>>
            +public/controllers/delete.php
        }
        class RegisterController {
            <<ControllerClass>>
            +controllers/RegisterController.php
        }
        class DeleteController {
            <<ControllerClass>>
            +controllers/DeleteController.php
        }
    }

    namespace Helpers {
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
    }

    namespace Modelos_y_Datos {
        class Paciente {
            -db : PDO
            +__construct(db)
            +crear(data) bool
            +obtenerTodos() array
            +buscarPorEmail(email) array|false
            +eliminar(id) bool
            +contarTodos() int
        }
        class Database {
            <<Singleton>>
            -instance : Database$
            -connection : PDO
            +getInstance() Database$
            +getConnection() PDO
        }
        class EnvLoader {
            +load(path) void$
        }
    }

    namespace Pruebas {
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
    }

    %% ── Relaciones de Enrutamiento y Presentación ───────────────────
    Router ..> IndexView : Renderiza GET (/)
    Router ..> PacientesView : Renderiza GET (/pacientes)
    Router ..> RegisterBridge : Enruta POST (/register)
    Router ..> DeleteBridge : Enruta POST (/delete)

    RegisterBridge ..> RegisterController : Carga
    DeleteBridge ..> DeleteController : Carga

    %% ── Relaciones de Negocio y Datos (Capa de Lógica) ────────────────
    RegisterController ..> Validator : Valida datos
    RegisterController ..> Paciente : Crea paciente
    DeleteController ..> Paciente : Elimina paciente

    Paciente ..> Database : Obtiene conexión
    Database ..> EnvLoader : Carga variables (.env)

    %% ── Relaciones de Pruebas ────────────────────────────────────────
    SqlInjectionTest ..> Paciente : Valida seguridad SQLi
    ValidatorTest ..> Validator : Valida reglas de negocio
```
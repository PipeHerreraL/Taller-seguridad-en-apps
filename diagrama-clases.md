# Diagrama de Arquitectura y Clases — ClinicaApp

El siguiente diagrama representa la arquitectura del proyecto basada en el patrón de **Front Controller** y **Modelo-Vista-Controlador (MVC)**, junto con sus componentes de infraestructura, utilidades y la suite de pruebas unitarias y de seguridad.

```mermaid
classDiagram
    direction LR

    namespace Enrutamiento_y_Control {
        class Router {
            <<Script>>
            +router.php
        }
        class FrontController {
            <<Script>>
            +public/index.php
        }
        class RegisterController {
            <<Controller>>
            +controllers/RegisterController.php
        }
        class DeleteController {
            <<Controller>>
            +controllers/DeleteController.php
        }
    }

    namespace Presentacion {
        class IndexView {
            <<View>>
            +views/index.php
        }
        class PacientesView {
            <<View>>
            +views/pacientes.php
        }
        class Error404View {
            <<View>>
            +views/404.php
        }
        class Error403View {
            <<View>>
            +views/403.php
        }
        class HeaderTemplate {
            <<Partial>>
            +views/templates/header.php
        }
        class FooterTemplate {
            <<Partial>>
            +views/templates/footer.php
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
        class EnvLoader {
            +load(path) void$
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

    %% ── Enrutamiento y Flujo de Peticiones ──────────────────────────
    Router ..> FrontController : Delega peticiones dinámicas
    Router ..> Error403View : Carga (403 Fallback)
    
    FrontController ..> IndexView : Carga (/)
    FrontController ..> PacientesView : Carga (/pacientes)
    FrontController ..> RegisterController : Invoca (/register)
    FrontController ..> DeleteController : Invoca (/delete)
    FrontController ..> Error404View : Carga (404 Fallback)
    
    %% ── Inclusión de Plantillas Comunes ─────────────────────────────
    IndexView ..> HeaderTemplate : Incluye
    IndexView ..> FooterTemplate : Incluye
    PacientesView ..> HeaderTemplate : Incluye
    PacientesView ..> FooterTemplate : Incluye
    Error403View ..> HeaderTemplate : Incluye
    Error403View ..> FooterTemplate : Incluye
    Error404View ..> HeaderTemplate : Incluye
    Error404View ..> FooterTemplate : Incluye

    %% ── Relaciones del Controlador (Lógica de Negocio) ──────────────
    RegisterController ..> Validator : Valida datos
    RegisterController ..> Paciente : Crea registro
    DeleteController ..> Paciente : Elimina registro

    %% ── Acceso a Datos e Infraestructura ────────────────────────────
    Paciente ..> Database : Obtiene conexión PDO
    Database ..> EnvLoader : Carga variables (.env)

    %% ── Suite de Pruebas Automatizadas ──────────────────────────────
    SqlInjectionTest ..> Paciente : Audita seguridad SQLi
    ValidatorTest ..> Validator : Audita reglas de validación
```
# Diagrama de Clases — ClinicaApp

```mermaid
classDiagram
    direction TB

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

    %% ── Helpers ──────────────────────────────────────────────────
    class Validator {
        -errors : array
        +required(value, field) void
        +email(email) void
        +minLength(value, field, min) void
        +numeric(value, field) void
        +date(value, field) void
        +noSpecialChars(value, field) void
        +inList(value, field, allowed) void
        +phone(value, field) void
        +sanitize(value) string$
        +getErrors() array
        +hasErrors() bool
    }

    %% ── Modelos ──────────────────────────────────────────────────
    class Paciente {
        -db : PDO
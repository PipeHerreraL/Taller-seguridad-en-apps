## Estructura del proyecto

```
/
├── controllers/             ← Lógica POST (oculta del exterior por seguridad)
│   ├── RegisterController.php
│   └── DeleteController.php
├── src/                     ← Clases con namespace App\
│   ├── Config/Database.php  ← Singleton PDO
│   ├── Models/Paciente.php  ← CRUD con prepared statements
│   └── Helpers/Validator.php← Validación y sanitización
├── tests/                   ← PHPUnit
│   ├── SqlInjectionTest.php ← 22 casos con 9 payloads distintos
│   └── ValidatorTest.php    ← 27 casos de validación
├── docs/                    ← Documentación
│   ├── diagrama-clases.md   ← Diagrama Mermaid
│   └── README.md            ← Este archivo
├── autoload.php             ← PSR-4 manual
├── composer.json
├── phpunit.xml
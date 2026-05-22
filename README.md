# ClinicaApp — Guía de comandos

Sistema de Registro de Pacientes · Taller de Seguridad en Aplicaciones Web  
Stack: **PHP 8.2 · MySQL · PDO · PHPUnit 11**

---

## Requisitos previos

| Herramienta | Versión mínima |
|---|---|
| PHP | 8.1+ |
| MySQL / MariaDB | 5.7+ |
| Composer (incluido como `composer.phar`) | — |
| Extensiones PHP | `pdo_mysql`, `pdo_sqlite` |

---

## 1 · Base de datos

### Con MySQL en Homebrew (macOS)

```bash
# Iniciar el servicio
brew services start mysql

# Crear la base de datos y la tabla
mysql -u root -p < database.sql
```

### Con XAMPP

1. Abre el panel de XAMPP e inicia **Apache** y **MySQL**.
2. Ve a `http://localhost/phpmyadmin`.
3. Abre la pestaña **SQL**, pega el contenido de `database.sql` y ejecuta.

### Credenciales por defecto

El archivo `src/Config/Database.php` usa:

```
host:     localhost
database: clinica_db
user:     root
password: (vacío)
```

Edita ese archivo si tu configuración es diferente.

---

## 2 · Dependencias de desarrollo

```bash
# Instala PHPUnit y sus dependencias (solo en dev)
php composer.phar install
```

---

## 3 · Servidor de desarrollo

```bash
# Levanta el servidor con el router para URLs sin .php
php -S localhost:8080 router.php
```

> **Importante:** usa `router.php` (no `-t public/`) para que las URLs
> sin extensión como `/index` y `/pacientes` funcionen correctamente.

| URL | Descripción |
|---|---|
| `http://localhost:8080/` | Formulario de registro |
| `http://localhost:8080/index` | Formulario de registro |
| `http://localhost:8080/pacientes` | Listado de pacientes |



## 4 · Comandos de Composer útiles

El proyecto cuenta con varios comandos predefinidos en `composer.json` para facilitar el desarrollo:

```bash
# Iniciar el servidor local en http://localhost:8080 usando el enrutador
composer serve

# Ejecutar todas las pruebas con formato legible (--testdox)
composer test

# Ejecutar solo las pruebas de inyección SQL
composer test:sql

# Ejecutar solo las pruebas del validador de datos
composer test:validator

# Ejecutar pruebas con reporte de cobertura (requiere Xdebug)
composer test:coverage

# Inicializar la base de datos y la tabla desde database.sql
composer db:init
```

---

## Estructura del proyecto

```
/
├── public/                  ← Document root (entry-points del navegador)
│   ├── index.php            ← Formulario de registro
│   ├── pacientes.php        ← Listado de pacientes
│   ├── register.php         ← Puente público para registro
│   ├── delete.php           ← Puente público para eliminación
│   └── assets/              ← CSS, JS y fuentes estáticas
│       ├── css/styles.css
│       ├── fonts/inter.woff2
│       └── js/main.js
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
├── diagrama-clases.md       ← Diagrama Mermaid
├── README.md                ← Este archivo
├── autoload.php             ← PSR-4 manual
├── composer.json
├── phpunit.xml
```
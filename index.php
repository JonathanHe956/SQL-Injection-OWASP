<?php require 'db.php';
$mensaje = null;
$ok = false;
$resultados = [];
$inyeccionVisible = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $usuario  = is_string($_POST['usuario'] ?? null) ? $_POST['usuario'] : '';
  $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

  // ===== VERSIÓN VULNERABLE (quita los // para activar) =====
  $sql = "SELECT usuario, password FROM usuarios WHERE usuario = '$usuario' AND password = '$password'";
  try {
    $filas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $error) {
    error_log('Error en la consulta del login: ' . $error->getMessage());
    $filas = [];
  }
  $fila = $filas[0] ?? false;

  $ok = (bool)$fila;
  $mensaje = $ok ? 'Acceso concedido' : 'Datos incorrectos';

  $esLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
  if ($esLocal && stripos($usuario, 'union') !== false) {
    $inyeccionVisible = $usuario;
    $resultados = $filas;
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="logo.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<title>Educación a Distancia ITA</title>
<style>
  :root{
    --bg:#0b1220;
    --card:rgba(20,30,48,.72);
    --border:rgba(255,255,255,.10);
    --txt:#e8edf5;
    --muted:#9fb0c7;
    --accent:#2f5bff;
    --accent-hover:#2449d8;
    --ok:#1f8a4c;
    --err:#b4324a;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{
    min-height:100vh;
    font-family:"Poppins","Segoe UI",system-ui,Arial,sans-serif;
    color:var(--txt);
    display:flex;align-items:center;justify-content:center;
    padding-top:72px;
    /* Pon una imagen 'fondo.jpg' en esta carpeta y se usará de fondo. */
    background:
      linear-gradient(rgba(10,12,16,0),rgba(10,12,16,0)),
      url('fondo.jpg') center/cover no-repeat,
      #0b0d12;
    padding:24px;
  }
  .card{
    position:relative;
    width:100%;max-width:400px;
    background:var(--card);
    border:1px solid var(--border);
    border-radius:16px;
    padding:40px 32px 32px;
    backdrop-filter:blur(10px);
    box-shadow:0 20px 60px rgba(0,0,0,.45);
  }
  /* Rejilla de puntos decorativa */
  .dots{
    position:absolute;
    width:40px;height:40px;
    background-image:radial-gradient(var(--accent) 1.5px,transparent 2px);
    background-size:10px 10px;
    opacity:.85;
  }
  .dots.tr{top:10px;right:10px}
  .dots.bl{bottom:10px;left:10px}
  .logo{
    width:120px;height:120px;margin:0 auto 24px;
    display:flex;align-items:center;justify-content:center;
    font-size:30px;font-weight:700;color:var(--muted);
  }
  .logo img{width:100%;height:100%;object-fit:contain;display:block}
  label{display:block;font-size:13px;color:var(--muted);margin:0 0 6px 2px}
  .field{margin-bottom:18px}
  input{
    width:100%;padding:14px 16px;
    background:rgba(255,255,255,.04);
    border:1px solid var(--border);
    border-radius:10px;
    color:var(--txt);font-size:15px;
    transition:border-color .15s,background .15s;
  }
  input::placeholder{color:#6f829c}
  input:focus{outline:none;border-color:var(--accent);background:rgba(47,91,255,.08)}
  button{
    width:100%;padding:14px;margin-top:4px;
    background:var(--accent);
    color:#fff;font-size:15px;font-weight:600;
    border:none;border-radius:10px;cursor:pointer;
    transition:background .15s;
  }
  button:hover{background:var(--accent-hover)}
  .link{display:block;text-align:center;margin-top:18px;font-size:13px}
  .link a{color:var(--muted);text-decoration:none}
  .link a:hover{color:var(--txt);text-decoration:underline}
  .alert{
    padding:12px 14px;border-radius:10px;margin-bottom:20px;
    font-size:14px;text-align:center;
  }
  .alert.ok{background:rgba(31,138,76,.18);border:1px solid var(--ok);color:#8ef0b4}
  .alert.err{background:rgba(180,50,74,.18);border:1px solid var(--err);color:#ff9fb0}
  .results{margin-top:18px;padding:12px 14px;border:1px solid var(--border);border-radius:8px;background:rgba(0,0,0,.25);font:13px/1.6 Consolas,monospace;overflow-wrap:anywhere}
  .result-query{display:block;color:#ff9fb0;margin-bottom:6px}
  .results-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 18px}
  .result{margin:0;padding:7px 9px;border-radius:6px;background:rgba(255,255,255,.04)}
  .card.has-results{max-width:760px;padding:24px}
  .card.has-results .logo{width:88px;height:88px;margin-bottom:12px}
  .card.has-results .field{margin-bottom:12px}
  .card.has-results input{padding:10px 12px}
  .card.has-results button{padding:11px;margin-top:0}
  .card.has-results .link{margin-top:10px}
  @media(max-width:600px){.results-list{grid-template-columns:1fr}}

  /* ===== Barra superior ===== */
  .topbar{
    position:fixed;top:0;left:0;right:0;height:72px;z-index:50;
    background:#223a86;
    display:flex;align-items:center;gap:36px;
    padding:0 28px;
    box-shadow:0 2px 12px rgba(0,0,0,.35);
  }
  .brand{
    display:flex;align-items:center;justify-content:center;
    height:46px;flex:none;
    font-weight:800;font-size:14px;letter-spacing:1px;color:#fff;
  }
  .brand img{height:36px;width:auto;display:block}
  .nav{display:flex;align-items:center;gap:30px;height:100%}
  .nav-item{position:relative;height:100%;display:flex;align-items:center}
  .nav-item>span{
    color:#e5e7eb;font-size:14px;line-height:24px;font-weight:600;cursor:pointer;
    display:inline-flex;align-items:center;gap:4px;
    transition:color .15s;
  }
  .nav-item:hover>span{color:#9ca3af}
  .nav-item .caret{font-size:11px;opacity:.85;transition:transform .15s}
  .nav-item:hover .caret{transform:rotate(180deg)}
  .dropdown{
    position:absolute;top:72px;left:-16px;
    min-width:300px;
    background:rgba(20,30,48,.97);
    border:1px solid var(--border);
    border-radius:0 0 12px 12px;
    padding:18px 20px;
    box-shadow:0 18px 40px rgba(0,0,0,.5);
    opacity:0;visibility:hidden;transform:translateY(-6px);
    transition:opacity .15s,transform .15s,visibility .15s;
  }
  .nav-item:hover .dropdown{opacity:1;visibility:visible;transform:translateY(0)}
  .dropdown h4{font-size:15px;color:#fff;margin-bottom:4px}
  .dropdown p{font-size:13px;color:var(--muted);margin-bottom:14px;line-height:1.4}
  .dropdown .cat{font-size:11px;letter-spacing:1px;color:#7f93b5;text-transform:uppercase;margin:6px 0 8px}
  .spacer{flex:1}
  .btn-plat{
    background:var(--accent);color:#fff;font-weight:600;font-size:14px;
    padding:11px 18px;border-radius:10px;text-decoration:none;white-space:nowrap;
  }
  .btn-plat:hover{background:var(--accent-hover)}
  @media(max-width:860px){.nav{display:none}}

  /* ===== Aviso de página de prueba ===== */
  .aviso{
    position:fixed;right:14px;bottom:14px;z-index:60;
    background:rgba(180,50,74,.92);
    color:#fff;font-size:12px;line-height:1.3;
    padding:8px 12px;border-radius:8px;max-width:260px;
    box-shadow:0 6px 18px rgba(0,0,0,.4);
  }
</style>
</head>
<body>
  <header class="topbar">
    <!-- Pon tu imagen 'ead.png' en esta carpeta. Si no existe, se ve el texto EAD. -->
    <div class="brand"><img src="ead.png" alt="Logo" onerror="this.replaceWith(document.createTextNode('EAD'))"></div>
    <nav class="nav">
      <div class="nav-item"><span>Inicio</span></div>
      <div class="nav-item"><span>Modelo Educativo</span></div>
      <div class="nav-item">
        <span>Oferta Educativa <i class="caret">▾</i></span>
        <div class="dropdown">
          <h4>Ingeniería en Gestión Empresarial</h4>
          <p>Impulsa negocios con visión estratégica y liderazgo.</p>
          <h4>Ingeniería Industrial</h4>
          <p>Transforma procesos y mejora la productividad.</p>
          <h4>Ingeniería en Sistemas Computacionales</h4>
          <p>Diseña soluciones tecnológicas y desarrolla software.</p>
        </div>
      </div>
      <div class="nav-item">
        <span>Convocatoria <i class="caret">▾</i></span>
        <div class="dropdown">
          <div class="cat">Modalidad Mixta</div>
          <h4>Ingeniería en Gestión Empresarial</h4>
          <p>80% en línea y 20% presencial, los sábados.</p>
          <div class="cat">A Distancia (100% en línea)</div>
          <h4>Ingeniería en Sistemas Computacionales</h4>
          <p>Estudia completamente en línea desde cualquier lugar.</p>
        </div>
      </div>
      <div class="nav-item">
        <span>Procesos Administrativos <i class="caret">▾</i></span>
        <div class="dropdown">
          <h4>Proceso de Equivalencia</h4>
          <p>Solicita la revalidación de estudios y equivalencia de materias cursadas en otras instituciones.</p>
        </div>
      </div>
    </nav>
    <div class="spacer"></div>
    <a class="btn-plat" href="#">Plataforma Virtual →</a>
  </header>

  <div class="aviso"> Página de prueba — NO es un sitio oficial. Entorno de laboratorio.</div>

  <form class="card<?= count($resultados) > 0 ? ' has-results' : '' ?>" method="POST">
    <span class="dots tr"></span>
    <span class="dots bl"></span>
    <div class="logo"><img src="logo.png" alt="Logo" onerror="this.replaceWith(document.createTextNode('SQL_I'))"></div>

    <?php if ($mensaje !== null): ?>
      <div class="alert <?= $ok ? 'ok' : 'err' ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($inyeccionVisible !== null && count($resultados) > 0): ?>
      <div class="results">
        <div class="results-list">
          <?php foreach ($resultados as $resultado): ?>
            <p class="result">
            <span class="result-query"><?= htmlspecialchars($inyeccionVisible, ENT_QUOTES, 'UTF-8') ?></span>
            Usuario: <?= htmlspecialchars((string)$resultado['usuario'], ENT_QUOTES, 'UTF-8') ?><br>
            Contraseña: <?= htmlspecialchars((string)$resultado['password'], ENT_QUOTES, 'UTF-8') ?></p>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="field">
      <label for="usuario">Usuario</label>
      <input id="usuario" name="usuario" placeholder="Usuario" autocomplete="off">
    </div>

    <div class="field">
      <label for="password">Contraseña</label>
      <input id="password" name="password" type="password" placeholder="Contraseña">
    </div>

    <button type="submit">Ingresar</button>

    <span class="link"><a href="#">¿Olvidaste tu contraseña?</a></span>
  </form>
</body>
</html>

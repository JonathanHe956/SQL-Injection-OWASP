<?php
$db = new PDO('sqlite:' . __DIR__ . '/demo.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("CREATE TABLE IF NOT EXISTS usuarios (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  usuario TEXT,
  password TEXT
)");

$db->exec("INSERT INTO usuarios (usuario, password)
  SELECT 'admin', 'admin123'
  WHERE NOT EXISTS (SELECT 1 FROM usuarios)");

$usuariosDemo = [
  ['axhel', 'password'],
  ['pablo', '123456'],
  ['jonathan', 'windows'],
  ['alondra', 'contraseña'],
  ['omar', '987654321'],
  ['justin', 'administracion'],
];
$insertarUsuario = $db->prepare(
  "INSERT INTO usuarios (usuario, password)
   SELECT ?, ?
   WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = ?)"
);

foreach ($usuariosDemo as [$nombre, $clave]) {
  $insertarUsuario->execute([$nombre, $clave, $nombre]);
}
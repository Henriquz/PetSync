<?php
/*------------------------------------------------------------
 |  PetSync – Configuração de acesso MySQL
 |------------------------------------------------------------
 |  Ajuste somente as 4 variáveis abaixo se o seu ambiente
 |  tiver usuário, senha ou host diferentes.
 *-----------------------------------------------------------*/

$DB_HOST = 'localhost';   // geralmente “localhost” no XAMPP
$DB_USER = 'root';        // usuário MySQL
$DB_PASS = '';            // senha (vazia no XAMPP padrão)
$DB_NAME = 'petsync';     // banco criado pelo petsync_full.sql

/*------------------------------------------------------------
 |  Conexão procedural mysqli (usada nos endpoints /api/*.php)
 *-----------------------------------------------------------*/
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_error) {
    http_response_code(500);
    die('Erro de conexão: ' . $mysqli->connect_error);
}

/*------------------------------------------------------------
 | (Opcional) Conexão PDO – útil se quiser usar PDO depois:
 *-----------------------------------------------------------*/
// try {
//     $pdo = new PDO(
//         "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
//         $DB_USER,
//         $DB_PASS,
//         [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
//     );
// } catch (PDOException $e) {
//     http_response_code(500);
//     die('PDO erro: '.$e->getMessage());
// }
?>

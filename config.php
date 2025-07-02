<?php
// config.php
// --- Configurações do Banco de Dados ---
//$db_host = 'sql304.infinityfree.com'; 
//$db_user = 'if0_39376507';      
//$db_pass = 'JsJImtFhyeFLg';       
//$db_name = 'if0_39376507_petsync';  -->

// --- Configurações do Banco de Dados localhost ---
 $db_host = 'localhost'; // Geralmente 'localhost'
 $db_user = 'root';      // Usuário padrão do XAMPP
 $db_pass = '';          // Senha padrão do XAMPP (vazio)
 $db_name = 'petsync';   // O nome que você deu ao seu banco de dados

// --- Conexão ---
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);

// --- Verificação de Erro ---
if ($mysqli->connect_error) {
    die('Erro de Conexão (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Define o charset para UTF-8 para evitar problemas com acentuação
$mysqli->set_charset('utf8');

// Inicia a sessão em todas as páginas que incluírem este arquivo
session_start();

function criar_notificacao($mysqli, $usuario_id, $mensagem, $link = null) {
    $stmt = $mysqli->prepare("INSERT INTO notificacoes (usuario_id, mensagem, link) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $usuario_id, $mensagem, $link);
    $stmt->execute();
    $stmt->close();
}
<?php
// config.php
// --- Configurações do Banco de Dados ---
//$db_host = 'sql304.infinityfree.com';
//$db_user = 'if0_39376507';
//$db_pass = 'JsJImtFhyeFLg';
//$db_name = 'if0_39376507_petsync'; -->

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
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ==========================================================
// INÍCIO: Função de Notificação Modificada
// ==========================================================
/**
 * Cria uma notificação para um usuário.
 *
 * @param mysqli $mysqli A conexão com o banco de dados.
 * @param int $usuario_id O ID do usuário que receberá a notificação.
 * @param string $mensagem O texto da notificação.
 * @param string $link O link de destino (opcional).
 * @param string $tipo O tipo de notificação ('automatica' ou 'alerta').
 * @return bool Retorna true em caso de sucesso, false em caso de falha.
 */
// Dentro de config.php

function criar_notificacao($mysqli, $usuario_id, $mensagem, $link = '', $tipo = 'automatica', $imagem_url = null) {
    if (!in_array($tipo, ['automatica', 'alerta'])) {
        $tipo = 'automatica';
    }

    $sql = "INSERT INTO notificacoes (usuario_id, mensagem, link, tipo, imagem_url) VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        // O bind_param foi atualizado para "issss"
        $stmt->bind_param("issss", $usuario_id, $mensagem, $link, $tipo, $imagem_url);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    return false;
}
// ==========================================================
// FIM: Função de Notificação Modificada
// ==========================================================
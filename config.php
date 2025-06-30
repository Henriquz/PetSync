<?php
// config.php

// --- Configurações do Banco de Dados ---
$db_host = 'localhost'; // Geralmente 'localhost'
$db_user = 'root';      // Usuário padrão do XAMPP
$db_pass = '';          // Senha padrão do XAMPP (vazio)
$db_name = 'petsync';   // O nome que você deu ao seu banco de dados

// --- Conexão ---
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

// --- Verificação de Erro ---
if ($mysqli->connect_error) {
    die('Erro de Conexão (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Define o charset para UTF-8 para evitar problemas com acentuação
$mysqli->set_charset('utf8');

// Inicia a sessão em todas as páginas que incluírem este arquivo
session_start();
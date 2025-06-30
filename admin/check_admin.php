<?php
// Este script verifica se o usuário está logado E se ele é um admin.
// Ele deve ser incluído no topo de todas as páginas restritas.

// A sessão já deve ter sido iniciada pelo config.php
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['is_admin']) || !$_SESSION['usuario']['is_admin']) {
    // Se não for admin, redireciona para a página de login
    header('Location: ../login.php');
    exit;
}
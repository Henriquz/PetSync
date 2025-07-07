<?php
// Este script verifica se o usuário está logado E se ele é um colaborador.
// Ele deve ser incluído no topo de todas as páginas restritas aos colaboradores.

// A sessão já deve ter sido iniciada pelo config.php
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['is_colaborador']) || !$_SESSION['usuario']['is_colaborador']) {
    // Se não for colaborador, redireciona para a página de login
    header('Location: ../login.php');
    exit;
}


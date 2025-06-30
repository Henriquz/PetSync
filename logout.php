<?php
// Incluir o config.php garante que a sessão seja iniciada antes de ser destruída.
// É mais robusto que usar apenas session_start() isoladamente.
include 'config.php';

// Destrói todos os dados da sessão
session_destroy();

// Redireciona para a página inicial
header('Location: index.php');
exit;
<?php
// Garante que a sessão foi iniciada (já que o config.php faz isso)
if (!isset($_SESSION['usuario'])) {
    // Se não há usuário na sessão, redireciona para a página de login
    header('Location: ../login.php');
    exit;
}
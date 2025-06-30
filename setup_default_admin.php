<?php
// Este script deve ser executado apenas uma vez para garantir que o admin padrão exista.
// Depois de executar, você pode apagar este arquivo por segurança.

include 'config.php';

echo '<h1>Setup do Administrador Padrão</h1>';

$admin_email = 'admin@petsync.com';
$admin_nome = 'Administrador Principal';
$admin_senha = 'petsync'; // Defina uma senha padrão forte

// Verifica se o admin já existe
$stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param('s', $admin_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<p style="color: blue;">O administrador padrão já existe. Nenhuma ação foi tomada.</p>';
} else {
    // Se não existe, cria o admin padrão
    $hash = password_hash($admin_senha, PASSWORD_DEFAULT);
    $is_admin = 1;
    $is_active = 1;

    $insert_stmt = $mysqli->prepare("INSERT INTO usuarios (nome, email, senha, is_admin, is_active) VALUES (?, ?, ?, ?, ?)");
    $insert_stmt->bind_param('sssii', $admin_nome, $admin_email, $hash, $is_admin, $is_active);
    
    if ($insert_stmt->execute()) {
        echo '<p style="color: green;">Administrador padrão criado com sucesso!</p>';
        echo '<p><strong>Email:</strong> ' . $admin_email . '</p>';
        echo '<p><strong>Senha:</strong> ' . $admin_senha . '</p>';
    } else {
        echo '<p style="color: red;">Erro ao criar o administrador padrão: ' . $insert_stmt->error . '</p>';
    }
}

$mysqli->close();
echo '<p style="font-weight: bold; color: orange;">AVISO: Por segurança, apague o arquivo setup_default_admin.php agora.</p>';
<?php
include '../config.php';
include 'check_admin.php'; // Garante que apenas administradores logados possam executar esta ação

header('Content-Type: application/json'); // Define o tipo de resposta como JSON

// A chave da configuração que queremos alterar
$chave_config = 'exibir_secao_produtos';
$novo_valor = '1';

// Verifica se a chave já existe no banco de dados
$stmt_check = $mysqli->prepare("SELECT chave FROM configuracoes WHERE chave = ?");
$stmt_check->bind_param('s', $chave_config);
$stmt_check->execute();
$result = $stmt_check->get_result();

$sucesso = false;

if ($result->num_rows > 0) {
    // Se a chave existe, atualiza o valor
    $stmt_update = $mysqli->prepare("UPDATE configuracoes SET valor = ? WHERE chave = ?");
    $stmt_update->bind_param('ss', $novo_valor, $chave_config);
    if ($stmt_update->execute()) {
        $sucesso = true;
    }
} else {
    // Se a chave não existe, insere a nova configuração
    $stmt_insert = $mysqli->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?)");
    $stmt_insert->bind_param('ss', $chave_config, $novo_valor);
    if ($stmt_insert->execute()) {
        $sucesso = true;
    }
}

// Retorna uma resposta JSON indicando se a operação foi bem-sucedida
if ($sucesso) {
    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Falha ao atualizar a configuração no banco de dados.']);
}

exit;
?>
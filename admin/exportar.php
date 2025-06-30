<?php
// Inclui as configurações e verifica se o usuário é admin
include '../config.php';
include 'check_admin.php';

// Pega o tipo de relatório da URL
$relatorio = $_GET['relatorio'] ?? '';

$dados = [];
$cabecalho = [];
$nome_arquivo = 'relatorio_petsync.csv';

// Usa um switch para preparar os dados de cada relatório
switch ($relatorio) {
    case 'ultimos_usuarios':
        $nome_arquivo = 'relatorio_ultimos_usuarios_' . date('Y-m-d') . '.csv';
        $cabecalho = ['Nome', 'Email', 'Data de Cadastro'];
        $dados = $mysqli->query("SELECT nome, email, data_cadastro FROM usuarios ORDER BY data_cadastro DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
        break;

    case 'clientes_ativos':
        $nome_arquivo = 'relatorio_clientes_ativos_' . date('Y-m-d') . '.csv';
        $cabecalho = ['Nome', 'Email', 'Telefone'];
        $dados = $mysqli->query("SELECT nome, email, telefone FROM usuarios WHERE is_admin = 0 AND is_active = 1 ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
        break;

    // Você pode adicionar mais casos aqui para outros relatórios no futuro
    
    default:
        die("Relatório não especificado ou inválido.");
}

// Define os headers para forçar o download do arquivo
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');

// Abre o "arquivo" de saída do PHP
$output = fopen('php://output', 'w');

// Adiciona o BOM para compatibilidade com Excel no Windows
echo "\xEF\xBB\xBF";

// Escreve a linha do cabeçalho no CSV
fputcsv($output, $cabecalho);

// Escreve cada linha de dados no CSV
foreach ($dados as $linha) {
    fputcsv($output, $linha);
}

// Fecha o arquivo de saída
fclose($output);
exit;
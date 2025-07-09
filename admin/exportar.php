<?php
include '../config.php';
include 'check_admin.php';

if (isset($_GET["relatorio"])) {
    $relatorio = $_GET["relatorio"];
    $filename = "relatorio_" . $relatorio . ".csv";

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");

    $output = fopen("php://output", "w");

    switch ($relatorio) {
        case "ultimos_usuarios":
            fputcsv($output, array("Nome", "E-mail", "Data de Cadastro"));
            $result = $mysqli->query("SELECT nome, email, data_cadastro FROM usuarios ORDER BY data_cadastro DESC LIMIT 10");
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
        case "clientes_ativos":
            fputcsv($output, array("Nome", "E-mail", "Telefone"));
            $result = $mysqli->query("SELECT nome, email, telefone FROM usuarios WHERE is_admin = 0 AND is_active = 1 ORDER BY nome ASC");
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
        case "colaboradores_ativos":
            fputcsv($output, array("Nome", "E-mail", "Telefone"));
            $result = $mysqli->query("SELECT nome, email, telefone FROM usuarios WHERE is_colaborador = 1 AND is_active = 1 ORDER BY nome ASC");
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
        case "admins_ativos":
            fputcsv($output, array("Nome", "E-mail", "Telefone"));
            $result = $mysqli->query("SELECT nome, email, telefone FROM usuarios WHERE is_admin = 1 AND is_active = 1 ORDER BY nome ASC");
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
        case "pets_por_especie":
            fputcsv($output, array("Espécie", "Total"));
            $result = $mysqli->query("SELECT especie, COUNT(id) as total FROM pets GROUP BY especie ORDER BY total DESC");
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
        case "produtos_mais_vendidos":
            fputcsv($output, array("Produto", "Total Vendido"));
            $result = $mysqli->query("SELECT p.nome, SUM(pi.quantidade) as total_vendido FROM pedido_itens pi JOIN produtos p ON pi.produto_id = p.id GROUP BY p.nome ORDER BY total_vendido DESC LIMIT 10");
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
        case "produtos_baixo_estoque":
            fputcsv($output, array("Produto", "Estoque"));
            $result = $mysqli->query("SELECT nome, estoque FROM produtos WHERE estoque <= 10 ORDER BY estoque ASC");
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
        case "agendamentos_por_status":
            fputcsv($output, array("Status", "Total"));
            $result = $mysqli->query("SELECT status, COUNT(id) as total FROM agendamentos GROUP BY status");
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
        case "agendamentos_por_servico":
            fputcsv($output, array("Serviço", "Total"));
            $result = $mysqli->query("SELECT servico, COUNT(id) as total FROM agendamentos GROUP BY servico ORDER BY total DESC LIMIT 10");
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
        case "pedidos_por_status":
            fputcsv($output, array("Status", "Total"));
            $result = $mysqli->query("SELECT status, COUNT(id) as total FROM pedidos GROUP BY status");
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
        case "pedidos_por_forma_pagamento":
            fputcsv($output, array("Forma de Pagamento", "Total"));
            $result = $mysqli->query("SELECT forma_pagamento, COUNT(id) as total FROM pedidos GROUP BY forma_pagamento");
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, $row);
            }
            break;
        default:
            // Handle unknown report type
            break;
    }

    fclose($output);
    exit();
} else {
    echo "Nenhum relatório especificado.";
}

?>


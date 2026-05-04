<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Buscar dados do cliente
$query = "SELECT * FROM utilizadores WHERE id = :id AND cargo = 'cliente'";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$cliente) {
    header('Location: clientes.php');
    exit();
}

// Buscar histórico de aluguéis do cliente
$query = "SELECT a.*, v.marca, v.modelo, v.matricula, 
          p.valor as valor_pago, p.metodo_pagamento, p.data_pagamento
          FROM alugueis a
          JOIN viaturas v ON a.viatura_id = v.id
          LEFT JOIN pagamentos p ON a.id = p.aluguer_id
          WHERE a.utilizador_id = :cliente_id
          ORDER BY a.criado_em DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':cliente_id', $id);
$stmt->execute();
$historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar reservas do cliente
$query = "SELECT r.*, v.marca, v.modelo, v.matricula
          FROM reservas r
          JOIN viaturas v ON r.viatura_id = v.id
          WHERE r.utilizador_id = :cliente_id
          ORDER BY r.criado_em DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':cliente_id', $id);
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico do Cliente - Funcionário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fa; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1E3A5F; }
        h2 { color: #FF8C00; margin-top: 20px; }
        .info-cliente { background: #f0f2f5; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .tabela { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabela th, .tabela td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .tabela th { background: #1E3A5F; color: white; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-secondary { background: #6c757d; color: white; }
        .etiqueta { padding: 3px 8px; border-radius: 4px; font-size: 12px; }
        .etiqueta-sucesso { background: #28a745; color: white; }
        .etiqueta-aviso { background: #ffc107; color: #333; }
        .etiqueta-perigo { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1> Histórico do Cliente</h1>
        
        <div class="info-cliente">
            <strong> Nome:</strong> <?= htmlspecialchars($cliente['nome']) ?><br>
            <strong> Email:</strong> <?= htmlspecialchars($cliente['email']) ?><br>
            <strong> Telefone:</strong> <?= htmlspecialchars($cliente['telefone'] ?? '---') ?><br>
            <strong> NUIT:</strong> <?= htmlspecialchars($cliente['NUIT'] ?? '---') ?>
        </div>
        
        <h2>Aluguer Realizado</h2>
        <?php if(count($historico) > 0): ?>
        <table class="tabela">
            <thead>
                <tr><th>Viatura</th><th>Período</th><th>Valor</th><th>Data Devolução</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach($historico as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['marca'] . ' ' . $a['modelo']) ?><br><small><?= $a['matricula'] ?></small></td>
                    <td><?= date('d/m/Y', strtotime($a['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($a['data_fim'])) ?></td>
                    <td>Mts <?= number_format($a['preco_total'], 2) ?></td>
                    <td><?= $a['data_devolucao'] ? date('d/m/Y', strtotime($a['data_devolucao'])) : '-' ?></td>
                    <td>
                        <span class="etiqueta etiqueta-<?= $a['status'] == 'finalizado' ? 'sucesso' : ($a['status'] == 'ativo' ? 'aviso' : 'perigo') ?>">
                            <?= ucfirst($a['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>Nenhum aluguer encontrado.</p>
        <?php endif; ?>
        
        <h2>Reservas</h2>
        <?php if(count($reservas) > 0): ?>
        <table class="tabela">
            <thead>
                <tr><th>Viatura</th><th>Período</th><th>Valor</th><th>Status</th><th>Data</th></tr>
            </thead>
            <tbody>
                <?php foreach($reservas as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['marca'] . ' ' . $r['modelo']) ?><br><small><?= $r['matricula'] ?></small></td>
                    <td><?= date('d/m/Y', strtotime($r['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($r['data_fim'])) ?></td>
                    <td>MZN <?= number_format($r['preco_total'], 2) ?></td>
                    <td>
                        <span class="etiqueta etiqueta-<?= $r['status'] == 'confirmada' ? 'sucesso' : ($r['status'] == 'pendente' ? 'aviso' : 'perigo') ?>">
                            <?= ucfirst($r['status']) ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($r['criado_em'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>Nenhuma reserva encontrada.</p>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="clientes.php" class="btn btn-secondary">Voltar</a>
        </div>
    </div>
</body>
</html>
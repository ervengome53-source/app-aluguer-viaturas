<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

$reserva_id = $_GET['reserva_id'] ?? null;
$valor_total = 0;
$descricao = '';

if($reserva_id) {
    $query = "SELECT r.*, v.marca, v.modelo, v.preco_dia 
              FROM reservas r 
              JOIN viaturas v ON r.viatura_id = v.id 
              WHERE r.id = :id AND r.utilizador_id = :utilizador_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $reserva_id);
    $stmt->bindParam(':utilizador_id', $utilizador['id']);
    $stmt->execute();
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($reserva) {
        $valor_total = $reserva['preco_total'];
        $descricao = $reserva['marca'] . ' ' . $reserva['modelo'];
    }
}

// Buscar pagamentos do cliente
$query = "SELECT p.*, 
          CASE 
              WHEN p.reserva_id IS NOT NULL THEN 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM reservas r JOIN viaturas v ON r.viatura_id = v.id WHERE r.id = p.reserva_id)
              ELSE 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = p.aluguer_id)
          END as descricao
          FROM pagamentos p 
          WHERE p.utilizador_id = :utilizador_id 
          ORDER BY p.data_criacao DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamentos </title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/cliente.css">
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($reserva_id): ?>
                <?php include '../components/formulario_pagamento.php'; ?>
                <?php exibirFormularioPagamento($valor_total, $reserva_id, null); ?>
            <?php else: ?>
                <div class="cartao">
                    <div class="cartao-cabecalho">
                        <h3 class="cartao-titulo"> Histórico de Pagamentos</h3>
                    </div>
                    
                    <?php if(count($pagamentos) > 0): ?>
                    <div class="container-tabela">
                        <table class="tabela">
                            <thead>
                                <tr>
                                    <th>Referência</th>
                                    <th>Descrição</th>
                                    <th>Valor</th>
                                    <th>Método</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th>Recibo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pagamentos as $pagamento): ?>
                                <tr>
                                    <td><?= $pagamento['referencia_pagamento'] ?></td>
                                    <td><?= htmlspecialchars($pagamento['descricao']) ?></td>
                                    <td>€ <?= number_format($pagamento['valor'], 2) ?></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $pagamento['metodo_pagamento'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($pagamento['data_criacao'])) ?></td>
                                    <td>
                                        <span class="etiqueta etiqueta-<?= $pagamento['estado'] == 'confirmado' ? 'sucesso' : 'aviso' ?>">
                                            <?= ucfirst($pagamento['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($pagamento['estado'] == 'confirmado'): ?>
                                            <button class="btn btn-info btn-sm" onclick="window.open('../pagamentos/recibo.php?id=<?= $pagamento['id'] ?>', '_blank')">
                                                
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="texto-centro" style="padding: 3rem;">
                        <div style="font-size: 3rem;"></div>
                        <h3>Nenhum pagamento encontrado</h3>
                        <p>Os seus pagamentos aparecerão aqui.</p>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/pagamentos.js"></script>
</body>
</html>
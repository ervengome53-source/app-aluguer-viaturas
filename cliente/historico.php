<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// Buscar histórico de aluguéis finalizados
$query = "SELECT a.*, v.marca, v.modelo, v.imagem, 
          p.valor as valor_pago, p.metodo_pagamento, p.data_pagamento
          FROM alugueis a 
          JOIN viaturas v ON a.viatura_id = v.id 
          LEFT JOIN pagamentos p ON a.id = p.aluguer_id
          WHERE a.utilizador_id = :utilizador_id AND a.status = 'finalizado'
          ORDER BY a.data_devolucao DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$query = "SELECT 
          COUNT(*) as total_alugueis,
          SUM(preco_total) as total_gasto,
          AVG(preco_total) as media_gasto
          FROM alugueis WHERE utilizador_id = :utilizador_id AND status = 'finalizado'";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Aluguer - SIGAV </title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/cliente.css">
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Total de Aluguer</h3>
                        <div class="estatistica-numero"> <?= $stats['total_alugueis'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Total Gasto</h3>
                        <div class="estatistica-numero"> MZN <?= number_format($stats['total_gasto'] ?? 0, 2) ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Media por Aluguer</h3>
                        <div class="estatistica-numero"> MZN <?= number_format($stats['media_gasto'] ?? 0, 2) ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
            </div>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Histórico de Aluguer</h3>
                </div>
                
                <?php if(count($historico) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>Viatura</th>
                                <th>Período</th>
                                <th>Dias</th>
                                <th>Valor</th>
                                <th>Pagamento</th>
                                <th>Data Devolução</th>
                                <th>Recibo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($historico as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($item['marca'] . ' ' . $item['modelo']) ?></strong>
                                 </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($item['data_inicio'])) ?><br>
                                    <small>até <?= date('d/m/Y', strtotime($item['data_fim'])) ?></small>
                                 </td>
                                <td><?= $item['total_dias'] ?> dias</td>
                                <td>MZN <?= number_format($item['preco_total'], 2) ?></td>
                                <td>
                                    <?php if($item['valor_pago']): ?>
                                        MZN <?= number_format($item['valor_pago'], 2) ?><br>
                                        <small><?= ucfirst(str_replace('_', ' ', $item['metodo_pagamento'])) ?></small>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                 </td>
                                <td><?= date('d/m/Y', strtotime($item['data_devolucao'])) ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="emitirReciboAluguer(<?= $item['id'] ?>)">
                                         Recibo
                                    </button>
                                 </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="texto-centro" style="padding: 3rem;">
                    <div style="font-size: 3rem;"></div>
                    <h3>Nenhum aluguer encontrado</h3>
                 
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function emitirReciboAluguer(aluguerId) {
            window.open(`../pagamentos/recibo.php?aluguer_id=${aluguerId}`, '_blank');
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>
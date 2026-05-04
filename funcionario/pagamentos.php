<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Buscar pagamentos pendentes
$query = "SELECT p.*, u.nome as cliente_nome, 
          CASE 
              WHEN p.reserva_id IS NOT NULL THEN 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM reservas r JOIN viaturas v ON r.viatura_id = v.id WHERE r.id = p.reserva_id)
              ELSE 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = p.aluguer_id)
          END as descricao
          FROM pagamentos p
          JOIN utilizadores u ON p.utilizador_id = u.id
          WHERE p.estado = 'pendente'
          ORDER BY p.data_criacao ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$pagamentos_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar pagamentos confirmados recentes
$query = "SELECT p.*, u.nome as cliente_nome,
          CASE 
              WHEN p.reserva_id IS NOT NULL THEN 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM reservas r JOIN viaturas v ON r.viatura_id = v.id WHERE r.id = p.reserva_id)
              ELSE 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = p.aluguer_id)
          END as descricao
          FROM pagamentos p
          JOIN utilizadores u ON p.utilizador_id = u.id
          WHERE p.estado = 'confirmado'
          ORDER BY p.data_pagamento DESC
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$pagamentos_confirmados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$query = "SELECT 
          COUNT(CASE WHEN estado = 'pendente' THEN 1 END) as pendentes,
          SUM(CASE WHEN estado = 'pendente' THEN valor ELSE 0 END) as valor_pendente,
          COUNT(CASE WHEN estado = 'confirmado' AND DATE(data_pagamento) = CURDATE() THEN 1 END) as confirmados_hoje,
          SUM(CASE WHEN estado = 'confirmado' AND DATE(data_pagamento) = CURDATE() THEN valor ELSE 0 END) as valor_hoje
          FROM pagamentos";
$stmt = $db->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Pagamentos - Funcionário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/funcionario.css">
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Pagamentos Pendentes</h3>
                        <div class="estatistica-numero"><?= $stats['pendentes'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Valor Pendente</h3>
                        <div class="estatistica-numero">MZN <?= number_format($stats['valor_pendente'] ?? 0, 2) ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Pagamentos Hoje</h3>
                        <div class="estatistica-numero"><?= $stats['confirmados_hoje'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Valor Hoje</h3>
                        <div class="estatistica-numero">MZN <?= number_format($stats['valor_hoje'] ?? 0, 2) ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
            </div>
            
            <!-- Pagamentos Pendentes -->
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Pagamentos Pendentes</h3>
                </div>
                
                <?php if(count($pagamentos_pendentes) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>Referência</th>
                                <th>Cliente</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Método</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pagamentos_pendentes as $pagamento): ?>
                            <tr>
                                <td><?= $pagamento['referencia_pagamento'] ?></td>
                                <td><?= htmlspecialchars($pagamento['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars(substr($pagamento['descricao'], 0, 40)) ?>...</td>
                                <td>MZN <?= number_format($pagamento['valor'], 2) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $pagamento['metodo_pagamento'])) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pagamento['data_criacao'])) ?></td>
                                <td class="tabela-acoes">
                                    <?php if($pagamento['metodo_pagamento'] == 'dinheiro'): ?>
                                        <button class="btn btn-sucesso btn-sm" onclick="confirmarPagamento(<?= $pagamento['id'] ?>, 'dinheiro')">
                                            Confirmar Pagamento
                                        </button>
                                    <?php elseif($pagamento['metodo_pagamento'] == 'transferencia'): ?>
                                        <button class="btn btn-info btn-sm" onclick="verComprovativo(<?= $pagamento['id'] ?>)">
                                            Ver Comprovativo
                                        </button>
                                        <button class="btn btn-sucesso btn-sm" onclick="confirmarPagamento(<?= $pagamento['id'] ?>, 'transferencia')">
                                            Confirmar
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-info btn-sm" onclick="verDetalhesPagamento(<?= $pagamento['id'] ?>)">
                                            Ver Detalhes
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-perigo btn-sm" onclick="cancelarPagamento(<?= $pagamento['id'] ?>)">
                                        Cancelar
                                    </button>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="texto-centro" style="padding: 2rem;">
                    <div style="font-size: 2rem;"></div>
                    <p>Nenhum pagamento pendente</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagamentos Confirmados Recentes -->
            <div class="cartao" style="margin-top: 1.5rem;">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Últimos Pagamentos Confirmados</h3>
                    <button class="btn btn-info btn-sm" onclick="window.location.href='../admin/relatorio_pagamentos.php'">
                        Ver Relatório Completo
                    </button>
                </div>
                
                <?php if(count($pagamentos_confirmados) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>Referência</th>
                                <th>Cliente</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Método</th>
                                <th>Data Pagamento</th>
                                <th>Recibo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pagamentos_confirmados as $pagamento): ?>
                            <tr>
                                <td><?= $pagamento['referencia_pagamento'] ?></td>
                                <td><?= htmlspecialchars($pagamento['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars(substr($pagamento['descricao'], 0, 40)) ?>...</td>
                                <td>MZN <?= number_format($pagamento['valor'], 2) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $pagamento['metodo_pagamento'])) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])) ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="window.open('../pagamentos/recibo.php?id=<?= $pagamento['id'] ?>', '_blank')">
                                        
                                    </button>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="texto-centro" style="padding: 2rem;">
                    <p>Nenhum pagamento confirmado ainda</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        async function confirmarPagamento(id, metodo) {
            modal.confirmar(`Confirmar este pagamento via ${metodo}?`, async () => {
                const resultado = await API.post('../api/pagamentos.php?acao=confirmar', { pagamento_id: id });
                if(resultado && resultado.sucesso) {
                    Utilitarios.mostrarNotificacao('Pagamento confirmado!', 'sucesso');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    Utilitarios.mostrarNotificacao('Erro ao confirmar pagamento', 'erro');
                }
            });
        }
        
        async function cancelarPagamento(id) {
            modal.confirmar('Cancelar este pagamento?', async () => {
                const resultado = await API.post('../api/pagamentos.php?acao=cancelar', { pagamento_id: id });
                if(resultado && resultado.sucesso) {
                    Utilitarios.mostrarNotificacao('Pagamento cancelado!', 'aviso');
                    setTimeout(() => window.location.reload(), 1000);
                }
            });
        }
        
        function verDetalhesPagamento(id) {
            window.open(`../pagamentos/detalhe.php?id=${id}`, '_blank');
        }
        
        function verComprovativo(id) {
            window.open(`../pagamentos/comprovativo.php?id=${id}`, '_blank');
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
</body>
</html>
<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$mensagem = '';
$erro = '';

// Processar confirmação de pagamento via POST
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_id'])) {
    $id = (int)$_POST['confirmar_id'];
    $query = "UPDATE pagamentos SET estado = 'confirmado', data_pagamento = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    if($stmt->execute()) {
        $mensagem = '<i class="fas fa-check-circle"></i> Pagamento confirmado com sucesso!';
    } else {
        $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao confirmar pagamento';
    }
}

// Processar cancelamento de pagamento
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancelar_id'])) {
    $id = (int)$_POST['cancelar_id'];
    $query = "UPDATE pagamentos SET estado = 'cancelado' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    if($stmt->execute()) {
        $mensagem = '<i class="fas fa-check-circle"></i> Pagamento cancelado com sucesso!';
    } else {
        $erro = '<i class="fas fa-exclamation-triangle"></i> Erro ao cancelar pagamento';
    }
}

// Buscar pagamentos pendentes
$query = "SELECT p.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
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
          LIMIT 10";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Pagamentos - Funcionário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .container-app { display: flex; min-height: 100vh; }
        .conteudo-principal { flex: 1; margin-left: 270px; padding: 1.5rem; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .stat-number { font-size: 1.6rem; font-weight: bold; color: #1E3A5F; }
        .stat-label { font-size: 0.7rem; color: #888; margin-top: 0.3rem; display: flex; align-items: center; justify-content: center; gap: 0.3rem; }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #1E3A5F, #2a5298);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 { color: white; margin: 0; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        
        .btn-primary {
            background: #FF8C00;
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-primary:hover { background: #e67e00; transform: translateY(-2px); }
        
        .tabela-container { overflow-x: auto; }
        .tabela { width: 100%; border-collapse: collapse; }
        .tabela th {
            padding: 0.8rem 1rem;
            text-align: left;
            background: #f8f9fa;
            color: #1E3A5F;
            border-bottom: 2px solid #FF8C00;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .tabela td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .tabela tr:hover td { background: #fef9e6; }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        
        .btn-confirmar {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
        }
        .btn-confirmar:hover { background: #218838; transform: translateY(-2px); }
        
        .btn-cancelar {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
        }
        .btn-cancelar:hover { background: #c82333; transform: translateY(-2px); }
        
        .btn-recibo {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 0.3rem 0.7rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
        }
        .btn-recibo:hover { background: #138496; transform: translateY(-2px); }
        
        .acoes-cell { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .valor-destaque { font-weight: bold; color: #FF8C00; }
        
        .notificacao {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .notificacao.sucesso { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .notificacao.erro { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-confirm {
            background: white;
            border-radius: 16px;
            max-width: 420px;
            width: 90%;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .modal-confirm .modal-header {
            background: linear-gradient(135deg, #1E3A5F, #2a5298);
            padding: 1rem 1.5rem;
        }
        .modal-confirm .modal-header h3 { color: white; margin: 0; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .modal-confirm .modal-body { padding: 1.5rem; text-align: center; }
        .modal-confirm .modal-icon { font-size: 3rem; margin-bottom: 1rem; }
        .modal-confirm .modal-message { font-size: 0.95rem; color: #333; margin-bottom: 0.5rem; }
        .modal-confirm .modal-details { font-size: 0.8rem; color: #666; background: #f8f9fa; padding: 0.8rem; border-radius: 10px; margin-top: 0.8rem; }
        .modal-confirm .modal-footer { padding: 1rem 1.5rem 1.5rem; display: flex; justify-content: center; gap: 1rem; }
        
        .btn-cancel-modal {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        .btn-cancel-modal:hover { background: #5a6268; transform: translateY(-2px); }
        
        .btn-confirm-modal {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        .btn-confirm-modal:hover { background: #c82333; transform: translateY(-2px); }
        .btn-confirm-success { background: #28a745; }
        .btn-confirm-success:hover { background: #218838; }
        
        .empty-state { text-align: center; padding: 2.5rem; color: #999; }
        .empty-state i { font-size: 2.5rem; margin-bottom: 0.5rem; color: #ddd; }
        
        @media (max-width: 992px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .conteudo-principal { margin-left: 0; padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .acoes-cell { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($mensagem): ?>
                <div class="notificacao sucesso"><?= $mensagem ?></div>
            <?php endif; ?>
            <?php if($erro): ?>
                <div class="notificacao erro"><?= $erro ?></div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['pendentes'] ?? 0 ?></div>
                    <div class="stat-label"><i class="fas fa-clock"></i> Pendentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">MZN <?= number_format($stats['valor_pendente'] ?? 0, 2) ?></div>
                    <div class="stat-label"><i class="fas fa-money-bill-wave"></i> Valor Pendente</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['confirmados_hoje'] ?? 0 ?></div>
                    <div class="stat-label"><i class="fas fa-check-circle"></i> Confirmados Hoje</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">MZN <?= number_format($stats['valor_hoje'] ?? 0, 2) ?></div>
                    <div class="stat-label"><i class="fas fa-chart-line"></i> Valor Hoje</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-hourglass-half"></i> Pagamentos Pendentes</h3>
                    <a href="registar_pagamento.php" class="btn-primary"><i class="fas fa-plus"></i> Novo Pagamento</a>
                </div>
                
                <?php if(count($pagamentos_pendentes) > 0): ?>
                <div class="tabela-container">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Cliente</th>
                                <th><i class="fas fa-car"></i> Descrição</th>
                                <th><i class="fas fa-money-bill-wave"></i> Valor</th>
                                <th><i class="fas fa-credit-card"></i> Método</th>
                                <th><i class="fas fa-calendar"></i> Data</th>
                                <th><i class="fas fa-cogs"></i> Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pagamentos_pendentes as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($p['cliente_nome']) ?></strong><br>
                                    <small><i class="fas fa-envelope"></i> <?= $p['cliente_email'] ?></small><br>
                                    <small><i class="fas fa-phone"></i> <?= $p['cliente_telefone'] ?? '---' ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($p['descricao']) ?>
                                    <br><small><i class="fas fa-hashtag"></i> <?= $p['referencia_pagamento'] ?></small>
                                </td>
                                <td><span class="valor-destaque">MZN <?= number_format($p['valor'], 2) ?></span></td>
                                <td><span class="badge badge-warning"><i class="fas fa-credit-card"></i> <?= ucfirst(str_replace('_', ' ', $p['metodo_pagamento'])) ?></span></td>
                                <td><?= date('d/m/Y', strtotime($p['data_criacao'])) ?><br><small><?= date('H:i', strtotime($p['data_criacao'])) ?></small></td>
                                <td class="acoes-cell">
                                    <button class="btn-confirmar" onclick="abrirModalConfirmar(<?= $p['id'] ?>, '<?= addslashes($p['cliente_nome']) ?>', <?= $p['valor'] ?>)">
                                        <i class="fas fa-check"></i> Confirmar
                                    </button>
                                    <button class="btn-cancelar" onclick="abrirModalCancelar(<?= $p['id'] ?>, '<?= addslashes($p['cliente_nome']) ?>', <?= $p['valor'] ?>)">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state"><i class="fas fa-check-circle"></i><p>Não há pagamentos pendentes</p></div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Últimos Pagamentos Confirmados</h3>
                </div>
                
                <?php if(count($pagamentos_confirmados) > 0): ?>
                <div class="tabela-container">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> Referência</th>
                                <th><i class="fas fa-user"></i> Cliente</th>
                                <th><i class="fas fa-car"></i> Descrição</th>
                                <th><i class="fas fa-money-bill-wave"></i> Valor</th>
                                <th><i class="fas fa-calendar"></i> Data</th>
                                <th><i class="fas fa-file-pdf"></i> Recibo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pagamentos_confirmados as $p): ?>
                            <tr>
                                <td><?= $p['referencia_pagamento'] ?></td>
                                <td><?= htmlspecialchars($p['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars($p['descricao']) ?></td>
                                <td><span class="badge badge-success">MZN <?= number_format($p['valor'], 2) ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($p['data_pagamento'])) ?></td>
                                <td><button class="btn-recibo" onclick="emitirRecibo(<?= $p['id'] ?>)"><i class="fas fa-file-pdf"></i> Recibo</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state"><i class="fas fa-inbox"></i><p>Nenhum pagamento confirmado</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- MODAL DE CONFIRMAÇÃO -->
    <div id="modalConfirmacao" class="modal">
        <div class="modal-confirm">
            <div class="modal-header">
                <h3><i class="fas fa-question-circle"></i> Confirmar Ação</h3>
            </div>
            <div class="modal-body">
                <div class="modal-icon" id="modalIcone"><i class="fas fa-exclamation-triangle" style="color: #ffc107; font-size: 3rem;"></i></div>
                <div class="modal-message" id="modalMensagem">Tem certeza que deseja realizar esta ação?</div>
                <div class="modal-details" id="modalDetalhes"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel-modal" onclick="fecharModalConfirmacao()"><i class="fas fa-times"></i> Cancelar</button>
                <button class="btn-confirm-modal" id="btnConfirmarAcao"><i class="fas fa-check"></i> Confirmar</button>
            </div>
        </div>
    </div>
    
    <form id="formConfirmar" method="POST" style="display: none;">
        <input type="hidden" name="confirmar_id" id="confirmar_id">
    </form>
    <form id="formCancelar" method="POST" style="display: none;">
        <input type="hidden" name="cancelar_id" id="cancelar_id">
    </form>
    
    <script>
        let acaoPendente = null, idPendente = null;
        
        function abrirModalConfirmar(id, nome, valor) {
            idPendente = id; acaoPendente = 'confirmar';
            document.getElementById('modalIcone').innerHTML = '<i class="fas fa-check-circle" style="color: #28a745; font-size: 3rem;"></i>';
            document.getElementById('modalMensagem').innerHTML = 'Confirmar este pagamento?';
            document.getElementById('modalDetalhes').innerHTML = '<strong>Cliente:</strong> ' + nome + '<br><strong>Valor:</strong> MZN ' + valor.toFixed(2);
            document.getElementById('btnConfirmarAcao').className = 'btn-confirm-modal btn-confirm-success';
            document.getElementById('btnConfirmarAcao').innerHTML = '<i class="fas fa-check"></i> Sim, confirmar';
            document.getElementById('modalConfirmacao').style.display = 'flex';
        }
        
        function abrirModalCancelar(id, nome, valor) {
            idPendente = id; acaoPendente = 'cancelar';
            document.getElementById('modalIcone').innerHTML = '<i class="fas fa-times-circle" style="color: #dc3545; font-size: 3rem;"></i>';
            document.getElementById('modalMensagem').innerHTML = 'Cancelar este pagamento?';
            document.getElementById('modalDetalhes').innerHTML = '<strong>Cliente:</strong> ' + nome + '<br><strong>Valor:</strong> MZN ' + valor.toFixed(2);
            document.getElementById('btnConfirmarAcao').className = 'btn-confirm-modal';
            document.getElementById('btnConfirmarAcao').innerHTML = '<i class="fas fa-trash"></i> Sim, cancelar';
            document.getElementById('modalConfirmacao').style.display = 'flex';
        }
        
        function fecharModalConfirmacao() {
            document.getElementById('modalConfirmacao').style.display = 'none';
            idPendente = null; acaoPendente = null;
        }
        
        function executarAcao() {
            if (acaoPendente === 'confirmar' && idPendente) {
                document.getElementById('confirmar_id').value = idPendente;
                document.getElementById('formConfirmar').submit();
            } else if (acaoPendente === 'cancelar' && idPendente) {
                document.getElementById('cancelar_id').value = idPendente;
                document.getElementById('formCancelar').submit();
            }
        }
        
        function emitirRecibo(id) { window.open('../pagamentos/recibo.php?id=' + id, '_blank'); }
        
        document.getElementById('btnConfirmarAcao').onclick = function() { executarAcao(); fecharModalConfirmacao(); };
        window.onclick = function(event) { if (event.target === document.getElementById('modalConfirmacao')) fecharModalConfirmacao(); };
        document.getElementById('modalConfirmacao').style.display = 'none';
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
</body>
</html>
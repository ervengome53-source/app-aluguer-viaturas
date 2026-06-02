<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$funcionario_id = $_SESSION['utilizador_id'];

$aluguer_id = $_GET['id'] ?? null;
$busca = $_GET['busca'] ?? '';
$mensagem = '';
$erro = '';
$devolucao_processada = false;

// Buscar alugueis ativos
$query = "SELECT a.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
          v.marca, v.modelo, v.matricula, v.preco_dia
          FROM alugueis a
          JOIN utilizadores u ON a.utilizador_id = u.id
          JOIN viaturas v ON a.viatura_id = v.id
          WHERE a.status = 'ativo'
          ORDER BY a.data_fim ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$alugueis_ativos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar aluguer específico pelo ID
$aluguer = null;
if($aluguer_id) {
    foreach($alugueis_ativos as $a) {
        if($a['id'] == $aluguer_id) {
            $aluguer = $a;
            break;
        }
    }
}

// Busca manual de aluguer
if(!empty($busca) && !$aluguer) {
    $query = "SELECT a.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
              v.marca, v.modelo, v.matricula, v.preco_dia
              FROM alugueis a
              JOIN utilizadores u ON a.utilizador_id = u.id
              JOIN viaturas v ON a.viatura_id = v.id
              WHERE a.status = 'ativo' 
              AND (u.nome LIKE :busca OR u.email LIKE :busca OR v.matricula LIKE :busca OR a.id LIKE :busca)
              LIMIT 1";
    $stmt = $db->prepare($query);
    $busca_param = "%{$busca}%";
    $stmt->bindParam(':busca', $busca_param);
    $stmt->execute();
    $aluguer = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Calcular multa
$multa = 0;
$dias_atraso = 0;
if($aluguer) {
    $data_fim = new DateTime($aluguer['data_fim']);
    $hoje = new DateTime();
    if($hoje > $data_fim) {
        $dias_atraso = $data_fim->diff($hoje)->days;
        $multa = $dias_atraso * 25;
    }
}

// Processar devolução
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'processar_devolucao') {
    $aluguer_id = $_POST['aluguer_id'];
    $observacoes = $_POST['observacoes'] ?? '';
    $multa_paga = isset($_POST['multa_paga']) ? 1 : 0;
    $dano_reportado = $_POST['dano_reportado'] ?? '';
    $estado_veiculo = $_POST['estado_veiculo'] ?? 'bom';
    $quilometragem = $_POST['quilometragem'] ?? 0;
    
    $db->beginTransaction();
    
    try {
        $query = "UPDATE alugueis SET status = 'finalizado', data_devolucao = NOW(), 
                  observacoes = CONCAT(IFNULL(observacoes, ''), ' Devolução: ', :observacoes),
                  multa_atraso = :multa, quilometragem_chegada = :quilometragem
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->bindParam(':multa', $multa);
        $stmt->bindParam(':quilometragem', $quilometragem);
        $stmt->bindParam(':id', $aluguer_id);
        $stmt->execute();
        
        $query = "UPDATE viaturas SET status = 'disponivel' WHERE id = (SELECT viatura_id FROM alugueis WHERE id = :id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $aluguer_id);
        $stmt->execute();
        
        if($multa > 0) {
            $motivo = "Atraso na devolução de {$dias_atraso} dias";
            $status_multa = $multa_paga ? 'pago' : 'pendente';
            $query = "INSERT INTO multas (aluguer_id, utilizador_id, valor, motivo, status) 
                      VALUES (:aluguer_id, :utilizador_id, :valor, :motivo, :status)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':aluguer_id', $aluguer_id);
            $stmt->bindParam(':utilizador_id', $aluguer['utilizador_id']);
            $stmt->bindParam(':valor', $multa);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':status', $status_multa);
            $stmt->execute();
        }
        
        if($dano_reportado) {
            $query = "INSERT INTO viaturas_danos (aluguer_id, viatura_id, descricao, status, data_registo) 
                      VALUES (:aluguer_id, :viatura_id, :descricao, 'reportado', NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':aluguer_id', $aluguer_id);
            $stmt->bindParam(':viatura_id', $aluguer['viatura_id']);
            $stmt->bindParam(':descricao', $dano_reportado);
            $stmt->execute();
        }
        
        $db->commit();
        $devolucao_processada = true;
        $mensagem = 'Devolução processada com sucesso!';
        
    } catch(Exception $e) {
        $db->rollBack();
        $erro = 'Erro ao processar devolução: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Registar Devolução - SIGAV</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fb; overflow-x: hidden; }
        
        .container-app { display: flex; min-height: 100vh; width: 100%; }
        .barra-lateral { width: 280px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; position: fixed; left: 0; top: 0; height: 100vh; overflow-y: auto; z-index: 100; transition: all 0.3s ease; }
        .conteudo-principal { flex: 1; margin-left: 280px; padding: 2rem; background: #f5f7fb; min-height: 100vh; width: calc(100% - 280px); }
        .barra-superior { background: white; border-radius: 1rem; padding: 1rem 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 2rem; font-weight: 700; color: #1a1a2e; margin-bottom: 0.5rem; }
        .page-header p { color: #666; font-size: 0.95rem; }
        
        /* Seção de Busca */
        .search-section {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .search-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .search-box {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 0.8rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #FF8C00;
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1);
        }
        
        .btn-search {
            background: #FF8C00;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-search:hover {
            background: #e67e00;
            transform: translateY(-2px);
        }
        
        /* Lista de Aluguéis */
        .alugueis-list {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        
        .aluguel-item {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid #FF8C00;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .aluguel-item:hover {
            background: #fef9e6;
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .aluguel-info {
            flex: 2;
        }
        
        .aluguel-info strong {
            font-size: 1rem;
            color: #1a1a2e;
            display: block;
            margin-bottom: 0.2rem;
        }
        
        .aluguel-info small {
            color: #666;
            font-size: 0.8rem;
        }
        
        .aluguel-datas {
            flex: 1;
            text-align: center;
        }
        
        .aluguel-status {
            flex: 0.5;
            text-align: right;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .badge-atrasado { background: #f8d7da; color: #721c24; }
        .badge-prazo { background: #d4edda; color: #155724; }
        
        /* Card de Detalhes */
        .details-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .details-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: white;
        }
        
        .details-header h3 {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .details-body {
            padding: 1.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: #1a1a2e;
        }
        
        .multa-box {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border-radius: 0.8rem;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .sucesso-box {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 0.8rem;
            padding: 1rem;
            margin: 1rem 0;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .form-group label i {
            color: #FF8C00;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 0.8rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #FF8C00;
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .checkbox-group input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn {
            padding: 0.75rem 1.8rem;
            border-radius: 0.8rem;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #FF8C00, #FF6B00);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 140, 0, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .empty-state {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            color: #999;
        }
        
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 0.8rem;
            z-index: 2000;
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .toast-success { border-left: 4px solid #28a745; }
        .toast-success i { color: #28a745; }
        .toast-error { border-left: 4px solid #dc3545; }
        .toast-error i { color: #dc3545; }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
        }
        
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 1.5rem; width: 90%; max-width: 450px; animation: modalFadeIn 0.3s ease; overflow: hidden; }
        @keyframes modalFadeIn { from { opacity: 0; transform: translateY(-50px); } to { opacity: 1; transform: translateY(0); } }
        .modal-header { padding: 1.2rem 1.5rem; background: linear-gradient(135deg, #FF8C00, #FF6B00); color: white; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
        .modal-close { background: none; border: none; color: white; font-size: 1.3rem; cursor: pointer; transition: all 0.3s ease; }
        .modal-close:hover { transform: rotate(90deg); }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1rem 1.5rem 1.5rem; display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid #eee; }
        
        @media (max-width: 768px) {
            .barra-lateral { width: 0; transform: translateX(-100%); }
            .conteudo-principal { margin-left: 0; width: 100%; padding: 1rem; }
            .info-grid { grid-template-columns: 1fr; }
            .search-box { flex-direction: column; }
            .btn-search { width: 100%; justify-content: center; }
            .aluguel-item { flex-direction: column; text-align: center; }
            .aluguel-status { text-align: center; }
            .form-actions { flex-direction: column; }
            .btn { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($erro): ?>
            <div class="toast-notification toast-error" id="toastErro">
                <i class="fas fa-exclamation-circle fa-lg"></i>
                <span><?= htmlspecialchars($erro) ?></span>
            </div>
            <script>setTimeout(() => { const t = document.getElementById('toastErro'); if(t) t.style.display = 'none'; }, 3000);</script>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>Registar Devolução</h1>
                <p>Processe a devolução de viaturas alugadas</p>
            </div>
            
            <?php if($devolucao_processada): ?>
            <div class="sucesso-box">
                <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <h3 style="margin-bottom: 0.5rem;">Devolução Processada!</h3>
                <p><?= $mensagem ?></p>
                <p style="font-size: 0.8rem; margin-top: 0.5rem;">Redirecionando para o dashboard...</p>
            </div>
            <script>
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 2000);
            </script>
            <?php endif; ?>
            
            <!-- Seção de Busca Manual -->
            <div class="search-section">
                <div class="search-title">
                    <i class="fas fa-search"></i> Buscar Aluguer Manualmente
                </div>
                <form method="GET" action="">
                    <div class="search-box">
                        <input type="text" name="busca" class="search-input" placeholder="Digite nome do cliente, email, matrícula ou ID do aluguer..." value="<?= htmlspecialchars($busca) ?>">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>
            
            <?php if(!$aluguer && count($alugueis_ativos) > 0): ?>
            <!-- Lista de Aluguéis Ativos -->
            <div class="details-card">
                <div class="details-header">
                    <h3><i class="fas fa-list"></i> Aluguéis Ativos</h3>
                </div>
                <div class="details-body">
                    <div class="alugueis-list">
                        <?php foreach($alugueis_ativos as $a): ?>
                        <?php $atrasado = strtotime($a['data_fim']) < time(); ?>
                        <div class="aluguel-item" onclick="window.location.href='?id=<?= $a['id'] ?>'">
                            <div class="aluguel-info">
                                <strong><i class="fas fa-user"></i> <?= htmlspecialchars($a['cliente_nome']) ?></strong>
                                <small><i class="fas fa-car"></i> <?= htmlspecialchars($a['marca'] . ' ' . $a['modelo']) ?> - <?= $a['matricula'] ?></small>
                            </div>
                            <div class="aluguel-datas">
                                <i class="fas fa-calendar"></i> Devolução: <?= date('d/m/Y', strtotime($a['data_fim'])) ?>
                            </div>
                            <div class="aluguel-status">
                                <?php if($atrasado): ?>
                                    <span class="badge badge-atrasado"><i class="fas fa-exclamation-triangle"></i> Atrasado</span>
                                <?php else: ?>
                                    <span class="badge badge-prazo"><i class="fas fa-check-circle"></i> No prazo</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <?php elseif($aluguer): ?>
            <!-- Formulário de Devolução -->
            <div class="details-card">
                <div class="details-header">
                    <h3><i class="fas fa-info-circle"></i> Informações do Aluguer</h3>
                </div>
                <div class="details-body">
                    <div class="info-grid">
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-user"></i> Cliente:</span>
                            <span class="info-value"><?= htmlspecialchars($aluguer['cliente_nome']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-envelope"></i> Email:</span>
                            <span class="info-value"><?= $aluguer['cliente_email'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-phone"></i> Telefone:</span>
                            <span class="info-value"><?= $aluguer['cliente_telefone'] ?? '---' ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-car"></i> Viatura:</span>
                            <span class="info-value"><?= htmlspecialchars($aluguer['marca'] . ' ' . $aluguer['modelo']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-id-card"></i> Matrícula:</span>
                            <span class="info-value"><?= $aluguer['matricula'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-calendar-alt"></i> Período:</span>
                            <span class="info-value"><?= date('d/m/Y', strtotime($aluguer['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($aluguer['data_fim'])) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-money-bill-wave"></i> Valor:</span>
                            <span class="info-value">MZN <?= number_format($aluguer['preco_total'], 2) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-calendar-check"></i> Previsão:</span>
                            <span class="info-value"><?= date('d/m/Y', strtotime($aluguer['data_fim'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <form method="POST" id="formDevolucao">
                <input type="hidden" name="acao" value="processar_devolucao">
                <input type="hidden" name="aluguer_id" value="<?= $aluguer['id'] ?>">
                
                <div class="details-card">
                    <div class="details-header">
                        <h3><i class="fas fa-clipboard-list"></i> Dados da Devolução</h3>
                    </div>
                    <div class="details-body">
                        
                        <?php if($dias_atraso > 0): ?>
                        <div class="multa-box">
                            <h4 style="margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Multa por Atraso</h4>
                            <p>Dias de atraso: <strong><?= $dias_atraso ?></strong> dias</p>
                            <p style="font-size: 1.2rem; margin: 0.5rem 0;">Valor da multa: <strong>MZN <?= number_format($multa, 2) ?></strong></p>
                            <div class="checkbox-group">
                                <input type="checkbox" name="multa_paga" id="multa_paga" value="1">
                                <label for="multa_paga"><i class="fas fa-check-circle"></i> Cliente pagou a multa</label>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="sucesso-box">
                            <i class="fas fa-check-circle"></i>
                            <p style="margin-top: 0.5rem;">Devolução dentro do prazo. Sem multas aplicadas.</p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label><i class="fas fa-tachometer-alt"></i> Quilometragem atual</label>
                            <input type="number" name="quilometragem" class="form-control" placeholder="Quilometragem no momento da devolução" value="<?= $aluguer['quilometragem_saida'] ?? 0 ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-car-side"></i> Estado do Veículo</label>
                            <select name="estado_veiculo" id="estado_veiculo" class="form-control">
                                <option value="bom"><i class="fas fa-smile"></i> Bom estado - Sem danos</option>
                                <option value="regular"><i class="fas fa-meh"></i> Regular - Pequenos danos estéticos</option>
                                <option value="dano"><i class="fas fa-frown"></i> Danificado - Necessita reparação</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="div_danos" style="display: none;">
                            <label><i class="fas fa-tools"></i> Descrição dos Danos</label>
                            <textarea name="dano_reportado" class="form-control" rows="3" placeholder="Descreva detalhadamente os danos encontrados..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Observações de Devolução</label>
                            <textarea name="observacoes" class="form-control" rows="3" placeholder="Observações adicionais sobre a devolução..."></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='devolucao.php'">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" onclick="confirmarDevolucao()">
                                <i class="fas fa-check"></i> Confirmar Devolução
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <?php else: ?>
            <!-- Nenhum aluguer encontrado -->
            <div class="empty-state">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.5rem;">Nenhum aluguer encontrado</h3>
                <p>Não há aluguéis em andamento para devolução ou a busca não retornou resultados.</p>
                <button class="btn btn-primary" onclick="window.location.href='devolucao.php'" style="margin-top: 1rem;">
                    <i class="fas fa-sync-alt"></i> Limpar Busca
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- MODAL CONFIRMAÇÃO -->
    <div id="modalConfirmacao" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-question-circle"></i> Confirmar Devolução</h3>
                <button class="modal-close" onclick="fecharModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja processar esta devolução?</p>
                <div style="background: #f8f9fa; border-radius: 0.8rem; padding: 1rem; margin-top: 1rem;">
                    <p style="margin-bottom: 0.5rem;"><i class="fas fa-user"></i> <strong>Cliente:</strong> <?= $aluguer ? htmlspecialchars($aluguer['cliente_nome']) : '-' ?></p>
                    <p style="margin-bottom: 0.5rem;"><i class="fas fa-car"></i> <strong>Viatura:</strong> <?= $aluguer ? htmlspecialchars($aluguer['marca'] . ' ' . $aluguer['modelo']) : '-' ?></p>
                    <?php if($multa > 0): ?>
                    <p><i class="fas fa-exclamation-triangle"></i> <strong>Multa:</strong> MZN <?= number_format($multa, 2) ?></p>
                    <?php endif; ?>
                </div>
                <p style="margin-top: 1rem; font-size: 0.8rem; color: #666;">
                    <i class="fas fa-info-circle"></i> Esta ação não pode ser desfeita.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="fecharModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn btn-primary" onclick="submitFormulario()">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>
    
    <script>
        const estadoVeiculo = document.getElementById('estado_veiculo');
        const divDanos = document.getElementById('div_danos');
        
        if(estadoVeiculo) {
            estadoVeiculo.addEventListener('change', function() {
                if(divDanos) {
                    divDanos.style.display = (this.value === 'dano' || this.value === 'regular') ? 'block' : 'none';
                }
            });
        }
        
        function confirmarDevolucao() {
            document.getElementById('modalConfirmacao').classList.add('active');
        }
        
        function fecharModal() {
            document.getElementById('modalConfirmacao').classList.remove('active');
        }
        
        function submitFormulario() {
            document.getElementById('formDevolucao').submit();
        }
        
        document.addEventListener('keydown', function(event) {
            if(event.key === 'Escape') {
                fecharModal();
            }
        });
        
        window.onclick = function(event) {
            const modal = document.getElementById('modalConfirmacao');
            if(event.target === modal) {
                fecharModal();
            }
        }
    </script>
</body>
</html>
<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$funcionario_id = $_SESSION['utilizador_id'];

$aluguer_id = $_GET['id'] ?? null;
$mensagem = '';
$erro = '';

// Buscar alugueres ativos para select
$query = "SELECT a.*, u.nome as cliente_nome, v.marca, v.modelo, v.matricula, v.preco_dia
          FROM alugueis a
          JOIN utilizadores u ON a.utilizador_id = u.id
          JOIN viaturas v ON a.viatura_id = v.id
          WHERE a.status = 'ativo'
          ORDER BY a.data_fim ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$alugueis_ativos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar dados do aluguer específico
$aluguer = null;
if($aluguer_id) {
    foreach($alugueis_ativos as $a) {
        if($a['id'] == $aluguer_id) {
            $aluguer = $a;
            break;
        }
    }
}

// Calcular multa se aplicável
$multa = 0;
$dias_atraso = 0;
if($aluguer) {
    $data_fim = new DateTime($aluguer['data_fim']);
    $hoje = new DateTime();
    if($hoje > $data_fim) {
        $dias_atraso = $data_fim->diff($hoje)->days;
        $multa = $dias_atraso * 25; // Multa de 25€ por dia
    }
}

// Processar devolução
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aluguer_id = $_POST['aluguer_id'];
    $observacoes = $_POST['observacoes'];
    $multa_paga = $_POST['multa_paga'] ?? 0;
    $dano_reportado = $_POST['dano_reportado'] ?? '';
    
    $db->beginTransaction();
    
    try {
        // Atualizar aluguer
        $query = "UPDATE alugueis SET status = 'finalizado', data_devolucao = NOW(), 
                  observacoes = CONCAT(observacoes, ' / Devolução: ', :observacoes),
                  multa_atraso = :multa
                  WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->bindParam(':multa', $multa);
        $stmt->bindParam(':id', $aluguer_id);
        $stmt->execute();
        
        // Atualizar status da viatura para disponível
        $query = "UPDATE viaturas SET status = 'disponivel' WHERE id = (SELECT viatura_id FROM alugueis WHERE id = :id)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $aluguer_id);
        $stmt->execute();
        
        // Registrar multa se houver
        if($multa > 0) {
            $query = "INSERT INTO multas (aluguer_id, utilizador_id, valor, motivo, status) 
                      VALUES (:aluguer_id, :utilizador_id, :valor, :motivo, 'pendente')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':aluguer_id', $aluguer_id);
            $stmt->bindParam(':utilizador_id', $aluguer['utilizador_id']);
            $stmt->bindParam(':valor', $multa);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->execute();
        }
        
        // Registrar dano se houver
        if($dano_reportado) {
            $query = "INSERT INTO viaturas_danos (aluguer_id, viatura_id, descricao, data_registo) 
                      VALUES (:aluguer_id, :viatura_id, :descricao, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':aluguer_id', $aluguer_id);
            $stmt->bindParam(':viatura_id', $aluguer['viatura_id']);
            $stmt->bindParam(':descricao', $dano_reportado);
            $stmt->execute();
        }
        
        $db->commit();
        $mensagem = 'Devolução processada com sucesso!';
        
        echo "<script>setTimeout(() => { window.location.href = 'dashboard.php'; }, 2000);</script>";
        
    } catch(Exception $e) {
        $db->rollBack();
        $erro = 'Erro ao processar devolução: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Devolução - Funcionário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/funcionario.css">
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
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Registar Devolução</h3>
                </div>
                
                <?php if(!$aluguer && count($alugueis_ativos) > 0): ?>
                <div class="selecionar-aluguer">
                    <h4>Selecione um aluguer ativo para devolução:</h4>
                    <div class="aluguer-lista">
                        <?php foreach($alugueis_ativos as $a): ?>
                        <div class="aluguer-card" onclick="window.location.href='?id=<?= $a['id'] ?>'">
                            <div class="aluguer-info">
                                <strong><?= htmlspecialchars($a['cliente_nome']) ?></strong><br>
                                <small><?= htmlspecialchars($a['marca'] . ' ' . $a['modelo']) ?> - <?= $a['matricula'] ?></small>
                            </div>
                            <div class="aluguer-datas">
                                Data fim: <?= date('d/m/Y', strtotime($a['data_fim'])) ?>
                            </div>
                            <div class="aluguer-status">
                                <?php if(strtotime($a['data_fim']) < time()): ?>
                                    <span class="etiqueta etiqueta-perigo">Atrasado</span>
                                <?php else: ?>
                                    <span class="etiqueta etiqueta-info">No prazo</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php elseif($aluguer): ?>
                
                <form method="POST" class="form-devolucao">
                    <input type="hidden" name="aluguer_id" value="<?= $aluguer['id'] ?>">
                    
                    <div class="aluguer-detalhes">
                        <div class="detalhes-grid">
                            <div><strong>Cliente:</strong> <?= htmlspecialchars($aluguer['cliente_nome']) ?></div>
                            <div><strong>Viatura:</strong> <?= htmlspecialchars($aluguer['marca'] . ' ' . $aluguer['modelo']) ?></div>
                            <div><strong>Matrícula:</strong> <?= $aluguer['matricula'] ?></div>
                            <div><strong>Período:</strong> <?= date('d/m/Y', strtotime($aluguer['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($aluguer['data_fim'])) ?></div>
                            <div><strong>Valor Aluguer:</strong> MZN <?= number_format($aluguer['preco_total'], 2) ?></div>
                            <div><strong>Data prevista devolução:</strong> <?= date('d/m/Y', strtotime($aluguer['data_fim'])) ?></div>
                        </div>
                    </div>
                    
                    <?php if($dias_atraso > 0): ?>
                    <div class="calculo-multa">
                        <h4> Multa por Atraso</h4>
                        <p>Dias de atraso: <strong><?= $dias_atraso ?></strong> dias</p>
                        <p>Valor da multa: <strong>MZN <?= number_format($multa, 2) ?></strong></p>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">
                                <input type="checkbox" name="multa_paga" value="1">
                                Cliente pagou a multa
                            </label>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alerta-sucesso">
                         Devolução dentro do prazo. Sem multas aplicadas.
                    </div>
                    <?php endif; ?>
                    
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Estado do Veículo</label>
                        <select name="estado_veiculo" class="controlo-formulario" id="estado_veiculo">
                            <option value="bom">Bom estado</option>
                            <option value="regular">Regular - Pequenos danos</option>
                            <option value="dano">Danificado - Necessita reparação</option>
                        </select>
                    </div>
                    
                    <div class="grupo-formulario" id="div_danos" style="display: none;">
                        <label class="rotulo-formulario">Descrição dos Danos</label>
                        <textarea name="dano_reportado" class="controlo-formulario" rows="3" placeholder="Descreva os danos encontrados..."></textarea>
                    </div>
                    
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Observações de Devolução</label>
                        <textarea name="observacoes" class="controlo-formulario" rows="3" placeholder="Observações sobre o estado do veículo, quilometragem, etc..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secundario" onclick="window.location.href='dashboard.php'">Cancelar</button>
                        <button type="submit" class="btn btn-destaque"> Confirmar Devolução</button>
                    </div>
                </form>
                
                <?php else: ?>
                <div class="texto-centro" style="padding: 3rem;">
                    <div style="font-size: 3rem;"></div>
                    <h3>Nenhum aluguer ativo no momento</h3>
                    <p>Não há aluguéis em andamento para devolução.</p>
                    <a href="dashboard.php" class="btn btn-primario">Voltar ao Dashboard</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
        .aluguer-lista {
            display: grid;
            gap: 1rem;
        }
        
        .aluguer-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--cinza-claro);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transicao);
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .aluguer-card:hover {
            background: rgba(255, 140, 0, 0.1);
            transform: translateX(5px);
        }
        
        .aluguer-detalhes {
            background: var(--cinza-claro);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .detalhes-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }
        
        .calculo-multa {
            background: var(--perigo);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .alerta-sucesso {
            background: var(--sucesso);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: center;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .detalhes-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <script>
        document.getElementById('estado_veiculo')?.addEventListener('change', function() {
            const divDanos = document.getElementById('div_danos');
            if(divDanos) {
                divDanos.style.display = this.value === 'dano' || this.value === 'regular' ? 'block' : 'none';
            }
        });
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
</body>
</html>
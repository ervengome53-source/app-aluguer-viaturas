<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// Verificar se veio de uma reserva específica
$reserva_id = $_GET['reserva_id'] ?? 0;
$reserva = null;
$valor_total = 0;

if($reserva_id > 0) {
    $query = "SELECT r.*, v.marca, v.modelo, v.preco_dia 
              FROM reservas r 
              JOIN viaturas v ON r.viatura_id = v.id 
              WHERE r.id = :id AND r.utilizador_id = :utilizador_id AND r.status = 'confirmada'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $reserva_id);
    $stmt->bindParam(':utilizador_id', $utilizador['id']);
    $stmt->execute();
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($reserva) {
        $valor_total = $reserva['preco_total'];
    }
}

// Processar pagamento
$mensagem = '';
$erro = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    // VALIDAÇÃO: Verificar se o método foi selecionado
    if(empty($_POST['metodo_pagamento'])) {
        $erro = "Por favor, selecione um método de pagamento (Dinheiro, Cartão, MB WAY ou Transferência)!";
    } else {
        $metodo = $_POST['metodo_pagamento'];
        
        // Validar se o método é permitido
        $metodos_validos = ['dinheiro', 'cartao', 'mbway', 'transferencia'];
        if(!in_array($metodo, $metodos_validos)) {
            $erro = "Método de pagamento inválido!";
        } else {
            $referencia = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $dados = json_encode(['observacoes' => $_POST['observacoes'] ?? '']);
            
            $query = "INSERT INTO pagamentos (utilizador_id, reserva_id, valor, metodo_pagamento, referencia_pagamento, estado, dados_transacao) 
                      VALUES (:user_id, :reserva_id, :valor, :metodo, :referencia, 'pendente', :dados)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $utilizador['id']);
            $stmt->bindParam(':reserva_id', $reserva_id);
            $stmt->bindParam(':valor', $valor_total);
            $stmt->bindParam(':metodo', $metodo);
            $stmt->bindParam(':referencia', $referencia);
            $stmt->bindParam(':dados', $dados);
            
            if($stmt->execute()) {
                $mensagem = "Pagamento registado! Aguarde confirmação do funcionário.";
                // Redirecionar para evitar reenvio do formulário
                header("Location: pagamentos.php?sucesso=1");
                exit();
            } else {
                $erro = "Erro ao processar pagamento. Tente novamente.";
            }
        }
    }
}

// Buscar mensagem de sucesso via GET
if(isset($_GET['sucesso'])) {
    $mensagem = "Pagamento registado! Aguarde confirmação do funcionário.";
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
          ORDER BY p.data_criacao DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$query = "SELECT 
          COUNT(*) as total,
          SUM(CASE WHEN estado = 'confirmado' THEN valor ELSE 0 END) as total_pago,
          SUM(CASE WHEN estado = 'pendente' THEN valor ELSE 0 END) as total_pendente
          FROM pagamentos WHERE utilizador_id = :utilizador_id";
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
    <title>Pagamentos - RentCar</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .stats-pagamentos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1E3A5F;
        }
        
        .filtros {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .filtro-btn {
            padding: 0.4rem 1rem;
            border: none;
            background: #e9ecef;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filtro-btn.ativo, .filtro-btn:hover {
            background: #1E3A5F;
            color: white;
        }
        
        .pagamento-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .pagamento-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #eee;
        }
        
        .pagamento-ref {
            font-weight: bold;
            color: #1E3A5F;
        }
        
        .pagamento-valor {
            font-size: 1.3rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .status-confirmado {
            background: #28a745;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .status-pendente {
            background: #ffc107;
            color: #333;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .form-pagamento {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .metodos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.8rem;
            margin: 1rem 0;
        }
        
        .metodo {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 0.8rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .metodo:hover {
            border-color: #FF8C00;
            background: rgba(255, 140, 0, 0.05);
        }
        
        .metodo.selecionado {
            border-color: #FF8C00;
            background: rgba(255, 140, 0, 0.1);
            color: #FF8C00;
        }
        
        .btn-recibo {
            background: #17a2b8;
            color: white;
            padding: 0.3rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.75rem;
        }
        
        .notificacao {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notificacao.sucesso {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .notificacao.erro {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .erro-metodo {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #dc3545;
        }
        
        .btn-confirmar {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 15px;
            transition: all 0.3s ease;
        }
        
        .btn-confirmar:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .detalhe-reserva {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .detalhe-reserva p {
            margin: 0.5rem 0;
        }
        
        .valor-destaque {
            font-size: 1.3rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .controlo-formulario {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($mensagem): ?>
                <div class="notificacao sucesso">
                    <i class="fas fa-check-circle"></i> <?= $mensagem ?>
                </div>
            <?php endif; ?>
            
            <?php if($erro): ?>
                <div class="notificacao erro">
                    <i class="fas fa-exclamation-triangle"></i> <?= $erro ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulário de Pagamento (se veio de uma reserva) -->
            <?php if($reserva): ?>
            <div class="form-pagamento">
                <h3 style="color: #1E3A5F; margin-bottom: 1rem;">
                   Pagar Reserva
                </h3>
                
                <div class="detalhe-reserva">
                    <p><strong> Viatura:</strong> <?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></p>
                    <p><strong> Período:</strong> <?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></p>
                    <p><strong> Valor total </strong> : <strong> MZN <?= number_format($valor_total, 2) ?></p></strong>
                </div>
                
                <form method="POST" id="formPagamento">
                    <input type="hidden" name="acao" value="pagar">
                    <input type="hidden" name="metodo_pagamento" id="metodo_selecionado" value="">
                    
                    <div class="form-group">
                        <label> Selecione o Método de Pagamento <span style="color: red;"> </span></label>
                        <div class="metodos">
                            <div class="metodo" data-metodo="dinheiro">
                                <i class="fas fa-money-bill-wave" style="font-size: 1.8rem;"></i><br>
                                Dinheiro
                            </div>
                            <div class="metodo" data-metodo="cartao">
                                <i class="fas fa-credit-card" style="font-size: 1.8rem;"></i><br>
                                Cartão
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Observações (opcional)</label>
                        <textarea name="observacoes" class="controlo-formulario" rows="2" placeholder="Notas adicionais..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-confirmar">
                        <i class="fas fa-check-circle"></i> Confirmar Pagamento
                    </button>
                </form>
            </div>
            
            <script>
                let metodoSelecionado = false;
                
                // Selecionar método de pagamento
                document.querySelectorAll('.metodo').forEach(el => {
                    el.addEventListener('click', function() {
                        document.querySelectorAll('.metodo').forEach(m => m.classList.remove('selecionado'));
                        this.classList.add('selecionado');
                        document.getElementById('metodo_selecionado').value = this.dataset.metodo;
                        metodoSelecionado = true;
                        
                        // Remover mensagem de erro se existir
                        const erroDiv = document.getElementById('erro-metodo-msg');
                        if(erroDiv) erroDiv.remove();
                    });
                });
                
                // Validar antes de enviar
                document.getElementById('formPagamento').addEventListener('submit', function(e) {
                    const metodo = document.getElementById('metodo_selecionado').value;
                    
                    if(!metodo) {
                        e.preventDefault();
                        
                        // Remover erro existente
                        const erroExistente = document.getElementById('erro-metodo-msg');
                        if(erroExistente) erroExistente.remove();
                        
                        // Criar mensagem de erro
                        const erroDiv = document.createElement('div');
                        erroDiv.id = 'erro-metodo-msg';
                        erroDiv.className = 'erro-metodo';
                        erroDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ❌ Por favor, selecione um método de pagamento!';
                        
                        // Inserir antes do formulário
                        const form = document.getElementById('formPagamento');
                        form.insertBefore(erroDiv, form.firstChild);
                        
                        // Scroll até o erro
                        erroDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            </script>
            <?php endif; ?>
            
            <!-- Estatísticas -->
            <div class="stats-pagamentos">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
                    <small>Total de Pagamentos</small>
                </div>
                <div class="stat-card">
                    <div class="stat-value">MZN <?= number_format($stats['total_pago'] ?? 0, 2) ?></div>
                    <small>Total Confirmado</small>
                </div>
                <div class="stat-card">
                    <div class="stat-value">MZN <?= number_format($stats['total_pendente'] ?? 0, 2) ?></div>
                    <small>Pendente</small>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filtros">
                <button class="filtro-btn ativo" data-filtro="todos"><i class="fas fa-list"></i> Todos</button>
                <button class="filtro-btn" data-filtro="confirmado"><i class="fas fa-check-circle"></i> Confirmados</button>
                <button class="filtro-btn" data-filtro="pendente"><i class="fas fa-clock"></i> Pendentes</button>
            </div>
            
            <!-- Lista de Pagamentos -->
            <div id="lista-pagamentos">
                <?php if(count($pagamentos) > 0): ?>
                    <?php foreach($pagamentos as $p): ?>
                    <div class="pagamento-card" data-status="<?= $p['estado'] ?>">
                        <div class="pagamento-header">
                            <div>
                                <span class="pagamento-ref"> <?= $p['referencia_pagamento'] ?></span>
                                <br>
                                <small> <?= date('d/m/Y - H:i', strtotime($p['data_criacao'])) ?></small>
                            </div>
                            <div class="pagamento-valor">MZN <?= number_format($p['valor'], 2) ?></div>
                        </div>
                        <div style="margin-bottom: 0.5rem;">
                            <strong> Descrição:</strong> <?= htmlspecialchars($p['descricao'] ?? '-') ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                            <div>
                                <strong> Método:</strong> <?= ucfirst(str_replace('_', ' ', $p['metodo_pagamento'])) ?>
                            </div>
                            <div>
                                <span class="status-<?= $p['estado'] ?>">
                                    <?php if($p['estado'] == 'confirmado'): ?>
                                        <i class="fas fa-check-circle"></i> Confirmado
                                    <?php else: ?>
                                        <i class="fas fa-hourglass-half"></i> Pendente
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <?php if($p['estado'] == 'confirmado'): ?>
                        <div style="margin-top: 0.8rem; text-align: right;">
                            <button class="btn-recibo" onclick="emitirRecibo(<?= $p['id'] ?>)">
                                <i class="fas fa-download"></i> Baixar Recibo
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="pagamento-card" style="text-align: center; padding: 2rem;">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p>Nenhum pagamento encontrado.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Filtros
        document.querySelectorAll('.filtro-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filtro = this.dataset.filtro;
                document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('ativo'));
                this.classList.add('ativo');
                
                document.querySelectorAll('.pagamento-card').forEach(card => {
                    if(filtro === 'todos' || card.dataset.status === filtro) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
        
        function emitirRecibo(id) {
            window.open(`../pagamentos/recibo.php?id=${id}`, '_blank');
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>
<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

$reserva_id = $_GET['reserva_id'] ?? 0;
$reserva = null;
$valor_total = 0;
$mensagem = '';
$erro = '';

// Buscar dados da reserva
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
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    $metodo = $_POST['metodo_pagamento'];
    $referencia = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    $observacoes = $_POST['observacoes'] ?? '';
    
    $query = "INSERT INTO pagamentos (utilizador_id, reserva_id, valor, metodo_pagamento, referencia_pagamento, estado, dados_transacao) 
              VALUES (:user_id, :reserva_id, :valor, :metodo, :referencia, 'pendente', :observacoes)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $utilizador['id']);
    $stmt->bindParam(':reserva_id', $reserva_id);
    $stmt->bindParam(':valor', $valor_total);
    $stmt->bindParam(':metodo', $metodo);
    $stmt->bindParam(':referencia', $referencia);
    $stmt->bindParam(':observacoes', $observacoes);
    
    if($stmt->execute()) {
        $mensagem = "✅ Pagamento registado com sucesso! Aguarde confirmação do funcionário.";
    } else {
        $erro = "❌ Erro ao processar pagamento. Tente novamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar Reserva - RentCar</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1E3A5F 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .pagamento-container {
            max-width: 480px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .pagamento-header {
            background: linear-gradient(135deg, #1E3A5F, #2a5298);
            padding: 25px;
            text-align: center;
            color: white;
        }
        
        .pagamento-header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .pagamento-header p {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .pagamento-body {
            padding: 25px;
        }
        
        .info-reserva {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
        }
        
        .info-linha {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.85rem;
        }
        
        .info-linha:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            color: #666;
        }
        
        .info-valor {
            font-weight: 600;
            color: #333;
        }
        
        .total {
            border-top: 1px solid #ddd;
            margin-top: 10px;
            padding-top: 10px;
        }
        
        .total .info-valor {
            color: #28a745;
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #1E3A5F;
            font-size: 0.85rem;
        }
        
        .metodos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .metodo {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }
        
        .metodo:hover {
            border-color: #1E3A5F;
        }
        
        .metodo.selected {
            border-color: #1E3A5F;
            background: rgba(30,58,95,0.05);
        }
        
        .metodo-icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 5px;
        }
        
        .metodo-text {
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.85rem;
            resize: vertical;
            font-family: inherit;
        }
        
        textarea:focus {
            outline: none;
            border-color: #1E3A5F;
        }
        
        .btn-pagar {
            width: 100%;
            padding: 12px;
            background: #1E3A5F;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-pagar:hover {
            background: #FF8C00;
            transform: translateY(-2px);
        }
        
        .mensagem {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .erro {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .voltar {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #FF8C00;
            text-decoration: none;
            font-size: 0.8rem;
        }
        
        .voltar:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 500px) {
            .metodos {
                grid-template-columns: 1fr;
            }
            .pagamento-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="pagamento-container">
        <div class="pagamento-header">
            <h1>💳 Pagar Reserva</h1>
            <p>Conclua o seu pagamento de forma segura</p>
        </div>
        
        <div class="pagamento-body">
            <?php if($mensagem): ?>
                <div class="mensagem">
                    <?= $mensagem ?>
                </div>
                <a href="reservas.php" class="voltar">← Voltar para Minhas Reservas</a>
            <?php elseif($erro): ?>
                <div class="erro">
                    <?= $erro ?>
                </div>
                <a href="javascript:history.back()" class="voltar">← Tentar novamente</a>
            <?php elseif($reserva): ?>
            
            <!-- Informações da Reserva -->
            <div class="info-reserva">
                <div class="info-linha">
                    <span class="info-label">🚗 Viatura</span>
                    <span class="info-valor"><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></span>
                </div>
                <div class="info-linha">
                    <span class="info-label">📅 Período</span>
                    <span class="info-valor"><?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?> até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></span>
                </div>
                <div class="info-linha total">
                    <span class="info-label">💰 Valor total</span>
                    <span class="info-valor">MZN <?= number_format($valor_total, 2) ?></span>
                </div>
            </div>
            
            <!-- Formulário de Pagamento -->
            <form method="POST" id="formPagamento">
                <input type="hidden" name="acao" value="pagar">
                
                <div class="form-group">
                    <label>💳 Método de Pagamento</label>
                    <div class="metodos">
                        <div class="metodo" data-metodo="dinheiro">
                            <span class="metodo-icon">💵</span>
                            <span class="metodo-text">Dinheiro</span>
                        </div>
                        <div class="metodo" data-metodo="cartao">
                            <span class="metodo-icon">💳</span>
                            <span class="metodo-text">Cartão</span>
                        </div>
                    </div>
                    <input type="hidden" name="metodo_pagamento" id="metodo_selecionado" required>
                </div>
                
                <div class="form-group">
                    <label>📝 Observações (opcional)</label>
                    <textarea name="observacoes" rows="2" placeholder="Notas adicionais sobre o pagamento..."></textarea>
                </div>
                
                <button type="submit" class="btn-pagar">
                    ✅ Confirmar Pagamento
                </button>
            </form>
            
            <a href="reservas.php" class="voltar">← Voltar para Minhas Reservas</a>
            
            <?php else: ?>
            <div style="text-align: center; padding: 20px;">
                <p>⚠️ Reserva não encontrada ou já foi paga.</p>
                <a href="reservas.php" class="voltar">← Voltar para Minhas Reservas</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Seleção do método de pagamento
        document.querySelectorAll('.metodo').forEach(el => {
            el.addEventListener('click', function() {
                document.querySelectorAll('.metodo').forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
                document.getElementById('metodo_selecionado').value = this.dataset.metodo;
            });
        });
    </script>
</body>
</html>
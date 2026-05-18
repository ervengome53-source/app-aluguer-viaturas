<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Buscar detalhes da reserva
$query = "SELECT r.*, 
          u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
          v.marca, v.modelo, v.matricula, v.preco_dia
          FROM reservas r
          JOIN utilizadores u ON r.utilizador_id = u.id
          JOIN viaturas v ON r.viatura_id = v.id
          WHERE r.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$reserva) {
    header('Location: reservas.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Reserva - Funcionário</title>
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
            padding: 30px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header {
            background: linear-gradient(135deg, #1E3A5F, #2a5298);
            padding: 25px 30px;
            color: white;
        }
        
        .header h1 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header h1::before {
            content: "";
            font-size: 1.5rem;
        }
        
        .content {
            padding: 30px;
        }
        
        .info-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .info-section h3 {
            color: #1E3A5F;
            font-size: 0.9rem;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 8px;
            border-bottom: 2px solid #FF8C00;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .info-label {
            font-weight: bold;
            width: 130px;
            color: #1E3A5F;
        }
        
        .info-value {
            color: #333;
            flex: 1;
        }
        
        .status-pendente {
            background: #ffc107;
            color: #333;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .status-confirmada {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .acoes {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            .content {
                padding: 20px;
            }
            .info-row {
                flex-direction: column;
                margin-bottom: 12px;
            }
            .info-label {
                width: 100%;
                margin-bottom: 4px;
            }
            .acoes {
                flex-direction: column;
            }
            .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Detalhes da Reserva</h1>
        </div>
        
        <div class="content">
            <!-- Cliente -->
            <div class="info-section">
                <h3>👤 Cliente</h3>
                <div class="info-row">
                    <div class="info-label">Nome:</div>
                    <div class="info-value"><?= htmlspecialchars($reserva['cliente_nome']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?= htmlspecialchars($reserva['cliente_email']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Telefone:</div>
                    <div class="info-value"><?= htmlspecialchars($reserva['cliente_telefone'] ?? 'Não informado') ?></div>
                </div>
            </div>
            
            <!-- Viatura -->
            <div class="info-section">
                <h3>Viatura</h3>
                <div class="info-row">
                    <div class="info-label">Modelo:</div>
                    <div class="info-value"><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Matrícula:</div>
                    <div class="info-value"><?= htmlspecialchars($reserva['matricula']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Preço por dia:</div>
                    <div class="info-value">MZN <?= number_format($reserva['preco_dia'], 2) ?></div>
                </div>
            </div>
            
            <!-- Período -->
            <div class="info-section">
                <h3>Período da Reserva</h3>
                <div class="info-row">
                    <div class="info-label">Data de Início:</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Data de Fim:</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Total de Dias:</div>
                    <div class="info-value"><?= $reserva['total_dias'] ?> dias</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Valor Total:</div>
                    <div class="info-value"><strong style="color: #28a745;">MZN <?= number_format($reserva['preco_total'], 2) ?></strong></div>
                </div>
            </div>
            
            <!-- Status -->
            <div class="info-section">
                <h3>Status</h3>
                <div class="info-row">
                    <div class="info-label">Situação:</div>
                    <div class="info-value">
                        <?php if($reserva['status'] == 'pendente'): ?>
                            <span class="status-pendente">Pendente</span>
                        <?php elseif($reserva['status'] == 'confirmada'): ?>
                            <span class="status-confirmada">Confirmada</span>
                        <?php else: ?>
                            <span class="status-cancelada">Cancelada</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Data da Reserva:</div>
                    <div class="info-value"><?= date('d/m/Y H:i', strtotime($reserva['criado_em'])) ?></div>
                </div>
            </div>
            
            <!-- Botões -->
            <div class="acoes">
                <a href="reservas.php" class="btn btn-secondary">
                    Voltar
                </a>
                <?php if($reserva['status'] == 'pendente'): ?>
                    <button class="btn btn-success" onclick="confirmarReserva(<?= $reserva['id'] ?>)">
                        Confirmar Reserva
                    </button>
                    <button class="btn btn-danger" onclick="rejeitarReserva(<?= $reserva['id'] ?>)">
                        Rejeitar Reserva
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function confirmarReserva(id) {
            modal.confirmar('Confirmar esta reserva?', () => {
                window.location.href = `reservas.php?confirmar=${id}`;
            });
        }
        
        function rejeitarReserva(id) {
            modal.confirmar('Rejeitar esta reserva?', () => {
                window.location.href = `reservas.php?rejeitar=${id}`;
            });
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
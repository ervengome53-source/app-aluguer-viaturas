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

// Buscar aluguéis do cliente
$query = "SELECT a.*, v.marca, v.modelo, v.matricula
          FROM alugueis a
          JOIN viaturas v ON a.viatura_id = v.id
          WHERE a.utilizador_id = :cliente_id
          ORDER BY a.criado_em DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':cliente_id', $id);
$stmt->execute();
$alugueis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico do Cliente</title>
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
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
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
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header h1::before {
            content: "";
            font-size: 1.8rem;
        }
        
        .content {
            padding: 25px 30px;
        }
        
        /* Card do Cliente */
        .cliente-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #FF8C00;
        }
        
        .cliente-card h3 {
            color: #1E3A5F;
            font-size: 1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cliente-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .info-icon {
            font-size: 1.3rem;
        }
        
        .info-text {
            font-size: 0.9rem;
        }
        
        .info-text strong {
            color: #1E3A5F;
            display: block;
            font-size: 0.7rem;
            margin-bottom: 2px;
        }
        
        /* Tabela */
        .tabela-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        
        th {
            background: #1E3A5F;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        /* Status Badges */
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .status-finalizado {
            background: #d4edda;
            color: #155724;
        }
        
        .status-ativo {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-cancelado {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Valor */
        .valor {
            font-weight: 600;
            color: #28a745;
        }
        
        /* Botão Voltar */
        .btn-voltar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 25px;
        }
        
        .btn-voltar:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        /* Vazio */
        .vazio {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .vazio-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        /* Títulos */
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #1E3A5F;
            font-size: 1.2rem;
        }
        
        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabeçalho -->
        <div class="header">
            <h1>Histórico do Cliente</h1>
        </div>
        
        <!-- Conteúdo -->
        <div class="content">
            <!-- Card do Cliente -->
            <div class="cliente-card">
                <h3>👤 Informações do Cliente</h3>
                <div class="cliente-info">
                    <div class="info-item">
                        <div class="info-icon">👤</div>
                        <div class="info-text">
                            <strong>Nome</strong>
                            <?= htmlspecialchars($cliente['nome']) ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">📧</div>
                        <div class="info-text">
                            <strong>Email</strong>
                            <?= htmlspecialchars($cliente['email']) ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">📞</div>
                        <div class="info-text">
                            <strong>Telefone</strong>
                            <?= htmlspecialchars($cliente['telefone'] ?? '---') ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Título da Tabela -->
            <div class="section-title">
                <span></span>
                <span>Alugueres Realizados</span>
            </div>
            
            <!-- Tabela de Aluguéis -->
            <?php if(count($alugueis) > 0): ?>
            <div class="tabela-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Viatura</th>
                            <th>Período</th>
                            <th>Valor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($alugueis as $a): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($a['marca'] . ' ' . $a['modelo']) ?></strong>
                                <br>
                                <small style="color: #888;"><?= $a['matricula'] ?></small>
                            </td>
                            <td>
                                <?= date('d/m/Y', strtotime($a['data_inicio'])) ?>
                                <br>
                                <small>até <?= date('d/m/Y', strtotime($a['data_fim'])) ?></small>
                             </td>
                            <td class="valor">MZN <?= number_format($a['preco_total'], 2) ?></td>
                            <td>
                                <?php if($a['status'] == 'finalizado'): ?>
                                    <span class="status status-finalizado">Finalizado</span>
                                <?php elseif($a['status'] == 'ativo'): ?>
                                    <span class="status status-ativo">Em andamento</span>
                                <?php else: ?>
                                    <span class="status status-cancelado">Cancelado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="vazio">
                <div class="vazio-icon"></div>
                <p>Este cliente ainda não realizou nenhum aluguer.</p>
            </div>
            <?php endif; ?>
            
            <hr>
            
            <!-- Botão Voltar -->
            <a href="clientes.php" class="btn-voltar">
                Voltar para Lista de Clientes
            </a>
        </div>
    </div>
</body>
</html>
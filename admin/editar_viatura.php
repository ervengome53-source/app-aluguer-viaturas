<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensagem = '';
$erro = '';

// Buscar dados da viatura
$query = "SELECT * FROM viaturas WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$viatura = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$viatura) {
    header('Location: viaturas.php');
    exit();
}

// Processar edição
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $modelo = $_POST['modelo'];
    $marca = $_POST['marca'];
    $ano = $_POST['ano'];
    $matricula = $_POST['matricula'];
    $preco_dia = $_POST['preco_dia'];
    $tipo = $_POST['tipo'];
    $combustivel = $_POST['combustivel'];
    $transmissao = $_POST['transmissao'];
    $lugares = $_POST['lugares'];
    $status = $_POST['status'];
    $descricao = $_POST['descricao'];
    
    $query = "UPDATE viaturas SET 
              modelo = :modelo,
              marca = :marca,
              ano = :ano,
              matricula = :matricula,
              preco_dia = :preco_dia,
              tipo = :tipo,
              combustivel = :combustivel,
              transmissao = :transmissao,
              lugares = :lugares,
              status = :status,
              descricao = :descricao
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':modelo', $modelo);
    $stmt->bindParam(':marca', $marca);
    $stmt->bindParam(':ano', $ano);
    $stmt->bindParam(':matricula', $matricula);
    $stmt->bindParam(':preco_dia', $preco_dia);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->bindParam(':combustivel', $combustivel);
    $stmt->bindParam(':transmissao', $transmissao);
    $stmt->bindParam(':lugares', $lugares);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':descricao', $descricao);
    $stmt->bindParam(':id', $id);
    
    if($stmt->execute()) {
        $mensagem = 'Viatura atualizada com sucesso!';
        // Atualizar dados da viatura
        $viatura = array_merge($viatura, $_POST);
    } else {
        $erro = ' Erro ao atualizar viatura';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Viatura - Admin</title>
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
            max-width: 900px;
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
            content: " ";
            font-size: 1.5rem;
        }
        
        .content {
            padding: 30px;
        }
        
        .mensagem {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .erro {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .form-section h3 {
            color: #1E3A5F;
            font-size: 1rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 10px;
            border-bottom: 2px solid #FF8C00;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1E3A5F;
            font-size: 0.85rem;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #FF8C00;
            box-shadow: 0 0 0 3px rgba(255,140,0,0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
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
        
        .btn-primary {
            background: #1E3A5F;
            color: white;
        }
        
        .btn-primary:hover {
            background: #FF8C00;
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
        
        /* Status badges nos selects */
        select option {
            padding: 8px;
        }
        
        /* Responsivo */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .content {
                padding: 20px;
            }
            .header {
                padding: 20px;
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
            <h1>Editar Viatura</h1>
        </div>
        
        <div class="content">
            <?php if($mensagem): ?>
                <div class="mensagem">
                    <span></span>
                    <span><?= $mensagem ?></span>
                </div>
            <?php endif; ?>
            
            <?php if($erro): ?>
                <div class="erro">
                    <span></span>
                    <span><?= $erro ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="formEditarViatura">
                <!-- Secção: Informações Básicas -->
                <div class="form-section">
                    <h3>Informações Básicas</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Marca</label>
                            <input type="text" name="marca" value="<?= htmlspecialchars($viatura['marca']) ?>" required placeholder="Ex: Honda, BMW, Toyota">
                        </div>
                        <div class="form-group">
                            <label>Modelo</label>
                            <input type="text" name="modelo" value="<?= htmlspecialchars($viatura['modelo']) ?>" required placeholder="Ex: Civic, X5, Corolla">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ano</label>
                            <input type="number" name="ano" value="<?= $viatura['ano'] ?>" required placeholder="2024">
                        </div>
                        <div class="form-group">
                            <label>Matrícula</label>
                            <input type="text" name="matricula" value="<?= htmlspecialchars($viatura['matricula']) ?>" required placeholder="AB-12-34">
                        </div>
                    </div>
                </div>
                
                <!-- Secção: Preços e Categorias -->
                <div class="form-section">
                    <h3>Preços e Categorias</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Preço por dia (MZN)</label>
                            <input type="number" step="0.01" name="preco_dia" value="<?= $viatura['preco_dia'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo</label>
                            <select name="tipo">
                                <option value="carro" <?= $viatura['tipo'] == 'carro' ? 'selected' : '' ?>>Carro</option>
                                <option value="moto" <?= $viatura['tipo'] == 'moto' ? 'selected' : '' ?>>Moto</option>
                                <option value="van" <?= $viatura['tipo'] == 'van' ? 'selected' : '' ?>>Van</option>
                                <option value="luxo" <?= $viatura['tipo'] == 'luxo' ? 'selected' : '' ?>>Luxo</option>
                                <option value="economico" <?= $viatura['tipo'] == 'economico' ? 'selected' : '' ?>>Económico</option>
                                <option value="suv" <?= $viatura['tipo'] == 'suv' ? 'selected' : '' ?>>SUV</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Secção: Características Técnicas -->
                <div class="form-section">
                    <h3>Características Técnicas</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Combustível</label>
                            <select name="combustivel">
                                <option value="gasolina" <?= $viatura['combustivel'] == 'gasolina' ? 'selected' : '' ?>>Gasolina</option>
                                <option value="diesel" <?= $viatura['combustivel'] == 'diesel' ? 'selected' : '' ?>>Diesel</option>
                                <option value="eletrico" <?= $viatura['combustivel'] == 'eletrico' ? 'selected' : '' ?>>Elétrico</option>
                                <option value="hibrido" <?= $viatura['combustivel'] == 'hibrido' ? 'selected' : '' ?>>Híbrido</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Transmissão</label>
                            <select name="transmissao">
                                <option value="manual" <?= $viatura['transmissao'] == 'manual' ? 'selected' : '' ?>>Manual</option>
                                <option value="automatico" <?= $viatura['transmissao'] == 'automatico' ? 'selected' : '' ?>>Automático</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Lugares</label>
                            <input type="number" name="lugares" value="<?= $viatura['lugares'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="disponivel" <?= $viatura['status'] == 'disponivel' ? 'selected' : '' ?>>🟢 Disponível</option>
                                <option value="alugado" <?= $viatura['status'] == 'alugado' ? 'selected' : '' ?>>🔴 Alugado</option>
                                <option value="manutencao" <?= $viatura['status'] == 'manutencao' ? 'selected' : '' ?>>🟠 Manutenção</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Secção: Descrição -->
                <div class="form-section">
                    <h3>Descrição</h3>
                    <div class="form-group">
                        <textarea name="descricao" rows="4" placeholder="Descreva as características da viatura..."><?= htmlspecialchars($viatura['descricao']) ?></textarea>
                    </div>
                </div>
                
                <!-- Botões -->
                <div class="acoes">
                    <a href="viaturas.php" class="btn btn-secondary">
                       Cancelar
                    </a>
                    <button type="button" class="btn btn-primary" onclick="confirmarEdicao()">
                        Guardar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function confirmarEdicao() {
            modal.confirmar('Tem certeza que deseja guardar as alterações desta viatura?', () => {
                document.getElementById('formEditarViatura').submit();
            });
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
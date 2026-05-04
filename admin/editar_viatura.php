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
        $erro = 'Erro ao atualizar viatura';
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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1E3A5F;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #1E3A5F;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #FF8C00;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #1E3A5F;
            color: white;
        }
        .btn-primary:hover {
            background: #FF8C00;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .mensagem {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .erro {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .acoes {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Viatura</h1>
        
        <?php if($mensagem): ?>
            <div class="mensagem"><?= $mensagem ?></div>
        <?php endif; ?>
        
        <?php if($erro): ?>
            <div class="erro"><?= $erro ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Marca</label>
                    <input type="text" name="marca" value="<?= htmlspecialchars($viatura['marca']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Modelo</label>
                    <input type="text" name="modelo" value="<?= htmlspecialchars($viatura['modelo']) ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Ano</label>
                    <input type="number" name="ano" value="<?= $viatura['ano'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Matrícula</label>
                    <input type="text" name="matricula" value="<?= htmlspecialchars($viatura['matricula']) ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Preço por dia (Mts)</label>
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
                        <option value="disponivel" <?= $viatura['status'] == 'disponivel' ? 'selected' : '' ?>>Disponível</option>
                        <option value="alugado" <?= $viatura['status'] == 'alugado' ? 'selected' : '' ?>>Alugado</option>
                        <option value="manutencao" <?= $viatura['status'] == 'manutencao' ? 'selected' : '' ?>>Manutenção</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" rows="4"><?= htmlspecialchars($viatura['descricao']) ?></textarea>
            </div>
            
            <div class="acoes">
                <button type="submit" class="btn btn-primary"> Guardar Alterações</button>
                <a href="viaturas.php" class="btn btn-secondary"> Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
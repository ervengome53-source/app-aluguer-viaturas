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
        $viatura = array_merge($viatura, $_POST);
    } else {
        $erro = 'Erro ao atualizar viatura';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Viatura - Admin</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <?php if($mensagem): ?>
                <div class="notificacao sucesso"><i class="fas fa-check-circle"></i> <?= $mensagem ?></div>
            <?php endif; ?>
            
            <?php if($erro): ?>
                <div class="notificacao erro"><i class="fas fa-exclamation-triangle"></i> <?= $erro ?></div>
            <?php endif; ?>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"><i class="fas fa-edit"></i> Editar Viatura</h3>
                    <a href="viaturas.php" class="btn btn-secundario"><i class="fas fa-arrow-left"></i> Voltar</a>
                </div>
                
                <div class="cartao-corpo">
                    <form method="POST" id="formEditarViatura">
                        <!-- Secção: Informações Básicas -->
                        <div class="form-section">
                            <h4><i class="fas fa-info-circle"></i> Informações Básicas</h4>
                            <div class="form-row">
                                <div class="grupo-formulario">
                                    <label class="rotulo-formulario"><i class="fas fa-tag"></i> Marca</label>
                                    <input type="text" name="marca" class="controlo-formulario" value="<?= htmlspecialchars($viatura['marca']) ?>" required>
                                </div>
                                <div class="grupo-formulario">
                                    <label class="rotulo-formulario"><i class="fas fa-car-side"></i> Modelo</label>
                                    <input type="text" name="modelo" class="controlo-formulario" value="<?= htmlspecialchars($viatura['modelo']) ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="grupo-formulario">
                                    <label class="rotulo-formulario"><i class="fas fa-calendar"></i> Ano</label>
                                    <input type="number" name="ano" class="controlo-formulario" value="<?= $viatura['ano'] ?>" required>
                                </div>
                                <div class="grupo-formulario">
                                    <label class="rotulo-formulario"><i class="fas fa-id-card"></i> Matrícula</label>
                                    <input type="text" name="matricula" class="controlo-formulario" value="<?= htmlspecialchars($viatura['matricula']) ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Secção: Preços e Categorias -->
                        <div class="form-section">
                            <h4><i class="fas fa-tags"></i> Preços e Categorias</h4>
                            <div class="form-row">
                                <div class="grupo-formulario">
                                    <label class="rotulo-formulario"><i class="fas fa-money-bill-wave"></i> Preço por dia (MZN)</label>
                                    <input type="number" step="0.01" name="preco_dia" class="controlo-formulario" value="<?= $viatura['preco_dia'] ?>" required>
                                </div>
                                <div class="grupo-formulario">
                                    <label class="rotulo-formulario"><i class="fas fa-list"></i> Tipo</label>
                                    <select name="tipo" class="controlo-formulario">
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
                            <h4><i class="fas fa-cogs"></i> Características Técnicas</h4>
                            <div class="form-row">
                                <div class="grupo-formulario">
                                    <label class="rotulo-formulario"><i class="fas fa-gas-pump"></i> Combustível</label>
                                    <select name="combustivel" class="controlo-formulario">
                                        <option value="gasolina" <?= $viatura['combustivel'] == 'gasolina' ? 'selected' : '' ?>>Gasolina</option>
                                        <option value="diesel" <?= $viatura['combustivel'] == 'diesel' ? 'selected' : '' ?>>Diesel</option>
                                        <option value="eletrico" <?= $viatura['combustivel'] == 'eletrico' ? 'selected' : '' ?>>Elétrico</option>
                                        <option value="hibrido" <?= $viatura['combustivel'] == 'hibrido' ? 'selected' : '' ?>>Híbrido</option>
                                    </select>
                                </div>
                                <div class="grupo-formulario">
                                    <label class="rotulo-formulario"><i class="fas fa-sliders-h"></i> Transmissão</label>
                                    <select name="transmissao" class="controlo-formulario">
                                        <option value="manual" <?= $viatura['transmissao'] == 'manual' ? 'selected' : '' ?>>Manual</option>
                                        <option value="automatico" <?= $viatura['transmissao'] == 'automatico' ? 'selected' : '' ?>>Automático</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="grupo-formulario">
                                    <label class="rotulo-formulario"><i class="fas fa-users"></i> Lugares</label>
                                    <input type="number" name="lugares" class="controlo-formulario" value="<?= $viatura['lugares'] ?>" required>
                                </div>
                                <div class="grupo-formulario">
                                    <label class="rotulo-formulario"><i class="fas fa-toggle-on"></i> Status</label>
                                    <select name="status" class="controlo-formulario">
                                        <option value="disponivel" <?= $viatura['status'] == 'disponivel' ? 'selected' : '' ?>><i class="fas fa-check-circle"></i> Disponível</option>
                                        <option value="alugado" <?= $viatura['status'] == 'alugado' ? 'selected' : '' ?>><i class="fas fa-key"></i> Alugado</option>
                                        <option value="manutencao" <?= $viatura['status'] == 'manutencao' ? 'selected' : '' ?>><i class="fas fa-tools"></i> Manutenção</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Secção: Descrição -->
                        <div class="form-section">
                            <h4><i class="fas fa-align-left"></i> Descrição</h4>
                            <div class="grupo-formulario">
                                <textarea name="descricao" class="controlo-formulario" rows="4"><?= htmlspecialchars($viatura['descricao']) ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Botões -->
                        <div class="form-actions">
                            <button type="button" class="btn btn-secundario" onclick="window.location.href='viaturas.php'">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-primario">
                                <i class="fas fa-save"></i> Guardar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .form-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--cinza);
        }
        .form-section:last-child {
            border-bottom: none;
        }
        .form-section h4 {
            color: var(--azul-escuro);
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--cinza);
        }
    </style>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM viaturas WHERE status = 'disponivel' ORDER BY preco_dia ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$viaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar capa de cada viatura
function buscarFotoCapa($db, $viatura_id) {
    $query = "SELECT imagem_path FROM viaturas_imagens 
              WHERE viatura_id = :viatura_id AND is_capa = 1 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':viatura_id', $viatura_id);
    $stmt->execute();
    $foto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($foto) {
        return '../' . $foto['imagem_path'];
    }
    
    // Segunda tentativa: qualquer foto
    $query = "SELECT imagem_path FROM viaturas_imagens 
              WHERE viatura_id = :viatura_id 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':viatura_id', $viatura_id);
    $stmt->execute();
    $foto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($foto) {
        return '../' . $foto['imagem_path'];
    }
    
    return null;
}

// Mapeamento de imagens padrão (fallback)
$imagens_map = [
    '500' => 'fiat500.jpg',
    'Focus' => 'focus.jpg',
    'Golf' => 'golf.jpg',
    'Corolla' => 'corolla.jpg',
    'Civic' => 'civic.jpg',
    'X5' => 'bmw_x5.jpg',
    'Tucson' => 'tucson.jpg',
    'Classe C' => 'mercedes.jpg',
    'Model 3' => 'Pink Tesla.jpg',
    'Sprinter' => 'sprinter.jpg',
    'Auris V19' => 'auris.jpg'
];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Viaturas</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/cliente.css">
    <style>
        .grade-veiculos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1rem 0;
        }
        .cartao-veiculo {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .cartao-veiculo:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .imagem-veiculo {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f5f5f5;
        }
        .info-veiculo {
            padding: 1rem;
        }
        .titulo-veiculo {
            font-size: 1.2rem;
            font-weight: bold;
            color: #1E3A5F;
            margin-bottom: 0.5rem;
        }
        .preco-veiculo {
            font-size: 1.3rem;
            color: #FF8C00;
            font-weight: bold;
            margin: 0.5rem 0;
        }
        .btn-ver-detalhes {
            background: #1E3A5F;
            color: white;
            padding: 0.6rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
            width: 100%;
            transition: background 0.3s ease;
        }
        .btn-ver-detalhes:hover {
            background: #FF8C00;
        }
        .caracteristicas {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin: 0.5rem 0;
            font-size: 0.75rem;
            color: #666;
        }
        .caracteristicas span {
            background: #f0f0f0;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"><i class="fas fa-car"></i> Catálogo de Viaturas</h3>
                </div>
                
                <div class="grade-veiculos">
                    <?php foreach($viaturas as $viatura): ?>
                    <?php 
                    // Tentar buscar foto da galeria
                    $foto_capa = buscarFotoCapa($db, $viatura['id']);
                    
                    // Se não tiver foto na galeria, usar mapeamento padrão
                    if(!$foto_capa) {
                        $img_nome = $imagens_map[$viatura['modelo']] ?? $viatura['imagem'] ?? 'placeholder.jpg';
                        $foto_capa = '../uploads/veiculos/' . $img_nome;
                    }
                    ?>
                    <div class="cartao-veiculo">
                        <img src="<?= $foto_capa ?>" 
                             alt="<?= htmlspecialchars($viatura['modelo']) ?>" 
                             class="imagem-veiculo"
                             onerror="this.src='https://via.placeholder.com/300x200/1E3A5F/FFFFFF?text=Sem+Imagem'">
                        <div class="info-veiculo">
                            <h3 class="titulo-veiculo"><?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?></h3>
                            <div class="caracteristicas">
                                <span><i class="fas fa-calendar"></i> <?= $viatura['ano'] ?></span>
                                <span><i class="fas fa-tag"></i> <?= ucfirst($viatura['tipo']) ?></span>
                                <span><i class="fas fa-users"></i> <?= $viatura['lugares'] ?> lugares</span>
                                <span><i class="fas fa-cogs"></i> <?= ucfirst($viatura['transmissao']) ?></span>
                                <span><i class="fas fa-gas-pump"></i> <?= ucfirst($viatura['combustivel']) ?></span>
                            </div>
                            <p><?= htmlspecialchars(substr($viatura['descricao'], 0, 80)) ?>...</p>
                            <div class="preco-veiculo"><i class="fas fa-money-bill-wave"></i> <?= number_format($viatura['preco_dia'], 2) ?> <small>/dia</small></div>
                            <a href="detalhe_viatura.php?id=<?= $viatura['id'] ?>" class="btn-ver-detalhes">
                                <i class="fas fa-search"></i> Ver Detalhes
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>	
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>
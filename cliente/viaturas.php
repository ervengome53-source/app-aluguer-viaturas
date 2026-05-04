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
?>
<!DOCTYPE html>
<html lang="pt">
<head>
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
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo">Catálogo de Viaturas</h3>
                </div>
                
					<div class="grade-veiculos">
							<?php foreach($viaturas as $viatura): ?>
							<?php 
							// Mapeamento correto das imagens
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
								'Sprinter' => 'sprinter.jpg'
							];
							$img_nome = $imagens_map[$viatura['modelo']] ?? $viatura['imagem'] ?? 'placeholder.jpg';
							?>
							<div class="cartao-veiculo">
								<img src="../uploads/veiculos/<?= $img_nome ?>" 
									 alt="<?= htmlspecialchars($viatura['modelo']) ?>" 
									 class="imagem-veiculo"
									 onerror="this.src='https://via.placeholder.com/300x200/1E3A5F/FFFFFF?text=Sem+Imagem'">
								<div class="info-veiculo">
									<h3 class="titulo-veiculo"><?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?></h3>
									<p><?= $viatura['ano'] ?> • <?= ucfirst($viatura['tipo']) ?> • <?= $viatura['lugares'] ?> lugares</p>
									<p><?= htmlspecialchars(substr($viatura['descricao'], 0, 80)) ?>...</p>
									<div class="preco-veiculo"><?= number_format($viatura['preco_dia'], 2) ?> <small>/dia</small></div>
									<!-- Botão corrigido - agora vai para detalhes -->
									<a href="detalhe_viatura.php?id=<?= $viatura['id'] ?>" class="btn-ver-detalhes">
										 Ver Detalhes
									</a>
								</div>
							</div>
							<?php endforeach; ?>
					</div>	
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>
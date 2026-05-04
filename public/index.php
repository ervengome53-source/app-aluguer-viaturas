<?php
require_once '../config/database.php';
session_start();

$database = new Database();
$db = $database->getConnection();

// Filtros
$tipo = $_GET['tipo'] ?? '';
$preco_max = $_GET['preco_max'] ?? '';
$preco_min = $_GET['preco_min'] ?? '';

$query = "SELECT * FROM viaturas WHERE status = 'disponivel'";
$params = [];

if($tipo) {
    $query .= " AND tipo = :tipo";
    $params[':tipo'] = $tipo;
}
if($preco_max) {
    $query .= " AND preco_dia <= :preco_max";
    $params[':preco_max'] = $preco_max;
}
if($preco_min) {
    $query .= " AND preco_dia >= :preco_min";
    $params[':preco_min'] = $preco_min;
}

$query .= " ORDER BY preco_dia ASC";

$stmt = $db->prepare($query);
foreach($params as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}
$stmt->execute();
$viaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mapeamento das imagens
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
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Aluguer de Viaturas</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        :root {
            --laranja: #FF8C00;
            --azul-escuro: #1E3A5F;
            --transicao: all 0.3s ease;
            --sombra-grande: 0 10px 15px rgba(0,0,0,0.1);
        }
        
        body {
            margin: 0;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .cabecalho-publico {
            background: rgba(30, 58, 95, 0.95);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--laranja);
        }
        
        .botoes-nav {
            display: flex;
            gap: 1rem;
        }
        
        .botoes-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: var(--transicao);
        }
        
        .botoes-nav a:hover {
            background: var(--laranja);
        }
        
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=1920');
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            margin-top: 60px;
        }
        
        .conteudo-hero {
            max-width: 600px;
            padding: 2rem;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .container-principal {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            margin-top: -50px;
            position: relative;
            z-index: 10;
            box-shadow: var(--sombra-grande);
        }
        
        .barra-filtros {
            background: white;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .grupo-filtro {
            flex: 1;
            min-width: 150px;
        }
        
        .rotulo-formulario {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--azul-escuro);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .controlo-formulario {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transicao);
        }
        
        .controlo-formulario:focus {
            outline: none;
            border-color: var(--laranja);
            box-shadow: 0 0 0 3px rgba(255,140,0,0.1);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transicao);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .btn-primario {
            background: var(--azul-escuro);
            color: white;
        }
        
        .btn-primario:hover {
            background: var(--laranja);
            transform: translateY(-2px);
        }
        
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
            transition: var(--transicao);
        }
        
        .cartao-veiculo:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .imagem-veiculo {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f0f0f0;
        }
        
        .info-veiculo {
            padding: 1rem;
        }
        
        .titulo-veiculo {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--azul-escuro);
            margin-bottom: 0.5rem;
        }
        
        .preco-veiculo {
            font-size: 1.3rem;
            color: var(--laranja);
            font-weight: bold;
            margin: 0.5rem 0;
        }
        
        .rodape {
            background: var(--azul-escuro);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 2rem; }
            .grade-veiculos { grid-template-columns: 1fr; }
            .barra-filtros { flex-direction: column; }
            .grupo-filtro { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="cabecalho-publico">
        <div class="logo">SIGAV</div>
        <div class="botoes-nav">
            <a href="login.php">Entrar</a>
            <a href="register.php">Registar</a>
        </div>
    </div>
    
    <div class="hero">
        <div class="conteudo-hero">
            <h1>Alugue o Carro dos Seus Sonhos</h1>
            <p>Preços competitivos, Viaturas modernas e atendimento 24/7</p>
        </div>
    </div>
    
    <div class="container-principal">
        <div class="barra-filtros">
            <div class="grupo-filtro">
                <label class="rotulo-formulario"> Tipo de Veículo</label>
                <select class="controlo-formulario" id="tipoFiltro">
                    <option value="">Todos</option>
                    <option value="carro">Carro</option>
                    <option value="moto">Moto</option>
                    <option value="van">Van</option>
                    <option value="luxo">Luxo</option>
                    <option value="economico">Económico</option>
                    <option value="suv">SUV</option>
                </select>
            </div>
            <div class="grupo-filtro">
                <label class="rotulo-formulario"> Preço Mínimo</label>
                <input type="number" class="controlo-formulario" id="precoMin" placeholder="Mts">
            </div>
            <div class="grupo-filtro">
                <label class="rotulo-formulario"> Preço Máximo</label>
                <input type="number" class="controlo-formulario" id="precoMax" placeholder="Mts">
            </div>
            <div class="grupo-filtro">
                <button class="btn btn-primario" onclick="aplicarFiltros()"> Filtrar</button>
            </div>
        </div>
        
        <div class="grade-veiculos" id="gradeVeiculos">
            <?php foreach($viaturas as $viatura): ?>
            <?php 
            // Usar o mapeamento para obter a imagem correta
            $img_nome = $imagens_map[$viatura['modelo']] ?? $viatura['imagem'] ?? 'placeholder.jpg';
            ?>
            <div class="cartao-veiculo">
                <img src="../uploads/veiculos/<?= $img_nome ?>" 
                     alt="<?= htmlspecialchars($viatura['modelo']) ?>" 
                     class="imagem-veiculo"
                     onerror="this.src='https://via.placeholder.com/300x200/1E3A5F/FFFFFF?text=<?= urlencode($viatura['modelo']) ?>'">
                <div class="info-veiculo">
                    <h3 class="titulo-veiculo"><?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?></h3>
                    <p><?= $viatura['ano'] ?> • <?= ucfirst($viatura['tipo']) ?> • <?= $viatura['lugares'] ?> lugares</p>
                    <p><?= htmlspecialchars(substr($viatura['descricao'], 0, 100)) ?>...</p>
                    <div class="preco-veiculo"> Mts <?= number_format($viatura['preco_dia'], 2) ?> <small>/dia</small></div>
                    <button class="btn btn-primario" onclick="window.location.href='login.php'" style="width: 100%; margin-top: 0.5rem;">
                         Reservar Agora
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="rodape">
        <p>&copy; Sistema de Gestão de Aluguer de Viaturas</p>
        <p>Qualidade e Confiança em cada aluguer</p>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        function aplicarFiltros() {
            const tipo = document.getElementById('tipoFiltro').value;
            const precoMin = document.getElementById('precoMin').value;
            const precoMax = document.getElementById('precoMax').value;
            
            let url = 'index.php?';
            if(tipo) url += `tipo=${tipo}&`;
            if(precoMin) url += `preco_min=${precoMin}&`;
            if(precoMax) url += `preco_max=${precoMax}&`;
            
            window.location.href = url;
        }
    </script>
</body>
</html>
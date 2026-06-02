<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Filtros
$tipo_filtro = $_GET['tipo'] ?? 'todos';
$preco_min = $_GET['preco_min'] ?? '';
$preco_max = $_GET['preco_max'] ?? '';
$busca = $_GET['busca'] ?? '';

// Buscar viaturas com filtros
$query = "SELECT * FROM viaturas WHERE status = 'disponivel'";

if($tipo_filtro != 'todos') {
    $query .= " AND tipo = :tipo";
}
if($preco_min) {
    $query .= " AND preco_dia >= :preco_min";
}
if($preco_max) {
    $query .= " AND preco_dia <= :preco_max";
}
if($busca) {
    $query .= " AND (marca LIKE :busca OR modelo LIKE :busca)";
}

$query .= " ORDER BY preco_dia ASC";

$stmt = $db->prepare($query);

if($tipo_filtro != 'todos') {
    $stmt->bindParam(':tipo', $tipo_filtro);
}
if($preco_min) {
    $stmt->bindParam(':preco_min', $preco_min);
}
if($preco_max) {
    $stmt->bindParam(':preco_max', $preco_max);
}
if($busca) {
    $buscaParam = "%$busca%";
    $stmt->bindParam(':busca', $buscaParam);
}

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

$tipos_veiculos = [
    'carro' => 'Carro',
    'moto' => 'Moto',
    'van' => 'Van',
    'luxo' => 'Luxo',
    'economico' => 'Económico',
    'suv' => 'SUV'
];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Catálogo de Viaturas - SIGAV</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            overflow-x: hidden;
        }

        .container-app {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .barra-lateral {
            width: 280px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            transition: all 0.3s ease;
        }

        .conteudo-principal {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: #f5f7fb;
            min-height: 100vh;
            width: calc(100% - 280px);
        }

        .barra-superior {
            background: white;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #666;
            font-size: 0.95rem;
        }

        /* Filters Bar */
        .filters-bar {
            background: white;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 0.8rem;
            font-size: 0.85rem;
            outline: none;
            background: white;
        }

        .filter-select:focus {
            border-color: #FF8C00;
        }

        .search-box {
            display: flex;
            gap: 0.5rem;
        }

        .search-input {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 0.8rem;
            font-size: 0.85rem;
            width: 220px;
            outline: none;
        }

        .search-input:focus {
            border-color: #FF8C00;
        }

        .btn-filter {
            background: #FF8C00;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
        }

        .btn-filter:hover {
            background: #e67e00;
            transform: translateY(-2px);
        }

        .btn-clear {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            text-decoration: none;
        }

        .btn-clear:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #FF8C00;
        }

        .stat-label {
            font-size: 0.7rem;
            color: #666;
            margin-top: 0.3rem;
        }

        /* Grid de Veículos */
        .veiculos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .veiculo-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .veiculo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .veiculo-imagem {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .veiculo-info {
            padding: 1rem;
        }

        .veiculo-titulo {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }

        .veiculo-caracteristicas {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 0.5rem 0;
        }

        .caracteristica {
            background: #f0f2f5;
            padding: 0.2rem 0.6rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            color: #666;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .veiculo-descricao {
            font-size: 0.8rem;
            color: #666;
            margin: 0.5rem 0;
            line-height: 1.4;
        }

        .veiculo-preco {
            font-size: 1.3rem;
            font-weight: 700;
            color: #FF8C00;
            margin: 0.5rem 0;
        }

        .veiculo-preco small {
            font-size: 0.7rem;
            font-weight: normal;
            color: #999;
        }

        .btn-detalhes {
            background: #FF8C00;
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 0.8rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            text-decoration: none;
        }

        .btn-detalhes:hover {
            background: #e67e00;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
            grid-column: span 3;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .barra-lateral {
                width: 0;
                transform: translateX(-100%);
            }
            .conteudo-principal {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .filters-bar {
                flex-direction: column;
            }
            .filter-group {
                width: 100%;
                justify-content: center;
            }
            .search-box {
                width: 100%;
            }
            .search-input {
                flex: 1;
            }
            .veiculos-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="page-header">
                <h1>Catálogo de Viaturas</h1>
                <p>Escolha a viatura perfeita para a sua viagem</p>
            </div>
            
            <!-- Filters -->
            <div class="filters-bar">
                <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; width: 100%;">
                    <div class="filter-group">
                        <select name="tipo" class="filter-select" onchange="this.form.submit()">
                            <option value="todos" <?= $tipo_filtro == 'todos' ? 'selected' : '' ?>>Todos os tipos</option>
                            <?php foreach($tipos_veiculos as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $tipo_filtro == $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="preco_min" class="filter-select" onchange="this.form.submit()">
                            <option value="">Preço mínimo</option>
                            <option value="500" <?= $preco_min == '500' ? 'selected' : '' ?>>MZN 500</option>
                            <option value="1000" <?= $preco_min == '1000' ? 'selected' : '' ?>>MZN 1.000</option>
                            <option value="2000" <?= $preco_min == '2000' ? 'selected' : '' ?>>MZN 2.000</option>
                            <option value="5000" <?= $preco_min == '5000' ? 'selected' : '' ?>>MZN 5.000</option>
                        </select>
                        
                        <select name="preco_max" class="filter-select" onchange="this.form.submit()">
                            <option value="">Preço máximo</option>
                            <option value="500" <?= $preco_max == '500' ? 'selected' : '' ?>>MZN 500</option>
                            <option value="1000" <?= $preco_max == '1000' ? 'selected' : '' ?>>MZN 1.000</option>
                            <option value="2000" <?= $preco_max == '2000' ? 'selected' : '' ?>>MZN 2.000</option>
                            <option value="5000" <?= $preco_max == '5000' ? 'selected' : '' ?>>MZN 5.000</option>
                            <option value="10000" <?= $preco_max == '10000' ? 'selected' : '' ?>>MZN 10.000</option>
                        </select>
                    </div>
                    
                    <div class="search-box">
                        <input type="text" name="busca" class="search-input" placeholder="Buscar por marca ou modelo..." value="<?= htmlspecialchars($busca) ?>">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <?php if($tipo_filtro != 'todos' || $preco_min || $preco_max || $busca): ?>
                            <a href="catalogo.php" class="btn-clear">
                                <i class="fas fa-times"></i> Limpar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= count($viaturas) ?></div>
                    <div class="stat-label"><i class="fas fa-car"></i> Viaturas Disponíveis</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($viaturas ? min(array_column($viaturas, 'preco_dia')) : 0, 0) ?> MT</div>
                    <div class="stat-label"><i class="fas fa-tag"></i> Preço mais baixo</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= count(array_unique(array_column($viaturas, 'marca'))) ?></div>
                    <div class="stat-label"><i class="fas fa-building"></i> Marcas disponíveis</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $tipos_count = count(array_unique(array_column($viaturas, 'tipo')));
                        echo $tipos_count;
                        ?>
                    </div>
                    <div class="stat-label"><i class="fas fa-tag"></i> Tipos de veículos</div>
                </div>
            </div>
            
            <!-- Grid de Veículos -->
            <div class="veiculos-grid">
                <?php if(count($viaturas) > 0): ?>
                    <?php foreach($viaturas as $viatura): ?>
                    <?php 
                    $foto_capa = buscarFotoCapa($db, $viatura['id']);
                    $icone_tipo = '';
                    switch($viatura['tipo']) {
                        case 'carro': $icone_tipo = 'fa-car'; break;
                        case 'moto': $icone_tipo = 'fa-motorcycle'; break;
                        case 'van': $icone_tipo = 'fa-shuttle-van'; break;
                        case 'luxo': $icone_tipo = 'fa-gem'; break;
                        case 'economico': $icone_tipo = 'fa-coins'; break;
                        case 'suv': $icone_tipo = 'fa-car'; break;
                        default: $icone_tipo = 'fa-car';
                    }
                    ?>
                    <div class="veiculo-card">
                        <img src="<?= $foto_capa ?: 'https://via.placeholder.com/320x200/1a1a2e/FFFFFF?text=' . urlencode($viatura['marca'] . ' ' . $viatura['modelo']) ?>" 
                             alt="<?= htmlspecialchars($viatura['modelo']) ?>" 
                             class="veiculo-imagem"
                             onerror="this.src='https://via.placeholder.com/320x200/1a1a2e/FFFFFF?text=Sem+Imagem'">
                        <div class="veiculo-info">
                            <h3 class="veiculo-titulo"><?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?></h3>
                            
                            <div class="veiculo-caracteristicas">
                                <span class="caracteristica"><i class="fas fa-calendar"></i> <?= $viatura['ano'] ?></span>
                                <span class="caracteristica"><i class="<?= $icone_tipo ?>"></i> <?= ucfirst($viatura['tipo']) ?></span>
                                <span class="caracteristica"><i class="fas fa-users"></i> <?= $viatura['lugares'] ?> lugares</span>
                                <span class="caracteristica"><i class="fas fa-cogs"></i> <?= ucfirst($viatura['transmissao']) ?></span>
                                <span class="caracteristica"><i class="fas fa-gas-pump"></i> <?= ucfirst($viatura['combustivel']) ?></span>
                            </div>
                            
                            <p class="veiculo-descricao">
                                <?= htmlspecialchars(substr($viatura['descricao'] ?? 'Viatura disponível para aluguer', 0, 80)) ?>...
                            </p>
                            
                            <div class="veiculo-preco">
                                <i class="fas fa-money-bill-wave"></i> MZN <?= number_format($viatura['preco_dia'], 2) ?> <small>/dia</small>
                            </div>
                            
                            <a href="detalhe_viatura.php?id=<?= $viatura['id'] ?>" class="btn-detalhes">
                                <i class="fas fa-search"></i> Ver Detalhes
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Nenhuma viatura encontrada</p>
                        <small>Tente ajustar os filtros de busca</small>
                        <a href="catalogo.php" class="btn-filter" style="margin-top: 1rem;">
                            <i class="fas fa-sync-alt"></i> Limpar Filtros
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>
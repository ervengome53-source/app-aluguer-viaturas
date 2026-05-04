<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Se não tem ID, redirecionar
if($id <= 0) {
    header('Location: viaturas.php');
    exit();
}

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

// Mapeamento das imagens (igual ao catálogo)
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

// Processar reserva via POST
$mensagem = '';
$erro = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    
    // Calcular dias
    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    $dias = $inicio->diff($fim)->days + 1;
    $preco_total = $viatura['preco_dia'] * $dias;
    
    // Inserir reserva
    $query = "INSERT INTO reservas (utilizador_id, viatura_id, data_inicio, data_fim, total_dias, preco_total) 
              VALUES (:user_id, :viatura_id, :data_inicio, :data_fim, :dias, :preco)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $utilizador['id']);
    $stmt->bindParam(':viatura_id', $viatura['id']);
    $stmt->bindParam(':data_inicio', $data_inicio);
    $stmt->bindParam(':data_fim', $data_fim);
    $stmt->bindParam(':dias', $dias);
    $stmt->bindParam(':preco', $preco_total);
    
    if($stmt->execute()) {
        $mensagem = 'Reserva realizada com sucesso!';
        echo "<script>setTimeout(() => { window.location.href = 'reservas.php'; }, 2000);</script>";
    } else {
        $erro = 'Erro ao realizar reserva. Tente novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?> - SIGAV</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .container-app {
            display: flex;
            min-height: 100vh;
        }
        
        .conteudo-principal {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
        }
        
        .detalhes-viatura {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 900px;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .imagem-viatura {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .preco-grande {
            font-size: 2rem;
            color: #FF8C00;
            font-weight: bold;
            margin: 1rem 0;
        }
        
        .caracteristicas-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 12px;
            margin: 1rem 0;
        }
        
        .form-reserva {
            background: #f5f5f5;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1.5rem;
        }
        
        .data-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .total-reserva {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin: 1rem 0;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .total-reserva span {
            color: #FF8C00;
            font-size: 1.5rem;
        }
        
        .btn-confirmar {
            background: #1E3A5F;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            width: 100%;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-confirmar:hover {
            background: #FF8C00;
        }
        
        .notificacao {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .notificacao.sucesso {
            background: #d4edda;
            color: #155724;
        }
        
        .notificacao.erro {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .conteudo-principal {
                margin-left: 0;
            }
            .caracteristicas-grid {
                grid-template-columns: 1fr;
            }
            .data-group {
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
            
            <?php if($mensagem): ?>
                <div class="notificacao sucesso"><?= $mensagem ?></div>
            <?php endif; ?>
            
            <?php if($erro): ?>
                <div class="notificacao erro"><?= $erro ?></div>
            <?php endif; ?>
            
            <div class="detalhes-viatura">
                <!-- IMAGEM DA VIATURA (mesma do catálogo) -->
                <img src="../uploads/veiculos/<?= $img_nome ?>" 
                     alt="<?= htmlspecialchars($viatura['modelo']) ?>" 
                     class="imagem-viatura"
                     onerror="this.src='https://via.placeholder.com/600x300/1E3A5F/FFFFFF?text=Sem+Imagem'">
                
                <h1><?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?></h1>
                <p class="preco-grande">Mts <?= number_format($viatura['preco_dia'], 2) ?> <small>/dia</small></p>
                
                <div class="caracteristicas-grid">
                    <div> Ano: <?= $viatura['ano'] ?></div>
                    <div> Tipo: <?= ucfirst($viatura['tipo']) ?></div>
                    <div> Transmissão: <?= ucfirst($viatura['transmissao']) ?></div>
                    <div> Combustível: <?= ucfirst($viatura['combustivel']) ?></div>
                    <div> Lugares: <?= $viatura['lugares'] ?></div>
                    <div> Status: <span style="color: <?= $viatura['status'] == 'disponivel' ? 'green' : 'red' ?>"><?= ucfirst($viatura['status']) ?></span></div>
                </div>
                
                <div class="descricao">
                    <h3> Descrição</h3>
                    <p><?= nl2br(htmlspecialchars($viatura['descricao'])) ?></p>
                </div>
                
                <?php if($viatura['status'] == 'disponivel'): ?>
                <div class="form-reserva">
                    <h3> Reservar este veículo</h3>
                    <form method="POST" id="formReserva">
                        <div class="data-group">
                            <div>
                                <label>Data de Início</label>
                                <input type="date" name="data_inicio" id="data_inicio" class="form-control" required>
                            </div>
                            <div>
                                <label>Data de Fim</label>
                                <input type="date" name="data_fim" id="data_fim" class="form-control" required>
                            </div>
                        </div>
                        <div class="total-reserva">
                            Total: <span id="totalValor">0.00</span> MZN
                        </div>
                        <button type="submit" class="btn-confirmar"> Confirmar Reserva</button>
                    </form>
                </div>
                <?php else: ?>
                <div style="background:#f8d7da; color:#721c24; padding:1rem; border-radius:8px; margin-top:1rem;">
                     Este veículo não está disponível no momento.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        const precoDia = <?= $viatura['preco_dia'] ?>;
        const dataInicio = document.getElementById('data_inicio');
        const dataFim = document.getElementById('data_fim');
        const totalSpan = document.getElementById('totalValor');
        
        function calcularTotal() {
            if(dataInicio.value && dataFim.value) {
                const inicio = new Date(dataInicio.value);
                const fim = new Date(dataFim.value);
                const dias = Math.ceil((fim - inicio) / (1000 * 60 * 60 * 24)) + 1;
                const total = dias * precoDia;
                totalSpan.innerHTML = total.toFixed(2);
            }
        }
        
        dataInicio.addEventListener('change', calcularTotal);
        dataFim.addEventListener('change', calcularTotal);
        
        // Configurar data mínima para hoje
        const hoje = new Date().toISOString().split('T')[0];
        dataInicio.min = hoje;
        dataFim.min = hoje;
    </script>
</body>
</html>
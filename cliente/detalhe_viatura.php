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

// Mapeamento das imagens (igual ao catálogo) - fallback
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* ============================================ */
        /* GALERIA DE FOTOS - ESTILOS */
        /* ============================================ */
        .galeria-container {
            margin-bottom: 1.5rem;
        }
        
        .foto-principal {
            background: #f5f5f5;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1rem;
            text-align: center;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .imagem-principal {
            width: 100%;
            max-height: 350px;
            object-fit: contain;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .imagem-principal:hover {
            transform: scale(1.02);
        }
        
        .miniaturas {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 0.5rem;
        }
        
        .miniatura {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .miniatura:hover {
            transform: scale(1.05);
        }
        
        .miniatura.ativa {
            border-color: #FF8C00;
            box-shadow: 0 0 0 2px rgba(255,140,0,0.3);
        }
        
        .sem-fotos {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 12px;
            color: #999;
        }
        
        .sem-fotos i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            color: #ccc;
        }
        
        /* Modal para ampliar imagem */
        .modal-imagem {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }
        
        .modal-imagem img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }
        
        .modal-imagem .close-modal {
            position: absolute;
            top: 20px;
            right: 35px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .modal-imagem .close-modal:hover {
            color: #FF8C00;
        }
        
        /* ============================================ */
        
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
            .miniatura {
                width: 55px;
                height: 55px;
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
                
                <!-- ============================================ -->
                <!-- GALERIA DE FOTOS (MÚLTIPLAS IMAGENS) -->
                <!-- ============================================ -->
                <div class="galeria-container">
                    <div class="foto-principal" id="fotoPrincipal">
                        <img id="fotoPrincipalImg" src="" alt="Foto principal" class="imagem-principal">
                    </div>
                    <div class="miniaturas" id="miniaturas">
                        <!-- Miniaturas serão carregadas via JavaScript -->
                        <div class="sem-fotos" id="loadingFotos">
                            <i class="fas fa-spinner fa-pulse"></i>
                            <p>Carregando fotos...</p>
                        </div>
                    </div>
                </div>
                
                <!-- ============================================ -->
                <!-- INFORMAÇÕES DA VIATURA -->
                <!-- ============================================ -->
                <h1><i class="fas fa-car"></i> <?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?></h1>
                <p class="preco-grande"><i class="fas fa-money-bill-wave"></i> MZN <?= number_format($viatura['preco_dia'], 2) ?> <small>/dia</small></p>
                
                <div class="caracteristicas-grid">
                    <div><i class="fas fa-calendar"></i> Ano: <?= $viatura['ano'] ?></div>
                    <div><i class="fas fa-tag"></i> Tipo: <?= ucfirst($viatura['tipo']) ?></div>
                    <div><i class="fas fa-cogs"></i> Transmissão: <?= ucfirst($viatura['transmissao']) ?></div>
                    <div><i class="fas fa-gas-pump"></i> Combustível: <?= ucfirst($viatura['combustivel']) ?></div>
                    <div><i class="fas fa-users"></i> Lugares: <?= $viatura['lugares'] ?></div>
                    <div><i class="fas fa-chart-line"></i> Status: <span style="color: <?= $viatura['status'] == 'disponivel' ? '#28a745' : '#dc3545' ?>"><?= ucfirst($viatura['status']) ?></span></div>
                </div>
                
                <div class="descricao">
                    <h3><i class="fas fa-align-left"></i> Descrição</h3>
                    <p><?= nl2br(htmlspecialchars($viatura['descricao'])) ?></p>
                </div>
                
                <?php if($viatura['status'] == 'disponivel'): ?>
                <div class="form-reserva">
                    <h3><i class="fas fa-calendar-check"></i> Reservar este veículo</h3>
                    <form method="POST" id="formReserva">
                        <div class="data-group">
                            <div>
                                <label><i class="fas fa-calendar-alt"></i> Data de Início</label>
                                <input type="date" name="data_inicio" id="data_inicio" class="form-control" required>
                            </div>
                            <div>
                                <label><i class="fas fa-calendar-alt"></i> Data de Fim</label>
                                <input type="date" name="data_fim" id="data_fim" class="form-control" required>
                            </div>
                        </div>
                        <div class="total-reserva">
                            Total: <span id="totalValor">0.00</span> MZN
                        </div>
                        <button type="submit" class="btn-confirmar">
                            <i class="fas fa-check-circle"></i> Confirmar Reserva
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <div style="background:#f8d7da; color:#721c24; padding:1rem; border-radius:8px; margin-top:1rem;">
                    <i class="fas fa-exclamation-triangle"></i> Este veículo não está disponível no momento.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal para ampliar imagem -->
    <div id="modalImagem" class="modal-imagem" onclick="fecharModalImagem()">
        <span class="close-modal">&times;</span>
        <img id="modalImagemImg" src="" alt="Imagem ampliada">
    </div>
    
    <script>
        const precoDia = <?= $viatura['preco_dia'] ?>;
        const dataInicio = document.getElementById('data_inicio');
        const dataFim = document.getElementById('data_fim');
        const totalSpan = document.getElementById('totalValor');
        const viaturaId = <?= $id ?>;
        
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
        
        // ============================================
        // CARREGAR GALERIA DE FOTOS
        // ============================================
        function carregarGaleria() {
            const miniaturasDiv = document.getElementById('miniaturas');
            const fotoPrincipalImg = document.getElementById('fotoPrincipalImg');
            
            fetch(`../api/buscar_fotos.php?viatura_id=${viaturaId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.sucesso && data.fotos.length > 0) {
                        const fotos = data.fotos;
                        
                        // Encontrar foto capa ou primeira
                        const fotoPrincipal = fotos.find(f => f.is_capa == 1) || fotos[0];
                        fotoPrincipalImg.src = '../' + fotoPrincipal.imagem_path;
                        fotoPrincipalImg.alt = 'Foto principal';
                        
                        // Gerar miniaturas
                        miniaturasDiv.innerHTML = fotos.map(foto => `
                            <img src="../${foto.imagem_path}" 
                                 class="miniatura ${foto.id === fotoPrincipal.id ? 'ativa' : ''}"
                                 data-id="${foto.id}"
                                 data-path="../${foto.imagem_path}"
                                 onclick="mudarFoto('${foto.imagem_path}', this)">
                        `).join('');
                    } else {
                        // Fallback: usar imagem padrão do catálogo
                        const imgPadrao = '../uploads/veiculos/<?= $img_nome ?>';
                        fotoPrincipalImg.src = imgPadrao;
                        fotoPrincipalImg.alt = '<?= htmlspecialchars($viatura['modelo']) ?>';
                        fotoPrincipalImg.onerror = function() {
                            this.src = 'https://via.placeholder.com/600x300/1E3A5F/FFFFFF?text=Sem+Imagem';
                        };
                        miniaturasDiv.innerHTML = `
                            <div class="sem-fotos">
                                <i class="fas fa-camera"></i>
                                <p>Nenhuma foto adicional disponível</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar fotos:', error);
                    // Fallback para imagem padrão
                    fotoPrincipalImg.src = '../uploads/veiculos/<?= $img_nome ?>';
                    fotoPrincipalImg.onerror = function() {
                        this.src = 'https://via.placeholder.com/600x300/1E3A5F/FFFFFF?text=Sem+Imagem';
                    };
                    miniaturasDiv.innerHTML = `
                        <div class="sem-fotos">
                            <i class="fas fa-camera"></i>
                            <p>Nenhuma foto adicional disponível</p>
                        </div>
                    `;
                });
        }
        
        function mudarFoto(caminho, elemento) {
            // Atualizar imagem principal
            document.getElementById('fotoPrincipalImg').src = '../' + caminho;
            
            // Remover classe ativa de todas as miniaturas
            document.querySelectorAll('.miniatura').forEach(el => {
                el.classList.remove('ativa');
            });
            
            // Adicionar classe ativa na miniatura clicada
            elemento.classList.add('ativa');
        }
        
        function ampliarImagem() {
            const imgSrc = document.getElementById('fotoPrincipalImg').src;
            if(imgSrc && !imgSrc.includes('placeholder')) {
                document.getElementById('modalImagemImg').src = imgSrc;
                document.getElementById('modalImagem').style.display = 'flex';
            }
        }
        
        function fecharModalImagem() {
            document.getElementById('modalImagem').style.display = 'none';
        }
        
        // Adicionar evento de clique na imagem principal para ampliar
        document.getElementById('fotoPrincipalImg').addEventListener('click', ampliarImagem);
        
        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                fecharModalImagem();
            }
        });
        
        // Carregar galeria ao iniciar
        carregarGaleria();
    </script>
</body>
</html>
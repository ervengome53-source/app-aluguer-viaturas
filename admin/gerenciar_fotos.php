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

// Criar diretório para fotos se não existir
$upload_dir = '../uploads/veiculos/' . $id . '/';
if(!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Buscar fotos da viatura
$query = "SELECT * FROM viaturas_imagens WHERE viatura_id = :viatura_id ORDER BY ordem ASC, id ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':viatura_id', $id);
$stmt->execute();
$fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Fotos - <?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?></title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .container-app { display: flex; min-height: 100vh; }
        .conteudo-principal { flex: 1; margin-left: 270px; padding: 1.5rem; }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #1E3A5F, #2a5298);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 { color: white; margin: 0; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        
        .btn-primary {
            background: #FF8C00;
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-primary:hover { background: #e67e00; transform: translateY(-2px); }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            text-decoration: none;
        }
        .btn-secondary:hover { background: #5a6268; transform: translateY(-2px); }
        
        /* Área de upload */
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            margin: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover { border-color: #FF8C00; background: rgba(255,140,0,0.05); }
        .upload-area.drag-over { border-color: #28a745; background: rgba(40,167,69,0.05); }
        
        .upload-icon { font-size: 3rem; color: #ccc; margin-bottom: 1rem; }
        .upload-text { color: #666; margin-bottom: 0.5rem; }
        .upload-subtext { font-size: 0.7rem; color: #999; }
        
        /* Galeria de fotos */
        .galeria {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
            padding: 1rem;
        }
        
        .foto-card {
            background: #f8f9fa;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #eee;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .foto-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        .foto-card.capa { border: 2px solid #FF8C00; }
        
        .foto-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            background: #e9ecef;
        }
        
        .foto-info {
            padding: 0.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .capa-badge {
            background: #FF8C00;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.6rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .btn-foto {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.3rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-capa { color: #FF8C00; }
        .btn-capa:hover { background: rgba(255,140,0,0.1); }
        
        .btn-excluir { color: #dc3545; }
        .btn-excluir:hover { background: rgba(220,53,69,0.1); }
        
        .sem-fotos {
            text-align: center;
            padding: 3rem;
            color: #999;
        }
        
        .sem-fotos i { font-size: 3rem; margin-bottom: 1rem; color: #ddd; }
        
        /* Loading */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            display: none;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #FF8C00;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .notificacao {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .notificacao.sucesso { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .notificacao.erro { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .conteudo-principal { margin-left: 0; padding: 1rem; }
            .galeria { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }
        }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-images"></i> 
                        Gerenciar Fotos - <?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?>
                        <small style="font-size: 0.7rem;">(<?= $viatura['matricula'] ?>)</small>
                    </h3>
                    <a href="viaturas.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
                
                <!-- Área de Upload -->
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="upload-text">Clique ou arraste fotos aqui</div>
                    <div class="upload-subtext">Formatos: JPG, PNG, WEBP, GIF (max. 5MB cada)</div>
                    <input type="file" id="fileInput" multiple accept="image/jpeg,image/png,image/webp,image/gif" style="display: none;">
                </div>
                
                <!-- Galeria de Fotos -->
                <div id="galeriaContainer">
                    <?php if(count($fotos) > 0): ?>
                    <div class="galeria" id="galeriaFotos">
                        <?php foreach($fotos as $foto): ?>
                        <div class="foto-card <?= $foto['is_capa'] ? 'capa' : '' ?>" data-id="<?= $foto['id'] ?>">
                            <img src="../<?= $foto['imagem_path'] ?>" class="foto-img" alt="Foto">
                            <div class="foto-info">
                                <div>
                                    <?php if($foto['is_capa']): ?>
                                        <span class="capa-badge"><i class="fas fa-star"></i> Capa</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if(!$foto['is_capa']): ?>
                                        <button class="btn-foto btn-capa" onclick="definirCapa(<?= $foto['id'] ?>, <?= $id ?>)" title="Definir como capa">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn-foto btn-excluir" onclick="removerFoto(<?= $foto['id'] ?>, <?= $id ?>)" title="Excluir foto">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="sem-fotos">
                        <i class="fas fa-camera"></i>
                        <p>Nenhuma foto adicionada ainda.</p>
                        <p style="font-size: 0.8rem;">Clique na área acima para adicionar fotos</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading -->
    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
    </div>
    
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const viaturaId = <?= $id ?>;
        
        // Clique na área de upload
        uploadArea.addEventListener('click', () => fileInput.click());
        
        // Drag & Drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if(files.length > 0) uploadFotos(files);
        });
        
        // Upload via input
        fileInput.addEventListener('change', (e) => {
            if(e.target.files.length > 0) uploadFotos(e.target.files);
        });
        
        function uploadFotos(files) {
            const formData = new FormData();
            
            for(let i = 0; i < files.length; i++) {
                formData.append('fotos[]', files[i]);
            }
            formData.append('viatura_id', viaturaId);
            
            document.getElementById('loading').style.display = 'flex';
            
            fetch('../api/upload_fotos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                if(data.sucesso) {
                    mostrarNotificacao('Fotos enviadas com sucesso!', 'sucesso');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    mostrarNotificacao(data.mensagem || 'Erro ao enviar fotos', 'erro');
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                mostrarNotificacao('Erro ao enviar fotos', 'erro');
            });
        }
        
        function definirCapa(fotoId, viaturaId) {
            document.getElementById('loading').style.display = 'flex';
            
            fetch('../api/definir_capa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ foto_id: fotoId, viatura_id: viaturaId })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                if(data.sucesso) {
                    mostrarNotificacao('Foto definida como capa!', 'sucesso');
                    window.location.reload();
                } else {
                    mostrarNotificacao(data.mensagem || 'Erro ao definir capa', 'erro');
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                mostrarNotificacao('Erro ao definir capa', 'erro');
            });
        }
        
        function removerFoto(fotoId, viaturaId) {
            if(confirm('Tem certeza que deseja remover esta foto?')) {
                document.getElementById('loading').style.display = 'flex';
                
                fetch('../api/remover_foto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ foto_id: fotoId, viatura_id: viaturaId })
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    if(data.sucesso) {
                        mostrarNotificacao('Foto removida!', 'sucesso');
                        window.location.reload();
                    } else {
                        mostrarNotificacao(data.mensagem || 'Erro ao remover foto', 'erro');
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    mostrarNotificacao('Erro ao remover foto', 'erro');
                });
            }
        }
        
        function mostrarNotificacao(mensagem, tipo) {
            const notificacao = document.createElement('div');
            notificacao.className = `notificacao ${tipo}`;
            notificacao.innerHTML = `<i class="fas ${tipo === 'sucesso' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i> ${mensagem}`;
            document.body.appendChild(notificacao);
            setTimeout(() => notificacao.remove(), 3000);
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
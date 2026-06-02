<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['utilizador_id']) || $_SESSION['utilizador_cargo'] !== 'admin') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$viatura_id = $_POST['viatura_id'] ?? 0;

if(!$viatura_id) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID da viatura não informado']);
    exit();
}

// Criar diretório
$upload_dir = '../uploads/veiculos/' . $viatura_id . '/';
if(!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$arquivos = $_FILES['fotos'] ?? [];
$sucessos = 0;
$erros = [];

if(empty($arquivos['name'][0])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum arquivo enviado']);
    exit();
}

$tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

// Buscar maior ordem atual
$query = "SELECT COALESCE(MAX(ordem), -1) as max_ordem FROM viaturas_imagens WHERE viatura_id = :viatura_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':viatura_id', $viatura_id);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$proxima_ordem = ($result['max_ordem'] ?? -1) + 1;

for($i = 0; $i < count($arquivos['name']); $i++) {
    $nome_original = $arquivos['name'][$i];
    $tipo = $arquivos['type'][$i];
    $tamanho = $arquivos['size'][$i];
    $tmp_name = $arquivos['tmp_name'][$i];
    
    // Validar tipo
    if(!in_array($tipo, $tipos_permitidos)) {
        $erros[] = "$nome_original: Formato não permitido";
        continue;
    }
    
    // Validar tamanho
    if($tamanho > $max_size) {
        $erros[] = "$nome_original: Excede 5MB";
        continue;
    }
    
    // Gerar nome único
    $extensao = pathinfo($nome_original, PATHINFO_EXTENSION);
    $nome_arquivo = time() . '_' . uniqid() . '.' . $extensao;
    $caminho = $upload_dir . $nome_arquivo;
    $caminho_db = 'uploads/veiculos/' . $viatura_id . '/' . $nome_arquivo;
    
    if(move_uploaded_file($tmp_name, $caminho)) {
        // Verificar se é a primeira foto (capa)
        $is_capa = ($proxima_ordem == 0) ? 1 : 0;
        
        $query = "INSERT INTO viaturas_imagens (viatura_id, imagem_path, ordem, is_capa) 
                  VALUES (:viatura_id, :path, :ordem, :is_capa)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':viatura_id', $viatura_id);
        $stmt->bindParam(':path', $caminho_db);
        $stmt->bindParam(':ordem', $proxima_ordem);
        $stmt->bindParam(':is_capa', $is_capa);
        
        if($stmt->execute()) {
            $sucessos++;
            $proxima_ordem++;
        } else {
            $erros[] = "$nome_original: Erro ao salvar no banco";
        }
    } else {
        $erros[] = "$nome_original: Erro ao fazer upload";
    }
}

echo json_encode([
    'sucesso' => $sucessos > 0,
    'mensagem' => $sucessos . ' foto(s) enviada(s) com sucesso' . (count($erros) > 0 ? '. Erros: ' . implode(', ', $erros) : ''),
    'total' => $sucessos
]);
?>
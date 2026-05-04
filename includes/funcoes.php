<?php
// includes/funcoes.php
// Funções auxiliares globais do sistema

/**
 * Sanitiza uma string para evitar XSS
 */
function sanitizar($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Gera uma referência única
 */
function gerarReferencia($prefixo = 'RENT') {
    return $prefixo . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Calcula o total com IVA e taxa de serviço
 */
function calcularTotalComTaxas($subtotal, $iva = 23, $taxa_servico = 5) {
    $valor_iva = $subtotal * ($iva / 100);
    $valor_taxa = $subtotal * ($taxa_servico / 100);
    return [
        'subtotal' => $subtotal,
        'iva' => $valor_iva,
        'taxa_servico' => $valor_taxa,
        'total' => $subtotal + $valor_iva + $valor_taxa
    ];
}

/**
 * Verifica se uma data está disponível para um veículo
 */
function verificarDisponibilidade($db, $veiculo_id, $data_inicio, $data_fim) {
    $query = "SELECT id FROM reservas 
              WHERE viatura_id = :veiculo_id 
              AND status IN ('pendente', 'confirmada')
              AND ((data_inicio BETWEEN :inicio AND :fim) 
              OR (data_fim BETWEEN :inicio AND :fim)
              OR (:inicio BETWEEN data_inicio AND data_fim))";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':veiculo_id', $veiculo_id);
    $stmt->bindParam(':inicio', $data_inicio);
    $stmt->bindParam(':fim', $data_fim);
    $stmt->execute();
    
    return $stmt->rowCount() == 0;
}

/**
 * Formata um valor para moeda
 */
function formatarMoeda($valor) {
    return '€ ' . number_format($valor, 2, ',', '.');
}

/**
 * Formata uma data para exibição
 */
function formatarData($data, $formato = 'd/m/Y') {
    return date($formato, strtotime($data));
}

/**
 * Calcula a idade a partir da data de nascimento
 */
function calcularIdade($data_nascimento) {
    $nascimento = new DateTime($data_nascimento);
    $hoje = new DateTime();
    return $hoje->diff($nascimento)->y;
}

/**
 * Gera um slug a partir de uma string
 */
function gerarSlug($texto) {
    $texto = preg_replace('~[^\\pL\d]+~u', '-', $texto);
    $texto = trim($texto, '-');
    $texto = iconv('utf-8', 'us-ascii//TRANSLIT', $texto);
    $texto = strtolower($texto);
    return preg_replace('~[^-a-z0-9]+~', '', $texto);
}

/**
 * Registra uma ação no log do sistema
 */
function registrarLog($db, $utilizador_id, $acao, $descricao) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    $query = "INSERT INTO registos (utilizador_id, acao, descricao, endereco_ip) 
              VALUES (:user_id, :acao, :descricao, :ip)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $utilizador_id);
    $stmt->bindParam(':acao', $acao);
    $stmt->bindParam(':descricao', $descricao);
    $stmt->bindParam(':ip', $ip);
    return $stmt->execute();
}

/**
 * Envia uma notificação push para o navegador (simplificado)
 */
function enviarNotificacao($titulo, $mensagem, $icone = '/assets/imagens/logo.png') {
    // Implementação para Web Push Notifications
    // Esta função seria integrada com um serviço como Firebase Cloud Messaging
    return true;
}

/**
 * Gera um número de recibo único
 */
function gerarNumeroRecibo() {
    return 'REC-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Verifica se o utilizador está autenticado via API
 */
function verificarAuthAPI($db) {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';
    
    if(empty($token)) {
        return false;
    }
    
    $token = str_replace('Bearer ', '', $token);
    $query = "SELECT id FROM tokens_api WHERE token = :token AND expira_em > NOW()";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Limpa o cache do sistema
 */
function limparCache() {
    $cacheDirs = ['../temp/cache', '../twig/cache'];
    foreach($cacheDirs as $dir) {
        if(is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach($files as $file) {
                if(is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
    return true;
}
?>
<?php
// includes/validacoes.php
// Funções de validação para o sistema

/**
 * Valida um endereço de email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida um número de telefone português
 */
function validarTelefone($telefone) {
    // Remove espaços e caracteres especiais
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    // Verifica se tem 9 dígitos e começa com 2, 9, ou 3
    return preg_match('/^(2[1-9]|9[0-9]|3[0-9])[0-9]{7}$/', $telefone);
}

/**
 * Valida um NIF português
 */
function validarNIF($nif) {
    $nif = preg_replace('/[^0-9]/', '', $nif);
    
    if(strlen($nif) != 9) {
        return false;
    }
    
    $check = 0;
    for($i = 0; $i < 8; $i++) {
        $check += $nif[$i] * (9 - $i);
    }
    $check = 11 - ($check % 11);
    if($check >= 10) $check = 0;
    
    return $check == $nif[8];
}

/**
 * Valida uma matrícula portuguesa (formato antigo e novo)
 */
function validarMatricula($matricula) {
    $matricula = strtoupper(preg_replace('/[^A-Z0-9]/', '', $matricula));
    
    // Formato antigo: AA-00-00
    if(preg_match('/^[A-Z]{2}[0-9]{2}[0-9]{2}$/', $matricula)) {
        return true;
    }
    // Formato novo: 00-AA-00
    if(preg_match('/^[0-9]{2}[A-Z]{2}[0-9]{2}$/', $matricula)) {
        return true;
    }
    
    return false;
}

/**
 * Valida uma data (formato YYYY-MM-DD)
 */
function validarData($data, $formato = 'Y-m-d') {
    $d = DateTime::createFromFormat($formato, $data);
    return $d && $d->format($formato) === $data;
}

/**
 * Valida se a data de início é anterior à data de fim
 */
function validarPeriodo($data_inicio, $data_fim) {
    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    return $inicio < $fim;
}

/**
 * Valida um número de cartão de crédito (algoritmo de Luhn)
 */
function validarCartaoCredito($numero) {
    $numero = preg_replace('/[^0-9]/', '', $numero);
    $sum = 0;
    $alt = false;
    
    for($i = strlen($numero) - 1; $i >= 0; $i--) {
        $n = $numero[$i];
        if($alt) {
            $n *= 2;
            if($n > 9) $n -= 9;
        }
        $sum += $n;
        $alt = !$alt;
    }
    
    return ($sum % 10) == 0 && strlen($numero) >= 13 && strlen($numero) <= 19;
}

/**
 * Valida o código CVV do cartão
 */
function validarCVV($cvv) {
    return preg_match('/^[0-9]{3,4}$/', $cvv);
}

/**
 * Valida uma senha (mínimo 6 caracteres, pelo menos 1 letra e 1 número)
 */
function validarSenha($senha) {
    return strlen($senha) >= 6 && preg_match('/[A-Za-z]/', $senha) && preg_match('/[0-9]/', $senha);
}

/**
 * Valida um CEP português
 */
function validarCEP($cep) {
    return preg_match('/^[0-9]{4}-[0-9]{3}$/', $cep);
}

/**
 * Valida se um valor está dentro de um intervalo
 */
function validarIntervalo($valor, $min, $max) {
    return $valor >= $min && $valor <= $max;
}

/**
 * Valida se um arquivo é uma imagem válida
 */
function validarImagem($arquivo, $maxSize = 5242880) { // 5MB padrão
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if($arquivo['size'] > $maxSize) {
        return false;
    }
    
    if(!in_array($arquivo['type'], $tiposPermitidos)) {
        return false;
    }
    
    if(!getimagesize($arquivo['tmp_name'])) {
        return false;
    }
    
    return true;
}

/**
 * Sanitiza e valida um input para evitar SQL Injection
 */
function validarInput($input, $tipo = 'string') {
    $input = trim($input);
    
    switch($tipo) {
        case 'email':
            return validarEmail($input) ? $input : false;
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT);
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT);
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL);
        default:
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}
?>
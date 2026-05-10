<?php
// pagamentos/recibo.php
require_once '../config/database.php';
require_once '../config/auth.php';

$pagamento_id = $_GET['id'] ?? 0;

$database = new Database();
$db = $database->getConnection();

// Buscar dados do pagamento
$query = "SELECT p.*, u.nome as cliente_nome, u.nif as cliente_nif, u.morada as cliente_morada,
          CASE 
              WHEN p.reserva_id IS NOT NULL THEN 
                  (SELECT CONCAT(v.marca, ' ', v.modelo, ' - ', r.data_inicio, ' a ', r.data_fim) 
                   FROM reservas r JOIN viaturas v ON r.viatura_id = v.id WHERE r.id = p.reserva_id)
              ELSE 
                  (SELECT CONCAT(v.marca, ' ', v.modelo, ' - ', a.data_inicio, ' a ', a.data_fim) 
                   FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = p.aluguer_id)
          END as descricao
          FROM pagamentos p 
          JOIN utilizadores u ON p.utilizador_id = u.id 
          WHERE p.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $pagamento_id);
$stmt->execute();
$pagamento = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$pagamento) {
    die('Pagamento não encontrado');
}

// Calcular valores
$subtotal = $pagamento['valor'] / 1.28;
$iva = $subtotal * 0.23;
$taxa_servico = $subtotal * 0.05;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo</title>
	   <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 1cm; }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 2rem;
            background: #f5f5f5;
        }
        
        .recibo {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .cabecalho-recibo {
            text-align: center;
            border-bottom: 2px solid var(--laranja);
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        
        .logo-recibo {
            font-size: 2rem;
            color: var(--azul-escuro);
        }
        
        .titulo-recibo {
            font-size: 1.5rem;
            color: var(--azul-escuro);
            margin: 0.5rem 0;
        }
        
        .info-empresa {
            text-align: center;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        .info-cliente {
            margin: 1rem 0;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .detalhes-pagamento {
            margin: 1rem 0;
        }
        
        .tabela-detalhes {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        
        .tabela-detalhes td {
            padding: 0.5rem;
            border-bottom: 1px solid #ddd;
        }
        
        .tabela-detalhes td:last-child {
            text-align: right;
        }
        
        .total {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--laranja);
        }
        
        .rodape-recibo {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 0.75rem;
            color: #666;
        }
        
        .btn-imprimir {
            background: var(--azul-escuro);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 1rem;
            font-size: 1rem;
        }
        
        .btn-imprimir:hover {
            background: var(--laranja);
        }
    </style>
</head>
<body>
    <div class="recibo">
        <div class="cabecalho-recibo">
            <div class="logo-recibo"> SIGAV</div>
            <div class="titulo-recibo">RECIBO DE PAGAMENTO</div>
        </div>
        
        <div class="info-empresa">
            <strong>SIGAV - Aluguer de Viaturas, Lda.</strong><br>
            NIF: 123456789<br>
            Morada: Matola, Circulo - Ndlavela<br>
            Tel: +258 000 000 | Email: sigav@gmail.com
        </div>
        
        <div class="info-cliente">
            <strong>Cliente:</strong> <?= htmlspecialchars($pagamento['cliente_nome']) ?><br>
            <strong>NUIT:</strong> <?= htmlspecialchars($pagamento['cliente_NUIT'] ?? 'Consumidor Final') ?><br>
            <strong>Morada:</strong> <?= htmlspecialchars($pagamento['cliente_morada'] ?? '---') ?>
        </div>
        
        <div class="detalhes-pagamento">
            <h3>Detalhes do Pagamento</h3>
            <table class="tabela-detalhes">
                <tr>
                    <td><strong>Referência:</strong> <?= $pagamento['referencia_pagamento'] ?></td>
                    <td><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pagamento['data_pagamento'] ?: $pagamento['data_criacao'])) ?></td>
                </tr>
                <tr>
                    <td><strong>Método:</strong> <?= ucfirst(str_replace('_', ' ', $pagamento['metodo_pagamento'])) ?></td>
                    <td><strong>Estado:</strong> <?= ucfirst($pagamento['estado']) ?></td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Descrição:</strong> <?= htmlspecialchars($pagamento['descricao']) ?></td>
                </tr>
            </table>
            
            <h3>Resumo de Valores</h3>
            <table class="tabela-detalhes">
                <tr>
                    <td>Subtotal</td>
                    <td>MZN<?= number_format($subtotal, 2) ?></td>
                </tr>
                <tr>
                    <td>IVA (23%)</td>
                    <td>MZN<?= number_format($iva, 2) ?></td>
                </tr>
                <tr>
                    <td>Taxa de Serviço (5%)</td>
                    <td>MZN<?= number_format($taxa_servico, 2) ?></td>
                </tr>
                <tr class="total">
                    <td><strong>TOTAL PAGO</strong></td>
                    <td><strong>MZN<?= number_format($pagamento['valor'], 2) ?></strong></td>
                </tr>
            </table>
        </div>
        
        <div class="rodape-recibo">
            <p>Este documento serve como comprovativo de pagamento válido para todos os efeitos legais.</p>
            <p>Obrigado pela preferência!</p>
        </div>
        
        <div style="text-align: center;" class="no-print">
            <button class="btn-imprimir" onclick="window.print()">
                 Imprimir Recibo
            </button>
            <button class="btn-imprimir" onclick="window.close()" style="background: #666;">
                 Fechar
            </button>
        </div>
    </div>
</body>
</html>
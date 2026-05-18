<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

$reserva_id = $_GET['reserva_id'] ?? 0;
$mensagem = '';
$erro = '';

// ============================================
// SE TEM RESERVA_ID -> MOSTRA APENAS FORMULÁRIO DE PAGAMENTO
// ============================================
if($reserva_id > 0) {
    // Buscar dados da reserva
    $query = "SELECT r.*, v.marca, v.modelo, v.preco_dia 
              FROM reservas r 
              JOIN viaturas v ON r.viatura_id = v.id 
              WHERE r.id = :id AND r.utilizador_id = :utilizador_id AND r.status = 'confirmada'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $reserva_id);
    $stmt->bindParam(':utilizador_id', $utilizador['id']);
    $stmt->execute();
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    $valor_total = $reserva['preco_total'] ?? 0;
    
    // Processar pagamento
    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
        $metodo = $_POST['metodo_pagamento'];
        $referencia = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $observacoes = $_POST['observacoes'] ?? '';
        
        // Recolher dados específicos do método
        $dados_especificos = [];
        if($metodo == 'cartao') {
            $dados_especificos = [
                'numero_cartao' => $_POST['numero_cartao'] ?? '',
                'validade' => $_POST['validade'] ?? '',
                'cvv' => $_POST['cvv'] ?? '',
                'nome_titular' => $_POST['nome_titular'] ?? ''
            ];
        } elseif($metodo == 'carteira_movel') {
            $dados_especificos = [
                'operadora' => $_POST['operadora'] ?? '',
                'telefone' => $_POST['telefone_carteira'] ?? '',
                'pin' => $_POST['pin_carteira'] ?? ''
            ];
        }
        
        $dados_json = json_encode($dados_especificos);
        
        $query = "INSERT INTO pagamentos (utilizador_id, reserva_id, valor, metodo_pagamento, referencia_pagamento, estado, dados_transacao) 
                  VALUES (:user_id, :reserva_id, :valor, :metodo, :referencia, 'pendente', :dados)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $utilizador['id']);
        $stmt->bindParam(':reserva_id', $reserva_id);
        $stmt->bindParam(':valor', $valor_total);
        $stmt->bindParam(':metodo', $metodo);
        $stmt->bindParam(':referencia', $referencia);
        $stmt->bindParam(':dados', $dados_json);
        
        if($stmt->execute()) {
            $mensagem = "Pagamento registado com sucesso! Aguarde confirmação do funcionário.";
        } else {
            $erro = "Erro ao processar pagamento. Tente novamente.";
        }
    }
    
    // MOSTRAR APENAS FORMULÁRIO DE PAGAMENTO
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pagar Reserva - RentCar</title>
        <link rel="stylesheet" href="../assets/css/estilo.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #1E3A5F 0%, #2a5298 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            .pagamento-container {
                max-width: 550px;
                width: 100%;
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.2);
                overflow: hidden;
                animation: fadeIn 0.3s ease;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: scale(0.95); }
                to { opacity: 1; transform: scale(1); }
            }
            .pagamento-header {
                background: linear-gradient(135deg, #1E3A5F, #2a5298);
                padding: 25px;
                text-align: center;
                color: white;
            }
            .pagamento-header h1 { font-size: 1.5rem; margin-bottom: 5px; }
            .pagamento-header p { font-size: 0.8rem; opacity: 0.8; }
            .pagamento-body { padding: 25px; }
            .info-reserva {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 15px;
                margin-bottom: 25px;
            }
            .info-linha {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
                font-size: 0.85rem;
            }
            .info-linha:last-child { margin-bottom: 0; }
            .info-label { color: #666; }
            .info-valor { font-weight: 600; color: #333; }
            .total { border-top: 1px solid #ddd; margin-top: 10px; padding-top: 10px; }
            .total .info-valor { color: #28a745; font-size: 1.1rem; }
            .form-group { margin-bottom: 20px; }
            .form-group label { display: block; margin-bottom: 10px; font-weight: 600; color: #1E3A5F; font-size: 0.85rem; }
            .metodos {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin-bottom: 20px;
            }
            .metodo {
                border: 2px solid #e9ecef;
                border-radius: 12px;
                padding: 15px;
                text-align: center;
                cursor: pointer;
                transition: all 0.2s ease;
                background: white;
            }
            .metodo:hover { border-color: #1E3A5F; }
            .metodo.selected { border-color: #1E3A5F; background: rgba(30,58,95,0.05); }
            .metodo-icon { font-size: 2rem; display: block; margin-bottom: 5px; }
            .metodo-text { font-size: 0.8rem; font-weight: 500; }
            .dados-pagamento {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 15px;
                margin-bottom: 20px;
                display: none;
            }
            .dados-pagamento.active { display: block; }
            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin-bottom: 15px;
            }
            input, select {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 0.85rem;
            }
            input:focus, select:focus { outline: none; border-color: #1E3A5F; }
            textarea {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 0.85rem;
                resize: vertical;
                font-family: inherit;
            }
            textarea:focus { outline: none; border-color: #1E3A5F; }
            .btn-pagar {
                width: 100%;
                padding: 12px;
                background: #1E3A5F;
                color: white;
                border: none;
                border-radius: 10px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-top: 10px;
            }
            .btn-pagar:hover { background: #FF8C00; transform: translateY(-2px); }
            .mensagem { background: #d4edda; color: #155724; padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
            .erro { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
            .voltar { display: block; text-align: center; margin-top: 20px; color: #FF8C00; text-decoration: none; font-size: 0.8rem; }
            .voltar:hover { text-decoration: underline; }
            @media (max-width: 600px) {
                .metodos { grid-template-columns: 1fr; }
                .form-row { grid-template-columns: 1fr; }
                .pagamento-body { padding: 20px; }
            }
        </style>
    </head>
    <body>
        <div class="pagamento-container">
            <div class="pagamento-header">
                <h1>Pagar Reserva</h1>
                <p>Conclua o seu pagamento de forma segura</p>
            </div>
            <div class="pagamento-body">
                <?php if($mensagem): ?>
                    <div class="mensagem"><?= $mensagem ?></div>
                    <a href="reservas.php" class="voltar">← Voltar para Minhas Reservas</a>
                <?php elseif($erro): ?>
                    <div class="erro"><?= $erro ?></div>
                    <a href="javascript:history.back()" class="voltar">← Tentar novamente</a>
                <?php elseif($reserva): ?>
                
                <!-- Informações da Reserva -->
                <div class="info-reserva">
                    <div class="info-linha"><span class="info-label">Viatura</span><span class="info-valor"><?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?></span></div>
                    <div class="info-linha"><span class="info-label">Período</span><span class="info-valor"><?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?> até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></span></div>
                    <div class="info-linha total"><span class="info-label">Valor total</span><span class="info-valor">MZN <?= number_format($valor_total, 2) ?></span></div>
                </div>
                
                <form method="POST" id="formPagamento">
                    <input type="hidden" name="acao" value="pagar">
                    
                    <!-- Métodos de Pagamento -->
                    <div class="form-group">
                        <label>Método de Pagamento</label>
                        <div class="metodos">
                            <div class="metodo" data-metodo="cartao">
                                <div class="metodo-icon"></div>
                                <div class="metodo-text">Cartão Crédito</div>
                            </div>
                            <div class="metodo" data-metodo="carteira_movel">
                                <div class="metodo-icon"></div>
                                <div class="metodo-text">Carteira Móvel</div>
                            </div>
                        </div>
                        <input type="hidden" name="metodo_pagamento" id="metodo_selecionado" required>
                    </div>
                    
                    <!-- Dados para Cartão de Crédito -->
                    <div id="dados_cartao" class="dados-pagamento">
                        <h4 style="margin-bottom: 15px; color: #1E3A5F;">Dados do Cartão</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Número do Cartão</label>
                                <input type="text" name="numero_cartao" placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                            <div class="form-group">
                                <label>Validade</label>
                                <input type="text" name="validade" placeholder="MM/AA">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="password" name="cvv" placeholder="123" maxlength="4">
                            </div>
                            <div class="form-group">
                                <label>Nome do Titular</label>
                                <input type="text" name="nome_titular" placeholder="Como no cartão">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dados para Carteira Móvel (M-Pesa/EMOLA) -->
                    <div id="dados_carteira_movel" class="dados-pagamento">
                        <h4 style="margin-bottom: 15px; color: #1E3A5F;">Dados da Carteira Móvel</h4>
                        <div class="form-group">
                            <label>Operadora</label>
                            <select name="operadora" class="controlo-formulario">
                                <option value="mpesa">M-Pesa</option>
                                <option value="emola">E-MOLA</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Número de Telemóvel</label>
                            <input type="tel" name="telefone_carteira" placeholder="84XXXXXXX" required>
                        </div>
                        <div class="form-group">
                            <label>PIN de Confirmação</label>
                            <input type="password" name="pin_carteira" placeholder="****" maxlength="4" required>
                        </div>
                        <small style="color: #666;">Será debitado o valor da sua conta de carteira móvel</small>
                    </div>
                    
                    <!-- Observações -->
                    <div class="form-group">
                        <label>Observações (opcional)</label>
                        <textarea name="observacoes" rows="2" placeholder="Notas adicionais sobre o pagamento..."></textarea>
                    </div>
                    
                    <!-- Botão que abre a modal de confirmação -->
                    <button type="button" class="btn-pagar" onclick="confirmarPagamento()">Confirmar Pagamento</button>
                </form>
                
                <a href="reservas.php" class="voltar">← Voltar para Minhas Reservas</a>
                
                <?php else: ?>
                <div style="text-align: center; padding: 20px;"><p>Reserva não encontrada ou já foi paga.</p><a href="reservas.php" class="voltar">← Voltar para Minhas Reservas</a></div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Modal de Confirmação -->
        <div id="modalConfirmarPagamento" class="modal" style="display: none;">
            <div class="modal-conteudo" style="max-width: 450px;">
                <div class="modal-cabecalho" style="background: linear-gradient(135deg, #1E3A5F, #2a5298);">
                    <h3 style="color: white;">Confirmar Pagamento</h3>
                    <button class="modal-fechar" onclick="fecharModalConfirmacao()" style="color: white;">&times;</button>
                </div>
                <div class="modal-corpo" style="padding: 20px;">
                    <p style="text-align: center; margin-bottom: 15px;">Tem certeza que deseja confirmar este pagamento?</p>
                    <div id="detalhesPagamentoModal" style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                        <!-- Detalhes serão preenchidos via JavaScript -->
                    </div>
                </div>
                <div class="modal-rodape" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px; background: #f8f9fa;">
                    <button class="btn btn-secundario" onclick="fecharModalConfirmacao()">Cancelar</button>
                    <button class="btn btn-success" id="btnConfirmarModal" onclick="enviarPagamento()">Confirmar</button>
                </div>
            </div>
        </div>
        
        <style>
            .modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 10000;
                justify-content: center;
                align-items: center;
            }
            .modal.ativo { display: flex; }
            .modal-conteudo {
                background: white;
                border-radius: 16px;
                width: 90%;
                max-width: 450px;
                overflow: hidden;
                animation: modalEntrar 0.3s ease;
            }
            @keyframes modalEntrar {
                from { transform: scale(0.9); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
            .modal-fechar {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
            }
            .btn-success { background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; }
            .btn-secundario { background: #6c757d; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; }
        </style>
        
        <script>
            let metodoAtual = '';
            
            // Seleção do método de pagamento
            document.querySelectorAll('.metodo').forEach(el => {
                el.addEventListener('click', function() {
                    const metodo = this.dataset.metodo;
                    metodoAtual = metodo;
                    
                    document.querySelectorAll('.metodo').forEach(m => m.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    document.querySelectorAll('.dados-pagamento').forEach(div => div.classList.remove('active'));
                    
                    if(metodo === 'cartao') {
                        document.getElementById('dados_cartao').classList.add('active');
                    } else if(metodo === 'carteira_movel') {
                        document.getElementById('dados_carteira_movel').classList.add('active');
                    }
                    
                    document.getElementById('metodo_selecionado').value = metodo;
                });
            });
            
            function confirmarPagamento() {
                const metodo = document.getElementById('metodo_selecionado').value;
                if(!metodo) {
                    alert('Selecione um método de pagamento');
                    return;
                }
                
                // Validar campos obrigatórios do método
                let valido = true;
                let detalhes = '';
                let dadosExtra = '';
                
                if(metodo === 'cartao') {
                    const numero = document.querySelector('input[name="numero_cartao"]').value;
                    const validade = document.querySelector('input[name="validade"]').value;
                    const cvv = document.querySelector('input[name="cvv"]').value;
                    const nome = document.querySelector('input[name="nome_titular"]').value;
                    
                    if(!numero || numero.replace(/\s/g, '').length < 16) { alert('Número do cartão inválido'); valido = false; }
                    else if(!validade) { alert('Validade inválida'); valido = false; }
                    else if(!cvv || cvv.length < 3) { alert('CVV inválido'); valido = false; }
                    else if(!nome) { alert('Nome do titular obrigatório'); valido = false; }
                    
                    detalhes = `Cartão: **** **** **** ${numero.slice(-4)}<br>Titular: ${nome}`;
                    
                } else if(metodo === 'carteira_movel') {
                    const operadora = document.querySelector('select[name="operadora"]').value;
                    const telefone = document.querySelector('input[name="telefone_carteira"]').value;
                    const pin = document.querySelector('input[name="pin_carteira"]').value;
                    
                    if(!telefone || telefone.length < 9) { alert('Número de telemóvel inválido'); valido = false; }
                    else if(!pin || pin.length < 4) { alert('PIN inválido'); valido = false; }
                    
                    const operadoraNome = operadora === 'mpesa' ? 'M-Pesa' : 'E-MOLA';
                    detalhes = `${operadoraNome}<br>Número: ${telefone}`;
                }
                
                if(!valido) return;
                
                // Mostrar modal com detalhes
                document.getElementById('detalhesPagamentoModal').innerHTML = `
                    <strong>Método:</strong> ${metodo === 'cartao' ? 'Cartão Crédito' : 'Carteira Móvel'}<br>
                    <strong>Valor:</strong> MZN <?= number_format($valor_total, 2) ?><br>
                    ${detalhes}
                `;
                
                document.getElementById('modalConfirmarPagamento').style.display = 'flex';
                document.getElementById('modalConfirmarPagamento').classList.add('ativo');
            }
            
            function fecharModalConfirmacao() {
                document.getElementById('modalConfirmarPagamento').style.display = 'none';
                document.getElementById('modalConfirmarPagamento').classList.remove('ativo');
            }
            
            function enviarPagamento() {
                document.getElementById('formPagamento').submit();
            }
        </script>
    </body>
    </html>
    <?php
    exit();
}

// ============================================
// SE NÃO TEM RESERVA_ID -> MOSTRA ESTATÍSTICAS E HISTÓRICO
// ============================================

// Buscar pagamentos do cliente
$query = "SELECT p.*, 
          CASE 
              WHEN p.reserva_id IS NOT NULL THEN 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM reservas r JOIN viaturas v ON r.viatura_id = v.id WHERE r.id = p.reserva_id)
              ELSE 
                  (SELECT CONCAT(v.marca, ' ', v.modelo) FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = p.aluguer_id)
          END as descricao
          FROM pagamentos p 
          WHERE p.utilizador_id = :utilizador_id 
          ORDER BY p.data_criacao DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$query = "SELECT 
          COUNT(*) as total,
          SUM(CASE WHEN estado = 'confirmado' THEN valor ELSE 0 END) as total_pago,
          SUM(CASE WHEN estado = 'pendente' THEN valor ELSE 0 END) as total_pendente
          FROM pagamentos WHERE utilizador_id = :utilizador_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':utilizador_id', $utilizador['id']);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pagamentos - RentCar</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <style>
        .stats-pagamentos { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: white; border-radius: 12px; padding: 1rem; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-value { font-size: 1.5rem; font-weight: bold; color: #1E3A5F; }
        .filtros { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .filtro-btn { padding: 0.3rem 0.8rem; border: none; background: #e9ecef; border-radius: 20px; cursor: pointer; font-size: 0.8rem; }
        .filtro-btn.ativo { background: #1E3A5F; color: white; }
        .pagamento-card { background: white; border-radius: 12px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .pagamento-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; }
        .status-confirmado { background: #28a745; color: white; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; }
        .status-pendente { background: #ffc107; color: #333; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem; }
        .btn-recibo { background: #17a2b8; color: white; padding: 0.2rem 0.6rem; border: none; border-radius: 5px; cursor: pointer; font-size: 0.7rem; }
        @media (max-width: 768px) { .stats-pagamentos { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="stats-pagamentos">
                <div class="stat-card"><div class="stat-value"><?= $stats['total'] ?? 0 ?></div><small>Total de Pagamentos</small></div>
                <div class="stat-card"><div class="stat-value">MZN <?= number_format($stats['total_pago'] ?? 0, 2) ?></div><small>Total Confirmado</small></div>
                <div class="stat-card"><div class="stat-value">MZN <?= number_format($stats['total_pendente'] ?? 0, 2) ?></div><small>Pendente</small></div>
            </div>
            
            <div class="filtros">
                <button class="filtro-btn ativo" data-filtro="todos">Todos</button>
                <button class="filtro-btn" data-filtro="confirmado">Confirmados</button>
                <button class="filtro-btn" data-filtro="pendente">Pendentes</button>
            </div>
            
            <div id="lista-pagamentos">
                <?php if(count($pagamentos) > 0): ?>
                    <?php foreach($pagamentos as $p): ?>
                    <div class="pagamento-card" data-status="<?= $p['estado'] ?>">
                        <div class="pagamento-header">
                            <div><strong><?= $p['referencia_pagamento'] ?></strong><br><small><?= date('d/m/Y H:i', strtotime($p['data_criacao'])) ?></small></div>
                            <div style="font-weight: bold; color: #28a745;">MZN <?= number_format($p['valor'], 2) ?></div>
                        </div>
                        <div><strong>Descrição:</strong> <?= htmlspecialchars($p['descricao'] ?? '-') ?></div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                            <div><strong>Método:</strong> <?= ucfirst(str_replace('_', ' ', $p['metodo_pagamento'])) ?></div>
                            <div><span class="status-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></div>
                        </div>
                        <?php if($p['estado'] == 'confirmado'): ?>
                        <div style="margin-top: 0.5rem; text-align: right;"><button class="btn-recibo" onclick="window.open('../pagamentos/recibo.php?id=<?= $p['id'] ?>', '_blank')">Baixar Recibo</button></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="pagamento-card" style="text-align: center; padding: 2rem;"><p>Nenhum pagamento encontrado.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('.filtro-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filtro = this.dataset.filtro;
                document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('ativo'));
                this.classList.add('ativo');
                document.querySelectorAll('.pagamento-card').forEach(card => {
                    card.style.display = (filtro === 'todos' || card.dataset.status === filtro) ? 'block' : 'none';
                });
            });
        });
    </script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cliente.js"></script>
</body>
</html>
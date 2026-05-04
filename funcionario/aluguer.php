<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$funcionario_id = $_SESSION['utilizador_id'];

$reserva_id = $_GET['reserva_id'] ?? null;
$cliente_id = $_GET['cliente_id'] ?? null;
$mensagem = '';
$erro = '';

// Processar registo de aluguer
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente_id = $_POST['cliente_id'];
    $viatura_id = $_POST['viatura_id'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $total_dias = $_POST['total_dias'];
    $preco_total = $_POST['preco_total'];
    $reserva_id = $_POST['reserva_id'] ?? null;
    
    $db->beginTransaction();
    
    try {
        // Criar aluguer
        $query = "INSERT INTO alugueis (reserva_id, utilizador_id, viatura_id, funcionario_id, 
                  data_inicio, data_fim, total_dias, preco_total, status) 
                  VALUES (:reserva_id, :cliente_id, :viatura_id, :funcionario_id, 
                  :data_inicio, :data_fim, :total_dias, :preco_total, 'ativo')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':reserva_id', $reserva_id);
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->bindParam(':viatura_id', $viatura_id);
        $stmt->bindParam(':funcionario_id', $funcionario_id);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->bindParam(':total_dias', $total_dias);
        $stmt->bindParam(':preco_total', $preco_total);
        $stmt->execute();
        $aluguer_id = $db->lastInsertId();
        
        // Atualizar status da viatura
        $query = "UPDATE viaturas SET status = 'alugado' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $viatura_id);
        $stmt->execute();
        
        // Se veio de reserva, atualizar status da reserva
        if($reserva_id) {
            $query = "UPDATE reservas SET status = 'confirmada' WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $reserva_id);
            $stmt->execute();
        }
        
        $db->commit();
        $mensagem = 'Aluguer registado com sucesso!';
        
        // Redirecionar após 2 segundos
        echo "<script>setTimeout(() => { window.location.href = 'dashboard.php'; }, 2000);</script>";
        
    } catch(Exception $e) {
        $db->rollBack();
        $erro = 'Erro ao registar aluguer: ' . $e->getMessage();
    }
}

// Buscar cliente se fornecida
$cliente = null;
if($cliente_id) {
    $query = "SELECT * FROM utilizadores WHERE id = :id AND cargo = 'cliente'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $cliente_id);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar reserva se fornecida
$reserva = null;
if($reserva_id) {
    $query = "SELECT r.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
              v.marca, v.modelo, v.matricula, v.preco_dia, v.id as viatura_id
              FROM reservas r
              JOIN utilizadores u ON r.utilizador_id = u.id
              JOIN viaturas v ON r.viatura_id = v.id
              WHERE r.id = :id AND r.status = 'confirmada'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $reserva_id);
    $stmt->execute();
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($reserva) {
        $cliente = [
            'id' => $reserva['utilizador_id'],
            'nome' => $reserva['cliente_nome'],
            'email' => $reserva['cliente_email'],
            'telefone' => $reserva['cliente_telefone']
        ];
    }
}

// Buscar viaturas disponíveis
$query = "SELECT * FROM viaturas WHERE status = 'disponivel' ORDER BY marca, modelo";
$stmt = $db->prepare($query);
$stmt->execute();
$viaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar Aluguer - Funcionário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/funcionario.css">
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
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Registar Novo Aluguer</h3>
                </div>
                
                <form method="POST" class="form-aluguer" id="formAluguer">
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Cliente</label>
                            <?php if($cliente): ?>
                                <input type="hidden" name="cliente_id" value="<?= $cliente['id'] ?>">
                                <input type="text" class="controlo-formulario" value="<?= htmlspecialchars($cliente['nome'] . ' - ' . $cliente['email']) ?>" readonly>
                            <?php else: ?>
                                <div class="busca-cliente">
                                    <input type="text" id="busca_cliente" class="controlo-formulario" placeholder="Digite nome ou email do cliente..." autocomplete="off">
                                    <input type="hidden" name="cliente_id" id="cliente_id" required>
                                    <div id="resultadosBusca" class="resultados-busca"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Viatura</label>
                            <select name="viatura_id" id="viatura_id" class="controlo-formulario" required <?= $reserva ? 'disabled' : '' ?>>
                                <option value="">Selecionar viatura</option>
                                <?php foreach($viaturas as $v): ?>
                                    <option value="<?= $v['id'] ?>" data-preco="<?= $v['preco_dia'] ?>" 
                                        <?= ($reserva && $reserva['viatura_id'] == $v['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' - ' . $v['matricula']) ?> 
                                        (Mts <?= number_format($v['preco_dia'], 2) ?>/dia)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if($reserva): ?>
                                <input type="hidden" name="viatura_id" value="<?= $reserva['viatura_id'] ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Data de Início</label>
                            <input type="date" name="data_inicio" id="data_inicio" class="controlo-formulario" 
                                   value="<?= $reserva ? $reserva['data_inicio'] : date('Y-m-d') ?>" required>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Data de Fim</label>
                            <input type="date" name="data_fim" id="data_fim" class="controlo-formulario" 
                                   value="<?= $reserva ? $reserva['data_fim'] : '' ?>" required>
                        </div>
                    </div>
                    
                    <input type="hidden" name="reserva_id" value="<?= $reserva_id ?>">
                    <input type="hidden" name="total_dias" id="total_dias">
                    <input type="hidden" name="preco_total" id="preco_total">
                    
                    <div class="resumo-aluguer">
                        <h4>Resumo do Aluguer</h4>
                        <div class="linha-resumo">
                            <span>Dias:</span>
                            <span id="resumo_dias">0</span>
                        </div>
                        <div class="linha-resumo">
                            <span>Preço por dia:</span>
                            <span id="resumo_preco_dia">MZN 0,00</span>
                        </div>
                        <div class="linha-resumo total">
                            <span>Total:</span>
                            <span id="resumo_total">MZN 0,00</span>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secundario" onclick="window.location.href='dashboard.php'">Cancelar</button>
                        <button type="submit" class="btn btn-primario"> Registrar Aluguer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        .resumo-aluguer {
            background: var(--cinza-claro);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .linha-resumo {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
        }
        
        .linha-resumo.total {
            border-top: 1px solid var(--cinza);
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .resultados-busca {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--cinza);
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }
        
        .resultados-busca.ativo {
            display: block;
        }
        
        .resultado-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: var(--transicao);
        }
        
        .resultado-item:hover {
            background: var(--cinza-claro);
        }
        
        .busca-cliente {
            position: relative;
        }
    </style>
    
    <script>
        let precoDia = <?= $reserva ? $reserva['preco_dia'] : 0 ?>;
        
        function calcularTotal() {
            const dataInicio = document.getElementById('data_inicio').value;
            const dataFim = document.getElementById('data_fim').value;
            
            if(dataInicio && dataFim && precoDia > 0) {
                const inicio = new Date(dataInicio);
                const fim = new Date(dataFim);
                const dias = Math.ceil((fim - inicio) / (1000 * 60 * 60 * 24)) + 1;
                const total = dias * precoDia;
                
                document.getElementById('total_dias').value = dias;
                document.getElementById('preco_total').value = total.toFixed(2);
                document.getElementById('resumo_dias').innerHTML = dias;
                document.getElementById('resumo_preco_dia').innerHTML = `Mts ${precoDia.toFixed(2)}`;
                document.getElementById('resumo_total').innerHTML = `Mts ${total.toFixed(2)}`;
            }
        }
        
        document.getElementById('viatura_id')?.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            precoDia = parseFloat(selected.dataset.preco || 0);
            calcularTotal();
        });
        
        document.getElementById('data_inicio')?.addEventListener('change', calcularTotal);
        document.getElementById('data_fim')?.addEventListener('change', calcularTotal);
        
        // Busca de cliente
        const buscaInput = document.getElementById('busca_cliente');
        if(buscaInput) {
            let timeout;
            buscaInput.addEventListener('input', function(e) {
                clearTimeout(timeout);
                timeout = setTimeout(async () => {
                    if(this.value.length >= 3) {
                        const resultado = await API.get(`../api/clientes.php?busca=${encodeURIComponent(this.value)}`);
                        const container = document.getElementById('resultadosBusca');
                        
                        if(resultado && resultado.sucesso && resultado.dados.length > 0) {
                            container.innerHTML = resultado.dados.map(c => `
                                <div class="resultado-item" onclick="selecionarCliente(${c.id}, '${c.nome}', '${c.email}')">
                                    <strong>${c.nome}</strong><br>
                                    <small>${c.email} | ${c.telefone || 'Sem telefone'}</small>
                                </div>
                            `).join('');
                            container.classList.add('ativo');
                        } else {
                            container.innerHTML = '<div class="resultado-item">Nenhum cliente encontrado</div>';
                            container.classList.add('ativo');
                        }
                    }
                }, 500);
            });
            
            document.addEventListener('click', function(e) {
                if(!e.target.closest('.busca-cliente')) {
                    document.getElementById('resultadosBusca')?.classList.remove('ativo');
                }
            });
        }
        
        function selecionarCliente(id, nome, email) {
            document.getElementById('cliente_id').value = id;
            buscaInput.value = `${nome} - ${email}`;
            document.getElementById('resultadosBusca').classList.remove('ativo');
            
            // Carregar reservas do cliente
            carregarReservasCliente(id);
        }
        
        async function carregarReservasCliente(clienteId) {
            const resultado = await API.get(`../api/reservas.php?acao=por_cliente&cliente_id=${clienteId}&status=confirmada`);
            if(resultado && resultado.sucesso && resultado.dados.length > 0) {
                // Mostrar opção de usar reserva existente
                const reservasHtml = resultado.dados.map(r => `
                    <option value="${r.id}" data-inicio="${r.data_inicio}" data-fim="${r.data_fim}" data-veiculo="${r.viatura_id}">
                        ${r.marca} ${r.modelo} - ${r.data_inicio} a ${r.data_fim}
                    </option>
                `).join('');
                
                modal.abrir(`
                    <h4>Reservas Confirmadas do Cliente</h4>
                    <select id="selectReserva" class="controlo-formulario" style="margin: 1rem 0;">
                        <option value="">Selecionar reserva existente</option>
                        ${reservasHtml}
                    </select>
                    <button class="btn btn-primario" onclick="usarReservaSelecionada()">Usar Esta Reserva</button>
                `, 'Reservas Disponíveis');
            }
        }
        
        function usarReservaSelecionada() {
            const select = document.getElementById('selectReserva');
            const reservaId = select.value;
            if(reservaId) {
                window.location.href = `aluguer.php?reserva_id=${reservaId}`;
            }
        }
        
        <?php if($reserva): ?>
        // Pré-calcular para reserva existente
        window.addEventListener('DOMContentLoaded', () => {
            calcularTotal();
        });
        <?php endif; ?>
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
</body>
</html>
<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Processar ações CRUD
$mensagem = '';
$erro = '';

// Criar/Editar viatura
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    
    if($acao == 'criar') {
        $query = "INSERT INTO viaturas (modelo, marca, ano, matricula, preco_dia, tipo, combustivel, transmissao, lugares, descricao, status) 
                  VALUES (:modelo, :marca, :ano, :matricula, :preco_dia, :tipo, :combustivel, :transmissao, :lugares, :descricao, 'disponivel')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':modelo', $_POST['modelo']);
        $stmt->bindParam(':marca', $_POST['marca']);
        $stmt->bindParam(':ano', $_POST['ano']);
        $stmt->bindParam(':matricula', $_POST['matricula']);
        $stmt->bindParam(':preco_dia', $_POST['preco_dia']);
        $stmt->bindParam(':tipo', $_POST['tipo']);
        $stmt->bindParam(':combustivel', $_POST['combustivel']);
        $stmt->bindParam(':transmissao', $_POST['transmissao']);
        $stmt->bindParam(':lugares', $_POST['lugares']);
        $stmt->bindParam(':descricao', $_POST['descricao']);
        
        if($stmt->execute()) {
            $mensagem = 'Viatura adicionada com sucesso!';
        } else {
            $erro = 'Erro ao adicionar viatura';
        }
    }
    
    if($acao == 'editar') {
        $query = "UPDATE viaturas SET modelo = :modelo, marca = :marca, ano = :ano, matricula = :matricula, 
                  preco_dia = :preco_dia, tipo = :tipo, combustivel = :combustivel, transmissao = :transmissao, 
                  lugares = :lugares, descricao = :descricao, status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':modelo', $_POST['modelo']);
        $stmt->bindParam(':marca', $_POST['marca']);
        $stmt->bindParam(':ano', $_POST['ano']);
        $stmt->bindParam(':matricula', $_POST['matricula']);
        $stmt->bindParam(':preco_dia', $_POST['preco_dia']);
        $stmt->bindParam(':tipo', $_POST['tipo']);
        $stmt->bindParam(':combustivel', $_POST['combustivel']);
        $stmt->bindParam(':transmissao', $_POST['transmissao']);
        $stmt->bindParam(':lugares', $_POST['lugares']);
        $stmt->bindParam(':descricao', $_POST['descricao']);
        $stmt->bindParam(':status', $_POST['status']);
        $stmt->bindParam(':id', $_POST['id']);
        
        if($stmt->execute()) {
            $mensagem = 'Viatura atualizada com sucesso!';
        } else {
            $erro = 'Erro ao atualizar viatura';
        }
    }
}

// Excluir viatura
if(isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $query = "DELETE FROM viaturas WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if($stmt->execute()) {
        $mensagem = 'Viatura excluída com sucesso!';
    } else {
        $erro = 'Erro ao excluir viatura';
    }
}

// Buscar todas as viaturas
$query = "SELECT * FROM viaturas ORDER BY criado_em DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$viaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Viaturas - Admin</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
                    <h3 class="cartao-titulo"> Gestão de Viaturas</h3>
                    <button class="btn btn-primario" onclick="abrirModalNovaViatura()">+ Nova Viatura</button>
                </div>
                
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Viatura</th>
                                <th>Matrícula</th>
                                <th>Preço/Dia</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($viaturas as $viatura): ?>
                            <tr>
                                <td><?= $viatura['id'] ?></td>
                                <td><?= htmlspecialchars($viatura['marca'] . ' ' . $viatura['modelo']) ?> <br>
                                    <small><?= $viatura['ano'] ?> • <?= ucfirst($viatura['tipo']) ?></small>
                                </td>
                                <td><?= $viatura['matricula'] ?></td>
                                <td>MZN <?= number_format($viatura['preco_dia'], 2) ?></td>
                                <td>
                                    <span class="etiqueta etiqueta-<?= $viatura['status'] == 'disponivel' ? 'sucesso' : ($viatura['status'] == 'alugado' ? 'aviso' : 'perigo') ?>">
                                        <?= ucfirst($viatura['status']) ?>
                                    </span>
                                </td>
                                <td class="tabela-acoes">
                                    <button class="btn btn-info btn-sm" onclick="editarViatura(<?= $viatura['id'] ?>)">editar</button>
                                    <button class="btn btn-perigo btn-sm" onclick="excluirViatura(<?= $viatura['id'] ?>)">excluir</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Nova Viatura -->
    <div id="modalViatura" class="modal" style="display: none;">
        <div class="modal-conteudo" style="max-width: 600px;">
            <div class="modal-cabecalho">
                <h3 id="modalTitulo">Nova Viatura</h3>
                <button class="modal-fechar" onclick="fecharModalViatura()">&times;</button>
            </div>
            <form id="formViatura" method="POST">
                <input type="hidden" name="acao" id="formAcao" value="criar">
                <input type="hidden" name="id" id="viaturaId">
                <div class="modal-corpo">
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Marca</label>
                            <input type="text" name="marca" id="marca" class="controlo-formulario" required>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Modelo</label>
                            <input type="text" name="modelo" id="modelo" class="controlo-formulario" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Ano</label>
                            <input type="number" name="ano" id="ano" class="controlo-formulario" required>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Matrícula</label>
                            <input type="text" name="matricula" id="matricula" class="controlo-formulario" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Preço por dia (MZN)</label>
                            <input type="number" step="0.01" name="preco_dia" id="preco_dia" class="controlo-formulario" required>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Tipo</label>
                            <select name="tipo" id="tipo" class="controlo-formulario" required>
                                <option value="carro">Carro</option>
                                <option value="moto">Moto</option>
                                <option value="van">Van</option>
                                <option value="luxo">Luxo</option>
                                <option value="economico">Económico</option>
                                <option value="suv">SUV</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Combustível</label>
                            <select name="combustivel" id="combustivel" class="controlo-formulario" required>
                                <option value="gasolina">Gasolina</option>
                                <option value="diesel">Diesel</option>
                                <option value="eletrico">Elétrico</option>
                                <option value="hibrido">Híbrido</option>
                            </select>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Transmissão</label>
                            <select name="transmissao" id="transmissao" class="controlo-formulario" required>
                                <option value="manual">Manual</option>
                                <option value="automatico">Automático</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Lugares</label>
                            <input type="number" name="lugares" id="lugares" class="controlo-formulario" value="5" required>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Status</label>
                            <select name="status" id="status" class="controlo-formulario">
                                <option value="disponivel">Disponível</option>
                                <option value="alugado">Alugado</option>
                                <option value="manutencao">Manutenção</option>
                            </select>
                        </div>
                    </div>
                    <div class="grupo-formulario">
                        <label class="rotulo-formulario">Descrição</label>
                        <textarea name="descricao" id="descricao" class="controlo-formulario" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-rodape">
                    <button type="button" class="btn btn-secundario" onclick="fecharModalViatura()">Cancelar</button>
                    <button type="submit" class="btn btn-primario">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function abrirModalNovaViatura() {
            document.getElementById('modalTitulo').innerText = 'Nova Viatura';
            document.getElementById('formAcao').value = 'criar';
            document.getElementById('formViatura').reset();
            document.getElementById('modalViatura').style.display = 'flex';
        }
        
        async function editarViatura(id) {
            const resultado = await API.get(`../api/viaturas.php?id=${id}`);
            if(resultado && resultado.sucesso) {
                const v = resultado.dados;
                document.getElementById('modalTitulo').innerText = 'Editar Viatura';
                document.getElementById('formAcao').value = 'editar';
                document.getElementById('viaturaId').value = v.id;
                document.getElementById('marca').value = v.marca;
                document.getElementById('modelo').value = v.modelo;
                document.getElementById('ano').value = v.ano;
                document.getElementById('matricula').value = v.matricula;
                document.getElementById('preco_dia').value = v.preco_dia;
                document.getElementById('tipo').value = v.tipo;
                document.getElementById('combustivel').value = v.combustivel;
                document.getElementById('transmissao').value = v.transmissao;
                document.getElementById('lugares').value = v.lugares;
                document.getElementById('status').value = v.status;
                document.getElementById('descricao').value = v.descricao;
                document.getElementById('modalViatura').style.display = 'flex';
            }
        }
        
		
		function editarViatura(id) {
        // Redirecionar para a página de edição
        window.location.href = `editar_viatura.php?id=${id}`;
    }
        function excluirViatura(id) {
            modal.confirmar('Tem certeza que deseja excluir esta viatura?', () => {
                window.location.href = `?excluir=${id}`;
            });
        }
        
        function fecharModalViatura() {
            document.getElementById('modalViatura').style.display = 'none';
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
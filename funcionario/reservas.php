<?php
require_once '../config/auth.php';
Auth::cargo(['funcionario']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$filtro = $_GET['filtro'] ?? 'pendentes';

// Processar confirmação
if(isset($_GET['confirmar'])) {
    $id = (int)$_GET['confirmar'];
    $stmt = $db->prepare("UPDATE reservas SET status = 'confirmada' WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header('Location: reservas.php?filtro=' . $filtro);
    exit();
}

// Processar rejeição
if(isset($_GET['rejeitar'])) {
    $id = (int)$_GET['rejeitar'];
    $stmt = $db->prepare("UPDATE reservas SET status = 'rejeitada' WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    header('Location: reservas.php?filtro=' . $filtro);
    exit();
}

// Buscar reservas conforme filtro
if($filtro == 'pendentes') {
    $query = "SELECT r.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
              v.marca, v.modelo, v.matricula, v.imagem
              FROM reservas r
              JOIN utilizadores u ON r.utilizador_id = u.id
              JOIN viaturas v ON r.viatura_id = v.id
              WHERE r.status = 'pendente'
              ORDER BY r.criado_em ASC";
} elseif($filtro == 'confirmadas') {
    $query = "SELECT r.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
              v.marca, v.modelo, v.matricula, v.imagem
              FROM reservas r
              JOIN utilizadores u ON r.utilizador_id = u.id
              JOIN viaturas v ON r.viatura_id = v.id
              WHERE r.status = 'confirmada'
              ORDER BY r.data_inicio ASC";
} else {
    $query = "SELECT r.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
              v.marca, v.modelo, v.matricula, v.imagem
              FROM reservas r
              JOIN utilizadores u ON r.utilizador_id = u.id
              JOIN viaturas v ON r.viatura_id = v.id
              ORDER BY r.criado_em DESC";
}

$stmt = $db->prepare($query);
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Reservas - Funcionário</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/funcionario.css">
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Gestão de Reservas</h3>
                    <div class="filtros-reservas">
                        <a href="?filtro=pendentes" class="btn <?= $filtro == 'pendentes' ? 'btn-primario' : 'btn-secundario' ?> btn-sm">
                             Pendentes
                        </a>
                        <a href="?filtro=confirmadas" class="btn <?= $filtro == 'confirmadas' ? 'btn-primario' : 'btn-secundario' ?> btn-sm">
                             Confirmadas
                        </a>
                        <a href="?filtro=todas" class="btn <?= $filtro == 'todas' ? 'btn-primario' : 'btn-secundario' ?> btn-sm">
                             Todas
                        </a>
                    </div>
                </div>
                
                <?php if(count($reservas) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Viatura</th>
                                <th>Período</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($reservas as $reserva): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($reserva['cliente_nome']) ?></strong><br>
                                    <small><?= $reserva['cliente_email'] ?></small><br>
                                    <small>📞 <?= $reserva['cliente_telefone'] ?? '---' ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($reserva['marca'] . ' ' . $reserva['modelo']) ?><br>
                                    <small><?= $reserva['matricula'] ?></small>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($reserva['data_inicio'])) ?><br>
                                    <small>até <?= date('d/m/Y', strtotime($reserva['data_fim'])) ?></small><br>
                                    <small><?= $reserva['total_dias'] ?> dias</small>
                                </td>
                                <td>MZN <?= number_format($reserva['preco_total'], 2) ?></td>
                                <td>
                                    <span class="etiqueta etiqueta-<?= $reserva['status'] == 'pendente' ? 'aviso' : 'sucesso' ?>">
                                        <?= $reserva['status'] == 'pendente' ? ' Pendente' : ' Confirmada' ?>
                                    </span>
                                </td>
                                <td class="tabela-acoes">
									<button class="btn btn-sucesso btn-sm" onclick="confirmarReserva(<?= $reserva['id'] ?>)">
										Confirmar
									</button>
									<button class="btn btn-perigo btn-sm" onclick="rejeitarReserva(<?= $reserva['id'] ?>)">
										Rejeitar
									</button>
									<a href="detalhe_reserva.php?id=<?= $reserva['id'] ?>" class="btn btn-info btn-sm">
										Detalhes
									</a>
								</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="texto-centro" style="padding: 3rem;">
                    <div style="font-size: 3rem;"></div>
                    <h3>Nenhuma reserva encontrada</h3>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
        .filtros-reservas {
            display: flex;
            gap: 0.5rem;
        }
        .tabela-acoes {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    </style>
	<script>
    function confirmarReserva(id) {
        modal.confirmar('Confirmar esta reserva?', () => {
            window.location.href = `?confirmar=${id}&filtro=<?= $filtro ?>`;
        });
    }
    
    function rejeitarReserva(id) {
        modal.confirmar('Rejeitar esta reserva?', () => {
            window.location.href = `?rejeitar=${id}&filtro=<?= $filtro ?>`;
        });
    }
</script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/funcionario.js"></script>
	
	
</body>
</html>
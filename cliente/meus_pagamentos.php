<?php
require_once '../config/auth.php';
Auth::cargo(['cliente']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$utilizador = Auth::utilizador();

// Buscar todos os pagamentos
$query = "SELECT p.*, 
          CASE 
              WHEN p.reserva_id IS NOT NULL THEN 
                  (SELECT CONCAT(v.marca, ' ', v.modelo, ' - ', r.data_inicio, ' a ', r.data_fim) 
                   FROM reservas r JOIN viaturas v ON r.viatura_id = v.id WHERE r.id = p.reserva_id)
              ELSE 
                  (SELECT CONCAT(v.marca, ' ', v.modelo, ' - ', a.data_inicio, ' a ', a.data_fim) 
                   FROM alugueis a JOIN viaturas v ON a.viatura_id = v.id WHERE a.id = p.aluguer_id)
          END as descricao,
          f.numero_fatura, f.pdf_path
          FROM pagamentos p 
          LEFT JOIN faturas f ON p.id = f.pagamento_id
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
    <title>Meus Pagamentos</title>
    <link rel="stylesheet" href="../assets/css/estilo.css">
    <link rel="stylesheet" href="../assets/css/cliente.css">
</head>
<body>
    <div class="container-app">
        <?php include '../components/barra_lateral.php'; ?>
        
        <div class="conteudo-principal">
            <?php include '../components/cabecalho.php'; ?>
            
            <div class="grade-estatisticas">
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Total de Pagamentos</h3>
                        <div class="estatistica-numero"><?= $stats['total'] ?? 0 ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Total Pago</h3>
                        <div class="estatistica-numero">MZN <?= number_format($stats['total_pago'] ?? 0, 2) ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
                <div class="cartao-estatistica">
                    <div class="estatistica-info">
                        <h3>Pendentes</h3>
                        <div class="estatistica-numero">MZN <?= number_format($stats['total_pendente'] ?? 0, 2) ?></div>
                    </div>
                    <div class="estatistica-icone"></div>
                </div>
            </div>
            
            <div class="cartao">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Extrato de Pagamentos</h3>
                    <button class="btn btn-info" onclick="window.print()">Imprimir</button>
                </div>
                
                <div class="filtros-pagamentos">
                    <button class="filtro-btn ativo" data-filtro="todos">Todos</button>
                    <button class="filtro-btn" data-filtro="confirmado">Confirmados</button>
                    <button class="filtro-btn" data-filtro="pendente">Pendentes</button>
                </div>
                
                <?php if(count($pagamentos) > 0): ?>
                <div class="container-tabela">
                    <table class="tabela" id="tabelaPagamentos">
                        <thead>
                            <tr>
                                <th>Referência</th>
                                <th>Descrição</th>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Método</th>
                                <th>Status</th>
                                <th>Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pagamentos as $pagamento): ?>
                            <tr data-status="<?= $pagamento['estado'] ?>">
                                <td><?= $pagamento['referencia_pagamento'] ?></td>
                                <td><?= htmlspecialchars(substr($pagamento['descricao'], 0, 50)) ?>...</td>
                                <td><?= date('d/m/Y', strtotime($pagamento['data_criacao'])) ?></td>
                                <td>MZN <?= number_format($pagamento['valor'], 2) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $pagamento['metodo_pagamento'])) ?></td>
                                <td>
                                    <span class="etiqueta etiqueta-<?= $pagamento['estado'] == 'confirmado' ? 'sucesso' : 'aviso' ?>">
                                        <?= ucfirst($pagamento['estado']) ?>
                                    </span>
                                 </td>
                                 <td>
                                    <?php if($pagamento['estado'] == 'confirmado'): ?>
                                        <button class="btn btn-info btn-sm" onclick="window.open('../pagamentos/recibo.php?id=<?= $pagamento['id'] ?>', '_blank')">
                                             Recibo
                                        </button>
                                        <?php if($pagamento['numero_fatura']): ?>
                                            <button class="btn btn-secundario btn-sm" onclick="window.open('<?= $pagamento['pdf_path'] ?>', '_blank')">
                                                 Fatura
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                  </td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="texto-centro" style="padding: 3rem;">
                    <div style="font-size: 3rem;"></div>
                    <h3>Nenhum pagamento encontrado</h3>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
        .filtros-pagamentos {
            display: flex;
            gap: 0.5rem;
            padding: 1rem;
            background: var(--cinza-claro);
            border-bottom: 1px solid var(--cinza);
        }
        
        .filtro-btn {
            padding: 0.4rem 1rem;
            border: none;
            background: var(--branco);
            border-radius: 20px;
            cursor: pointer;
            transition: var(--transicao);
        }
        
        .filtro-btn.ativo, .filtro-btn:hover {
            background: var(--laranja);
            color: var(--branco);
        }
    </style>
    
    <script>
        document.querySelectorAll('.filtro-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filtro = this.dataset.filtro;
                
                document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('ativo'));
                this.classList.add('ativo');
                
                document.querySelectorAll('#tabelaPagamentos tbody tr').forEach(row => {
                    if(filtro === 'todos' || row.dataset.status === filtro) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
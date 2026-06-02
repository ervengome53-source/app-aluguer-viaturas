<?php
require_once '../config/auth.php';
Auth::cargo(['admin']);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$mensagem = '';
$erro = '';

// Buscar configurações atuais
$query = "SELECT * FROM configuracoes WHERE id = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não existir configuração, criar padrão
if(!$config) {
    $query = "INSERT INTO configuracoes (id, nome_empresa, email_contato, telefone_contato, endereco, iva_padrao, taxa_servico, multa_atraso_dia) 
              VALUES (1, 'RentCar', 'geral@rentcar.com', '210000000', 'Rua Exemplo, 123 - Lisboa', 23, 5, 25)";
    $db->exec($query);
    $query = "SELECT * FROM configuracoes WHERE id = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Atualizar configurações
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_empresa = $_POST['nome_empresa'];
    $email_contato = $_POST['email_contato'];
    $telefone_contato = $_POST['telefone_contato'];
    $endereco = $_POST['endereco'];
    $iva_padrao = $_POST['iva_padrao'];
    $taxa_servico = $_POST['taxa_servico'];
    $multa_atraso_dia = $_POST['multa_atraso_dia'];
    $email_notificacoes = $_POST['email_notificacoes'];
    $dias_antecedencia_reserva = $_POST['dias_antecedencia_reserva'];
    $limite_cancelamento_horas = $_POST['limite_cancelamento_horas'];
    
    $query = "UPDATE configuracoes SET 
              nome_empresa = :nome_empresa,
              email_contato = :email_contato,
              telefone_contato = :telefone_contato,
              endereco = :endereco,
              iva_padrao = :iva_padrao,
              taxa_servico = :taxa_servico,
              multa_atraso_dia = :multa_atraso_dia,
              email_notificacoes = :email_notificacoes,
              dias_antecedencia_reserva = :dias_antecedencia_reserva,
              limite_cancelamento_horas = :limite_cancelamento_horas,
              updated_at = NOW()
              WHERE id = 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nome_empresa', $nome_empresa);
    $stmt->bindParam(':email_contato', $email_contato);
    $stmt->bindParam(':telefone_contato', $telefone_contato);
    $stmt->bindParam(':endereco', $endereco);
    $stmt->bindParam(':iva_padrao', $iva_padrao);
    $stmt->bindParam(':taxa_servico', $taxa_servico);
    $stmt->bindParam(':multa_atraso_dia', $multa_atraso_dia);
    $stmt->bindParam(':email_notificacoes', $email_notificacoes);
    $stmt->bindParam(':dias_antecedencia_reserva', $dias_antecedencia_reserva);
    $stmt->bindParam(':limite_cancelamento_horas', $limite_cancelamento_horas);
    
    if($stmt->execute()) {
        $mensagem = 'Configurações atualizadas com sucesso!';
        // Recarregar configurações
        $query = "SELECT * FROM configuracoes WHERE id = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $erro = 'Erro ao atualizar configurações';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Sistema - Admin</title>
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
                    <h3 class="cartao-titulo"> Configurações Gerais</h3>
                </div>
                
                <form method="POST" class="form-configuracoes">
                    <div class="config-section">
                        <h4> Dados da Empresa</h4>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">Nome da Empresa</label>
                                <input type="text" name="nome_empresa" class="controlo-formulario" 
                                       value="<?= htmlspecialchars($config['nome_empresa']) ?>" required>
                            </div>
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">Email de Contato</label>
                                <input type="email" name="email_contato" class="controlo-formulario" 
                                       value="<?= htmlspecialchars($config['email_contato']) ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">Telefone</label>
                                <input type="tel" name="telefone_contato" class="controlo-formulario" 
                                       value="<?= htmlspecialchars($config['telefone_contato']) ?>">
                            </div>
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">Email para Notificações</label>
                                <input type="email" name="email_notificacoes" class="controlo-formulario" 
                                       value="<?= htmlspecialchars($config['email_notificacoes']) ?>">
                            </div>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Endereço</label>
                            <textarea name="endereco" class="controlo-formulario" rows="2"><?= htmlspecialchars($config['endereco']) ?></textarea>
                        </div>
                    </div>
                    
                    <div class="config-section">
                        <h4> Taxas e Valores</h4>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">IVA Padrão (%)</label>
                                <input type="number" step="0.01" name="iva_padrao" class="controlo-formulario" 
                                       value="<?= $config['iva_padrao'] ?>" required>
                            </div>
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">Taxa de Serviço (%)</label>
                                <input type="number" step="0.01" name="taxa_servico" class="controlo-formulario" 
                                       value="<?= $config['taxa_servico'] ?>" required>
                            </div>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">Multa por Dia de Atraso (MZN)</label>
                            <input type="number" step="0.01" name="multa_atraso_dia" class="controlo-formulario" 
                                   value="<?= $config['multa_atraso_dia'] ?>" required>
                        </div>
                    </div>
                    
                    <div class="config-section">
                        <h4> Regras de Reserva</h4>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">Dias de Antecedência Mínima</label>
                                <input type="number" name="dias_antecedencia_reserva" class="controlo-formulario" 
                                       value="<?= $config['dias_antecedencia_reserva'] ?>">
                                <small>Dias necessários para fazer uma reserva</small>
                            </div>
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">Limite para Cancelamento (horas)</label>
                                <input type="number" name="limite_cancelamento_horas" class="controlo-formulario" 
                                       value="<?= $config['limite_cancelamento_horas'] ?>">
                                <small>Cancelamento gratuito até X horas antes</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="config-section">
                        <h4> Manutenção do Sistema</h4>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">
                                    <input type="checkbox" id="modo_manutencao" <?= ($config['modo_manutencao'] ?? 0) ? 'checked' : '' ?>>
                                    Ativar Modo de Manutenção
                                </label>
                            </div>
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">
                                    <input type="checkbox" id="backup_automatico" <?= ($config['backup_automatico'] ?? 1) ? 'checked' : '' ?>>
                                    Backup Automático Diário
                                </label>
                            </div>
                        </div>
                        <div class="grupo-formulario" id="div_mensagem_manutencao" style="display: none;">
                            <label class="rotulo-formulario">Mensagem de Manutenção</label>
                            <textarea name="mensagem_manutencao" class="controlo-formulario" rows="2"><?= htmlspecialchars($config['mensagem_manutencao'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="config-section">
                        <h4> Configurações de Email </h4>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">Servidor SMTP</label>
                                <input type="text" name="smtp_host" class="controlo-formulario" 
                                       value="<?= htmlspecialchars($config['smtp_host'] ?? 'smtp.gmail.com') ?>">
                            </div>
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">Porta SMTP</label>
                                <input type="number" name="smtp_port" class="controlo-formulario" 
                                       value="<?= $config['smtp_port'] ?? 587 ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">Email SMTP</label>
                                <input type="email" name="smtp_email" class="controlo-formulario" 
                                       value="<?= htmlspecialchars($config['smtp_email'] ?? '') ?>">
                            </div>
                            <div class="grupo-formulario">
                                <label class="rotulo-formulario">Senha SMTP</label>
                                <input type="password" name="smtp_senha" class="controlo-formulario" 
                                       placeholder="••••••••">
                            </div>
                        </div>
                        <div class="grupo-formulario">
                            <label class="rotulo-formulario">
                                <input type="checkbox" id="smtp_ssl" <?= ($config['smtp_ssl'] ?? 1) ? 'checked' : '' ?>>
                                Usar SSL/TLS
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions" style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" class="btn btn-secundario" onclick="resetarConfiguracoes()">Resetar Padrão</button>
                        <button type="submit" class="btn btn-primario"> Guardar Configurações</button>
                    </div>
                </form>
            </div>
            
            <div class="cartao" style="margin-top: 1.5rem;">
                <div class="cartao-cabecalho">
                    <h3 class="cartao-titulo"> Ações do Sistema</h3>
                </div>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button class="btn btn-info" onclick="backupBancoDados()">
                         Backup da Base de Dados
                    </button>
                    <button class="btn btn-info" onclick="limparCache()">
                         Limpar Cache
                    </button>
                    <button class="btn btn-perigo" onclick="verLogs()">
                         Ver Logs do Sistema
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .config-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--cinza);
        }
        
        .config-section:last-child {
            border-bottom: none;
        }
        
        .config-section h4 {
            color: var(--azul-escuro);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        small {
            display: block;
            font-size: 0.75rem;
            color: var(--cinza-escuro);
            margin-top: 0.25rem;
        }
    </style>
    
    <script>
        document.getElementById('modo_manutencao')?.addEventListener('change', function() {
            const div = document.getElementById('div_mensagem_manutencao');
            if(div) {
                div.style.display = this.checked ? 'block' : 'none';
            }
        });
        
        function resetarConfiguracoes() {
            modal.confirmar('Tem certeza que deseja resetar todas as configurações para os valores padrão?', () => {
                window.location.href = '?reset=true';
            });
        }
        
        function backupBancoDados() {
            modal.confirmar('Gerar backup da base de dados?', () => {
                window.location.href = '../api/backup.php';
            });
        }
        
        function limparCache() {
            modal.confirmar('Limpar cache do sistema?', async () => {
                const resultado = await API.post('../api/limpar_cache.php');
                if(resultado && resultado.sucesso) {
                    Utilitarios.mostrarNotificacao('Cache limpo com sucesso!', 'sucesso');
                } else {
                    Utilitarios.mostrarNotificacao('Erro ao limpar cache', 'erro');
                }
            });
        }
        
        function gerarRelatorioSistema() {
            window.open('../admin/relatorios.php?exportar=pdf', '_blank');
        }
        
        function verLogs() {
            window.open('../logs/sistema.log', '_blank');
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
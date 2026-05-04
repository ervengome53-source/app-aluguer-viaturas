-- ============================================
-- SISTEMA DE GESTÃO DE ALUGUER DE VIATURAS
-- BASE DE DADOS COMPLETA (VERSÃO FINAL CORRIGIDA)
-- ============================================

-- Criar Base de Dados
DROP DATABASE IF EXISTS sistema_aluguer;
CREATE DATABASE IF NOT EXISTS sistema_aluguer;
USE sistema_aluguer;

-- ============================================
-- TABELA DE UTILIZADORES
-- ============================================
CREATE TABLE IF NOT EXISTS utilizadores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    morada TEXT,
    nif VARCHAR(20),
    cargo ENUM('admin', 'funcionario', 'cliente') DEFAULT 'cliente',
    status ENUM('ativo', 'inativo', 'bloqueado') DEFAULT 'ativo',
    ultimo_acesso TIMESTAMP NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABELA DE VIATURAS
-- ============================================
CREATE TABLE IF NOT EXISTS viaturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    modelo VARCHAR(100) NOT NULL,
    marca VARCHAR(50) NOT NULL,
    ano INT,
    matricula VARCHAR(20) UNIQUE NOT NULL,
    preco_dia DECIMAL(10,2) NOT NULL,
    tipo ENUM('carro', 'moto', 'van', 'luxo', 'economico', 'suv') NOT NULL,
    combustivel ENUM('gasolina', 'diesel', 'eletrico', 'hibrido') NOT NULL,
    transmissao ENUM('manual', 'automatico') NOT NULL,
    lugares INT DEFAULT 5,
    imagem VARCHAR(255),
    imagens TEXT,
    quilometragem INT DEFAULT 0,
    cor VARCHAR(30),
    status ENUM('disponivel', 'alugado', 'manutencao', 'indisponivel') DEFAULT 'disponivel',
    descricao TEXT,
    caracteristicas TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABELA DE RESERVAS
-- ============================================
CREATE TABLE IF NOT EXISTS reservas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilizador_id INT NOT NULL,
    viatura_id INT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    total_dias INT,
    preco_total DECIMAL(10,2),
    status ENUM('pendente', 'confirmada', 'rejeitada', 'cancelada') DEFAULT 'pendente',
    pagamento_status ENUM('pendente', 'pago', 'reembolsado') DEFAULT 'pendente',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (viatura_id) REFERENCES viaturas(id) ON DELETE CASCADE
);

-- ============================================
-- TABELA DE ALUGUÉIS
-- ============================================
CREATE TABLE IF NOT EXISTS alugueis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reserva_id INT NULL,
    utilizador_id INT NOT NULL,
    viatura_id INT NOT NULL,
    funcionario_id INT,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    total_dias INT,
    preco_total DECIMAL(10,2),
    data_devolucao DATE,
    quilometragem_saida INT DEFAULT 0,
    quilometragem_chegada INT DEFAULT 0,
    status ENUM('ativo', 'finalizado', 'atrasado', 'cancelado') DEFAULT 'ativo',
    multa_atraso DECIMAL(10,2) DEFAULT 0,
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE SET NULL,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id),
    FOREIGN KEY (viatura_id) REFERENCES viaturas(id),
    FOREIGN KEY (funcionario_id) REFERENCES utilizadores(id)
);

-- ============================================
-- TABELA DE PAGAMENTOS
-- ============================================
CREATE TABLE IF NOT EXISTS pagamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    aluguer_id INT NULL,
    reserva_id INT NULL,
    utilizador_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    metodo_pagamento ENUM('dinheiro', 'cartao_credito', 'cartao_debito', 'transferencia', 'mbway', 'paypal') NOT NULL,
    estado ENUM('pendente', 'confirmado', 'falhou', 'reembolsado', 'cancelado') DEFAULT 'pendente',
    referencia_pagamento VARCHAR(100) UNIQUE,
    comprovativo VARCHAR(255),
    dados_transacao TEXT,
    data_pagamento TIMESTAMP NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluguer_id) REFERENCES alugueis(id) ON DELETE SET NULL,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE SET NULL,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id)
);

-- ============================================
-- TABELA DE MULTAS
-- ============================================
CREATE TABLE IF NOT EXISTS multas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    aluguer_id INT NOT NULL,
    utilizador_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    motivo TEXT,
    status ENUM('pendente', 'pago', 'cancelado') DEFAULT 'pendente',
    data_pagamento TIMESTAMP NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluguer_id) REFERENCES alugueis(id) ON DELETE CASCADE,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id)
);

-- ============================================
-- TABELA DE FATURAS / RECIBOS
-- ============================================
CREATE TABLE IF NOT EXISTS faturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pagamento_id INT NOT NULL,
    numero_fatura VARCHAR(50) UNIQUE NOT NULL,
    nif_cliente VARCHAR(20),
    nome_cliente VARCHAR(100),
    morada_cliente TEXT,
    valor_total DECIMAL(10,2) NOT NULL,
    iva DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL,
    data_emissao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pdf_path VARCHAR(255),
    FOREIGN KEY (pagamento_id) REFERENCES pagamentos(id) ON DELETE CASCADE
);

-- ============================================
-- TABELA DE TAXAS E CONFIGURAÇÕES
-- ============================================
CREATE TABLE IF NOT EXISTS taxas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('percentagem', 'valor_fixo') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABELA DE CONFIGURAÇÕES GLOBAIS
-- ============================================
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome_empresa VARCHAR(100) DEFAULT 'RentCar',
    email_contato VARCHAR(100),
    telefone_contato VARCHAR(20),
    endereco TEXT,
    iva_padrao DECIMAL(5,2) DEFAULT 23.00,
    taxa_servico DECIMAL(5,2) DEFAULT 5.00,
    multa_atraso_dia DECIMAL(10,2) DEFAULT 25.00,
    email_notificacoes VARCHAR(100),
    dias_antecedencia_reserva INT DEFAULT 1,
    limite_cancelamento_horas INT DEFAULT 24,
    modo_manutencao BOOLEAN DEFAULT FALSE,
    mensagem_manutencao TEXT,
    backup_automatico BOOLEAN DEFAULT TRUE,
    smtp_host VARCHAR(100),
    smtp_port INT DEFAULT 587,
    smtp_email VARCHAR(100),
    smtp_senha VARCHAR(255),
    smtp_ssl BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABELA DE CONFIGURAÇÕES DE PAGAMENTO
-- ============================================
CREATE TABLE IF NOT EXISTS config_pagamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    metodos_ativos TEXT,
    mbway_ativado BOOLEAN DEFAULT TRUE,
    cartao_credito_ativado BOOLEAN DEFAULT TRUE,
    transferencia_ativada BOOLEAN DEFAULT TRUE,
    paypal_ativado BOOLEAN DEFAULT FALSE,
    mbway_valor_maximo DECIMAL(10,2) DEFAULT 500.00,
    cartao_taxa DECIMAL(5,2) DEFAULT 2.50,
    paypal_email VARCHAR(100),
    banco_iban VARCHAR(50),
    banco_swift VARCHAR(20),
    banco_nome VARCHAR(100),
    stripe_public_key VARCHAR(100),
    stripe_secret_key VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABELA DE MÉTODOS DE PAGAMENTO DOS UTILIZADORES
-- ============================================
CREATE TABLE IF NOT EXISTS metodos_pagamento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilizador_id INT NOT NULL,
    tipo ENUM('cartao', 'mbway', 'paypal') NOT NULL,
    nome_titular VARCHAR(100),
    numero_cartao VARCHAR(50),
    validade VARCHAR(10),
    referencia_mbway VARCHAR(20),
    email_paypal VARCHAR(100),
    padrao BOOLEAN DEFAULT FALSE,
    estado ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
);

-- ============================================
-- TABELA DE AVALIAÇÕES
-- ============================================
CREATE TABLE IF NOT EXISTS avaliacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilizador_id INT NOT NULL,
    viatura_id INT NOT NULL,
    nota INT CHECK (nota BETWEEN 1 AND 5),
    comentario TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (viatura_id) REFERENCES viaturas(id) ON DELETE CASCADE
);

-- ============================================
-- TABELA DE DANOS EM VIATURAS
-- ============================================
CREATE TABLE IF NOT EXISTS viaturas_danos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    aluguer_id INT NOT NULL,
    viatura_id INT NOT NULL,
    descricao TEXT NOT NULL,
    custo_reparacao DECIMAL(10,2) DEFAULT 0,
    status ENUM('reportado', 'em_reparacao', 'reparado') DEFAULT 'reportado',
    data_registo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_reparacao TIMESTAMP NULL,
    FOREIGN KEY (aluguer_id) REFERENCES alugueis(id) ON DELETE CASCADE,
    FOREIGN KEY (viatura_id) REFERENCES viaturas(id)
);

-- ============================================
-- TABELA DE REGISTOS DE ATIVIDADES (LOGS)
-- ============================================
CREATE TABLE IF NOT EXISTS registos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilizador_id INT,
    acao VARCHAR(255),
    descricao TEXT,
    endereco_ip VARCHAR(45),
    user_agent TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE SET NULL
);

-- ============================================
-- TABELA DE TOKENS API
-- ============================================
CREATE TABLE IF NOT EXISTS tokens_api (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilizador_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expira_em TIMESTAMP NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
);

-- ============================================
-- INSERIR DADOS INICIAIS
-- ============================================

INSERT INTO taxas (nome, tipo, valor, descricao) VALUES
('IVA', 'percentagem', 23.00, 'Imposto sobre o Valor Acrescentado'),
('Taxa de Serviço', 'percentagem', 5.00, 'Taxa de serviço administrativo'),
('Multa Atraso Diário', 'valor_fixo', 25.00, 'Multa por dia de atraso na devolução');

INSERT INTO configuracoes (id, nome_empresa, email_contato, telefone_contato, endereco, iva_padrao, taxa_servico, multa_atraso_dia) VALUES
(1, 'RentCar', 'geral@rentcar.com', '210000000', 'Rua Exemplo, 123 - Lisboa', 23.00, 5.00, 25.00);

INSERT INTO config_pagamentos (id, metodos_ativos, mbway_ativado, cartao_credito_ativado, transferencia_ativada, paypal_ativado, mbway_valor_maximo, cartao_taxa, paypal_email, banco_iban, banco_swift, banco_nome) VALUES
(1, 'dinheiro,cartao_credito,mbway,transferencia', 1, 1, 1, 0, 500.00, 2.50, 'rentcar@paypal.com', 'PT50 0000 0000 0000 0000 0000 0', 'EXMPPT3X', 'Banco Exemplo SA');

INSERT INTO utilizadores (nome, email, senha, cargo, status) VALUES 
('Administrador', 'admin@rentcar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'ativo'),
('João Silva', 'funcionario@rentcar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'funcionario', 'ativo'),
('Maria Santos', 'cliente@rentcar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 'ativo');

INSERT INTO viaturas (modelo, marca, ano, matricula, preco_dia, tipo, combustivel, transmissao, lugares, imagem, descricao, quilometragem, cor) VALUES
('Civic', 'Honda', 2022, 'AB-12-34', 150.00, 'carro', 'gasolina', 'automatico', 5, 'civic.jpg', 'Carro económico e confortável, ideal para família', 15000, 'Prata'),
('X5', 'BMW', 2023, 'CD-56-78', 350.00, 'luxo', 'diesel', 'automatico', 5, 'x5.jpg', 'SUV de luxo com todos os equipamentos', 5000, 'Preto'),
('Corolla', 'Toyota', 2022, 'EF-90-12', 140.00, 'carro', 'hibrido', 'automatico', 5, 'corolla.jpg', 'Económico e fiável', 20000, 'Branco'),
('Focus', 'Ford', 2021, 'GH-34-56', 120.00, 'economico', 'gasolina', 'manual', 5, 'focus.jpg', 'Carro desportivo e ágil', 35000, 'Azul'),
('Tucson', 'Hyundai', 2023, 'IJ-78-90', 180.00, 'suv', 'diesel', 'automatico', 5, 'tucson.jpg', 'SUV espaçoso e moderno', 8000, 'Vermelho'),
('Golf', 'Volkswagen', 2022, 'KL-12-34', 130.00, 'carro', 'gasolina', 'manual', 5, 'golf.jpg', 'Compacto e económico', 12000, 'Cinza'),
('Classe C', 'Mercedes', 2023, 'MN-56-78', 300.00, 'luxo', 'diesel', 'automatico', 5, 'mercedes.jpg', 'Sedan de luxo com acabamentos premium', 3000, 'Preto'),
('500', 'Fiat', 2021, 'OP-90-12', 80.00, 'economico', 'gasolina', 'manual', 4, 'fiat500.jpg', 'Ideal para cidade, fácil estacionar', 25000, 'Amarelo'),
('Sprinter', 'Mercedes', 2022, 'QR-34-56', 200.00, 'van', 'diesel', 'manual', 9, 'sprinter.jpg', 'Perfeito para grupos e transporte de carga', 18000, 'Branco'),
('Model 3', 'Tesla', 2023, 'ST-78-90', 280.00, 'luxo', 'eletrico', 'automatico', 5, 'tesla.jpg', 'Elétrico com tecnologia de ponta', 2000, 'Vermelho');

-- ============================================
-- CRIAR ÍNDICES ADICIONAIS (CORRIGIDOS)
-- ============================================

CREATE INDEX idx_reservas_datas ON reservas(data_inicio, data_fim);
CREATE INDEX idx_alugueis_datas ON alugueis(data_inicio, data_fim);
CREATE INDEX idx_pagamentos_data ON pagamentos(data_criacao, estado);
CREATE INDEX idx_viaturas_preco ON viaturas(preco_dia, status);
CREATE INDEX idx_utilizadores_email ON utilizadores(email);
CREATE INDEX idx_viaturas_status ON viaturas(status);

-- ============================================
-- FIM
-- ============================================
SELECT 'Base de dados criada com sucesso!' as mensagem;
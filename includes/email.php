<?php
// includes/email.php
// Sistema de envio de emails usando PHPMailer

require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Classe para envio de emails
 */
class EmailService {
    private $mail;
    private $config;
    
    public function __construct($config = null) {
        $this->mail = new PHPMailer(true);
        $this->config = $config ?: $this->getConfiguracaoPadrao();
        $this->setup();
    }
    
    private function getConfiguracaoPadrao() {
        return [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'no-reply@rentcar.com',
            'password' => '',
            'encryption' => PHPMailer::ENCRYPTION_STARTTLS,
            'from_email' => 'no-reply@rentcar.com',
            'from_name' => 'RentCar Sistema'
        ];
    }
    
    private function setup() {
        try {
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['username'];
            $this->mail->Password = $this->config['password'];
            $this->mail->SMTPSecure = $this->config['encryption'];
            $this->mail->Port = $this->config['port'];
            $this->mail->CharSet = 'UTF-8';
            $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
        } catch(Exception $e) {
            error_log("Erro ao configurar email: " . $e->getMessage());
        }
    }
    
    /**
     * Envia email de confirmação de reserva
     */
    public function enviarConfirmacaoReserva($email, $nome, $reserva) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email, $nome);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Reserva Confirmada - RentCar';
            
            $this->mail->Body = $this->templateConfirmacaoReserva($nome, $reserva);
            $this->mail->AltBody = strip_tags($this->mail->Body);
            
            return $this->mail->send();
        } catch(Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia email de confirmação de pagamento
     */
    public function enviarConfirmacaoPagamento($email, $nome, $pagamento, $recibo_path = null) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email, $nome);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Pagamento Confirmado - RentCar';
            
            $this->mail->Body = $this->templateConfirmacaoPagamento($nome, $pagamento);
            $this->mail->AltBody = strip_tags($this->mail->Body);
            
            if($recibo_path && file_exists($recibo_path)) {
                $this->mail->addAttachment($recibo_path, 'recibo.pdf');
            }
            
            return $this->mail->send();
        } catch(Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia email de notificação de atraso na devolução
     */
    public function enviarNotificacaoAtraso($email, $nome, $aluguer) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email, $nome);
            $this->mail->isHTML(true);
            $this->mail->Subject = ' Atraso na Devolução - RentCar';
            
            $dias_atraso = (new DateTime())->diff(new DateTime($aluguer['data_fim']))->days;
            $multa = $dias_atraso * 25;
            
            $this->mail->Body = $this->templateNotificacaoAtraso($nome, $aluguer, $dias_atraso, $multa);
            $this->mail->AltBody = strip_tags($this->mail->Body);
            
            return $this->mail->send();
        } catch(Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia email de boas-vindas para novo cliente
     */
    public function enviarBoasVindas($email, $nome, $senha = null) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email, $nome);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Bem-vindo à RentCar!';
            
            $this->mail->Body = $this->templateBoasVindas($nome, $senha);
            $this->mail->AltBody = strip_tags($this->mail->Body);
            
            return $this->mail->send();
        } catch(Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia email de reset de senha
     */
    public function enviarResetSenha($email, $nome, $token) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email, $nome);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Recuperação de Senha - RentCar';
            
            $link = "https://" . $_SERVER['HTTP_HOST'] . "../public/reset_senha.php?token=" . $token;
            $this->mail->Body = $this->templateResetSenha($nome, $link);
            $this->mail->AltBody = strip_tags($this->mail->Body);
            
            return $this->mail->send();
        } catch(Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Template de confirmação de reserva
     */
    private function templateConfirmacaoReserva($nome, $reserva) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1E3A5F; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f5f5f5; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                .button { background: #FF8C00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>RentCar - Reserva Confirmada</h2>
                </div>
                <div class='content'>
                    <h3>Olá, {$nome}!</h3>
                    <p>Sua reserva foi confirmada com sucesso!</p>
                    <p><strong>Detalhes da reserva:</strong></p>
                    <ul>
                        <li><strong>Veículo:</strong> {$reserva['marca']} {$reserva['modelo']}</li>
                        <li><strong>Período:</strong> " . date('d/m/Y', strtotime($reserva['data_inicio'])) . " a " . date('d/m/Y', strtotime($reserva['data_fim'])) . "</li>
                        <li><strong>Valor total:</strong> € " . number_format($reserva['preco_total'], 2) . "</li>
                    </ul>
                    <p>Para mais informações, acesse o seu painel de controlo.</p>
                    <a href='https://" . $_SERVER['HTTP_HOST'] . "/cliente/reservas.php' class='button'>Ver Minhas Reservas</a>
                </div>
                <div class='footer'>
                    <p>RentCar - Sistema de Gestão de Aluguer de Viaturas</p>
                    <p>© " . date('Y') . " RentCar. Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template de confirmação de pagamento
     */
    private function templateConfirmacaoPagamento($nome, $pagamento) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1E3A5F; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f5f5f5; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>SIGAV - Pagamento Confirmado</h2>
                </div>
                <div class='content'>
                    <h3>Olá, {$nome}!</h3>
                    <p>Seu pagamento foi processado com sucesso!</p>
                    <p><strong>Detalhes do pagamento:</strong></p>
                    <ul>
                        <li><strong>Referência:</strong> {$pagamento['referencia_pagamento']}</li>
                        <li><strong>Valor pago:</strong> MZN " . number_format($pagamento['valor'], 2) . "</li>
                        <li><strong>Método:</strong> " . ucfirst(str_replace('_', ' ', $pagamento['metodo_pagamento'])) . "</li>
                        <li><strong>Data:</strong> " . date('d/m/Y H:i') . "</li>
                    </ul>
                    <p>O recibo do pagamento está em anexo.</p>
                    <p>Obrigado pela preferência!</p>
                </div>
                <div class='footer'>
                    <p>SIGAV - Sistema de Gestão de Aluguer de Viaturas</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function templateNotificacaoAtraso($nome, $aluguer, $dias_atraso, $multa) {
        return "
        <!DOCTYPE html>
        <html>
        <head><style>body{font-family:Arial,sans-serif}</style></head>
        <body>
            <div style='max-width:600px;margin:0 auto;padding:20px'>
                <div style='background:#dc3545;color:white;padding:20px;text-align:center'>
                    <h2> Aviso de Atraso na Devolução</h2>
                </div>
                <div style='padding:20px;background:#f5f5f5'>
                    <h3>Olá, {$nome}!</h3>
                    <p>Verificamos que o veículo <strong>{$aluguer['marca']} {$aluguer['modelo']}</strong> ainda não foi devolvido.</p>
                    <p><strong>Período do aluguer:</strong> " . date('d/m/Y', strtotime($aluguer['data_inicio'])) . " a " . date('d/m/Y', strtotime($aluguer['data_fim'])) . "</p>
                    <p><strong>Dias de atraso:</strong> {$dias_atraso} dias</p>
                    <p><strong>Multa acumulada:</strong> MZN " . number_format($multa, 2) . "</p>
                    <p>Por favor, entre em contato conosco para regularizar a situação.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function templateBoasVindas($nome, $senha = null) {
        $senhaHtml = $senha ? "<p><strong>Senha temporária:</strong> {$senha}</p><p>Recomendamos alterar a senha após o primeiro acesso.</p>" : "";
        
        return "
        <!DOCTYPE html>
        <html>
        <head><style>body{font-family:Arial,sans-serif}</style></head>
        <body>
            <div style='max-width:600px;margin:0 auto;padding:20px'>
                <div style='background:#1E3A5F;color:white;padding:20px;text-align:center'>
                    <h2>Bem-vindo à SIGAV! </h2>
                </div>
                <div style='padding:20px;background:#f5f5f5'>
                    <h3>Olá, {$nome}!</h3>
                    <p>É com grande satisfação que recebemos você como nosso cliente!</p>
                    <p>Sua conta foi criada com sucesso no sistema SIGAV.</p>
                    {$senhaHtml}
                    <p>Agora você pode:</p>
                    <ul>
                        <li> Explorar nosso catálogo de veículos</li>
                        <li> Fazer reservas online</li>
                        <li> Acompanhar seu histórico de aluguéis</li>
                        <li> Gerir seu perfil</li>
                    </ul>
                    <a href='https://" . $_SERVER['HTTP_HOST'] . "../cliente/dashboard.php' style='background:#FF8C00;color:white;padding:10px 20px;text-decoration:none;border-radius:5px'>Acessar Minha Conta</a>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function templateResetSenha($nome, $link) {
        return "
        <!DOCTYPE html>
        <html>
        <head><style>body{font-family:Arial,sans-serif}</style></head>
        <body>
            <div style='max-width:600px;margin:0 auto;padding:20px'>
                <div style='background:#1E3A5F;color:white;padding:20px;text-align:center'>
                    <h2>Recuperação de Senha</h2>
                </div>
                <div style='padding:20px;background:#f5f5f5'>
                    <h3>Olá, {$nome}!</h3>
                    <p>Recebemos uma solicitação para redefinir sua senha.</p>
                    <p>Clique no botão abaixo para criar uma nova senha:</p>
                    <p style='text-align:center'><a href='{$link}' style='background:#FF8C00;color:white;padding:10px 20px;text-decoration:none;border-radius:5px'>Redefinir Senha</a></p>
                    <p>Se você não solicitou esta alteração, ignore este email.</p>
                    <p>Este link é válido por 24 horas.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

/**
 * Função helper para enviar emails rapidamente
 */
function enviarEmail($para, $assunto, $mensagem, $tipo = 'confirmacao_reserva', $dados = []) {
    $emailService = new EmailService();
    
    switch($tipo) {
        case 'confirmacao_reserva':
            return $emailService->enviarConfirmacaoReserva($para, $dados['nome'], $dados['reserva']);
        case 'confirmacao_pagamento':
            return $emailService->enviarConfirmacaoPagamento($para, $dados['nome'], $dados['pagamento'], $dados['recibo'] ?? null);
        case 'boas_vindas':
            return $emailService->enviarBoasVindas($para, $dados['nome'], $dados['senha'] ?? null);
        case 'reset_senha':
            return $emailService->enviarResetSenha($para, $dados['nome'], $dados['token']);
        default:
            return false;
    }
}
?>
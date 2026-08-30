<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Escolha a forma de carregamento que você configurou (Manual ou Composer)
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

function enviarCodigoEmail($destinatario, $codigo) {
    $mail = new PHPMailer(true);

    try {
       
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        
        
        $mail->Username   = 'sistemacheck34@gmail.com'; 
        $mail->Password   = ''; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

     
        $mail->setFrom('sistemacheck34@gmail.com', 'Sistema Check');
        $mail->addAddress($destinatario);

      
        $mail->addEmbeddedImage('LOGOCHECKADAP.jpg', 'logo_check');

        $mail->isHTML(true);
        $mail->Subject = 'Seu Código de Verificação - Sistema CHECK';
        
        
        $mail->Body = '
            <div style="font-family: Montserrat, Arial, sans-serif; max-width: 500px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
                <img src="cid:logo_check" alt="Logo Check" style="max-width: 120px; margin-bottom: 20px;">
                <h2 style="color: #333;">Código de Verificação</h2>
                <p style="color: #555; font-size: 16px;">Use o código de 4 dígitos abaixo para continuar a operação no sistema:</p>
                
                <div style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #004aad; margin: 25px 0;">
                    ' . $codigo . '
                </div>
                
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                
                <footer style="font-size: 12px; color: #888;">
                    <p><strong>Laboratório de Hardware</strong><br>Sistema CHECK de Gestão e Acesso</p>
                    <p>Contato: sistemacheck34@gmail.com</p>
                </footer>
            </div>
        ';

        $mail->send();
    } catch (Exception $e) {
        
        error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
    }
}
?>
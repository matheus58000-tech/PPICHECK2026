<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';

function enviarCodigoEmail($destinatario, $codigo) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sistemacheck34@gmail.com';
        $mail->Password   = 'gyxt gfui ywob jvbo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;

        $mail->setFrom('sistemacheck34@gmail.com', 'Sistema Check');
        $mail->addAddress($destinatario);

        $logoPath = __DIR__ . '/LOGOCHECKADAP.jpg';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'logo_check');
        }

        $mail->isHTML(true);
        $mail->Subject = 'Seu Código de Verificação - Sistema CHECK';

        $mail->Body = '
            <div style="font-family: Montserrat, Arial, sans-serif; max-width: 500px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center;">
                <img src="cid:logo_check" alt="Logo Check" style="max-width: 120px; margin-bottom: 20px;">
                <h2 style="color: #333;">Código de Verificação</h2>
                <p style="color: #555; font-size: 16px;">Use o código de 4 dígitos abaixo para continuar a operação no sistema:</p>
                <div style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #004aad; margin: 25px 0;">
                    ' . htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') . '
                </div>
                <p style="color: #888; font-size: 13px;">Este código expira em 10 minutos.</p>
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                <footer style="font-size: 12px; color: #888;">
                    <p><strong>Laboratório de Hardware</strong><br>Sistema CHECK de Gestão e Acesso</p>
                    <p>Contato: sistemacheck34@gmail.com</p>
                </footer>
            </div>
        ';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
        return false;
    }
}

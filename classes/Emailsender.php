<?php
/**
 * EmailSender Class
 * Handles sending invoice emails using PHPMailer
 * 
 * @package SkyBooking
 * @author SkyBooking Team
 */

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Class EmailSender
 * Mengirim email invoice kepada customer
 */
class EmailSender {
    /** @var PHPMailer */
    private $mail;
    
    /** @var bool */
    private $isConfigured = false;
    
    /** @var string */
    private $lastError = '';
    
    /**
     * Constructor
     * Initialize PHPMailer with SMTP configuration
     */
    public function __construct() {
        // Check if PHPMailer is available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            throw new Exception('PHPMailer not found. Run: composer require phpmailer/phpmailer');
        }
        
        $this->mail = new PHPMailer(true);
        
        try {
            // Enable verbose debug output (akan di-log)
            $this->mail->SMTPDebug = SMTP::DEBUG_OFF; // Set ke DEBUG_SERVER untuk troubleshooting
            $this->mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer: $str");
            };
            
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
            $this->mail->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
            
            // Additional SMTP options
            $this->mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Timeout settings
            $this->mail->Timeout = 30;
            $this->mail->SMTPKeepAlive = false;
            
            // Sender
            $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@skybooking.com';
            $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'SkyBooking';
            
            $this->mail->setFrom($fromEmail, $fromName);
            $this->mail->isHTML(true);
            $this->mail->CharSet = 'UTF-8';
            
            // Validate SMTP credentials
            if (empty($this->mail->Username) || empty($this->mail->Password)) {
                throw new Exception("SMTP credentials not configured. Check SMTP_USERNAME and SMTP_PASSWORD in .env file");
            }
            
            $this->isConfigured = true;
            
        } catch (Exception $e) {
            $this->isConfigured = false;
            $this->lastError = $e->getMessage();
            error_log("Email configuration error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if email is properly configured
     * @return bool
     */
    public function isConfigured() {
        return $this->isConfigured;
    }
    
    /**
     * Get last error message
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Send invoice email
     * @param array $bookingData Booking data
     * @param array $penumpangData Passenger data
     * @return bool Success status
     */
    public function sendInvoice($bookingData, $penumpangData) {
        if (!$this->isConfigured) {
            $this->lastError = "Email not properly configured";
            error_log("EmailSender: Cannot send - not configured");
            return false;
        }
        
        try {
            // Clear previous recipients
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            // Validate email
            $toEmail = $bookingData['email'];
            if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address: $toEmail");
            }
            
            // Set recipient
            $this->mail->addAddress($toEmail, $bookingData['nama_pemesan']);
            
            // Set reply-to (opsional, untuk email yang bisa dibalas)
            if (defined('SMTP_USERNAME') && filter_var(SMTP_USERNAME, FILTER_VALIDATE_EMAIL)) {
                $this->mail->addReplyTo(SMTP_USERNAME, SMTP_FROM_NAME);
            }
            
            // Email subject
            $this->mail->Subject = 'Invoice Pemesanan Tiket - ' . $bookingData['kode_booking'];
            
            // Generate HTML body
            $htmlBody = $this->generateInvoiceHTML($bookingData, $penumpangData);
            $this->mail->Body = $htmlBody;
            
            // Plain text alternative
            $this->mail->AltBody = $this->generatePlainText($bookingData, $penumpangData);
            
            // Send email
            $result = $this->mail->send();
            
            if ($result) {
                error_log("EmailSender: Successfully sent invoice to " . $toEmail);
                $this->lastError = '';
                return true;
            } else {
                throw new Exception("Failed to send email");
            }
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("Email sending error: " . $e->getMessage());
            error_log("PHPMailer ErrorInfo: " . $this->mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Test SMTP connection
     * @return array Test result with status and message
     */
    public function testConnection() {
        try {
            $this->mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
            $result = $this->mail->smtpConnect();
            $this->mail->smtpClose();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'SMTP connection successful!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'SMTP connection failed: ' . $this->mail->ErrorInfo
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }
    
    private function generateInvoiceHTML($booking, $penumpang) {
        $statusColors = [
            'pending' => ['bg' => '#FEF3C7', 'text' => '#92400E', 'label' => 'PENDING'],
            'lunas' => ['bg' => '#D1FAE5', 'text' => '#065F46', 'label' => 'LUNAS'],
            'batal' => ['bg' => '#FEE2E2', 'text' => '#991B1B', 'label' => 'BATAL']
        ];
        
        $status = $statusColors[$booking['status']] ?? $statusColors['pending'];
        
        // Generate passenger list HTML
        $penumpangHtml = '';
        foreach ($penumpang as $index => $p) {
            $penumpangHtml .= '
                <tr style="background: ' . ($index % 2 == 0 ? '#F9FAFB' : '#FFFFFF') . ';">
                    <td style="padding: 12px; border: 1px solid #E5E7EB;">' . ($index + 1) . '</td>
                    <td style="padding: 12px; border: 1px solid #E5E7EB;">' . htmlspecialchars($p['nama_lengkap']) . '</td>
                    <td style="padding: 12px; border: 1px solid #E5E7EB;">' . htmlspecialchars($p['jenis_kelamin']) . '</td>
                    <td style="padding: 12px; border: 1px solid #E5E7EB;">' . htmlspecialchars($p['tanggal_lahir']) . '</td>
                </tr>
            ';
        }
        
        $html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - ' . htmlspecialchars($booking['kode_booking']) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #F3F4F6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F3F4F6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3B82F6 0%, #1E3A8A 100%); padding: 40px; text-align: center;">
                            <h1 style="margin: 0; color: #FFFFFF; font-size: 32px; font-weight: bold;">
                                ✈️ SkyBooking
                            </h1>
                            <p style="margin: 10px 0 0 0; color: #DBEAFE; font-size: 16px;">Invoice Pemesanan Tiket Penerbangan</p>
                        </td>
                    </tr>
                    
                    <!-- Booking Code -->
                    <tr>
                        <td style="padding: 30px; background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); text-align: center; border-bottom: 4px solid #3B82F6;">
                            <p style="margin: 0 0 10px 0; color: #6B7280; font-size: 14px; font-weight: 600;">KODE BOOKING</p>
                            <h2 style="margin: 0; color: #1E3A8A; font-size: 36px; font-weight: bold; letter-spacing: 2px;">' . htmlspecialchars($booking['kode_booking']) . '</h2>
                            <div style="margin-top: 15px; display: inline-block; padding: 8px 20px; background: ' . $status['bg'] . '; color: ' . $status['text'] . '; border-radius: 20px; font-size: 12px; font-weight: bold;">
                                ' . $status['label'] . '
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Flight Information -->
                    <tr>
                        <td style="padding: 30px;">
                            <h3 style="margin: 0 0 20px 0; color: #1F2937; font-size: 18px; font-weight: bold; border-bottom: 2px solid #E5E7EB; padding-bottom: 10px;">
                                ✈️ Informasi Penerbangan
                            </h3>
                            <table width="100%" cellpadding="8" cellspacing="0">
                                <tr>
                                    <td style="color: #6B7280; width: 40%;">Maskapai</td>
                                    <td style="color: #1F2937; font-weight: bold;">' . htmlspecialchars($booking['maskapai']) . '</td>
                                </tr>
                                <tr style="background: #F9FAFB;">
                                    <td style="color: #6B7280;">Kode Penerbangan</td>
                                    <td style="color: #1F2937; font-weight: bold;">' . htmlspecialchars($booking['kode_penerbangan']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #6B7280;">Rute</td>
                                    <td style="color: #1F2937; font-weight: bold;">' . htmlspecialchars($booking['asal']) . ' → ' . htmlspecialchars($booking['tujuan']) . '</td>
                                </tr>
                                <tr style="background: #F9FAFB;">
                                    <td style="color: #6B7280;">Tanggal Keberangkatan</td>
                                    <td style="color: #1F2937; font-weight: bold;">' . date('d F Y', strtotime($booking['tanggal'])) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #6B7280;">Waktu Keberangkatan</td>
                                    <td style="color: #1F2937; font-weight: bold;">' . date('H:i', strtotime($booking['jam_berangkat'])) . ' WIB</td>
                                </tr>
                                <tr style="background: #F9FAFB;">
                                    <td style="color: #6B7280;">Waktu Tiba</td>
                                    <td style="color: #1F2937; font-weight: bold;">' . date('H:i', strtotime($booking['jam_tiba'])) . ' WIB</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Passenger Information -->
                    <tr>
                        <td style="padding: 30px; background: #F9FAFB;">
                            <h3 style="margin: 0 0 20px 0; color: #1F2937; font-size: 18px; font-weight: bold; border-bottom: 2px solid #E5E7EB; padding-bottom: 10px;">
                                👤 Informasi Pemesan
                            </h3>
                            <table width="100%" cellpadding="8" cellspacing="0">
                                <tr>
                                    <td style="color: #6B7280; width: 40%;">Nama Lengkap</td>
                                    <td style="color: #1F2937; font-weight: bold;">' . htmlspecialchars($booking['nama_pemesan']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #6B7280;">Email</td>
                                    <td style="color: #1F2937; font-weight: bold;">' . htmlspecialchars($booking['email']) . '</td>
                                </tr>
                                <tr>
                                    <td style="color: #6B7280;">No. HP</td>
                                    <td style="color: #1F2937; font-weight: bold;">' . htmlspecialchars($booking['no_hp']) . '</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Passenger List -->
                    <tr>
                        <td style="padding: 30px;">
                            <h3 style="margin: 0 0 20px 0; color: #1F2937; font-size: 18px; font-weight: bold; border-bottom: 2px solid #E5E7EB; padding-bottom: 10px;">
                                👥 Daftar Penumpang (' . count($penumpang) . ' Orang)
                            </h3>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #1E3A8A; color: #FFFFFF;">
                                        <th style="padding: 12px; border: 1px solid #1E3A8A; text-align: left;">No</th>
                                        <th style="padding: 12px; border: 1px solid #1E3A8A; text-align: left;">Nama Lengkap</th>
                                        <th style="padding: 12px; border: 1px solid #1E3A8A; text-align: left;">Jenis Kelamin</th>
                                        <th style="padding: 12px; border: 1px solid #1E3A8A; text-align: left;">Tanggal Lahir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ' . $penumpangHtml . '
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Total Price -->
                    <tr>
                        <td style="padding: 30px; background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);">
                            <table width="100%" cellpadding="10" cellspacing="0">
                                <tr>
                                    <td style="text-align: right; color: #6B7280; font-size: 18px; font-weight: 600;">Total Pembayaran:</td>
                                    <td style="text-align: right; color: #1E3A8A; font-size: 32px; font-weight: bold; width: 40%;">
                                        Rp ' . number_format($booking['total_harga'], 0, ',', '.') . '
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="text-align: right; color: #6B7280; font-size: 12px; padding-top: 0;">
                                        Tanggal Booking: ' . date('d F Y H:i', strtotime($booking['created_at'])) . ' WIB
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Important Notes -->
                    <tr>
                        <td style="padding: 30px; background: #FEF3C7; border-left: 4px solid #F59E0B;">
                            <h4 style="margin: 0 0 10px 0; color: #92400E; font-size: 16px; font-weight: bold;">
                                ⚠️ Catatan Penting:
                            </h4>
                            <ul style="margin: 0; padding-left: 20px; color: #92400E; font-size: 14px; line-height: 1.6;">
                                <li>Harap tiba di bandara minimal 2 jam sebelum keberangkatan</li>
                                <li>Bawa identitas asli yang sesuai dengan data penumpang</li>
                                <li>Simpan kode booking ini untuk check-in</li>
                                <li>Pastikan status pembayaran sudah LUNAS sebelum keberangkatan</li>
                            </ul>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px; background: #1F2937; text-align: center;">
                            <p style="margin: 0 0 10px 0; color: #9CA3AF; font-size: 14px;">
                                Terima kasih telah menggunakan SkyBooking
                            </p>
                            <p style="margin: 0; color: #6B7280; font-size: 12px;">
                                Email ini dikirim otomatis, mohon tidak membalas email ini
                            </p>
                            <p style="margin: 10px 0 0 0; color: #6B7280; font-size: 12px;">
                                © 2026 SkyBooking. All rights reserved.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        ';
        
        return $html;
    }
    
    private function generatePlainText($booking, $penumpang) {
        $text = "INVOICE PEMESANAN TIKET PESAWAT\n";
        $text .= "================================\n\n";
        $text .= "KODE BOOKING: " . $booking['kode_booking'] . "\n";
        $text .= "Status: " . strtoupper($booking['status']) . "\n\n";
        
        $text .= "INFORMASI PENERBANGAN\n";
        $text .= "---------------------\n";
        $text .= "Maskapai: " . $booking['maskapai'] . "\n";
        $text .= "Kode Penerbangan: " . $booking['kode_penerbangan'] . "\n";
        $text .= "Rute: " . $booking['asal'] . " → " . $booking['tujuan'] . "\n";
        $text .= "Tanggal: " . date('d F Y', strtotime($booking['tanggal'])) . "\n";
        $text .= "Waktu: " . date('H:i', strtotime($booking['jam_berangkat'])) . " - " . date('H:i', strtotime($booking['jam_tiba'])) . " WIB\n\n";
        
        $text .= "INFORMASI PEMESAN\n";
        $text .= "-----------------\n";
        $text .= "Nama: " . $booking['nama_pemesan'] . "\n";
        $text .= "Email: " . $booking['email'] . "\n";
        $text .= "No. HP: " . $booking['no_hp'] . "\n\n";
        
        $text .= "DAFTAR PENUMPANG\n";
        $text .= "----------------\n";
        foreach ($penumpang as $index => $p) {
            $text .= ($index + 1) . ". " . $p['nama_lengkap'] . " (" . $p['jenis_kelamin'] . ") - " . $p['tanggal_lahir'] . "\n";
        }
        
        $text .= "\nTOTAL PEMBAYARAN: Rp " . number_format($booking['total_harga'], 0, ',', '.') . "\n\n";
        $text .= "Tanggal Booking: " . date('d F Y H:i', strtotime($booking['created_at'])) . " WIB\n\n";
        $text .= "Terima kasih telah menggunakan SkyBooking!\n";
        
        return $text;
    }
}
?>
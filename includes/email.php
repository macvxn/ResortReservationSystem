<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Send OTP email to user
 * @param string $to_email Recipient email
 * @param string $to_name Recipient name (optional)
 * @param string $otp 6-digit OTP code
 * @return array ['success' => bool, 'message' => string]
 */
function sendOTPEmail($to_email, $to_name = 'User', $otp) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, 'Resort Reservation System');
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo(SMTP_FROM_EMAIL, 'No Reply');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification - Resort Reservation System';
        
        // Email body
        $mail->Body = getOTPEmailTemplate($otp, $to_name);
        $mail->AltBody = "Your verification code is: $otp\n\nThis code will expire in 15 minutes.\n\nIf you didn't request this code, please ignore this email.";

        $mail->send();
        return ['success' => true, 'message' => 'OTP sent successfully'];
        
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return ['success' => false, 'message' => "Failed to send email: {$mail->ErrorInfo}"];
    }
}

/**
 * Send welcome email after successful verification
 */
function sendWelcomeEmail($to_email, $to_name) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, 'Resort Reservation System');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Resort Reservation System!';
        $mail->Body = getWelcomeEmailTemplate($to_name);
        $mail->AltBody = "Welcome to Resort Reservation System!\n\nYour account has been verified successfully. You can now browse cottages and make reservations.";

        $mail->send();
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log("Welcome Email Error: {$mail->ErrorInfo}");
        return ['success' => false];
    }
}

/**
 * Send reservation confirmation email
 */
function sendReservationConfirmation($to_email, $to_name, $reservation_details) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, 'Resort Reservation System');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Reservation Submitted - Awaiting Approval';
        $mail->Body = getReservationEmailTemplate($to_name, $reservation_details);

        $mail->send();
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log("Reservation Email Error: {$mail->ErrorInfo}");
        return ['success' => false];
    }
}

/**
 * OTP Email Template
 */
function getOTPEmailTemplate($otp, $name) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .otp-box { background: white; border: 3px dashed #667eea; padding: 20px; text-align: center; margin: 20px 0; border-radius: 10px; }
            .otp-code { font-size: 36px; font-weight: bold; color: #667eea; letter-spacing: 8px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏖️ Email Verification</h1>
            </div>
            <div class='content'>
                <p>Hello <strong>$name</strong>,</p>
                <p>Thank you for registering with Resort Reservation System!</p>
                <p>To complete your registration, please use the following One-Time Password (OTP):</p>
                
                <div class='otp-box'>
                    <div class='otp-code'>$otp</div>
                    <p style='margin: 10px 0 0 0; color: #666; font-size: 14px;'>This code expires in 15 minutes</p>
                </div>
                
                <p><strong>Security Tips:</strong></p>
                <ul>
                    <li>Never share this code with anyone</li>
                    <li>Our team will never ask for this code</li>
                    <li>If you didn't request this, please ignore this email</li>
                </ul>
            </div>
            <div class='footer'>
                <p>© 2025 Resort Reservation System. All rights reserved.</p>
                <p>This is an automated email, please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Welcome Email Template
 */
function getWelcomeEmailTemplate($name) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .feature { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #667eea; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Welcome Aboard!</h1>
            </div>
            <div class='content'>
                <p>Hi <strong>$name</strong>,</p>
                <p>Your email has been successfully verified! You're all set to start booking your dream cottage getaway.</p>
                
                <h3>What's Next?</h3>
                <div class='feature'>
                    <strong>📋 Complete Your Profile</strong>
                    <p>Upload your ID for verification to unlock reservation features.</p>
                </div>
                <div class='feature'>
                    <strong>🏠 Browse Cottages</strong>
                    <p>Explore our beautiful cottages and find your perfect match.</p>
                </div>
                <div class='feature'>
                    <strong>📅 Make Reservations</strong>
                    <p>Book your stay and create unforgettable memories!</p>
                </div>
                
                <p style='margin-top: 30px;'>Happy browsing!</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Reservation Email Template
 */
function getReservationEmailTemplate($name, $details) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .detail-box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; border: 1px solid #ddd; }
            .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
            .label { font-weight: bold; color: #666; }
            .value { color: #333; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📋 Reservation Submitted</h1>
            </div>
            <div class='content'>
                <p>Hi <strong>$name</strong>,</p>
                <p>Your reservation has been successfully submitted and is now pending admin approval.</p>
                
                <div class='detail-box'>
                    <h3>Reservation Details</h3>
                    <div class='detail-row'>
                        <span class='label'>Cottage:</span>
                        <span class='value'>{$details['cottage_name']}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='label'>Check-in:</span>
                        <span class='value'>{$details['check_in']}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='label'>Check-out:</span>
                        <span class='value'>{$details['check_out']}</span>
                    </div>
                    <div class='detail-row'>
                        <span class='label'>Total Nights:</span>
                        <span class='value'>{$details['nights']} nights</span>
                    </div>
                    <div class='detail-row'>
                        <span class='label'>Total Price:</span>
                        <span class='value'>₱{$details['total_price']}</span>
                    </div>
                </div>
                
                <p><strong>What happens next?</strong></p>
                <ol>
                    <li>Our admin will review your reservation and payment proof</li>
                    <li>You'll receive an email notification once approved or if any issues arise</li>
                    <li>Check your dashboard for real-time status updates</li>
                </ol>
                
                <p>Thank you for choosing our resort!</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($to_email, $reset_token) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, 'Resort Reservation System');
        $mail->addAddress($to_email);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - Resort Reservation';
        
        // Create reset link
        $reset_link = BASE_URL . "auth/reset-password.php?token=" . $reset_token;
        
        $mail->Body = getPasswordResetEmailTemplate($reset_link);
        $mail->AltBody = "Password Reset Request\n\nClick this link to reset your password:\n$reset_link\n\nThis link will expire in 1 hour.\n\nIf you didn't request this, please ignore this email.";

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
        
    } catch (Exception $e) {
        error_log("Password Reset Email Error: {$mail->ErrorInfo}");
        return ['success' => false, 'message' => "Failed to send email: {$mail->ErrorInfo}"];
    }
}

/**
 * Password Reset Email Template
 */
function getPasswordResetEmailTemplate($reset_link) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 15px 30px; background: #667eea; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
            .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔒 Password Reset Request</h1>
            </div>
            <div class='content'>
                <p>Hello,</p>
                <p>We received a request to reset your password for your Resort Reservation account.</p>
                <p>Click the button below to create a new password:</p>
                
                <div style='text-align: center;'>
                    <a href='$reset_link' class='button'>Reset My Password</a>
                </div>
                
                <p style='color: #666; font-size: 14px; margin-top: 20px;'>
                    Or copy and paste this link into your browser:<br>
                    <a href='$reset_link' style='color: #667eea; word-break: break-all;'>$reset_link</a>
                </p>
                
                <div class='warning'>
                    <strong>⚠️ Important:</strong>
                    <ul style='margin: 10px 0; padding-left: 20px;'>
                        <li>This link will expire in <strong>1 hour</strong></li>
                        <li>If you didn't request this reset, please ignore this email</li>
                        <li>Your password will not change until you create a new one</li>
                        <li>Never share this link with anyone</li>
                    </ul>
                </div>
                
                <p style='margin-top: 30px;'>
                    If you're having trouble with the button, you can also reset your password by visiting the login page and clicking \"Forgot Password\".
                </p>
            </div>
            <div class='footer'>
                <p>© 2025 Resort Reservation System. All rights reserved.</p>
                <p>This is an automated email, please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>
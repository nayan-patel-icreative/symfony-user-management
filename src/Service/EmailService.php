<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class EmailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private string $fromEmail;
    private string $fromName;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger, string $fromEmail = null, string $fromName = null)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->fromEmail = $fromEmail ?: ($_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@symfony-app.com');
        $this->fromName = $fromName ?: ($_ENV['MAILER_FROM_NAME'] ?? 'Symfony App');
    }

    public function sendWelcomeEmail(string $toEmail, string $userName): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($toEmail)
                ->subject('Welcome to Symfony User Management System!')
                ->htmlTemplate('emails/welcome.html.twig')
                ->context([
                    'name' => $userName,
                    'user_email' => $toEmail,
                    'login_url' => $_ENV['APP_URL'] ?? 'http://127.0.0.1:33175/login'
                ]);

            $this->mailer->send($email);
            
            // Log email sent successfully
            $this->logger->info('Welcome email sent successfully', [
                'to' => $toEmail,
                'subject' => 'Welcome to Symfony User Management System!'
            ]);
            
            return true;
        } catch (\Exception $e) {
             // Error log
            $this->logger->error('Welcome email sending failed', [
                'to' => $toEmail,
                'subject' => 'Welcome to Symfony User Management System!',
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function sendPasswordResetEmail(string $toEmail, string $resetToken): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($toEmail)
                ->subject('Password Reset Request')
                ->htmlTemplate('emails/password_reset.html.twig')
                ->context([
                    'reset_token' => $resetToken,
                    'reset_url' => ($_ENV['APP_URL'] ?? 'http://127.0.0.1:38437') . '/reset-password/' . $resetToken
                ]);

            $this->mailer->send($email);
            
            return true;
        } catch (\Exception $e) {
            error_log('Failed to send password reset email: ' . $e->getMessage());
            return false;
        }
    }

    public function sendAccountVerificationEmail(string $toEmail, string $verificationToken): bool
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to($toEmail)
                ->subject('Verify Your Email Address')
                ->htmlTemplate('emails/verification.html.twig')
                ->context([
                    'verification_token' => $verificationToken,
                    'verification_url' => ($_ENV['APP_URL'] ?? 'http://127.0.0.1:38437') . '/verify-email/' . $verificationToken
                ]);

            $this->mailer->send($email);
            
            return true;
        } catch (\Exception $e) {
            error_log('Failed to send verification email: ' . $e->getMessage());
            return false;
        }
    }
}

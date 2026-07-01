<?php

namespace App\Services;

use App\Models\User;
use PHPMailer\PHPMailer\PHPMailer;

class MailService {
    public static function isEnabled(): bool {
        return self::envBool('MAIL_ENABLED', false);
    }

    /**
     * @param array<string, mixed> $payload Inbox::send payload
     */
    public static function sendForNotification(int $userId, int $notificationId, array $payload): void {
        if (!self::shouldSend($payload)) {
            return;
        }

        $user = User::getById($userId);
        if (!$user) {
            return;
        }

        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            self::deliver($email, $user, $notificationId, $payload);
        } catch (\Throwable $e) {
            error_log('[EasyTime Mail] Failed for notification #' . $notificationId . ': ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function shouldSend(array $payload): bool {
        if (!self::isEnabled()) {
            return false;
        }

        $type = (string) ($payload['type'] ?? Inbox::TYPE_INFO);
        if ($type === Inbox::TYPE_TASK) {
            return self::envBool('MAIL_NOTIFY_TASKS', true);
        }

        if ($type !== Inbox::TYPE_INFO || !self::envBool('MAIL_NOTIFY_INFO', true)) {
            return false;
        }

        $category = (string) ($payload['category'] ?? 'info');
        return in_array($category, ['success', 'rejected', 'password', 'info'], true);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $payload
     */
    private static function deliver(string $toEmail, array $user, int $notificationId, array $payload): void {
        self::ensureAutoload();

        $mail = new PHPMailer(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        self::applySmtpTransport($mail);

        $fromAddress = (string) (getenv('MAIL_FROM_ADDRESS') ?: 'noreply@easytime.local');
        $fromName = (string) (getenv('MAIL_FROM_NAME') ?: 'EasyTime');
        $mail->setFrom($fromAddress, $fromName);
        $mail->addReplyTo($fromAddress, $fromName);
        $mail->addAddress($toEmail, trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')));

        $title = trim((string) ($payload['title'] ?? 'EasyTime'));
        $mail->Subject = $title;
        $mail->isHTML(true);

        $viewData = self::buildViewData($user, $notificationId, $payload);
        $mail->Body = self::renderTemplate('notification', $viewData);
        $mail->AltBody = self::buildPlainText($viewData);

        $mail->send();
    }

    /** Testversand — gleiche SMTP-Einstellungen wie Produktion (siehe scripts/mailtest.php). */
    public static function sendTestEmail(string $toEmail, string $subject = 'EasyTime Mail-Test'): void {
        if (!self::isEnabled()) {
            throw new \RuntimeException('MAIL_ENABLED ist false.');
        }

        self::ensureAutoload();

        $mail = new PHPMailer(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        self::applySmtpTransport($mail);

        $fromAddress = (string) (getenv('MAIL_FROM_ADDRESS') ?: 'noreply@easytime.local');
        $fromName = (string) (getenv('MAIL_FROM_NAME') ?: 'EasyTime');
        $mail->setFrom($fromAddress, $fromName);
        $mail->addReplyTo($fromAddress, $fromName);
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = '<b>EasyTime Mail-Test</b><br>SMTP-Verbindung funktioniert.';
        $mail->AltBody = 'EasyTime Mail-Test — SMTP-Verbindung funktioniert.';
        $mail->send();
    }

    /** Gleiche SMTP-Einstellungen wie mailtest.php — einheitlich für alle Mails. */
    public static function applySmtpTransport(PHPMailer $mail): void {
        $mail->isSMTP();
        $mail->Host = (string) (getenv('MAIL_HOST') ?: 'pop.easydrivers.at');
        $mail->Port = (int) (getenv('MAIL_PORT') ?: 25);
        $mail->SMTPAuth = self::envBool('MAIL_SMTP_AUTH', true);

        if ($mail->SMTPAuth) {
            $mail->Username = (string) (getenv('MAIL_USERNAME') ?: '');
            $mail->Password = (string) (getenv('MAIL_PASSWORD') ?: '');
            $mail->AuthType = (string) (getenv('MAIL_AUTH_TYPE') ?: 'LOGIN');
        }

        $encryption = strtolower((string) (getenv('MAIL_ENCRYPTION') ?: 'tls'));
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPAutoTLS = false;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAutoTLS = true;
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = self::envBool('MAIL_SMTP_AUTO_TLS', true);
        }

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
    }

    private static function configureTransport(PHPMailer $mail): void {
        self::applySmtpTransport($mail);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function buildViewData(array $user, int $notificationId, array $payload): array {
        $type = (string) ($payload['type'] ?? Inbox::TYPE_INFO);
        $category = (string) ($payload['category'] ?? 'info');
        $actionUrl = trim((string) ($payload['action_url'] ?? ''));
        $inboxUrl = self::absoluteUrl('/?tab=inbox&notification_id=' . $notificationId);
        $actionAbsoluteUrl = $actionUrl !== '' ? self::absoluteUrl($actionUrl) : '';

        return [
            'recipientName' => trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')),
            'title' => trim((string) ($payload['title'] ?? '')),
            'message' => trim((string) ($payload['message'] ?? '')),
            'category' => $category,
            'categoryLabel' => self::categoryLabel($category),
            'categoryStyle' => self::categoryStyle($category),
            'type' => $type,
            'isTask' => $type === Inbox::TYPE_TASK,
            'inboxUrl' => $inboxUrl,
            'actionUrl' => $actionAbsoluteUrl,
            'appName' => (string) (getenv('MAIL_FROM_NAME') ?: 'EasyTime'),
            'year' => (int) date('Y'),
        ];
    }

    /**
     * @param array<string, mixed> $viewData
     */
    private static function buildPlainText(array $viewData): string {
        $lines = [
            (string) ($viewData['title'] ?? ''),
            '',
            (string) ($viewData['message'] ?? ''),
            '',
            'In EasyTime öffnen: ' . (string) ($viewData['inboxUrl'] ?? ''),
        ];

        if (!empty($viewData['actionUrl'])) {
            $lines[] = 'Aufgabe öffnen: ' . (string) $viewData['actionUrl'];
        }

        $lines[] = '';
        $lines[] = (string) ($viewData['appName'] ?? 'EasyTime');

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $viewData */
    private static function renderTemplate(string $name, array $viewData): string {
        $layoutPath = dirname(__DIR__) . '/Views/emails/layout.php';
        $contentPath = dirname(__DIR__) . '/Views/emails/' . $name . '.php';
        if (!is_file($contentPath)) {
            throw new \RuntimeException('Email template not found: ' . $name);
        }

        $render = static function (string $path, array $data): string {
            extract($data, EXTR_SKIP);
            ob_start();
            include $path;
            return (string) ob_get_clean();
        };

        $content = $render($contentPath, $viewData);
        return $render($layoutPath, array_merge($viewData, ['content' => $content]));
    }

    private static function categoryLabel(string $category): string {
        return match ($category) {
            'approval' => 'Aufgabe',
            'password' => 'Passwort',
            'success'  => 'Erfolg',
            'rejected' => 'Abgelehnt',
            'task'     => 'Aufgabe',
            default    => 'Info',
        };
    }

  /** @return array{background: string, color: string, border: string} */
    private static function categoryStyle(string $category): array {
        return match ($category) {
            'approval' => ['background' => '#ffedd5', 'color' => '#7c2d12', 'border' => '#fed7aa'],
            'password' => ['background' => '#fff8fc', 'color' => '#E8007D', 'border' => '#f9a8d4'],
            'success'  => ['background' => '#dcfce7', 'color' => '#166534', 'border' => '#bbf7d0'],
            'rejected' => ['background' => '#fee2e2', 'color' => '#991b1b', 'border' => '#fecaca'],
            default    => ['background' => '#f7fee7', 'color' => '#065f46', 'border' => '#d9f99d'],
        };
    }

    public static function absoluteUrl(string $path): string {
        $path = $path !== '' ? $path : '/';
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $base = rtrim((string) (getenv('APP_URL') ?: ''), '/');
        if ($base === '' && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }
        if ($base === '') {
            $base = 'http://localhost:8080';
        }

        return $base . (str_starts_with($path, '/') ? $path : '/' . $path);
    }

    private static function envBool(string $key, bool $default): bool {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private static function ensureAutoload(): void {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new \RuntimeException('Composer autoload missing. Run: composer install');
        }

        require_once $autoload;
        $loaded = true;
    }
}

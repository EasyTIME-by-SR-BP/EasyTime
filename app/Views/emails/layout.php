<?php
/** @var string $content */
/** @var string $appName */
/** @var int $year */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($title ?? $appName), ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body style="margin:0;padding:0;background-color:#fffdf2;font-family:Arial,Helvetica,sans-serif;color:#1f1f1f;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#fffdf2;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background-color:#ffffff;border:1px solid #ecfccb;border-radius:24px;overflow:hidden;box-shadow:0 10px 30px rgba(26,26,26,0.08);">
                    <tr>
                        <td style="padding:28px 32px 16px 32px;background:linear-gradient(135deg,#fffdf2 0%,#fff0f7 100%);border-bottom:1px solid #ecfccb;">
                            <div style="font-size:11px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#E8007D;margin-bottom:8px;">
                                <?= htmlspecialchars((string) $appName, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div style="font-size:13px;color:#4a4a4a;">Interne Benachrichtigung</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 32px 32px;">
                            <?= $content ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px 28px 32px;border-top:1px solid #ecfccb;background-color:#fffdf2;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;">
                                Diese E-Mail wurde automatisch von <?= htmlspecialchars((string) $appName, ENT_QUOTES, 'UTF-8') ?> versendet.
                                Antworten auf diese Nachricht werden nicht gelesen.
                            </p>
                            <p style="margin:10px 0 0 0;font-size:11px;color:#9ca3af;">
                                &copy; <?= (int) $year ?> <?= htmlspecialchars((string) $appName, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

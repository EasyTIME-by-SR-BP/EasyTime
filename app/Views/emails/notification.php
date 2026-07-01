<?php
/** @var string $recipientName */
/** @var string $title */
/** @var string $message */
/** @var string $categoryLabel */
/** @var array{background: string, color: string, border: string} $categoryStyle */
/** @var bool $isTask */
/** @var string $inboxUrl */
/** @var string $actionUrl */
?>
<?php if ($recipientName !== ''): ?>
    <p style="margin:0 0 18px 0;font-size:14px;line-height:1.6;color:#4a4a4a;">
        Hallo <?= htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') ?>,
    </p>
<?php endif; ?>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #ecfccb;border-radius:18px;background-color:#fffdf2;">
    <tr>
        <td style="padding:22px 24px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="padding-bottom:14px;">
                        <span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:10px;font-weight:700;border:1px solid <?= htmlspecialchars($categoryStyle['border'], ENT_QUOTES, 'UTF-8') ?>;background:<?= htmlspecialchars($categoryStyle['background'], ENT_QUOTES, 'UTF-8') ?>;color:<?= htmlspecialchars($categoryStyle['color'], ENT_QUOTES, 'UTF-8') ?>;">
                            <?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php if ($isTask): ?>
                            <span style="display:inline-block;margin-left:6px;padding:4px 10px;border-radius:999px;font-size:10px;font-weight:700;border:1px solid #fed7aa;background:#ffedd5;color:#7c2d12;">
                                Aufgabe
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding-bottom:12px;">
                        <h1 style="margin:0;font-size:20px;line-height:1.3;color:#1f1f1f;">
                            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                        </h1>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p style="margin:0;font-size:15px;line-height:1.7;color:#2d2d2d;white-space:pre-wrap;">
                            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table role="presentation" cellspacing="0" cellpadding="0" style="margin-top:24px;">
    <tr>
        <td style="padding-right:10px;padding-bottom:10px;">
            <a href="<?= htmlspecialchars($inboxUrl, ENT_QUOTES, 'UTF-8') ?>" style="display:inline-block;padding:12px 18px;border-radius:12px;background-color:#E8007D;color:#fff8fc;text-decoration:none;font-size:13px;font-weight:700;">
                In EasyTime öffnen
            </a>
        </td>
        <?php if ($isTask && $actionUrl !== ''): ?>
            <td style="padding-bottom:10px;">
                <a href="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>" style="display:inline-block;padding:12px 18px;border-radius:12px;border:1px solid #ecfccb;background-color:#ffffff;color:#1f1f1f;text-decoration:none;font-size:13px;font-weight:700;">
                    Aufgabe öffnen
                </a>
            </td>
        <?php endif; ?>
    </tr>
</table>

<p style="margin:18px 0 0 0;font-size:12px;line-height:1.6;color:#6b7280;">
    Aktionen wie „Als erledigt markieren“ sind nur in der Inbox in EasyTime möglich.
</p>

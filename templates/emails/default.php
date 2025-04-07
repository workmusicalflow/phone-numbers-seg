<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $subject ?? 'Notification - Oracle SMS' ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #4a6da7;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }

        .content {
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }

        .button {
            display: inline-block;
            background-color: #4a6da7;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }

        .note {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 10px;
            margin-top: 20px;
            border-radius: 5px;
            font-size: 14px;
        }

        .highlight {
            background-color: #e8f4ff;
            border: 1px solid #b3d7ff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .thank-you {
            font-size: 18px;
            color: #4a6da7;
            margin-top: 20px;
            text-align: center;
        }

        .divider {
            height: 1px;
            background-color: #ddd;
            margin: 20px 0;
        }

        .text-center {
            text-align: center;
        }

        .text-large {
            font-size: 18px;
        }

        .text-bold {
            font-weight: bold;
        }

        .text-primary {
            color: #4a6da7;
        }

        .text-success {
            color: #28a745;
        }

        .text-warning {
            color: #ffc107;
        }

        .text-danger {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1><?= $title ?? $subject ?? 'Notification' ?></h1>
    </div>
    <div class="content">
        <p>Bonjour <?= $username ?? 'Utilisateur' ?>,</p>

        <?php if (isset($message) && !empty($message)) : ?>
            <?= $message ?>
        <?php else : ?>
            <p>Ceci est une notification automatique de la part d'Oracle SMS.</p>
        <?php endif; ?>

        <?php if (isset($highlightContent) && !empty($highlightContent)) : ?>
            <div class="highlight">
                <?= $highlightContent ?>
            </div>
        <?php endif; ?>

        <?php if (isset($noteContent) && !empty($noteContent)) : ?>
            <div class="note">
                <strong>Note :</strong> <?= $noteContent ?>
            </div>
        <?php endif; ?>

        <?php if (isset($buttonUrl) && isset($buttonText)) : ?>
            <p class="text-center">
                <a href="<?= $buttonUrl ?>" class="button"><?= $buttonText ?></a>
            </p>
        <?php endif; ?>

        <?php if (isset($showThankYou) && $showThankYou) : ?>
            <p class="thank-you">Merci pour votre confiance!</p>
        <?php endif; ?>
    </div>
    <div class="footer">
        <p>© <?= date('Y') ?> Oracle SMS. Tous droits réservés.</p>
        <p>Pour toute question, veuillez contacter notre service client.</p>
        <?php if (isset($unsubscribeUrl)) : ?>
            <p><a href="<?= $unsubscribeUrl ?>">Se désabonner</a></p>
        <?php endif; ?>
    </div>
</body>

</html>
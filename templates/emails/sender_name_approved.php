<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nom d'expéditeur approuvé - Oracle SMS</title>
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

        .approval-info {
            background-color: #e8f4ff;
            border: 1px solid #b3d7ff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }

        .sender-name {
            font-size: 24px;
            font-weight: bold;
            color: #4a6da7;
            margin: 10px 0;
            padding: 10px;
            background-color: #f0f7ff;
            border: 1px dashed #b3d7ff;
            border-radius: 5px;
            display: inline-block;
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

        .thank-you {
            font-size: 18px;
            color: #4a6da7;
            margin-top: 20px;
            text-align: center;
        }

        .guidelines {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .guidelines h3 {
            margin-top: 0;
            color: #4a6da7;
        }

        .guidelines ul {
            padding-left: 20px;
        }

        .guidelines li {
            margin-bottom: 8px;
        }

        .success-icon {
            font-size: 48px;
            color: #28a745;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Nom d'expéditeur approuvé</h1>
    </div>
    <div class="content">
        <p>Bonjour <?= $username ?? 'Utilisateur' ?>,</p>

        <p>Nous avons le plaisir de vous informer que votre demande de nom d'expéditeur a été
            <strong>approuvée</strong>.
        </p>

        <div class="success-icon">✓</div>

        <div class="approval-info">
            <p>Nom d'expéditeur approuvé :</p>
            <div class="sender-name">
                <?= $senderName ?? 'SENDER' ?>
            </div>
            <p>Date d'approbation :
                <?= isset($approvalDate) ? date('d/m/Y', strtotime($approvalDate)) : date('d/m/Y') ?></p>
        </div>

        <p>Vous pouvez dès maintenant utiliser ce nom d'expéditeur pour vos campagnes SMS. Ce nom apparaîtra comme
            expéditeur des SMS que vous enverrez à vos contacts.</p>

        <div class="guidelines">
            <h3>Rappel des bonnes pratiques</h3>
            <ul>
                <li>Utilisez un nom d'expéditeur qui identifie clairement votre entreprise ou service</li>
                <li>Évitez d'utiliser des noms génériques qui pourraient prêter à confusion</li>
                <li>Respectez les réglementations locales concernant l'identification des expéditeurs de SMS</li>
                <li>Incluez toujours une option de désinscription dans vos messages</li>
            </ul>
        </div>

        <p style="text-align: center;">
            <a href="<?= $dashboardUrl ?? '#' ?>" class="button">Accéder à mon compte</a>
        </p>

        <div class="note">
            <strong>Note :</strong> Si vous n'avez pas demandé ce nom d'expéditeur ou si vous avez des questions,
            veuillez contacter notre service client immédiatement.
        </div>

        <p class="thank-you">Merci pour votre confiance!</p>
    </div>
    <div class="footer">
        <p>© <?= date('Y') ?> Oracle SMS. Tous droits réservés.</p>
        <p>Pour toute question concernant votre nom d'expéditeur, veuillez contacter notre service client.</p>
    </div>
</body>

</html>
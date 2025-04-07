<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crédits SMS ajoutés - Oracle SMS</title>
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

        .credit-info {
            background-color: #e8f4ff;
            border: 1px solid #b3d7ff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }

        .credit-amount {
            font-size: 36px;
            font-weight: bold;
            color: #4a6da7;
            margin: 10px 0;
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

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .summary-table th,
        .summary-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .summary-table th {
            background-color: #f2f2f2;
        }

        .summary-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Crédits SMS ajoutés</h1>
    </div>
    <div class="content">
        <p>Bonjour <?= $username ?? 'Utilisateur' ?>,</p>

        <p>Nous avons le plaisir de vous informer que des crédits SMS ont été ajoutés à votre compte.</p>

        <div class="credit-info">
            <p>Crédits SMS ajoutés :</p>
            <div class="credit-amount">
                <?= $creditAmount ?? '0' ?>
            </div>
            <p>Date d'ajout : <?= isset($addDate) ? date('d/m/Y', strtotime($addDate)) : date('d/m/Y') ?></p>
            <p>Nouveau solde total : <strong><?= $newBalance ?? '0' ?></strong> crédits SMS</p>
        </div>

        <?php if (isset($orderDetails) && !empty($orderDetails)) : ?>
            <h3>Détails de la commande</h3>
            <table class="summary-table">
                <tr>
                    <th>Référence</th>
                    <td><?= $orderDetails['reference'] ?? '-' ?></td>
                </tr>
                <tr>
                    <th>Date de commande</th>
                    <td><?= isset($orderDetails['orderDate']) ? date('d/m/Y', strtotime($orderDetails['orderDate'])) : '-' ?>
                    </td>
                </tr>
                <tr>
                    <th>Montant</th>
                    <td><?= $orderDetails['amount'] ?? '-' ?></td>
                </tr>
                <tr>
                    <th>Méthode de paiement</th>
                    <td><?= $orderDetails['paymentMethod'] ?? '-' ?></td>
                </tr>
            </table>
        <?php endif; ?>

        <p>Vous pouvez dès maintenant utiliser ces crédits pour envoyer des SMS à vos contacts.</p>

        <p style="text-align: center;">
            <a href="<?= $dashboardUrl ?? '#' ?>" class="button">Accéder à mon compte</a>
        </p>

        <div class="note">
            <strong>Note :</strong> Si vous n'avez pas commandé ces crédits ou si vous avez des questions concernant
            votre solde, veuillez contacter notre service client immédiatement.
        </div>

        <p class="thank-you">Merci pour votre confiance!</p>
    </div>
    <div class="footer">
        <p>© <?= date('Y') ?> Oracle SMS. Tous droits réservés.</p>
        <p>Pour toute question concernant vos crédits SMS, veuillez contacter notre service client.</p>
    </div>
</body>

</html>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande - Oracle SMS</title>
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

        .order-info {
            background-color: #e8f4ff;
            border: 1px solid #b3d7ff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .order-number {
            font-size: 24px;
            font-weight: bold;
            color: #4a6da7;
            margin: 10px 0;
            text-align: center;
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

        .steps {
            margin: 30px 0;
            padding: 0;
            list-style-type: none;
            counter-reset: steps;
        }

        .steps li {
            position: relative;
            margin-bottom: 15px;
            padding-left: 40px;
            padding-bottom: 15px;
            border-left: 2px solid #4a6da7;
        }

        .steps li:last-child {
            border-left: none;
        }

        .steps li::before {
            counter-increment: steps;
            content: counter(steps);
            position: absolute;
            left: -15px;
            top: -5px;
            width: 30px;
            height: 30px;
            background-color: #4a6da7;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
        }

        .steps li:last-child::before {
            background-color: #28a745;
        }

        .steps h4 {
            margin: 0 0 5px 0;
            color: #4a6da7;
        }

        .steps p {
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Confirmation de commande</h1>
    </div>
    <div class="content">
        <p>Bonjour <?= $username ?? 'Utilisateur' ?>,</p>

        <p>Nous vous remercions pour votre commande de crédits SMS. Votre demande a bien été enregistrée et est en cours
            de traitement.</p>

        <div class="order-info">
            <p>Référence de commande :</p>
            <div class="order-number">
                <?= $orderReference ?? 'ORD-' . date('YmdHis') ?>
            </div>
            <p>Date de commande :
                <?= isset($orderDate) ? date('d/m/Y H:i', strtotime($orderDate)) : date('d/m/Y H:i') ?></p>
        </div>

        <h3>Récapitulatif de votre commande</h3>
        <table class="summary-table">
            <tr>
                <th>Produit</th>
                <td>Pack de crédits SMS</td>
            </tr>
            <tr>
                <th>Quantité</th>
                <td><?= $creditAmount ?? '0' ?> crédits</td>
            </tr>
            <tr>
                <th>Prix unitaire</th>
                <td><?= $unitPrice ?? '-' ?></td>
            </tr>
            <tr>
                <th>Montant total</th>
                <td><strong><?= $totalAmount ?? '-' ?></strong></td>
            </tr>
            <tr>
                <th>Méthode de paiement</th>
                <td><?= $paymentMethod ?? 'Paiement sur facture' ?></td>
            </tr>
        </table>

        <h3>Prochaines étapes</h3>
        <ol class="steps">
            <li>
                <h4>Commande reçue</h4>
                <p>Votre commande a été enregistrée dans notre système.</p>
            </li>
            <li>
                <h4>Traitement du paiement</h4>
                <p>Notre équipe va traiter votre paiement selon la méthode choisie.</p>
            </li>
            <li>
                <h4>Ajout des crédits</h4>
                <p>Une fois le paiement confirmé, les crédits seront ajoutés à votre compte.</p>
            </li>
            <li>
                <h4>Confirmation finale</h4>
                <p>Vous recevrez une notification par email et SMS lorsque vos crédits seront disponibles.</p>
            </li>
        </ol>

        <div class="note">
            <strong>Note :</strong> Le traitement de votre commande peut prendre jusqu'à 24 heures ouvrables. Si vous
            avez des questions concernant votre commande, veuillez contacter notre service client en mentionnant votre
            numéro de référence.
        </div>

        <p style="text-align: center;">
            <a href="<?= $dashboardUrl ?? '#' ?>" class="button">Accéder à mon compte</a>
        </p>

        <p class="thank-you">Merci pour votre confiance!</p>
    </div>
    <div class="footer">
        <p>© <?= date('Y') ?> Oracle SMS. Tous droits réservés.</p>
        <p>Pour toute question concernant votre commande, veuillez contacter notre service client.</p>
    </div>
</body>

</html>
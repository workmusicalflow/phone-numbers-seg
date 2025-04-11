<?php

/**
 * Interface web pour la conversion des numéros de téléphone en contacts
 * 
 * Ce script fournit une interface web pour exécuter le script de conversion
 * des numéros de téléphone en contacts.
 */

// Vérifier si l'utilisateur est authentifié (à adapter selon votre système d'authentification)
session_start();
$isAuthenticated = false;

// Charger les variables d'environnement
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Charger les définitions du conteneur DI
$definitions = require __DIR__ . '/../src/config/di.php';

// Construire le conteneur DI
$containerBuilder = new DI\ContainerBuilder();
$containerBuilder->addDefinitions($definitions);
$container = $containerBuilder->build();

// Vérifier l'authentification via le service d'authentification
try {
    $authService = $container->get(App\Services\Interfaces\AuthServiceInterface::class);
    $currentUser = $authService->getCurrentUser();
    $isAuthenticated = $currentUser !== null;
    $isAdmin = $isAuthenticated && $currentUser->isAdmin();
} catch (Exception $e) {
    $isAuthenticated = false;
    $isAdmin = false;
}

// Rediriger vers la page de connexion si non authentifié
if (!$isAuthenticated) {
    header('Location: /login');
    exit;
}

// Variables pour l'interface
$result = null;
$error = null;
$success = false;

// Traiter la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $action = $_POST['action'];

        if ($action === 'convert') {
            $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 2;
            $dryRun = isset($_POST['dry_run']) && $_POST['dry_run'] === '1';
            $limit = isset($_POST['limit']) && !empty($_POST['limit']) ? (int)$_POST['limit'] : null;
            $offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;

            // Construire la commande
            $command = 'php ' . __DIR__ . '/../scripts/utils/convert_phone_numbers_to_contacts.php';
            $command .= ' --user-id=' . $userId;
            if ($dryRun) {
                $command .= ' --dry-run';
            }
            if ($limit !== null) {
                $command .= ' --limit=' . $limit;
            }
            if ($offset > 0) {
                $command .= ' --offset=' . $offset;
            }

            // Exécuter la commande et capturer la sortie
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);

            $result = implode("\n", $output);
            $success = $returnCode === 0;

            if (!$success) {
                $error = "La commande a échoué avec le code de retour $returnCode";
            }
        }
    } catch (Exception $e) {
        $error = "Erreur: " . $e->getMessage();
    }
}

// Récupérer la liste des utilisateurs pour le sélecteur
$users = [];
try {
    $userRepository = $container->get(App\Repositories\UserRepository::class);
    $users = $userRepository->findAll();
} catch (Exception $e) {
    // Ignorer l'erreur, le sélecteur d'utilisateur sera désactivé
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversion des numéros de téléphone en contacts</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            padding: 20px;
        }

        .result-container {
            max-height: 500px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="mb-4">Conversion des numéros de téléphone en contacts</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                La conversion a été exécutée avec succès.
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Options de conversion</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Utilisateur cible</label>
                        <select class="form-select" id="user_id" name="user_id"
                            <?php echo empty($users) ? 'disabled' : ''; ?>>
                            <?php if (empty($users)): ?>
                                <option value="2">AfricaQSHE (ID 2)</option>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user->getId(); ?>"
                                        <?php echo $user->getId() === 2 ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user->getUsername() . ' (ID ' . $user->getId() . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">Utilisateur auquel les contacts seront associés.</div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="dry_run" name="dry_run" value="1" checked>
                        <label class="form-check-label" for="dry_run">Mode simulation (dry-run)</label>
                        <div class="form-text">Cochez cette case pour simuler la conversion sans modifier la base de
                            données.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="limit" class="form-label">Limite</label>
                                <input type="number" class="form-control" id="limit" name="limit" min="1"
                                    placeholder="Tous les numéros">
                                <div class="form-text">Nombre maximum de numéros à traiter (vide = tous).</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="offset" class="form-label">Offset</label>
                                <input type="number" class="form-control" id="offset" name="offset" min="0" value="0">
                                <div class="form-text">Numéro à partir duquel commencer le traitement.</div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="action" value="convert" class="btn btn-primary">Lancer la
                        conversion</button>
                </form>
            </div>
        </div>

        <?php if ($result): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Résultat de la conversion</h5>
                </div>
                <div class="card-body">
                    <div class="result-container">
                        <?php echo htmlspecialchars($result); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="/" class="btn btn-secondary">Retour à l'accueil</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
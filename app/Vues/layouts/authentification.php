<!doctype html>
<html lang="<?= e(langue_actuelle()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(traduire_html_interface((string) ($titre ?? traduire('app.nom')))) ?></title>
    <script>
        try {
            const theme = localStorage.getItem('vote_upc_theme');
            if (theme === 'sombre') {
                document.documentElement.dataset.theme = 'sombre';
            }
        } catch (erreur) {}
    </script>
    <link rel="stylesheet" href="/assets/css/application.css">
</head>
<body class="page-authentification">
    <div class="outils-interface">
        <details class="selecteur-langue">
            <summary>
                <span><?= e(traduire('langue.libelle')) ?></span>
                <strong><?= e(traduire('langue.code')) ?></strong>
            </summary>
            <div class="menu-langue">
                <a href="/langue?choix=fr&retour=<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>" class="<?= langue_actuelle() === 'fr' ? 'actif' : '' ?>">
                    <?= e(traduire('langue.francais')) ?>
                </a>
                <a href="/langue?choix=en&retour=<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>" class="<?= langue_actuelle() === 'en' ? 'actif' : '' ?>">
                    <?= e(traduire('langue.anglais')) ?>
                </a>
            </div>
        </details>
        <button type="button" class="bouton-theme" data-theme-toggle>
            <?= e(traduire('theme.sombre')) ?>
        </button>
    </div>
    <main class="cadre-authentification">
        <?= traduire_html_interface($contenu) ?>
    </main>
    <script src="/assets/js/application.js"></script>
</body>
</html>

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
<body class="page-application">
    <?php
    $utilisateurConnecte = $utilisateur ?? \Application\Services\ServiceAuthentification::utilisateur();
    $roleCode = $utilisateurConnecte['role_code'] ?? '';
    $cheminActuel = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $menus = [
        'super_administrateur' => [
            [traduire('menu.tableau_de_bord'), '/super-administrateur/tableau-de-bord'],
            [traduire('menu.etudiants'), '/super-administrateur/etudiants'],
            [traduire('menu.import_excel'), '/super-administrateur/etudiants/importer'],
            [traduire('menu.appariteurs'), '/super-administrateur/appariteurs'],
            [traduire('menu.president_electoral'), '/super-administrateur/presidents-electoraux'],
            [traduire('menu.candidats'), '/super-administrateur/candidats'],
            [traduire('menu.elections'), '/super-administrateur/elections'],
            [traduire('menu.statistiques'), '/super-administrateur/statistiques'],
            [traduire('menu.activations'), '/super-administrateur/journal-activations'],
            [traduire('menu.activite'), '/super-administrateur/activites'],
        ],
        'president_electoral' => [
            [traduire('menu.tableau_de_bord'), '/president-electoral/tableau-de-bord'],
            [traduire('menu.candidats'), '/president-electoral/candidats'],
            [traduire('menu.validations'), '/president-electoral/elections/validations'],
            [traduire('menu.statistiques'), '/president-electoral/statistiques'],
            [traduire('menu.rapport_votes'), '/president-electoral/rapports/participation'],
            [traduire('menu.publication'), '/president-electoral/resultats/publication'],
            [traduire('menu.annulations'), '/president-electoral/elections/annulations'],
        ],
        'appariteur' => [
            [traduire('menu.tableau_de_bord'), '/appariteur/tableau-de-bord'],
            [traduire('menu.recherche'), '/appariteur/etudiants/rechercher'],
            [traduire('menu.etudiants'), '/appariteur/etudiants'],
            [traduire('menu.activations'), '/appariteur/activations'],
            [traduire('menu.comptes_bloques'), '/appariteur/comptes-bloques'],
        ],
        'etudiant' => [
            [traduire('menu.tableau_de_bord'), '/etudiant/tableau-de-bord'],
            [traduire('menu.candidats'), '/etudiant/candidats'],
            [traduire('menu.vote'), '/etudiant/vote'],
            [traduire('menu.resultats'), '/etudiant/resultats'],
            [traduire('menu.mot_de_passe'), '/etudiant/profil/mot-de-passe'],
        ],
    ];
    $menuActuel = $menus[$roleCode] ?? [];
    ?>

    <header class="barre-application">
        <div class="identite-application">
            <a class="marque-application" href="<?= e(\Application\Services\ServiceAuthentification::cheminApresConnexion($roleCode)) ?>">
                <?= e(traduire('app.nom')) ?>
            </a>
            <span><?= e(traduire('role.' . ($utilisateurConnecte['role_code'] ?? 'session'))) ?></span>
        </div>

        <nav class="navigation-application" aria-label="Navigation principale">
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
            <a href="<?= $roleCode === 'etudiant' ? '/etudiant/connexion?changer=1' : '/administration/connexion?changer=1' ?>"><?= e(traduire('bouton.changer_compte')) ?></a>
            <form method="post" action="/deconnexion">
                <button type="submit"><?= e(traduire('bouton.deconnexion')) ?></button>
            </form>
        </nav>
    </header>

    <div class="coque-application">
        <aside class="menu-lateral" aria-label="Menu du role">
            <div class="profil-rapide">
                <strong><?= e($utilisateurConnecte['nom_utilisateur'] ?? '') ?></strong>
                <span><?= e($utilisateurConnecte['email'] ?? '') ?></span>
            </div>

            <?php if (!empty($menuActuel)): ?>
                <nav>
                    <?php foreach ($menuActuel as [$libelle, $lien]): ?>
                        <a href="<?= e($lien) ?>" class="<?= $cheminActuel === $lien ? 'actif' : '' ?>">
                            <?= e($libelle) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </aside>

        <main class="conteneur-application">
            <?= traduire_html_interface($contenu) ?>
        </main>
    </div>

    <script src="/assets/js/application.js?v=<?= e((string) @filemtime(CHEMIN_RACINE . '/public/assets/js/application.js')) ?>"></script>
</body>
</html>

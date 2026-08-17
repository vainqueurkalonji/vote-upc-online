<section class="entete-page">
    <p class="surtitre"><?= e(traduire('super_admin.surtitre')) ?></p>
    <h1>
        <?= e(traduire('super_admin.bienvenue', [
            'nom_utilisateur' => $utilisateur['nom_utilisateur'] ?? '',
        ])) ?>
    </h1>
    <p><?= e(traduire('super_admin.description')) ?></p>
</section>

<section class="grille-statistiques" aria-label="Resume global">
    <article class="carte-statistique">
        <strong><?= e($statistiques['facultes'] ?? 0) ?></strong>
        <span>Facultes configurees</span>
    </article>
    <article class="carte-statistique">
        <strong><?= e($statistiques['promotions'] ?? 0) ?></strong>
        <span>Promotions officielles</span>
    </article>
    <article class="carte-statistique">
        <strong><?= e($statistiques['etudiants'] ?? 0) ?></strong>
        <span>Etudiants importes</span>
    </article>
    <article class="carte-statistique">
        <strong><?= e($statistiques['utilisateurs'] ?? 0) ?></strong>
        <span>Comptes utilisateurs</span>
    </article>
</section>

<section class="grille-tableau-de-bord">
    <?php
    $modules = [
        [
            'titre' => traduire('super_admin.module.etudiants.titre'),
            'texte' => 'Importer, consulter et corriger les etudiants avant les elections.',
            'lien' => '/super-administrateur/etudiants',
        ],
        [
            'titre' => traduire('super_admin.module.import.titre'),
            'texte' => 'Telecharger le modele officiel puis deposer le fichier rempli.',
            'lien' => '/super-administrateur/etudiants/importer',
        ],
        [
            'titre' => traduire('super_admin.module.appariteurs.titre'),
            'texte' => 'Creer les appariteurs et limiter chacun a une seule faculte.',
            'lien' => '/super-administrateur/appariteurs',
        ],
        [
            'titre' => traduire('super_admin.module.presidents.titre'),
            'texte' => 'Designer les responsables de validation et publication.',
            'lien' => '/super-administrateur/presidents-electoraux',
        ],
        [
            'titre' => traduire('super_admin.module.candidats.titre'),
            'texte' => 'Preparer les candidats, photos, slogans et visions.',
            'lien' => '/super-administrateur/candidats',
        ],
        [
            'titre' => traduire('super_admin.module.elections.titre'),
            'texte' => 'Creer une election avec portee, dates et chrono.',
            'lien' => '/super-administrateur/elections',
        ],
        [
            'titre' => traduire('super_admin.module.statistiques.titre'),
            'texte' => 'Suivre les effectifs, activations et participations anonymes.',
            'lien' => '/super-administrateur/statistiques',
        ],
        [
            'titre' => traduire('super_admin.module.activations.titre'),
            'texte' => traduire('super_admin.module.activations.texte'),
            'lien' => '/super-administrateur/journal-activations',
        ],
        [
            'titre' => 'Activite',
            'texte' => 'Voir les connexions, deconnexions et actions des appariteurs et presidents.',
            'lien' => '/super-administrateur/activites',
        ],
    ];
    ?>

    <?php foreach ($modules as $module): ?>
        <article class="carte-module">
            <div>
                <h2><?= e($module['titre']) ?></h2>
                <p><?= e($module['texte']) ?></p>
            </div>
            <a href="<?= e($module['lien']) ?>"><?= e(traduire('bouton.ouvrir')) ?></a>
        </article>
    <?php endforeach; ?>
</section>

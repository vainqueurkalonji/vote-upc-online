<?php
$libellesActions = [
    'connexion_reussie' => 'Connexion reussie',
    'deconnexion' => 'Deconnexion',
    'activation_etudiant' => 'Activation etudiant',
    'reactivation_etudiant' => 'Reactivation etudiant',
    'validation_lancement_validee' => 'Validation lancement',
    'validation_lancement_refusee' => 'Refus lancement',
    'publication_resultats' => 'Publication resultats',
    'annulation_faculte' => 'Annulation faculte',
];
$libellesRoles = [
    'appariteur' => 'Appariteur',
    'president_electoral' => 'President electoral',
];
$groupes = [];

foreach (($activites ?? []) as $activite) {
    $jour = substr((string) $activite['cree_le'], 0, 10);
    $groupes[$jour][] = $activite;
}

$resumeDetails = static function (array $details): string {
    $elements = [];

    foreach (['matricule', 'faculte', 'promotion', 'decision', 'email_envoye', 'type_connexion', 'methode'] as $cle) {
        if (!array_key_exists($cle, $details) || $details[$cle] === '') {
            continue;
        }

        $valeur = is_bool($details[$cle]) ? ($details[$cle] ? 'oui' : 'non') : (string) $details[$cle];
        $elements[] = $cle . ' : ' . $valeur;
    }

    return implode(' | ', $elements);
};
?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Super administrateur</p>
        <h1>Activite du personnel</h1>
        <p>Suivi compact des connexions, deconnexions et actions des appariteurs et presidents electoraux.</p>
    </div>
    <span class="badge-page">Tracabilite</span>
</section>

<section class="grille-statistiques">
    <article class="carte-statistique"><strong><?= e($statistiques['total'] ?? 0) ?></strong><span>Activites</span></article>
    <article class="carte-statistique"><strong><?= e($statistiques['connexions'] ?? 0) ?></strong><span>Connexions</span></article>
    <article class="carte-statistique"><strong><?= e($statistiques['deconnexions'] ?? 0) ?></strong><span>Deconnexions</span></article>
    <article class="carte-statistique"><strong><?= e($statistiques['presidents'] ?? 0) ?></strong><span>Actions presidents</span></article>
</section>

<section class="bloc-module">
    <div class="titre-bloc">
        <div>
            <p class="surtitre">Filtres</p>
            <h2>Afficher les evenements</h2>
        </div>
        <span class="etat-module"><?= e(count($activites ?? [])) ?> ligne(s)</span>
    </div>

    <form method="get" action="/super-administrateur/activites" class="formulaire formulaire-activites">
        <label>
            <span>Role</span>
            <select name="role">
                <option value="">Tous</option>
                <?php foreach ($libellesRoles as $code => $libelle): ?>
                    <option value="<?= e($code) ?>" <?= ($role_selectionne ?? '') === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Action</span>
            <select name="action">
                <option value="">Toutes</option>
                <?php foreach (($actions_disponibles ?? []) as $action): ?>
                    <option value="<?= e($action) ?>" <?= ($action_selectionnee ?? '') === $action ? 'selected' : '' ?>>
                        <?= e($libellesActions[$action] ?? $action) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Date</span>
            <input type="date" name="date" value="<?= e($date_selectionnee ?? '') ?>">
        </label>
        <button type="submit" class="bouton-principal">Filtrer</button>
    </form>
</section>

<section class="bloc-module">
    <div class="titre-bloc">
        <div>
            <p class="surtitre">Journal</p>
            <h2>Activites recentes</h2>
        </div>
        <span class="etat-module">Appariteurs / Presidents</span>
    </div>

    <?php if (empty($groupes)): ?>
        <div class="etat-vide-classe">Aucune activite trouvee pour ces filtres.</div>
    <?php endif; ?>

    <div class="liste-activites">
        <?php foreach ($groupes as $jour => $lignes): ?>
            <details class="classe-faculte groupe-activites" <?= $jour === array_key_first($groupes) ? 'open' : '' ?>>
                <summary>
                    <span>
                        <strong><?= e($jour) ?></strong>
                        <em><?= e(count($lignes)) ?> evenement(s)</em>
                    </span>
                    <span class="resume-classe">Voir</span>
                </summary>

                <div class="pile-activites">
                    <?php foreach ($lignes as $activite): ?>
                        <?php
                        $heure = substr((string) $activite['cree_le'], 11, 5);
                        $nomComplet = trim((string) ($activite['prenom'] ?? '') . ' ' . (string) ($activite['nom'] ?? '') . ' ' . (string) ($activite['post_nom'] ?? ''));
                        $role = (string) ($activite['role_code'] ?? '');
                        $details = $resumeDetails($activite['details'] ?? []);
                        ?>
                        <article class="ligne-activite">
                            <time><?= e($heure) ?></time>
                            <div>
                                <div class="ligne-activite-entete">
                                    <strong><?= e($nomComplet !== '' ? $nomComplet : ($activite['nom_utilisateur'] ?? 'Utilisateur')) ?></strong>
                                    <span class="badge-statut"><?= e($libellesRoles[$role] ?? $role) ?><?= !empty($activite['faculte_code']) ? ' - ' . e($activite['faculte_code']) : '' ?></span>
                                </div>
                                <p><?= e($libellesActions[$activite['action']] ?? $activite['action']) ?></p>
                                <?php if ($details !== ''): ?><small><?= e($details) ?></small><?php endif; ?>
                                <small><?= e($activite['email'] ?? '') ?><?= !empty($activite['adresse_ip']) ? ' | IP ' . e($activite['adresse_ip']) : '' ?></small>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</section>

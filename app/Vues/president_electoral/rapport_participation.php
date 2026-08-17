<?php
$rapport = $rapport ?? null;
$election = $rapport['election'] ?? null;
$totaux = $rapport['totaux'] ?? [];
$lignes = $rapport['lignes'] ?? [];
$libellePortee = static function (array $election): string {
    return match ((string) ($election['portee_type'] ?? '')) {
        'universite' => 'Toute l universite',
        'faculte' => 'Faculte ' . (string) ($election['faculte_code'] ?? ''),
        'promotion' => trim('Promotion ' . (string) ($election['faculte_code'] ?? '') . ' ' . (string) ($election['promotion_code'] ?? '')),
        'departement' => trim('Departement ' . (string) ($election['faculte_code'] ?? '') . ' ' . (string) ($election['departement_nom'] ?? '')),
        default => 'Portee academique',
    };
};
?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">President electoral</p>
        <h1>Rapport de participation</h1>
        <p>Document anonyme par election, faculte et promotion : votants, non-votants et taux de participation.</p>
    </div>
    <span class="badge-page">Document officiel</span>
</section>

<?php if (!empty($message_erreur)): ?><div class="alerte alerte-erreur"><?= e($message_erreur) ?></div><?php endif; ?>

<section class="bloc-module">
    <div class="titre-bloc">
        <div>
            <p class="surtitre">Selection</p>
            <h2>Choisir une election</h2>
        </div>
        <button type="button" class="bouton-secondaire" onclick="window.print()">Imprimer / PDF</button>
    </div>

    <?php if (empty($elections)): ?>
        <div class="etat-vide-classe">Aucune election lancee ou validee n'est disponible pour generer un rapport.</div>
    <?php else: ?>
        <form method="get" action="/president-electoral/rapports/participation" class="formulaire formulaire-selection-rapport">
            <label>
                <span>Election</span>
                <select name="election_id">
                    <?php foreach (($elections ?? []) as $electionOption): ?>
                        <option value="<?= e($electionOption['id']) ?>" <?= (int) $electionOption['id'] === (int) ($election_id ?? 0) ? 'selected' : '' ?>>
                            <?= e($electionOption['nom'] . ' - ' . $libellePortee($electionOption) . ' - ' . $electionOption['statut']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="bouton-principal">Afficher le rapport</button>
        </form>
    <?php endif; ?>
</section>

<?php if ($rapport && $election): ?>
    <section class="document-impression rapport-participation">
        <div class="document-entete">
            <div>
                <p class="surtitre">Vote UPC Online</p>
                <h2><?= e($election['nom']) ?></h2>
                <p><?= e($libellePortee($election)) ?> | <?= e($election['date_debut']) ?> - <?= e($election['date_fin']) ?></p>
            </div>
            <span class="badge-statut"><?= e($election['statut']) ?></span>
        </div>

        <div class="grille-statistiques">
            <article class="carte-statistique"><strong><?= e($totaux['etudiants'] ?? 0) ?></strong><span>Etudiants concernes</span></article>
            <article class="carte-statistique"><strong><?= e($totaux['actifs'] ?? 0) ?></strong><span>Comptes actives</span></article>
            <article class="carte-statistique"><strong><?= e($totaux['votants'] ?? 0) ?></strong><span>Votants</span></article>
            <article class="carte-statistique"><strong><?= e(number_format((float) ($totaux['taux_participation'] ?? 0), 2, ',', ' ')) ?>%</strong><span>Participation</span></article>
        </div>

        <div class="grille-statistiques grille-statistiques-secondaire">
            <article class="carte-statistique"><strong><?= e($totaux['non_votants'] ?? 0) ?></strong><span>Non-votants</span></article>
            <article class="carte-statistique"><strong><?= e($totaux['votes_valides'] ?? 0) ?></strong><span>Votes valides</span></article>
            <article class="carte-statistique"><strong><?= e($totaux['annules'] ?? 0) ?></strong><span>Participations annulees</span></article>
            <article class="carte-statistique"><strong><?= e($totaux['candidats'] ?? 0) ?></strong><span>Candidats</span></article>
        </div>

        <div class="tableau-responsive tableau-rapport">
            <table>
                <thead>
                    <tr>
                        <th>Faculte</th>
                        <th>Promotion</th>
                        <th>Etudiants</th>
                        <th>Actives</th>
                        <th>Bloques</th>
                        <th>Votants</th>
                        <th>Non-votants</th>
                        <th>Taux</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lignes as $ligne): ?>
                        <tr>
                            <td><?= e($ligne['faculte_code'] . ' - ' . $ligne['faculte_nom']) ?></td>
                            <td><?= e($ligne['promotion_code']) ?></td>
                            <td><?= e($ligne['total_etudiants']) ?></td>
                            <td><?= e($ligne['actifs']) ?></td>
                            <td><?= e($ligne['bloques']) ?></td>
                            <td><?= e($ligne['votants']) ?></td>
                            <td><?= e($ligne['non_votants']) ?></td>
                            <td><?= e(number_format((float) $ligne['taux_participation'], 2, ',', ' ')) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="note-securite-document">
            Rapport anonyme : aucun nom d'etudiant n'est affiche. Les chiffres servent au suivi officiel de la participation.
        </div>
    </section>
<?php endif; ?>

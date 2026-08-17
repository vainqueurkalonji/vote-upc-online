<?php
$statutElectionPresident = static function (array $election): string {
    $statut = (string) ($election['statut'] ?? '');

    if ($statut === 'brouillon') {
        $dateFinFuture = strtotime((string) ($election['date_fin'] ?? '')) > time();
        $aDesCandidats = (int) ($election['total_candidats'] ?? 0) > 0;

        return $dateFinFuture && $aDesCandidats ? 'Pret a valider' : 'En preparation';
    }

    if ($statut === 'en_attente_validation_lancement') {
        return 'En attente du president';
    }

    return $statut;
};
?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">President electoral</p>
        <h1>Bienvenue <?= e($utilisateur['nom_utilisateur'] ?? '') ?></h1>
        <p>Superviser les effectifs, candidats, validations, publications et annulations.</p>
    </div>
    <span class="badge-page">Aval officiel</span>
</section>

<section class="grille-statistiques" aria-label="Resume president">
    <article class="carte-statistique">
        <strong><?= e($statistiques_etudiants['total'] ?? 0) ?></strong>
        <span>Etudiants importes</span>
    </article>
    <article class="carte-statistique">
        <strong><?= e($statistiques_candidats['actifs'] ?? 0) ?></strong>
        <span>Candidats actifs</span>
    </article>
    <article class="carte-statistique">
        <strong><?= e($statistiques_elections['a_valider'] ?? 0) ?></strong>
        <span>Elections a valider</span>
    </article>
    <article class="carte-statistique">
        <strong><?= e($statistiques_elections['a_publier'] ?? 0) ?></strong>
        <span>Resultats a publier</span>
    </article>
</section>

<section class="grille-tableau-de-bord espace-bas">
    <article class="carte-module">
        <div>
            <h2>Validation du lancement</h2>
            <p>Autoriser le passage au vote apres controle de la periode et de la portee.</p>
        </div>
        <a href="/president-electoral/elections/validations">Ouvrir</a>
    </article>
    <article class="carte-module">
        <div>
            <h2>Candidats</h2>
            <p>Voir les candidats crees par le super administrateur avant publication.</p>
        </div>
        <a href="/president-electoral/candidats">Ouvrir</a>
    </article>
    <article class="carte-module">
        <div>
            <h2>Statistiques</h2>
            <p>Consulter les effectifs par faculte et promotion sans afficher les noms.</p>
        </div>
        <a href="/president-electoral/statistiques">Ouvrir</a>
    </article>
    <article class="carte-module">
        <div>
            <h2>Publication</h2>
            <p>Donner le dernier accord avant que les etudiants voient les resultats.</p>
        </div>
        <a href="/president-electoral/resultats/publication">Ouvrir</a>
    </article>
</section>

<section class="surface-module">
    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Elections</p>
                <h2>Suivi recent</h2>
            </div>
            <a class="lien-action" href="/president-electoral/elections/annulations">Annulations</a>
        </div>

        <div class="tableau-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Portee</th>
                        <th>Debut</th>
                        <th>Fin</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($elections_recentes)): ?>
                        <tr><td colspan="5">Aucune election creee pour le moment.</td></tr>
                    <?php endif; ?>

                    <?php foreach (($elections_recentes ?? []) as $election): ?>
                        <tr>
                            <td><?= e($election['nom']) ?></td>
                            <td><?= e($election['portee_type'] . (!empty($election['faculte_code']) ? ' - ' . $election['faculte_code'] : '')) ?></td>
                            <td><?= e($election['date_debut']) ?></td>
                            <td><?= e($election['date_fin']) ?></td>
                            <td><span class="badge-statut"><?= e($statutElectionPresident($election)) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Candidats</p>
                <h2>Derniers candidats</h2>
            </div>
            <a class="lien-action" href="/president-electoral/candidats">Voir tous</a>
        </div>

        <?php if (empty($candidats_recents)): ?>
            <div class="etat-vide-classe">Aucun candidat a verifier pour le moment.</div>
        <?php else: ?>
            <div class="liste-candidats compacte">
                <?php foreach (($candidats_recents ?? []) as $candidat): ?>
                    <?php $nomComplet = trim($candidat['nom'] . ' ' . $candidat['post_nom'] . ' ' . $candidat['prenom']); ?>
                    <article class="fiche-candidat">
                        <div class="photo-candidat photo-candidat-petit">
                            <?php if (!empty($candidat['photo_chemin'])): ?>
                                <img src="<?= e($candidat['photo_chemin']) ?>" alt="Photo de <?= e($nomComplet) ?>">
                            <?php else: ?>
                                <span><?= e(strtoupper(substr((string) $candidat['prenom'], 0, 1) . substr((string) $candidat['nom'], 0, 1))) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="detail-candidat">
                            <h3><?= e($nomComplet) ?></h3>
                            <div class="metadonnees-candidat">
                                <span><?= e(($candidat['faculte_code'] ?? 'Universite') . ' / ' . ($candidat['promotion_code'] ?? 'Toutes')) ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

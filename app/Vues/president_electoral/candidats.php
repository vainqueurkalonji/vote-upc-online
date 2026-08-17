<?php $sexeAffiche = static fn(?string $sexe): string => $sexe === 'F' ? 'Femme' : ($sexe === 'M' ? 'Homme' : 'Non precise'); ?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">President electoral</p>
        <h1>Candidats</h1>
        <p>Consulter les candidats crees par le super administrateur avant validation electorale.</p>
    </div>
    <span class="badge-page">Supervision</span>
</section>

<section class="grille-statistiques" aria-label="Resume candidats">
    <article class="carte-statistique">
        <strong><?= e($statistiques_candidats['total'] ?? 0) ?></strong>
        <span>Total candidats</span>
    </article>
    <article class="carte-statistique">
        <strong><?= e($statistiques_candidats['actifs'] ?? 0) ?></strong>
        <span>Actifs</span>
    </article>
    <article class="carte-statistique">
        <strong><?= e($statistiques_candidats['inactifs'] ?? 0) ?></strong>
        <span>Inactifs</span>
    </article>
    <article class="carte-statistique">
        <strong>Aval</strong>
        <span>Avant publication</span>
    </article>
</section>

<section class="bloc-module">
    <div class="titre-bloc">
        <div>
            <p class="surtitre">Liste officielle</p>
            <h2>Candidats disponibles</h2>
        </div>
        <span class="etat-module"><?= e(count($candidats ?? [])) ?> candidat(s)</span>
    </div>

    <?php if (empty($candidats)): ?>
        <div class="etat-vide-classe">Aucun candidat cree par le super administrateur pour le moment.</div>
    <?php else: ?>
        <div class="liste-candidats">
            <?php foreach (($candidats ?? []) as $candidat): ?>
                <?php $nomComplet = trim($candidat['nom'] . ' ' . $candidat['post_nom'] . ' ' . $candidat['prenom']); ?>
                <article class="fiche-candidat fiche-candidat-detaillee">
                    <div class="photo-candidat">
                        <?php if (!empty($candidat['photo_chemin'])): ?>
                            <img src="<?= e($candidat['photo_chemin']) ?>" alt="Photo de <?= e($nomComplet) ?>">
                        <?php else: ?>
                            <span><?= e(strtoupper(substr((string) $candidat['prenom'], 0, 1) . substr((string) $candidat['nom'], 0, 1))) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="detail-candidat">
                        <div class="ligne-candidat">
                            <h3><?= e($nomComplet) ?></h3>
                            <span class="badge-statut"><?= e($candidat['statut']) ?></span>
                        </div>
                        <p class="slogan-candidat"><?= e($candidat['slogan'] ?? '') ?></p>
                        <p><?= e($candidat['vision'] ?? '') ?></p>
                        <div class="metadonnees-candidat">
                            <span><?= e($sexeAffiche($candidat['sexe'] ?? null)) ?></span>
                            <span><?= e(($candidat['faculte_code'] ?? 'Universite') . ' / ' . ($candidat['promotion_code'] ?? 'Toutes')) ?></span>
                            <?php if (!empty($candidat['departement_nom'])): ?><span><?= e($candidat['departement_nom']) ?></span><?php endif; ?>
                            <?php if (!empty($candidat['election_nom'])): ?><span><?= e($candidat['election_nom']) ?></span><?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

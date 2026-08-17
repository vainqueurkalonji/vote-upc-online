<?php
$sessions = $sessions_vote ?? [];
$sexeAffiche = static fn(?string $sexe): string => $sexe === 'F' ? 'Femme' : ($sexe === 'M' ? 'Homme' : 'Non precise');
?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Etudiant</p>
        <h1>Candidats</h1>
        <p><?= e(($etudiant['faculte_code'] ?? '') . ' ' . ($etudiant['promotion_code'] ?? '') . ' - candidats classes par election') ?></p>
    </div>
    <span class="badge-page"><?= e($resume_sessions['candidats'] ?? 0) ?> profil(s)</span>
</section>

<?php if (empty($sessions)): ?>
    <div class="etat-vide-classe">
        Aucun candidat visible pour votre portee pour le moment. Les candidats apparaitront ici par election apres validation du president electoral.
    </div>
<?php else: ?>
    <div class="liste-sessions-etudiant">
        <?php foreach ($sessions as $session): ?>
            <?php
            $electionSession = $session['election'];
            $debutTs = strtotime((string) ($electionSession['date_debut'] ?? '')) * 1000;
            $finTs = strtotime((string) ($electionSession['date_fin'] ?? '')) * 1000;
            ?>
            <section class="bloc-election-etudiant <?= $session['peut_voter'] ? 'session-active' : '' ?>" id="election-<?= e($electionSession['id']) ?>" data-session-election>
                <div class="session-entete">
                    <div>
                        <p class="surtitre"><?= e($session['portee_libelle']) ?></p>
                        <h2><?= e($electionSession['nom']) ?></h2>
                        <p><?= e($session['nombre_candidats']) ?> candidat(s) pour cette election.</p>
                    </div>
                    <div class="compteur-session <?= $session['peut_voter'] ? 'compteur-ouvert' : '' ?>">
                        <span data-chrono-label>Calcul du chrono</span>
                        <strong
                            data-chrono-session
                            data-chrono-debut="<?= e($electionSession['date_debut']) ?>"
                            data-chrono-fin="<?= e($electionSession['date_fin']) ?>"
                            data-chrono-debut-ts="<?= e($debutTs) ?>"
                            data-chrono-fin-ts="<?= e($finTs) ?>"
                        >00:00:00</strong>
                        <span class="badge-statut"><?= e($electionSession['statut']) ?></span>
                    </div>
                </div>
                <div class="message-fin-session" data-message-fin-session hidden>Le temps de vote est termine. Les biographies restent consultables.</div>

                <?php if (empty($session['candidats'])): ?>
                    <div class="etat-vide-classe">Aucun candidat rattache a cette election pour le moment.</div>
                <?php else: ?>
                    <div class="grille-candidats-etudiant">
                        <?php foreach (($session['candidats'] ?? []) as $candidat): ?>
                            <?php $nomComplet = trim($candidat['nom'] . ' ' . $candidat['post_nom'] . ' ' . $candidat['prenom']); ?>
                            <article class="carte-candidat-etudiant carte-candidat-large">
                                <div class="portrait-candidat">
                                    <?php if (!empty($candidat['photo_chemin'])): ?>
                                        <img src="<?= e($candidat['photo_chemin']) ?>" alt="Photo de <?= e($nomComplet) ?>">
                                    <?php else: ?>
                                        <span><?= e(strtoupper(substr((string) $candidat['prenom'], 0, 1) . substr((string) $candidat['nom'], 0, 1))) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="contenu-candidat-etudiant">
                                    <div class="ligne-candidat">
                                        <h2><?= e($nomComplet) ?></h2>
                                        <span class="badge-statut"><?= e(($candidat['faculte_code'] ?? '') . ' ' . ($candidat['promotion_code'] ?? '')) ?></span>
                                    </div>
                                    <div class="metadonnees-candidat">
                                        <span><?= e($sexeAffiche($candidat['sexe'] ?? null)) ?></span>
                                    </div>
                                    <p class="slogan-grand"><?= e($candidat['slogan'] ?? '') ?></p>
                                    <details open>
                                        <summary>Biographie</summary>
                                        <p><?= e($candidat['biographie'] ?? '') ?></p>
                                    </details>
                                    <details>
                                        <summary>Vision</summary>
                                        <p><?= e($candidat['vision'] ?? '') ?></p>
                                    </details>
                                    <?php if (!empty($candidat['image_vision_chemin'])): ?>
                                        <img class="image-vision-candidat" src="<?= e($candidat['image_vision_chemin']) ?>" alt="Image de vision de <?= e($nomComplet) ?>">
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

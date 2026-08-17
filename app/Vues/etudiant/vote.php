<?php
$sessions = $sessions_vote ?? [];
$sexeAffiche = static fn(?string $sexe): string => $sexe === 'F' ? 'Femme' : ($sexe === 'M' ? 'Homme' : 'Non precise');
?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Etudiant</p>
        <h1>Vote</h1>
        <p>Chaque election possede son propre bulletin. Vous pouvez voter une fois par election ouverte.</p>
    </div>
    <span class="badge-page"><?= e($resume_sessions['votes_a_faire'] ?? 0) ?> vote(s) a faire</span>
</section>

<?php if (!empty($message_succes)): ?><div class="alerte alerte-succes"><?= e($message_succes) ?></div><?php endif; ?>
<?php if (!empty($message_erreur)): ?><div class="alerte alerte-erreur"><?= e($message_erreur) ?></div><?php endif; ?>

<?php if (empty($sessions)): ?>
    <div class="etat-vide-classe">Aucune election validee ne correspond a votre profil pour le moment.</div>
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
                        <p><?= e($electionSession['description'] ?? 'Bulletin de vote') ?></p>
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
                <div class="message-fin-session" data-message-fin-session hidden>Chronometre termine : le vote est ferme pour cette election. Les fiches candidats restent visibles.</div>

                <?php if ($session['a_deja_vote']): ?>
                    <div class="alerte alerte-succes">Votre vote pour cette election est deja enregistre. Les autres elections restent independantes.</div>
                <?php elseif (!$session['peut_voter']): ?>
                    <div class="alerte alerte-attention">Cette election n'est pas ouverte au vote actuellement.</div>
                <?php endif; ?>

                <div class="grille-candidats-etudiant">
                    <?php if (empty($session['candidats'])): ?>
                        <div class="etat-vide-classe">Aucun candidat disponible pour cette election.</div>
                    <?php endif; ?>

                    <?php foreach (($session['candidats'] ?? []) as $candidat): ?>
                        <?php $nomComplet = trim($candidat['nom'] . ' ' . $candidat['post_nom'] . ' ' . $candidat['prenom']); ?>
                        <article class="carte-candidat-etudiant carte-vote">
                            <div class="portrait-candidat">
                                <?php if (!empty($candidat['photo_chemin'])): ?>
                                    <img src="<?= e($candidat['photo_chemin']) ?>" alt="Photo de <?= e($nomComplet) ?>">
                                <?php else: ?>
                                    <span><?= e(strtoupper(substr((string) $candidat['prenom'], 0, 1) . substr((string) $candidat['nom'], 0, 1))) ?></span>
                                <?php endif; ?>
                            </div>
                            <h2><?= e($nomComplet) ?></h2>
                            <div class="metadonnees-candidat">
                                <span><?= e($sexeAffiche($candidat['sexe'] ?? null)) ?></span>
                                <span><?= e(($candidat['faculte_code'] ?? '') . ' ' . ($candidat['promotion_code'] ?? '')) ?></span>
                            </div>
                            <p><?= e($candidat['slogan'] ?? '') ?></p>
                            <details>
                                <summary>Biographie et vision</summary>
                                <p><?= e($candidat['biographie'] ?? 'Biographie en attente.') ?></p>
                                <p><strong>Vision :</strong> <?= e($candidat['vision'] ?? 'Vision en attente.') ?></p>
                            </details>
                            <?php if ($session['peut_voter'] && !$session['a_deja_vote']): ?>
                                <form
                                    method="post"
                                    action="/etudiant/vote"
                                    data-formulaire-vote
                                    data-candidat-nom="<?= e($nomComplet) ?>"
                                    data-candidat-photo="<?= e($candidat['photo_chemin'] ?? '') ?>"
                                    data-candidat-initiales="<?= e(strtoupper(substr((string) $candidat['prenom'], 0, 1) . substr((string) $candidat['nom'], 0, 1))) ?>"
                                    data-election-nom="<?= e($electionSession['nom']) ?>"
                                >
                                    <input type="hidden" name="election_id" value="<?= e($electionSession['id']) ?>">
                                    <input type="hidden" name="candidat_id" value="<?= e($candidat['id']) ?>">
                                    <button type="submit" class="bouton-principal">Voter pour ce candidat</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

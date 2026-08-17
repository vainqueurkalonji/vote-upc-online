<?php $sessionsResultats = $sessions_resultats ?? []; ?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Etudiant</p>
        <h1>Resultats</h1>
        <p>Les resultats sont separes par election et visibles seulement apres publication officielle.</p>
    </div>
    <span class="badge-page"><?= e(count($sessionsResultats)) ?> publication(s)</span>
</section>

<?php if (empty($sessionsResultats)): ?>
    <div class="alerte alerte-attention">Aucun resultat n'est encore publie officiellement pour vos elections.</div>
<?php else: ?>
    <div class="liste-sessions-etudiant">
        <?php foreach ($sessionsResultats as $session): ?>
            <?php
                $electionSession = $session['election'];
                $resultats = $session['resultats'] ?? [];
                $participation = $session['participation'] ?? ['votants' => 0, 'abstentions' => 0, 'annules' => 0];
                $vainqueur = $resultats[0] ?? null;
                $maxVoix = (int) ($vainqueur['voix'] ?? 0);
            ?>
            <section class="bloc-election-etudiant session-resultats resultat-suspense" id="election-<?= e($electionSession['id']) ?>" data-resultats-revelation>
                <div class="session-entete">
                    <div>
                        <p class="surtitre"><?= e($session['portee_libelle']) ?></p>
                        <h2><?= e($electionSession['nom']) ?></h2>
                        <p>Publication officielle validee par le president electoral.</p>
                    </div>
                    <div class="session-badges">
                        <span class="badge-statut"><?= e($electionSession['statut']) ?></span>
                        <span class="badge-statut">Aval presidentiel</span>
                    </div>
                </div>

                <?php if (empty($resultats)): ?>
                    <div class="etat-vide-classe">Aucun bulletin valide trouve pour cette election.</div>
                <?php else: ?>
                    <div class="rideau-resultats" data-rideau-resultats>
                        <p class="surtitre">Publication officielle</p>
                        <h3 data-texte-suspense>Verification des bulletins</h3>
                        <div class="barre-revelation"><i></i></div>
                        <p>Le vainqueur sera revele dans quelques instants.</p>
                    </div>

                    <div class="zone-resultats-officiels" data-zone-resultats>
                        <section class="revelation-vainqueur" data-vainqueur-revele>
                            <?php $nomVainqueur = trim($vainqueur['nom'] . ' ' . $vainqueur['post_nom'] . ' ' . $vainqueur['prenom']); ?>
                            <div class="portrait-vainqueur" data-photo-vainqueur>
                                <?php if (!empty($vainqueur['photo_chemin'])): ?>
                                    <img src="<?= e($vainqueur['photo_chemin']) ?>" alt="Photo de <?= e($nomVainqueur) ?>">
                                <?php else: ?>
                                    <span><?= e(strtoupper(substr((string) $vainqueur['prenom'], 0, 1) . substr((string) $vainqueur['nom'], 0, 1))) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="texte-vainqueur">
                                <p class="surtitre">Vainqueur publie</p>
                                <h1><?= e($nomVainqueur) ?></h1>
                                <p><?= e($maxVoix) ?> voix</p>
                            </div>
                        </section>

                        <section class="grille-statistiques">
                            <article class="carte-statistique"><strong><?= e($participation['votants'] ?? 0) ?></strong><span>Votants</span></article>
                            <article class="carte-statistique"><strong><?= e($participation['abstentions'] ?? 0) ?></strong><span>Abstentions</span></article>
                            <article class="carte-statistique"><strong><?= e($participation['annules'] ?? 0) ?></strong><span>Annules</span></article>
                            <article class="carte-statistique"><strong><?= e(count($resultats)) ?></strong><span>Candidats</span></article>
                        </section>

                        <div class="classement-resultats">
                            <?php foreach ($resultats as $index => $ligne): ?>
                                <?php
                                    $nomComplet = trim($ligne['nom'] . ' ' . $ligne['post_nom'] . ' ' . $ligne['prenom']);
                                    $voix = (int) $ligne['voix'];
                                    $pourcentage = $maxVoix > 0 ? max(4, (int) round(($voix / $maxVoix) * 100)) : 4;
                                ?>
                                <article class="ligne-resultat">
                                    <strong><?= e($index + 1) ?>. <?= e($nomComplet) ?></strong>
                                    <span><?= e($voix) ?> voix</span>
                                    <div><i style="width: <?= e($pourcentage) ?>%"></i></div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

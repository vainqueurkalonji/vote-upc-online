<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Appariteur</p>
        <h1>Bienvenue <?= e($utilisateur['nom_utilisateur'] ?? '') ?></h1>
        <p>
            <?php if (!empty($affectation)): ?>
                Vous etes affecte a <?= e($affectation['faculte_code'] . ' - ' . $affectation['faculte_nom']) ?>.
            <?php else: ?>
                Aucune faculte active n'est encore rattachee a ce compte.
            <?php endif; ?>
        </p>
    </div>
    <span class="badge-page"><?= !empty($affectation) ? e($affectation['faculte_code']) : 'Non affecte' ?></span>
</section>

<?php if (empty($affectation)): ?>
    <div class="alerte alerte-erreur">
        Ce compte appariteur n'a pas encore de faculte active. Le super administrateur doit rattacher l'appariteur a une faculte avant toute activation.
    </div>
<?php endif; ?>

<section class="grille-statistiques" aria-label="Resume appariteur">
    <article class="carte-statistique">
        <strong><?= e($statistiques['total'] ?? 0) ?></strong>
        <span>Etudiants de ma faculte</span>
    </article>
    <article class="carte-statistique">
        <strong><?= e($statistiques['non_actives'] ?? 0) ?></strong>
        <span>A activer</span>
    </article>
    <article class="carte-statistique">
        <strong><?= e($statistiques['actives'] ?? 0) ?></strong>
        <span>Actives</span>
    </article>
    <article class="carte-statistique">
        <strong><?= e($statistiques['bloques'] ?? 0) ?></strong>
        <span>Bloques</span>
    </article>
</section>

<section class="grille-import">
    <article class="bloc-module bloc-import-principal">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Recherche rapide</p>
                <h2>Trouver un etudiant</h2>
            </div>
            <span class="etat-module">Faculte filtree</span>
        </div>

        <form method="get" action="/appariteur/etudiants/rechercher" class="formulaire">
            <label>
                <span>Nom, promotion, matricule ou email</span>
                <input type="search" name="q" placeholder="Exemple : SOPHIA, L1, UPC/FASI/L1/001">
            </label>
            <button type="submit" class="bouton-principal">Rechercher dans ma faculte</button>
        </form>
    </article>

    <article class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Promotions</p>
                <h2>Repartition visible</h2>
            </div>
        </div>

        <div class="tableau-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Promotion</th>
                        <th>Total</th>
                        <th>A activer</th>
                        <th>Actives</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($repartition_promotions)): ?>
                        <tr>
                            <td colspan="4">Aucune promotion trouvee.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach (($repartition_promotions ?? []) as $ligne): ?>
                        <tr>
                            <td><?= e($ligne['promotion_code']) ?></td>
                            <td><?= e($ligne['total']) ?></td>
                            <td><?= e($ligne['non_actives']) ?></td>
                            <td><?= e($ligne['actives']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="surface-module">
    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Apercu</p>
                <h2>Etudiants de ma faculte</h2>
            </div>
            <a class="lien-action" href="/appariteur/etudiants">Voir toute la liste</a>
        </div>

        <div class="tableau-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Matricule</th>
                        <th>Nom complet</th>
                        <th>Promotion</th>
                        <th>Email</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($etudiants)): ?>
                        <tr>
                            <td colspan="6">Aucun etudiant visible pour cette affectation.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach (($etudiants ?? []) as $etudiant): ?>
                        <tr>
                            <td><?= e($etudiant['matricule']) ?></td>
                            <td><?= e(trim($etudiant['nom'] . ' ' . $etudiant['post_nom'] . ' ' . $etudiant['prenom'])) ?></td>
                            <td><?= e($etudiant['promotion_code']) ?></td>
                            <td><?= e($etudiant['email'] ?? '') ?></td>
                            <td><span class="badge-statut"><?= e($etudiant['statut']) ?></span></td>
                            <td><a class="lien-action" href="/appariteur/etudiants">Gerer</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

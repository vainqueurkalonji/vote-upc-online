<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Appariteur</p>
        <h1>Etudiants de ma faculte</h1>
        <p>
            <?php if (!empty($affectation)): ?>
                Liste limitee a <?= e($affectation['faculte_code'] . ' - ' . $affectation['faculte_nom']) ?>.
            <?php else: ?>
                Aucune faculte active n'est encore rattachee a ce compte.
            <?php endif; ?>
        </p>
    </div>
    <span class="badge-page"><?= !empty($affectation) ? e($affectation['faculte_code']) : 'Non affecte' ?></span>
</section>

<?php if (empty($affectation)): ?>
    <div class="alerte alerte-erreur">
        Ce compte appariteur n'a pas encore de faculte active. La liste restera vide tant que le rattachement n'est pas configure.
    </div>
<?php endif; ?>

<?php if (!empty($message_succes)): ?>
    <div class="alerte alerte-succes"><?= e($message_succes) ?></div>
<?php endif; ?>

<?php if (!empty($message_erreur)): ?>
    <div class="alerte alerte-erreur"><?= e($message_erreur) ?></div>
<?php endif; ?>

<section class="grille-statistiques" aria-label="Resume appariteur">
    <article class="carte-statistique">
        <strong><?= e($statistiques['total'] ?? 0) ?></strong>
        <span>Etudiants visibles</span>
    </article>
    <article class="carte-statistique">
        <strong><?= e($statistiques['non_actives'] ?? 0) ?></strong>
        <span>Non actives</span>
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

<section class="surface-module">
    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Recherche</p>
                <h2>Nom, promotion, matricule ou email</h2>
            </div>
        </div>

        <form method="get" action="/appariteur/etudiants/rechercher" class="formulaire">
            <div class="grille-formulaire">
                <label class="champ-large">
                    <span>Rechercher</span>
                    <input type="search" name="q" value="<?= e($recherche ?? '') ?>" placeholder="Exemple : SOPHIA, UPC/FASI/L1/001, L1">
                </label>
            </div>
            <button type="submit" class="bouton-principal">Rechercher</button>
        </form>
    </div>

    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Liste</p>
                <h2>Etudiants disponibles pour verification</h2>
            </div>
            <span class="etat-module"><?= e(count($etudiants ?? [])) ?> resultat(s)</span>
        </div>

        <div class="tableau-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Matricule</th>
                        <th>Nom complet</th>
                        <th>Faculte</th>
                        <th>Promotion</th>
                        <th>Email</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($etudiants)): ?>
                        <tr>
                            <td colspan="7">Aucun etudiant trouve dans votre faculte.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach (($etudiants ?? []) as $etudiant): ?>
                        <tr>
                            <td><?= e($etudiant['matricule']) ?></td>
                            <td><?= e(trim($etudiant['nom'] . ' ' . $etudiant['post_nom'] . ' ' . $etudiant['prenom'])) ?></td>
                            <td><?= e($etudiant['faculte_code']) ?></td>
                            <td><?= e($etudiant['promotion_code']) ?></td>
                            <td><?= e($etudiant['email'] ?? '') ?></td>
                            <td><span class="badge-statut"><?= e($etudiant['statut']) ?></span></td>
                            <td>
                                <?php if (in_array($etudiant['statut'], ['non_active', 'bloque'], true)): ?>
                                    <form method="post" action="/appariteur/etudiants/activer" class="formulaire-ligne">
                                        <input type="hidden" name="etudiant_id" value="<?= e($etudiant['id']) ?>">
                                        <button type="submit" class="bouton-tableau">
                                            <?= $etudiant['statut'] === 'bloque' ? 'Reactiver' : 'Activer' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="texte-discret">Deja actif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

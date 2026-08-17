<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Super administrateur</p>
        <h1>Gestion du president electoral</h1>
        <p>Creer le compte charge de valider le lancement, superviser les elections et autoriser la publication des resultats.</p>
    </div>
    <span class="badge-page">Aval obligatoire</span>
</section>

<?php if (!empty($message_succes)): ?>
    <div class="alerte alerte-succes"><?= e($message_succes) ?></div>
<?php endif; ?>

<?php if (!empty($message_erreur)): ?>
    <div class="alerte alerte-erreur"><?= e($message_erreur) ?></div>
<?php endif; ?>

<section class="grille-import">
    <article class="bloc-module bloc-import-principal">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Creation</p>
                <h2>Nouveau president electoral</h2>
            </div>
            <span class="etat-module">Compte securise</span>
        </div>

        <form method="post" action="/super-administrateur/presidents-electoraux" class="formulaire">
            <div class="grille-formulaire">
                <label>
                    <span>Nom</span>
                    <input type="text" name="nom" data-majuscules autocapitalize="characters" value="<?= e(ancien('nom', $anciennes_donnees ?? [])) ?>" required>
                    <?php if (!empty($erreurs['nom'])): ?><small><?= e($erreurs['nom']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Post-nom</span>
                    <input type="text" name="post_nom" data-majuscules autocapitalize="characters" value="<?= e(ancien('post_nom', $anciennes_donnees ?? [])) ?>" required>
                    <?php if (!empty($erreurs['post_nom'])): ?><small><?= e($erreurs['post_nom']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Prenom</span>
                    <input type="text" name="prenom" data-majuscules autocapitalize="characters" value="<?= e(ancien('prenom', $anciennes_donnees ?? [])) ?>" required>
                    <?php if (!empty($erreurs['prenom'])): ?><small><?= e($erreurs['prenom']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Nom utilisateur</span>
                    <input type="text" name="nom_utilisateur" value="<?= e(ancien('nom_utilisateur', $anciennes_donnees ?? [])) ?>" required>
                    <?php if (!empty($erreurs['nom_utilisateur'])): ?><small><?= e($erreurs['nom_utilisateur']) ?></small><?php endif; ?>
                </label>
                <label class="champ-large">
                    <span>Email</span>
                    <input type="email" name="email" value="<?= e(ancien('email', $anciennes_donnees ?? [])) ?>" required>
                    <?php if (!empty($erreurs['email'])): ?><small><?= e($erreurs['email']) ?></small><?php endif; ?>
                </label>
            </div>

            <div class="regles-mot-de-passe">
                Le mot de passe temporaire est genere automatiquement et envoye au president electoral par email.
            </div>

            <button type="submit" class="bouton-principal">Creer le president electoral</button>
        </form>
    </article>

    <article class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Droits</p>
                <h2>Role du president</h2>
            </div>
        </div>

        <ul class="liste-regles">
            <li>Valider le lancement avant que les etudiants puissent voter.</li>
            <li>Superviser les statistiques globales et anonymes.</li>
            <li>Annuler une election dans une faculte si necessaire.</li>
            <li>Autoriser la publication officielle des resultats.</li>
        </ul>
    </article>
</section>

<section class="surface-module">
    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Comptes existants</p>
                <h2>Presidents electoraux crees</h2>
            </div>
            <span class="etat-module"><?= e(count($presidents ?? [])) ?> compte(s)</span>
        </div>

        <div class="tableau-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nom complet</th>
                        <th>Nom utilisateur</th>
                        <th>Email</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($presidents)): ?>
                        <tr>
                            <td colspan="5">Aucun president electoral cree pour le moment.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach (($presidents ?? []) as $president): ?>
                        <tr>
                            <td><?= e(trim($president['nom'] . ' ' . $president['post_nom'] . ' ' . $president['prenom'])) ?></td>
                            <td><?= e($president['nom_utilisateur']) ?></td>
                            <td><?= e($president['email']) ?></td>
                            <td><?= e($president['statut']) ?></td>
                            <td>
                                <a class="lien-action" href="/super-administrateur/presidents-electoraux/modifier?id=<?= e($president['id']) ?>">Modifier</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

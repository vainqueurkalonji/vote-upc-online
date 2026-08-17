<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Super administrateur</p>
        <h1>Gestion des appariteurs</h1>
        <p>Creer les comptes des appariteurs et rattacher chaque compte a une seule faculte.</p>
    </div>
    <span class="badge-page">1 appariteur = 1 faculte</span>
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
                <h2>Nouvel appariteur</h2>
            </div>
            <span class="etat-module">Email automatique</span>
        </div>

        <form method="post" action="/super-administrateur/appariteurs" class="formulaire">
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
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="<?= e(ancien('email', $anciennes_donnees ?? [])) ?>" required>
                    <?php if (!empty($erreurs['email'])): ?><small><?= e($erreurs['email']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Faculte</span>
                    <select name="faculte_id" required>
                        <option value="">Choisir une faculte</option>
                        <?php foreach (($facultes ?? []) as $faculte): ?>
                            <option value="<?= e($faculte['id']) ?>" <?= ancien('faculte_id', $anciennes_donnees ?? []) === (string) $faculte['id'] ? 'selected' : '' ?>>
                                <?= e($faculte['code'] . ' - ' . $faculte['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($erreurs['faculte_id'])): ?><small><?= e($erreurs['faculte_id']) ?></small><?php endif; ?>
                </label>
            </div>

            <div class="regles-mot-de-passe">
                Le mot de passe temporaire est genere par le systeme puis envoye a l'adresse email de l'appariteur.
            </div>

            <button type="submit" class="bouton-principal">Creer l'appariteur</button>
        </form>
    </article>

    <article class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Regles</p>
                <h2>Restrictions appliquees</h2>
            </div>
        </div>

        <ul class="liste-regles">
            <li>Un appariteur est rattache a une seule faculte.</li>
            <li>Il verra uniquement les etudiants de cette faculte.</li>
            <li>Il pourra rechercher par nom, promotion, matricule ou email.</li>
            <li>Chaque activation sera visible dans le journal du super administrateur.</li>
        </ul>
    </article>
</section>

<section class="surface-module">
    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Comptes existants</p>
                <h2>Appariteurs crees</h2>
            </div>
            <span class="etat-module"><?= e(count($appariteurs ?? [])) ?> compte(s)</span>
        </div>

        <div class="tableau-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nom complet</th>
                        <th>Nom utilisateur</th>
                        <th>Email</th>
                        <th>Faculte</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appariteurs)): ?>
                        <tr>
                            <td colspan="6">Aucun appariteur cree pour le moment.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach (($appariteurs ?? []) as $appariteur): ?>
                        <tr>
                            <td><?= e(trim($appariteur['nom'] . ' ' . $appariteur['post_nom'] . ' ' . $appariteur['prenom'])) ?></td>
                            <td><?= e($appariteur['nom_utilisateur']) ?></td>
                            <td><?= e($appariteur['email']) ?></td>
                            <td><?= e($appariteur['faculte_code']) ?></td>
                            <td><?= e($appariteur['statut']) ?></td>
                            <td>
                                <a class="lien-action" href="/super-administrateur/appariteurs/modifier?id=<?= e($appariteur['id']) ?>">Modifier</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

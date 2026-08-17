<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Super administrateur</p>
        <h1>Gestion des etudiants</h1>
        <p>Consulter les etudiants importes, filtrer par faculte, statut ou recherche rapide.</p>
    </div>
    <span class="badge-page">Controle global</span>
</section>

<?php if (!empty($message_succes)): ?>
    <div class="alerte alerte-succes"><?= e($message_succes) ?></div>
<?php endif; ?>

<?php if (!empty($message_erreur)): ?>
    <div class="alerte alerte-erreur"><?= e($message_erreur) ?></div>
<?php endif; ?>

<section class="grille-statistiques" aria-label="Resume etudiants">
    <article class="carte-statistique">
        <strong><?= e($statistiques['total'] ?? 0) ?></strong>
        <span>Etudiants importes</span>
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

<section class="bloc-module espace-bas">
    <div class="titre-bloc">
        <div>
            <p class="surtitre">Classes academiques</p>
            <h2>Facultes et promotions</h2>
        </div>
        <span class="etat-module">Vue progressive</span>
    </div>

    <div class="grille-classes">
        <?php foreach (($repartition_academique ?? []) as $faculte): ?>
            <details class="classe-faculte" <?= ($faculte_selectionnee ?? '') === $faculte['code'] ? 'open' : '' ?>>
                <summary>
                    <span>
                        <strong><?= e($faculte['code']) ?></strong>
                        <em><?= e($faculte['nom']) ?></em>
                    </span>
                    <span class="resume-classe">
                        <?= e($faculte['total']) ?> etudiant(s)
                    </span>
                </summary>

                <div class="resume-faculte">
                    <span>Non actives : <?= e($faculte['non_actives']) ?></span>
                    <span>Actives : <?= e($faculte['actives']) ?></span>
                    <span>Bloques : <?= e($faculte['bloques']) ?></span>
                </div>

                <div class="promotions-classe">
                    <?php foreach (($faculte['promotions'] ?? []) as $promotion): ?>
                        <a
                            class="promotion-ligne <?= ($faculte_selectionnee ?? '') === $faculte['code'] && ($promotion_selectionnee ?? '') === $promotion['code'] ? 'actif' : '' ?>"
                            href="/super-administrateur/etudiants?faculte=<?= e($faculte['code']) ?>&promotion=<?= e($promotion['code']) ?>"
                        >
                            <span>
                                <strong><?= e($promotion['code']) ?></strong>
                                <em><?= e($promotion['nom']) ?></em>
                            </span>
                            <span><?= e($promotion['total']) ?> inscrit(s)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</section>

<section class="surface-module">
    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Filtres</p>
                <h2>Rechercher dans la base</h2>
            </div>
            <a class="lien-action" href="/super-administrateur/etudiants/importer">Importer Excel</a>
        </div>

        <form method="get" action="/super-administrateur/etudiants" class="formulaire">
            <div class="grille-formulaire">
                <label>
                    <span>Recherche</span>
                    <input type="search" name="q" value="<?= e($recherche ?? '') ?>" placeholder="Nom, matricule, email ou promotion">
                </label>
                <label>
                    <span>Faculte</span>
                    <select name="faculte">
                        <option value="">Toutes les facultes</option>
                        <?php foreach (($facultes ?? []) as $faculte): ?>
                            <option value="<?= e($faculte['code']) ?>" <?= ($faculte_selectionnee ?? '') === $faculte['code'] ? 'selected' : '' ?>>
                                <?= e($faculte['code'] . ' - ' . $faculte['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Statut</span>
                    <select name="statut">
                        <option value="">Tous les statuts</option>
                        <option value="non_active" <?= ($statut_selectionne ?? '') === 'non_active' ? 'selected' : '' ?>>Non active</option>
                        <option value="actif" <?= ($statut_selectionne ?? '') === 'actif' ? 'selected' : '' ?>>Active</option>
                        <option value="bloque" <?= ($statut_selectionne ?? '') === 'bloque' ? 'selected' : '' ?>>Bloque</option>
                    </select>
                </label>
                <label>
                    <span>Promotion</span>
                    <input type="text" name="promotion" value="<?= e($promotion_selectionnee ?? '') ?>" placeholder="L1, L2, M1...">
                </label>
                <label>
                    <span>Action</span>
                    <button type="submit" class="bouton-principal">Afficher les etudiants</button>
                </label>
            </div>
        </form>
    </div>

    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Liste</p>
                <h2>Etudiants trouves</h2>
            </div>
            <span class="etat-module"><?= e(count($etudiants ?? [])) ?> resultat(s)</span>
        </div>

        <?php if (empty($filtres_actifs)): ?>
            <div class="etat-vide-classe">
                Choisis une faculte, une promotion ou lance une recherche pour afficher la liste detaillee.
            </div>
        <?php else: ?>
            <div class="tableau-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom complet</th>
                            <th>Faculte</th>
                            <th>Departement</th>
                            <th>Promotion</th>
                            <th>Email</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($etudiants)): ?>
                            <tr>
                                <td colspan="8">Aucun etudiant trouve avec ces filtres.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach (($etudiants ?? []) as $etudiant): ?>
                            <tr>
                                <td><?= e($etudiant['matricule']) ?></td>
                                <td><?= e(trim($etudiant['nom'] . ' ' . $etudiant['post_nom'] . ' ' . $etudiant['prenom'])) ?></td>
                                <td><?= e($etudiant['faculte_code']) ?></td>
                                <td><?= e($etudiant['departement_nom'] ?? '') ?></td>
                                <td><?= e($etudiant['promotion_code']) ?></td>
                                <td><?= e($etudiant['email'] ?? '') ?></td>
                                <td><span class="badge-statut"><?= e($etudiant['statut']) ?></span></td>
                                <td>
                                    <a class="lien-action" href="/super-administrateur/etudiants/modifier?id=<?= e($etudiant['id']) ?>">Modifier</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

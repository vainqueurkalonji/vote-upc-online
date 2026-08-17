<?php
$statutAffiche = static fn(string $statut): string => $statut === 'brouillon' ? 'En preparation' : $statut;
$candidatModification = $candidat_modification ?? null;
$estModification = is_array($candidatModification);
$sexeAffiche = static fn(?string $sexe): string => $sexe === 'F' ? 'Femme' : ($sexe === 'M' ? 'Homme' : 'Non precise');
?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Super administrateur</p>
        <h1>Gestion des candidats</h1>
        <p>Creez d'abord une election, puis rattachez chaque candidat a cette election. La portee decide qui vote ; la faculte ci-dessous indique l'origine du candidat.</p>
    </div>
    <span class="badge-page">Election obligatoire</span>
</section>

<?php if (!empty($message_succes)): ?>
    <div class="alerte alerte-succes"><?= e($message_succes) ?></div>
<?php endif; ?>

<?php if (!empty($message_erreur)): ?>
    <div class="alerte alerte-erreur"><?= e($message_erreur) ?></div>
<?php endif; ?>

<?php if (empty($elections)): ?>
    <div class="alerte alerte-attention">
        Aucune election en preparation n'est disponible pour recevoir des candidats. Des qu'une election est envoyee au president ou lancee, les candidatures sont verrouillees.
    </div>
<?php endif; ?>

<section class="grille-statistiques" aria-label="Resume candidats">
    <article class="carte-statistique">
        <strong><?= e($statistiques_candidats['total'] ?? 0) ?></strong>
        <span>Candidats crees</span>
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
        <strong>Photo</strong>
        <span>JPG, PNG ou WEBP</span>
    </article>
</section>

<section class="grille-import">
    <article class="bloc-module bloc-import-principal">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Formulaire</p>
                <h2><?= $estModification ? 'Modifier le candidat' : 'Nouveau candidat' ?></h2>
            </div>
            <span class="etat-module"><?= $estModification ? 'Correction avant lancement' : 'Faculte / promotion' ?></span>
        </div>

        <?php if ($estModification): ?>
            <div class="alerte alerte-attention">
                Modification autorisee uniquement parce que l'election est encore en preparation. Apres lancement ou validation, la fiche sera verrouillee.
            </div>
        <?php endif; ?>

        <form method="post" action="<?= $estModification ? '/super-administrateur/candidats/modifier' : '/super-administrateur/candidats' ?>" class="formulaire" enctype="multipart/form-data">
            <?php if ($estModification): ?>
                <input type="hidden" name="candidat_id" value="<?= e($candidatModification['id']) ?>">
            <?php endif; ?>
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
                    <span>Sexe du candidat</span>
                    <select name="sexe" required>
                        <option value="">Choisir</option>
                        <option value="M" <?= ancien('sexe', $anciennes_donnees ?? []) === 'M' ? 'selected' : '' ?>>Masculin</option>
                        <option value="F" <?= ancien('sexe', $anciennes_donnees ?? []) === 'F' ? 'selected' : '' ?>>Feminin</option>
                    </select>
                    <?php if (!empty($erreurs['sexe'])): ?><small><?= e($erreurs['sexe']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Election</span>
                    <select name="election_id" data-election-candidat required>
                        <option value="">Choisir une election deja creee</option>
                        <?php foreach (($elections ?? []) as $election): ?>
                            <?php
                            $detailsPortee = 'Toute l universite';
                            if (($election['portee_type'] ?? '') === 'faculte') {
                                $detailsPortee = (string) ($election['faculte_code'] ?? 'Faculte');
                            } elseif (($election['portee_type'] ?? '') === 'promotion') {
                                $detailsPortee = trim((string) ($election['faculte_code'] ?? '') . ' ' . (string) ($election['promotion_code'] ?? ''));
                            } elseif (($election['portee_type'] ?? '') === 'departement') {
                                $detailsPortee = trim((string) ($election['faculte_code'] ?? '') . ' - ' . (string) ($election['departement_nom'] ?? 'Departement'));
                            }
                            ?>
                            <option
                                value="<?= e($election['id']) ?>"
                                data-portee-type="<?= e($election['portee_type'] ?? '') ?>"
                                data-faculte-id="<?= e($election['faculte_id'] ?? '') ?>"
                                data-promotion-id="<?= e($election['promotion_id'] ?? '') ?>"
                                data-departement-id="<?= e($election['departement_id'] ?? '') ?>"
                                <?= ancien('election_id', $anciennes_donnees ?? []) === (string) $election['id'] ? 'selected' : '' ?>
                            >
                                <?= e($election['nom'] . ' - ' . $detailsPortee . ' - ' . $statutAffiche((string) $election['statut'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="aide-champ" data-aide-election-candidat>Choisissez l'election avant la faculte d'origine du candidat.</small>
                    <?php if (!empty($erreurs['election_id'])): ?><small><?= e($erreurs['election_id']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Faculte du candidat</span>
                    <select name="faculte_id" data-select-faculte required>
                        <option value="">Choisir une faculte</option>
                        <?php foreach (($catalogue_academique ?? []) as $faculte): ?>
                            <option value="<?= e($faculte['id']) ?>" <?= ancien('faculte_id', $anciennes_donnees ?? []) === (string) $faculte['id'] ? 'selected' : '' ?>>
                                <?= e($faculte['code'] . ' - ' . $faculte['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($erreurs['faculte_id'])): ?><small><?= e($erreurs['faculte_id']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Promotion du candidat</span>
                    <select name="promotion_id" data-faculte-cible required>
                        <option value="">Choisir une promotion</option>
                        <?php foreach (($catalogue_academique ?? []) as $faculte): ?>
                            <?php foreach (($faculte['promotions'] ?? []) as $promotion): ?>
                                <option
                                    value="<?= e($promotion['id']) ?>"
                                    data-faculte-id="<?= e($faculte['id']) ?>"
                                    <?= ancien('promotion_id', $anciennes_donnees ?? []) === (string) $promotion['id'] ? 'selected' : '' ?>
                                >
                                    <?= e($faculte['code'] . ' - ' . $promotion['code']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($erreurs['promotion_id'])): ?><small><?= e($erreurs['promotion_id']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Departement du candidat</span>
                    <select name="departement_id" data-faculte-cible>
                        <option value="">Aucun departement precis</option>
                        <?php foreach (($departements ?? []) as $departement): ?>
                            <option
                                value="<?= e($departement['id']) ?>"
                                data-faculte-id="<?= e($departement['faculte_id']) ?>"
                                <?= ancien('departement_id', $anciennes_donnees ?? []) === (string) $departement['id'] ? 'selected' : '' ?>
                            >
                                <?= e($departement['faculte_code'] . ' - ' . $departement['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Photo passeport</span>
                    <input type="file" name="photo_candidat" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <?php if ($estModification && !empty($candidatModification['photo_chemin'])): ?>
                        <small>Photo actuelle conservee si vous ne choisissez pas un nouveau fichier.</small>
                    <?php endif; ?>
                    <?php if (!empty($erreurs['photo_candidat'])): ?><small><?= e($erreurs['photo_candidat']) ?></small><?php endif; ?>
                </label>
                <label class="champ-large">
                    <span>Slogan</span>
                    <input type="text" name="slogan" maxlength="255" value="<?= e(ancien('slogan', $anciennes_donnees ?? [])) ?>" required>
                    <?php if (!empty($erreurs['slogan'])): ?><small><?= e($erreurs['slogan']) ?></small><?php endif; ?>
                </label>
                <label class="champ-large">
                    <span>Biographie</span>
                    <textarea name="biographie" rows="4" required><?= e(ancien('biographie', $anciennes_donnees ?? [])) ?></textarea>
                    <?php if (!empty($erreurs['biographie'])): ?><small><?= e($erreurs['biographie']) ?></small><?php endif; ?>
                </label>
                <label class="champ-large">
                    <span>Vision</span>
                    <textarea name="vision" rows="5" required><?= e(ancien('vision', $anciennes_donnees ?? [])) ?></textarea>
                    <?php if (!empty($erreurs['vision'])): ?><small><?= e($erreurs['vision']) ?></small><?php endif; ?>
                </label>
                <label class="champ-large">
                    <span>Image de vision optionnelle</span>
                    <input type="file" name="image_vision" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <?php if ($estModification && !empty($candidatModification['image_vision_chemin'])): ?>
                        <small>Image actuelle conservee si vous ne choisissez pas un nouveau fichier.</small>
                    <?php endif; ?>
                    <?php if (!empty($erreurs['image_vision'])): ?><small><?= e($erreurs['image_vision']) ?></small><?php endif; ?>
                </label>
            </div>

            <?php if ($estModification && !empty($candidatModification['photo_chemin'])): ?>
                <div class="apercu-photo-formulaire">
                    <span>Photo actuelle</span>
                    <img src="<?= e($candidatModification['photo_chemin']) ?>" alt="Photo actuelle du candidat">
                </div>
            <?php endif; ?>

            <div class="actions-formulaire">
                <button type="submit" class="bouton-principal" <?= empty($elections) ? 'disabled' : '' ?>>
                    <?= $estModification ? 'Enregistrer les modifications' : 'Creer le candidat' ?>
                </button>
                <?php if ($estModification): ?>
                    <a class="bouton-secondaire" href="/super-administrateur/candidats">Annuler la modification</a>
                <?php endif; ?>
            </div>
        </form>
    </article>

    <article class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Controle</p>
                <h2>Regles appliquees</h2>
            </div>
        </div>

        <ul class="liste-regles">
            <li>Nom, post-nom et prenom restent en majuscules et lettres uniquement.</li>
            <li>La promotion affichee depend de la faculte choisie.</li>
            <li>Un candidat doit toujours etre rattache a une election en preparation.</li>
            <li>Apres la demande de lancement, aucun nouveau candidat ne peut etre ajoute.</li>
            <li>Les informations d'un candidat restent modifiables uniquement tant que l'election est en preparation.</li>
            <li>Si l'election concerne une faculte, seuls les candidats de cette faculte sont acceptes.</li>
            <li>Si l'election concerne toute l'universite, un candidat peut venir de n'importe quelle faculte.</li>
            <li>Les etudiants verront les candidats apres lancement et validation presidentielle.</li>
        </ul>
    </article>
</section>

<section class="bloc-module">
    <div class="titre-bloc">
        <div>
            <p class="surtitre">Liste</p>
            <h2>Candidats enregistres</h2>
        </div>
        <span class="etat-module"><?= e(count($candidats ?? [])) ?> candidat(s)</span>
    </div>

    <?php if (empty($candidats)): ?>
        <div class="etat-vide-classe">Aucun candidat cree pour le moment.</div>
    <?php else: ?>
        <div class="liste-candidats">
            <?php foreach (($candidats ?? []) as $candidat): ?>
                <?php $nomComplet = trim($candidat['nom'] . ' ' . $candidat['post_nom'] . ' ' . $candidat['prenom']); ?>
                <article class="fiche-candidat">
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
                        <div class="metadonnees-candidat">
                            <span><?= e($sexeAffiche($candidat['sexe'] ?? null)) ?></span>
                            <span><?= e(($candidat['faculte_code'] ?? 'Universite') . ' / ' . ($candidat['promotion_code'] ?? 'Toutes')) ?></span>
                            <?php if (!empty($candidat['departement_nom'])): ?><span><?= e($candidat['departement_nom']) ?></span><?php endif; ?>
                            <?php if (!empty($candidat['election_nom'])): ?><span><?= e($candidat['election_nom']) ?></span><?php endif; ?>
                            <?php if (!empty($candidat['election_statut'])): ?><span><?= e($statutAffiche((string) $candidat['election_statut'])) ?></span><?php endif; ?>
                        </div>
                        <div class="actions-candidat">
                            <?php if (($candidat['election_statut'] ?? '') === 'brouillon'): ?>
                                <a class="lien-action" href="/super-administrateur/candidats?modifier_id=<?= e($candidat['id']) ?>">Modifier</a>
                            <?php else: ?>
                                <span class="badge-statut">Verrouille apres lancement</span>
                            <?php endif; ?>
                            <form method="post" action="/super-administrateur/candidats/photo" enctype="multipart/form-data" class="formulaire-ligne">
                                <input type="hidden" name="candidat_id" value="<?= e($candidat['id']) ?>">
                                <input type="file" name="photo_candidat" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                                <button type="submit" class="bouton-tableau">Mettre photo</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

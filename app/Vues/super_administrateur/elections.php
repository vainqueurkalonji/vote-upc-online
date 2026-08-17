<?php
$valeur = static fn(string $cle, string $defaut = ''): string => ancien($cle, $anciennes_donnees ?? [], $defaut);
$dateChamp = static fn(string $cle): string => str_replace(' ', 'T', substr($valeur($cle), 0, 16));
$statutAffiche = static fn(string $statut): string => $statut === 'brouillon' ? 'En preparation' : $statut;
?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Super administrateur</p>
        <h1>Gestion des elections</h1>
        <p>Creer une session en preparation, affilier ses candidats, puis demander l'aval du president electoral.</p>
    </div>
    <span class="badge-page">Aval presidentiel</span>
</section>

<?php if (!empty($message_succes)): ?><div class="alerte alerte-succes"><?= e($message_succes) ?></div><?php endif; ?>
<?php if (!empty($message_erreur)): ?><div class="alerte alerte-erreur"><?= e($message_erreur) ?></div><?php endif; ?>

<section class="grille-statistiques">
    <article class="carte-statistique"><strong><?= e($statistiques_elections['total'] ?? 0) ?></strong><span>Elections</span></article>
    <article class="carte-statistique"><strong><?= e($statistiques_elections['a_valider'] ?? 0) ?></strong><span>A valider</span></article>
    <article class="carte-statistique"><strong><?= e($statistiques_elections['ouvertes'] ?? 0) ?></strong><span>Ouvertes</span></article>
    <article class="carte-statistique"><strong><?= e($statistiques_elections['a_publier'] ?? 0) ?></strong><span>A publier</span></article>
</section>

<section class="grille-import">
    <article class="bloc-module bloc-import-principal">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Creation</p>
                <h2>Nouvelle election</h2>
            </div>
            <span class="etat-module">Preparation d'abord</span>
        </div>

        <form method="post" action="/super-administrateur/elections" class="formulaire">
            <div class="grille-formulaire">
                <label class="champ-large">
                    <span>Nom de l'election</span>
                    <input type="text" name="nom" value="<?= e($valeur('nom')) ?>" required>
                    <?php if (!empty($erreurs['nom'])): ?><small><?= e($erreurs['nom']) ?></small><?php endif; ?>
                </label>
                <label class="champ-large">
                    <span>Description</span>
                    <textarea name="description" rows="3"><?= e($valeur('description')) ?></textarea>
                </label>
                <label>
                    <span>Portee du vote</span>
                    <select name="portee_type" data-portee-election required>
                        <?php foreach (['universite' => 'Toute l universite - tous les etudiants votent', 'faculte' => 'Une faculte - seuls les etudiants de cette faculte votent', 'promotion' => 'Une promotion - seuls les etudiants de cette promotion votent', 'departement' => 'Un departement - seuls les etudiants de ce departement votent'] as $code => $libelle): ?>
                            <option value="<?= e($code) ?>" <?= $valeur('portee_type', 'universite') === $code ? 'selected' : '' ?>><?= e($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="aide-champ" data-aide-portee-election>La portee decide qui aura le droit de voter.</small>
                    <?php if (!empty($erreurs['portee_type'])): ?><small><?= e($erreurs['portee_type']) ?></small><?php endif; ?>
                </label>
                <label data-zone-faculte>
                    <span>Faculte concernee par le vote</span>
                    <select name="faculte_id" data-select-faculte>
                        <option value="">Choisir une faculte</option>
                        <?php foreach (($catalogue_academique ?? []) as $faculte): ?>
                            <option value="<?= e($faculte['id']) ?>" <?= $valeur('faculte_id') === (string) $faculte['id'] ? 'selected' : '' ?>>
                                <?= e($faculte['code'] . ' - ' . $faculte['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($erreurs['faculte_id'])): ?><small><?= e($erreurs['faculte_id']) ?></small><?php endif; ?>
                </label>
                <label data-zone-promotion>
                    <span>Promotion</span>
                    <select name="promotion_id" data-faculte-cible>
                        <option value="">Choisir une promotion</option>
                        <?php foreach (($catalogue_academique ?? []) as $faculte): ?>
                            <?php foreach (($faculte['promotions'] ?? []) as $promotion): ?>
                                <option value="<?= e($promotion['id']) ?>" data-faculte-id="<?= e($faculte['id']) ?>" <?= $valeur('promotion_id') === (string) $promotion['id'] ? 'selected' : '' ?>>
                                    <?= e($faculte['code'] . ' - ' . $promotion['code']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($erreurs['promotion_id'])): ?><small><?= e($erreurs['promotion_id']) ?></small><?php endif; ?>
                </label>
                <label data-zone-departement>
                    <span>Departement</span>
                    <select name="departement_id" data-faculte-cible>
                        <option value="">Choisir un departement</option>
                        <?php foreach (($departements ?? []) as $departement): ?>
                            <option value="<?= e($departement['id']) ?>" data-faculte-id="<?= e($departement['faculte_id']) ?>" <?= $valeur('departement_id') === (string) $departement['id'] ? 'selected' : '' ?>>
                                <?= e($departement['faculte_code'] . ' - ' . $departement['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($erreurs['departement_id'])): ?><small><?= e($erreurs['departement_id']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Date et heure debut</span>
                    <input type="datetime-local" name="date_debut" value="<?= e($dateChamp('date_debut')) ?>" required>
                    <?php if (!empty($erreurs['date_debut'])): ?><small><?= e($erreurs['date_debut']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Date et heure fin</span>
                    <input type="datetime-local" name="date_fin" value="<?= e($dateChamp('date_fin')) ?>" required>
                    <?php if (!empty($erreurs['date_fin'])): ?><small><?= e($erreurs['date_fin']) ?></small><?php endif; ?>
                </label>
            </div>
            <button type="submit" class="bouton-principal">Creer l'election</button>
        </form>
    </article>

    <article class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Cycle</p>
                <h2>Etat de lancement</h2>
            </div>
        </div>
        <ul class="liste-regles">
            <li>La portee de l'election decide qui vote.</li>
            <li>La faculte du candidat se choisit ensuite dans le formulaire candidats.</li>
            <li>Affiliation des candidats avant la demande de lancement.</li>
            <li>Demande de lancement envoyee au president electoral apres ajout des candidats.</li>
            <li>Validation obligatoire du president electoral avant ouverture aux etudiants.</li>
            <li>Vote ouvert pendant le chrono uniquement.</li>
            <li>Resultats visibles apres publication officielle.</li>
        </ul>
    </article>
</section>

<section class="bloc-module">
    <div class="titre-bloc">
        <div>
            <p class="surtitre">Sessions</p>
            <h2>Elections creees</h2>
        </div>
        <span class="etat-module"><?= e(count($elections ?? [])) ?> session(s)</span>
    </div>

    <div class="tableau-responsive">
        <table>
            <thead>
                <tr><th>Nom</th><th>Portee</th><th>Debut</th><th>Fin</th><th>Candidats</th><th>Statut</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if (empty($elections)): ?><tr><td colspan="7">Aucune election creee.</td></tr><?php endif; ?>
                <?php foreach (($elections ?? []) as $election): ?>
                    <?php $electionExpiree = strtotime((string) $election['date_fin']) <= time(); ?>
                    <tr>
                        <td><?= e($election['nom']) ?></td>
                        <td><?= e($election['portee_type'] . (!empty($election['faculte_code']) ? ' - ' . $election['faculte_code'] : '')) ?></td>
                        <td><?= e($election['date_debut']) ?></td>
                        <td><?= e($election['date_fin']) ?></td>
                        <td><?= e($election['total_candidats'] ?? 0) ?></td>
                        <td><span class="badge-statut"><?= e($statutAffiche((string) $election['statut'])) ?></span></td>
                        <td>
                            <div class="actions-inline">
                                <a class="lien-action" href="/super-administrateur/elections/code-qr?id=<?= e($election['id']) ?>">Code QR</a>
                            <?php if (($election['statut'] ?? '') === 'brouillon'): ?>
                                <a class="lien-action" href="/super-administrateur/candidats?election_id=<?= e($election['id']) ?>">Affilier candidats</a>
                                <?php if ($electionExpiree): ?>
                                    <span class="badge-statut">Date fin depassee</span>
                                <?php elseif ((int) ($election['total_candidats'] ?? 0) > 0): ?>
                                    <form method="post" action="/super-administrateur/elections/lancer" class="formulaire-compact">
                                        <input type="hidden" name="election_id" value="<?= e($election['id']) ?>">
                                        <button type="submit">Demander lancement</button>
                                    </form>
                                <?php endif; ?>
                            <?php elseif (($election['statut'] ?? '') === 'en_attente_validation_lancement'): ?>
                                <span class="badge-statut">Chez le president</span>
                            <?php else: ?>
                                <span class="badge-statut">Suivi</span>
                            <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

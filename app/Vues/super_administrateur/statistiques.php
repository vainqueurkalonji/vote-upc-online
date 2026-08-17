<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Statistiques</p>
        <h1>Statistiques globales</h1>
        <p>Les effectifs sont regroupes par faculte puis par promotion pour garder une lecture claire et anonyme.</p>
    </div>
    <span class="badge-page">Vue par classes</span>
</section>

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

<section class="bloc-module">
    <div class="titre-bloc">
        <div>
            <p class="surtitre">Repartition</p>
            <h2>Facultes, promotions et statuts</h2>
        </div>
        <span class="etat-module">Sans noms</span>
    </div>

    <div class="grille-classes">
        <?php foreach (($repartition_academique ?? []) as $faculte): ?>
            <details class="classe-faculte">
                <summary>
                    <span>
                        <strong><?= e($faculte['code']) ?></strong>
                        <em><?= e($faculte['nom']) ?></em>
                    </span>
                    <span class="resume-classe"><?= e($faculte['total']) ?> etudiant(s)</span>
                </summary>

                <div class="resume-faculte">
                    <span>Non actives : <?= e($faculte['non_actives']) ?></span>
                    <span>Actives : <?= e($faculte['actives']) ?></span>
                    <span>Bloques : <?= e($faculte['bloques']) ?></span>
                </div>

                <div class="tableau-responsive tableau-compact">
                    <table>
                        <thead>
                            <tr>
                                <th>Promotion</th>
                                <th>Total</th>
                                <th>Non actives</th>
                                <th>Actives</th>
                                <th>Bloques</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($faculte['promotions'] ?? []) as $promotion): ?>
                                <tr>
                                    <td><?= e($promotion['code']) ?></td>
                                    <td><?= e($promotion['total']) ?></td>
                                    <td><?= e($promotion['non_actives']) ?></td>
                                    <td><?= e($promotion['actives']) ?></td>
                                    <td><?= e($promotion['bloques']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</section>

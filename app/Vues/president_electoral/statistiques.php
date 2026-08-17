<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">President electoral</p>
        <h1>Statistiques presidentielles</h1>
        <p>Vue globale et anonyme des effectifs par faculte puis par promotion.</p>
    </div>
    <span class="badge-page">Sans noms</span>
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

<section class="bloc-module bloc-action-document">
    <div>
        <p class="surtitre">Rapport officiel</p>
        <h2>Participation par election</h2>
        <p>Generer un document anonyme avec les votants et non-votants par faculte et promotion.</p>
    </div>
    <a class="bouton-principal" href="/president-electoral/rapports/participation">Ouvrir le rapport</a>
</section>

<section class="bloc-module">
    <div class="titre-bloc">
        <div>
            <p class="surtitre">Classes academiques</p>
            <h2>Facultes et promotions</h2>
        </div>
        <span class="etat-module">Vue progressive</span>
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

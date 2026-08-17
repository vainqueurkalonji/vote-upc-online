<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre"><?= e($surtitre ?? traduire('app.nom')) ?></p>
        <h1><?= e($titre ?? '') ?></h1>
        <p><?= e($description ?? '') ?></p>
    </div>
    <span class="badge-page"><?= e($badge ?? 'Module') ?></span>
</section>

<?php if (!empty($actions)): ?>
    <section class="barre-actions-page" aria-label="Actions du module">
        <?php foreach ($actions as $action): ?>
            <a href="<?= e($action['lien']) ?>"><?= e($action['libelle']) ?></a>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if (!empty($statistiques)): ?>
    <section class="grille-statistiques" aria-label="Indicateurs">
        <?php foreach ($statistiques as $statistique): ?>
            <article class="carte-statistique">
                <strong><?= e($statistique['valeur']) ?></strong>
                <span><?= e($statistique['libelle']) ?></span>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if (empty($compact)): ?>
<section class="surface-module">
    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Regles</p>
                <h2>Points a respecter</h2>
            </div>
        </div>

        <ul class="liste-regles">
            <?php foreach (($regles ?? []) as $regle): ?>
                <li><?= e($regle) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Structure</p>
                <h2>Structure fonctionnelle</h2>
            </div>
            <span class="etat-module">Regles definies</span>
        </div>

        <div class="tableau-responsive">
            <table>
                <thead>
                    <tr>
                        <?php foreach (($colonnes ?? []) as $colonne): ?>
                            <th><?= e($colonne) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($lignes ?? []) as $ligne): ?>
                        <tr>
                            <?php foreach ($ligne as $cellule): ?>
                                <td><?= e($cellule) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

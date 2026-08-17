<?php
$valeur = static fn(string $cle, string $defaut = ''): string => ancien($cle, $anciennes_donnees ?? [], (string) ($president[$cle] ?? $defaut));
?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Super administrateur</p>
        <h1>Modifier le president electoral</h1>
        <p>Corriger l'identite, l'email ou le nom utilisateur du president electoral.</p>
    </div>
    <span class="badge-page">Correction controlee</span>
</section>

<?php if (!empty($erreurs['general'])): ?>
    <div class="alerte alerte-erreur"><?= e($erreurs['general']) ?></div>
<?php endif; ?>

<section class="bloc-module">
    <div class="titre-bloc">
        <div>
            <p class="surtitre">Compte</p>
            <h2><?= e(trim($president['nom'] . ' ' . $president['post_nom'] . ' ' . $president['prenom'])) ?></h2>
        </div>
        <a class="lien-action" href="/super-administrateur/presidents-electoraux">Retour</a>
    </div>

    <form method="post" action="/super-administrateur/presidents-electoraux/modifier" class="formulaire">
        <input type="hidden" name="president_id" value="<?= e($president['id']) ?>">
        <div class="grille-formulaire">
            <label>
                <span>Nom</span>
                <input type="text" name="nom" data-majuscules autocapitalize="characters" value="<?= e($valeur('nom')) ?>" required>
                <?php if (!empty($erreurs['nom'])): ?><small><?= e($erreurs['nom']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Post-nom</span>
                <input type="text" name="post_nom" data-majuscules autocapitalize="characters" value="<?= e($valeur('post_nom')) ?>" required>
                <?php if (!empty($erreurs['post_nom'])): ?><small><?= e($erreurs['post_nom']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Prenom</span>
                <input type="text" name="prenom" data-majuscules autocapitalize="characters" value="<?= e($valeur('prenom')) ?>" required>
                <?php if (!empty($erreurs['prenom'])): ?><small><?= e($erreurs['prenom']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Nom utilisateur</span>
                <input type="text" name="nom_utilisateur" value="<?= e($valeur('nom_utilisateur')) ?>" required>
                <?php if (!empty($erreurs['nom_utilisateur'])): ?><small><?= e($erreurs['nom_utilisateur']) ?></small><?php endif; ?>
            </label>
            <label class="champ-large">
                <span>Email</span>
                <input type="email" name="email" value="<?= e($valeur('email')) ?>" required>
                <?php if (!empty($erreurs['email'])): ?><small><?= e($erreurs['email']) ?></small><?php endif; ?>
            </label>
        </div>

        <button type="submit" class="bouton-principal">Enregistrer les modifications</button>
    </form>
</section>

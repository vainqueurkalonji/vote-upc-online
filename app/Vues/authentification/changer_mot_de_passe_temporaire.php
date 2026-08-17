<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Securite</p>
        <h1>Choisir un nouveau mot de passe</h1>
        <p>Votre mot de passe temporaire doit etre remplace avant de continuer.</p>
    </div>
    <span class="badge-page">Obligatoire</span>
</section>

<?php if (!empty($erreurs['general'])): ?>
    <div class="alerte alerte-erreur"><?= e($erreurs['general']) ?></div>
<?php endif; ?>

<section class="bloc-module panneau-mot-de-passe">
    <form class="formulaire" method="post" action="/mot-de-passe-temporaire/changer" novalidate>
        <label>
            <span>Nouveau mot de passe</span>
            <input type="password" name="mot_de_passe" autocomplete="new-password" required>
            <?php if (!empty($erreurs['mot_de_passe'])): ?><small><?= e($erreurs['mot_de_passe']) ?></small><?php endif; ?>
        </label>

        <label>
            <span>Confirmation</span>
            <input type="password" name="confirmation_mot_de_passe" autocomplete="new-password" required>
            <?php if (!empty($erreurs['confirmation_mot_de_passe'])): ?><small><?= e($erreurs['confirmation_mot_de_passe']) ?></small><?php endif; ?>
        </label>

        <div class="regles-mot-de-passe">
            <strong>Mot de passe :</strong> minimum 8 caracteres, une majuscule, une minuscule, un chiffre et un symbole.
        </div>

        <button type="submit" class="bouton-principal">Enregistrer mon mot de passe</button>
    </form>
</section>

<section class="panneau-authentification">
    <div class="entete-authentification">
        <p class="surtitre">Verification email</p>
        <h1>Nouveau mot de passe</h1>
        <p>Entrez le code envoye a <strong><?= e($email_masque ?? '') ?></strong>, puis choisissez un nouveau mot de passe.</p>
    </div>

    <?php if (!empty($erreurs['general'])): ?>
        <div class="alerte alerte-erreur"><?= e($erreurs['general']) ?></div>
    <?php endif; ?>

    <form class="formulaire" method="post" action="/mot-de-passe-oublie/reinitialiser" novalidate>
        <label>
            <span>Code a 6 chiffres</span>
            <input
                class="champ-code-otp"
                type="text"
                name="code_otp"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                autocomplete="one-time-code"
                placeholder="000000"
                required
            >
            <?php if (!empty($erreurs['code_otp'])): ?><small><?= e($erreurs['code_otp']) ?></small><?php endif; ?>
        </label>

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

        <button type="submit" class="bouton-principal">Changer mon mot de passe</button>
    </form>
</section>

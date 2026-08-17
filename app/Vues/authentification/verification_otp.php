<section class="panneau-authentification">
    <div class="entete-authentification">
        <p class="surtitre">Verification securisee</p>
        <h1>Code OTP</h1>
        <p>
            <?= e($description_otp ?? 'Entrez le code recu par email pour terminer la connexion.') ?>
            Le code a ete envoye a <strong><?= e($email_masque ?? '') ?></strong>.
            Il expire dans <?= e($expiration_otp_minutes ?? 5) ?> minutes.
        </p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alerte alerte-succes"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if (!empty($erreurs['general'])): ?>
        <div class="alerte alerte-erreur"><?= e($erreurs['general']) ?></div>
    <?php endif; ?>

    <form class="formulaire" method="post" action="/otp/connexion" novalidate>
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

        <button type="submit" class="bouton-principal">Verifier et entrer</button>
    </form>

    <form class="formulaire formulaire-renvoi-otp" method="post" action="/otp/renvoyer">
        <button type="submit" class="bouton-secondaire-authentification">Renvoyer le code OTP</button>
    </form>
</section>

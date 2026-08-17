<section class="panneau-authentification">
    <div class="entete-authentification">
        <p class="surtitre"><?= e(traduire('app.nom')) ?></p>
        <h1>Mot de passe oublie</h1>
        <p>Entrez votre email ou votre nom utilisateur. Un code de verification sera envoye a votre adresse email.</p>
    </div>

    <?php if (!empty($erreurs['general'])): ?>
        <div class="alerte alerte-erreur"><?= e($erreurs['general']) ?></div>
    <?php endif; ?>

    <form class="formulaire" method="post" action="/mot-de-passe-oublie" novalidate>
        <label>
            <span>Email ou nom utilisateur</span>
            <input
                type="text"
                name="identifiant"
                value="<?= e(ancien('identifiant', $anciennes_donnees ?? [])) ?>"
                placeholder="email@gmail.com ou nom utilisateur"
                autocomplete="username"
                required
            >
            <?php if (!empty($erreurs['identifiant'])): ?><small><?= e($erreurs['identifiant']) ?></small><?php endif; ?>
        </label>

        <button type="submit" class="bouton-principal">Recevoir le code</button>
    </form>

    <a class="lien-retour" href="/administration/connexion"><?= e(traduire('mot_de_passe_oublie.retour')) ?></a>
</section>

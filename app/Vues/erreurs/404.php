<!doctype html>
<html lang="<?= e(langue_actuelle()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(traduire('erreur404.titre')) ?></title>
    <link rel="stylesheet" href="/assets/css/application.css">
</head>
<body class="page-authentification">
    <main class="cadre-authentification">
        <section class="panneau-authentification">
            <div class="entete-authentification">
                <p class="surtitre"><?= e(traduire('erreur404.surtitre')) ?></p>
                <h1><?= e(traduire('erreur404.titre')) ?></h1>
                <p><?= e(traduire('erreur404.description')) ?></p>
            </div>
            <a href="/" class="lien-retour"><?= e(traduire('erreur404.retour')) ?></a>
        </section>
    </main>
</body>
</html>

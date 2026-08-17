<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">President electoral</p>
        <h1>Publication des resultats</h1>
        <p>Autoriser l'affichage officiel des resultats aux etudiants.</p>
    </div>
    <span class="badge-page">Resultats caches avant aval</span>
</section>

<?php if (!empty($message_succes)): ?><div class="alerte alerte-succes"><?= e($message_succes) ?></div><?php endif; ?>
<?php if (!empty($message_erreur)): ?><div class="alerte alerte-erreur"><?= e($message_erreur) ?></div><?php endif; ?>

<section class="bloc-module">
    <div class="titre-bloc"><div><p class="surtitre">A publier</p><h2>Sessions terminees</h2></div><span class="etat-module"><?= e(count($elections ?? [])) ?></span></div>
    <div class="liste-candidats compacte">
        <?php if (empty($elections)): ?><div class="etat-vide-classe">Aucun resultat en attente de publication.</div><?php endif; ?>
        <?php foreach (($elections ?? []) as $election): ?>
            <article class="fiche-decision">
                <div>
                    <h3><?= e($election['nom']) ?></h3>
                    <p><?= e($election['portee_type'] . (!empty($election['faculte_code']) ? ' - ' . $election['faculte_code'] : '')) ?></p>
                    <div class="metadonnees-candidat">
                        <span><?= e($election['date_fin']) ?></span>
                        <span><?= e($election['total_candidats'] ?? 0) ?> candidat(s)</span>
                    </div>
                </div>
                <form method="post" action="/president-electoral/resultats/publication" class="formulaire formulaire-decision">
                    <input type="hidden" name="election_id" value="<?= e($election['id']) ?>">
                    <textarea name="commentaire" rows="2" placeholder="Commentaire de publication"></textarea>
                    <button class="bouton-principal" type="submit">Publier les resultats</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>

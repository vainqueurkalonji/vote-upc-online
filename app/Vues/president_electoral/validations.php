<?php
$statutAffiche = static function (array $election): string {
    if (($election['statut'] ?? '') === 'brouillon') {
        return 'Pret a valider';
    }

    if (($election['statut'] ?? '') === 'en_attente_validation_lancement') {
        return 'En attente du president';
    }

    return (string) ($election['statut'] ?? '');
};
?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">President electoral</p>
        <h1>Validation du lancement</h1>
        <p>Donner l'aval avant que la session puisse ouvrir le vote aux etudiants concernes.</p>
    </div>
    <span class="badge-page">Dernier mot</span>
</section>

<?php if (!empty($message_succes)): ?><div class="alerte alerte-succes"><?= e($message_succes) ?></div><?php endif; ?>
<?php if (!empty($message_erreur)): ?><div class="alerte alerte-erreur"><?= e($message_erreur) ?></div><?php endif; ?>

<section class="bloc-module">
    <div class="titre-bloc"><div><p class="surtitre">A traiter</p><h2>Elections en attente</h2></div><span class="etat-module"><?= e(count($elections ?? [])) ?></span></div>
    <div class="liste-candidats compacte">
        <?php if (empty($elections)): ?><div class="etat-vide-classe">Aucune election en attente de validation.</div><?php endif; ?>
        <?php foreach (($elections ?? []) as $election): ?>
            <article class="fiche-decision">
                <div>
                    <h3><?= e($election['nom']) ?></h3>
                    <p><?= e($election['portee_type'] . (!empty($election['faculte_code']) ? ' - ' . $election['faculte_code'] : '')) ?></p>
                    <div class="metadonnees-candidat">
                        <span><?= e($election['date_debut']) ?></span>
                        <span><?= e($election['date_fin']) ?></span>
                        <span><?= e($election['total_candidats'] ?? 0) ?> candidat(s)</span>
                        <span class="badge-statut"><?= e($statutAffiche($election)) ?></span>
                    </div>
                </div>
                <form method="post" action="/president-electoral/elections/validations" class="formulaire formulaire-decision">
                    <input type="hidden" name="election_id" value="<?= e($election['id']) ?>">
                    <textarea name="commentaire" rows="2" placeholder="Commentaire"></textarea>
                    <div class="actions-inline">
                        <button class="bouton-principal" type="submit" name="decision" value="valide">Valider</button>
                        <button class="bouton-secondaire-authentification" type="submit" name="decision" value="refuse">Refuser</button>
                    </div>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>

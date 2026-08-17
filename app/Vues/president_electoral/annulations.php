<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">President electoral</p>
        <h1>Annulation par faculte</h1>
        <p>Enregistrer une annulation motivee pour une faculte precise.</p>
    </div>
    <span class="badge-page">Decision motivee</span>
</section>

<?php if (!empty($message_succes)): ?><div class="alerte alerte-succes"><?= e($message_succes) ?></div><?php endif; ?>
<?php if (!empty($message_erreur)): ?><div class="alerte alerte-erreur"><?= e($message_erreur) ?></div><?php endif; ?>

<section class="bloc-module">
    <form method="post" action="/president-electoral/elections/annulations" class="formulaire">
        <div class="grille-formulaire">
            <label>
                <span>Election</span>
                <select name="election_id" required>
                    <option value="">Choisir une election</option>
                    <?php foreach (($elections ?? []) as $election): ?>
                        <option value="<?= e($election['id']) ?>"><?= e($election['nom'] . ' - ' . $election['statut']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Faculte</span>
                <select name="faculte_id" required>
                    <option value="">Choisir une faculte</option>
                    <?php foreach (($facultes ?? []) as $faculte): ?>
                        <option value="<?= e($faculte['id']) ?>"><?= e($faculte['code'] . ' - ' . $faculte['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="champ-large">
                <span>Raison</span>
                <textarea name="raison" rows="4" required></textarea>
            </label>
        </div>
        <button class="bouton-principal" type="submit">Enregistrer l'annulation</button>
    </form>
</section>

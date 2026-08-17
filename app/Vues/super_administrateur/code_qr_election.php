<?php
$election = $election ?? [];
$statutAffiche = (string) ($election['statut'] ?? '');
if ($statutAffiche === 'brouillon') {
    $statutAffiche = 'En preparation';
}
?>

<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Super administrateur</p>
        <h1>Code QR election</h1>
        <p>Document imprimable a afficher aux valves ou a partager pour ouvrir la connexion etudiant de cette election.</p>
    </div>
    <span class="badge-page">Acces etudiant</span>
</section>

<div class="actions-document">
    <a class="bouton-secondaire" href="/super-administrateur/elections">Retour elections</a>
    <button type="button" class="bouton-principal" onclick="window.print()">Imprimer le document</button>
</div>

<section class="document-impression document-qr-election">
    <div class="document-entete">
        <div>
            <p class="surtitre">Vote UPC Online</p>
            <h2><?= e($election['nom'] ?? '') ?></h2>
            <p><?= e($portee_libelle ?? 'Portee academique') ?></p>
        </div>
        <span class="badge-statut"><?= e($statutAffiche) ?></span>
    </div>

    <div class="zone-qr-election">
        <div class="bloc-qr">
            <img src="<?= e($url_qr ?? '') ?>" alt="Code QR de l'election <?= e($election['nom'] ?? '') ?>">
        </div>
        <div class="details-qr-election">
            <p class="surtitre">Lien de scan</p>
            <h3>Scanner pour acceder a la page etudiant</h3>
            <p>Le QR renvoie vers la connexion etudiant, puis vers le bulletin de cette election apres authentification.</p>
            <p class="url-qr"><?= e($url_acces ?? '') ?></p>
            <div class="resume-faculte">
                <span>Debut : <?= e($election['date_debut'] ?? '') ?></span>
                <span>Fin : <?= e($election['date_fin'] ?? '') ?></span>
                <span>Election : <?= e($election['id'] ?? '') ?></span>
            </div>
        </div>
    </div>

    <div class="note-securite-document">
        Seuls les etudiants importes, actives par leur appariteur et authentifies par OTP peuvent voter. Le QR ne remplace pas la verification du compte.
    </div>
</section>

<?php
$nomEtudiant = trim(($etudiant['prenom'] ?? '') . ' ' . ($etudiant['nom'] ?? ''));
$sessions = $sessions_vote ?? [];
$resume = $resume_sessions ?? [];
?>

<section class="scene-etudiant">
    <div>
        <p class="surtitre">Espace etudiant</p>
        <h1>Bienvenue <?= e($nomEtudiant) ?></h1>
        <p><?= e(($etudiant['faculte_code'] ?? '') . ' / ' . ($etudiant['promotion_code'] ?? '') . ' / ' . ($etudiant['matricule'] ?? '')) ?></p>
    </div>
    <div class="carte-session-etudiant">
        <span class="badge-statut">Sessions separees</span>
        <h2><?= e($resume['sessions'] ?? 0) ?> election(s)</h2>
        <p>Chaque vote est rattache a son election. Un vote universitaire ne bloque pas un vote facultaire.</p>
    </div>
</section>

<section class="grille-statistiques">
    <article class="carte-statistique"><strong><?= e($resume['sessions'] ?? 0) ?></strong><span>Sessions visibles</span></article>
    <article class="carte-statistique"><strong><?= e($resume['ouvertes'] ?? 0) ?></strong><span>Votes ouverts</span></article>
    <article class="carte-statistique"><strong><?= e($resume['votes_faits'] ?? 0) ?></strong><span>Votes deja faits</span></article>
    <article class="carte-statistique"><strong><?= e($resume['candidats'] ?? 0) ?></strong><span>Candidats visibles</span></article>
</section>

<section class="barre-actions-page">
    <a href="/etudiant/candidats">Voir les candidats</a>
    <a href="/etudiant/vote">Passer au vote</a>
    <a href="/etudiant/resultats">Resultats</a>
</section>

<section class="bloc-module">
    <div class="titre-bloc">
        <div>
            <p class="surtitre">Mes sessions</p>
            <h2>Elections qui me concernent</h2>
        </div>
        <span class="etat-module"><?= e(count($sessions)) ?> session(s)</span>
    </div>

    <?php if (empty($sessions)): ?>
        <div class="etat-vide-classe">Aucune election validee ne correspond encore a votre faculte, departement, promotion ou a toute l'universite.</div>
    <?php else: ?>
        <div class="liste-sessions-etudiant">
            <?php foreach ($sessions as $session): ?>
                <?php
                $electionSession = $session['election'];
                $debutTs = strtotime((string) ($electionSession['date_debut'] ?? '')) * 1000;
                $finTs = strtotime((string) ($electionSession['date_fin'] ?? '')) * 1000;
                ?>
                <article class="bloc-election-etudiant <?= $session['peut_voter'] ? 'session-active' : '' ?>" data-session-election>
                    <div class="session-entete">
                        <div>
                            <p class="surtitre"><?= e($session['portee_libelle']) ?></p>
                            <h3><?= e($electionSession['nom']) ?></h3>
                            <p><?= e($electionSession['description'] ?? 'Session electorale') ?></p>
                        </div>
                        <div class="compteur-session <?= $session['peut_voter'] ? 'compteur-ouvert' : '' ?>">
                            <span data-chrono-label>Calcul du chrono</span>
                            <strong
                                data-chrono-session
                                data-chrono-debut="<?= e($electionSession['date_debut']) ?>"
                                data-chrono-fin="<?= e($electionSession['date_fin']) ?>"
                                data-chrono-debut-ts="<?= e($debutTs) ?>"
                                data-chrono-fin-ts="<?= e($finTs) ?>"
                            >00:00:00</strong>
                            <span class="badge-statut"><?= e($electionSession['statut']) ?></span>
                        </div>
                    </div>
                    <div class="message-fin-session" data-message-fin-session hidden>Le temps de vote est termine. Vous gardez l'acces aux candidats et aux resultats publies.</div>
                    <div class="session-resume">
                        <span><?= e($session['nombre_candidats']) ?> candidat(s)</span>
                        <span><?= $session['a_deja_vote'] ? 'Vote deja enregistre' : 'Vote non encore enregistre' ?></span>
                        <span><?= $session['peut_voter'] ? 'Ouvert maintenant' : 'Non ouvert au vote' ?></span>
                    </div>
                    <div class="session-actions">
                        <a href="/etudiant/candidats#election-<?= e($electionSession['id']) ?>">Candidats</a>
                        <a href="/etudiant/vote#election-<?= e($electionSession['id']) ?>">Vote</a>
                        <a href="/etudiant/resultats#election-<?= e($electionSession['id']) ?>">Resultats</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

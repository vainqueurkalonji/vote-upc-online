<section class="entete-page entete-page-module">
    <div>
        <p class="surtitre">Import Excel</p>
        <h1>Importer les etudiants</h1>
        <p>Utilise le modele officiel pour eviter les colonnes dans le desordre. Les champs academiques sont prepares pour une saisie en majuscules.</p>
    </div>
    <span class="badge-page">Modele controle</span>
</section>

<?php if (!empty($message_succes)): ?>
    <div class="alerte alerte-succes"><?= e($message_succes) ?></div>
<?php endif; ?>

<?php if (!empty($message_erreur)): ?>
    <div class="alerte alerte-erreur"><?= e($message_erreur) ?></div>
<?php endif; ?>

<section class="grille-import">
    <article class="bloc-module bloc-import-principal">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Fichier officiel</p>
                <h2>1. Telecharger le modele Excel</h2>
            </div>
            <span class="etat-module">XLSX</span>
        </div>
        <p>Le modele contient les colonnes dans l'ordre, les catalogues FASI, FASE, Medecine, Droit, Theologie, les promotions officielles et les listes deroulantes.</p>
        <div class="barre-actions-page espace-haut">
            <a href="/modeles/modele_import_etudiants_vote_upc.xlsx" download>Telecharger le modele</a>
            <a href="/modeles/jeu_demo_200_etudiants_vote_upc.xlsx" download>Telecharger 200 etudiants demo</a>
        </div>
    </article>

    <article class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Depot</p>
                <h2>2. Selectionner le fichier rempli</h2>
            </div>
        </div>
        <form method="post" action="/super-administrateur/etudiants/importer" enctype="multipart/form-data" class="formulaire">
            <label>
                <span>Fichier Excel</span>
                <input type="file" name="fichier_etudiants" accept=".xlsx,.xls" required>
            </label>
            <button type="submit" class="bouton-principal">Deposer le fichier</button>
        </form>
    </article>
</section>

<section class="surface-module">
    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Saisie guidee</p>
                <h2>3. Preparer une ligne etudiant</h2>
            </div>
            <span class="etat-module">Listes + date</span>
        </div>

        <form class="formulaire formulaire-compact">
            <div class="grille-formulaire">
                <label>
                    <span>Nom</span>
                    <input type="text" data-majuscules autocapitalize="characters" placeholder="KABAMBA">
                </label>
                <label>
                    <span>Post-nom</span>
                    <input type="text" data-majuscules autocapitalize="characters" placeholder="MUKENDI">
                </label>
                <label>
                    <span>Prenom</span>
                    <input type="text" data-majuscules autocapitalize="characters" placeholder="SOPHIA">
                </label>
                <label>
                    <span>Matricule</span>
                    <input type="text" data-majuscules autocapitalize="characters" placeholder="UPC/FASI/L1/001">
                </label>
                <label>
                    <span>Date de naissance</span>
                    <input type="date">
                </label>
                <label>
                    <span>Sexe</span>
                    <select>
                        <option value="">Choisir</option>
                        <option>M</option>
                        <option>F</option>
                    </select>
                </label>
                <label>
                    <span>Faculte</span>
                    <select data-majuscules>
                        <option value="">Choisir</option>
                        <option>FASI</option>
                        <option>FASE</option>
                        <option>MEDECINE</option>
                        <option>DROIT</option>
                        <option>THEOLOGIE</option>
                    </select>
                </label>
                <label>
                    <span>Promotion</span>
                    <select data-majuscules>
                        <option value="">Choisir</option>
                        <option>L1</option>
                        <option>L2</option>
                        <option>L3</option>
                        <option>L4</option>
                        <option>M1</option>
                        <option>M2</option>
                        <option>G0</option>
                        <option>PR_BACH1</option>
                        <option>BACH2</option>
                        <option>BACH3</option>
                        <option>D4</option>
                    </select>
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" placeholder="etudiant@gmail.com">
                </label>
            </div>
        </form>
    </div>

    <div class="bloc-module">
        <div class="titre-bloc">
            <div>
                <p class="surtitre">Regles</p>
                <h2>Ce que le systeme verifiera</h2>
            </div>
        </div>

        <ul class="liste-regles">
            <li>Les noms, post-noms, prenoms, lieux et matricules seront convertis en majuscules.</li>
            <li>Le matricule et l'email doivent etre uniques.</li>
            <li>La promotion doit correspondre a la faculte choisie.</li>
            <li>Un etudiant importe commence avec le statut non active.</li>
            <li>L'appariteur active ensuite l'etudiant et le mot de passe temporaire part par email.</li>
        </ul>
    </div>
</section>

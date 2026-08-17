document.addEventListener('DOMContentLoaded', () => {
    preparerThemeInterface();
    preparerSelecteursLangue();
    preparerChampsMotDePasse();
    preparerChampsMajuscules();
    preparerListesAcademiques();
    preparerElectionCandidat();
    preparerPorteesElection();
    preparerChronos();
    preparerConfirmationVote();
    preparerRevelationResultats();

    const premierChamp = document.querySelector('input:not([disabled])');

    if (premierChamp) {
        premierChamp.focus();
    }
});

function langueInterface() {
    return document.documentElement.lang === 'en' ? 'en' : 'fr';
}

function textesInterface() {
    return {
        fr: {
            modeSombre: 'Mode sombre',
            modeClair: 'Mode clair',
            aideUniversite: "Tous les etudiants votent. La faculte du candidat se choisira dans le formulaire candidats.",
            aideFaculte: "Seuls les etudiants de la faculte choisie voteront.",
            aidePromotion: "Seuls les etudiants de la promotion choisie voteront.",
            aideDepartement: "Seuls les etudiants du departement choisi voteront.",
            chronoIndisponible: 'Chrono indisponible',
            debutVoteDans: 'Debut du vote dans',
            finVoteDans: 'Fin du vote dans',
            voteTermine: 'Vote termine',
            voteFerme: 'Vote ferme',
            confirmationVoteTitre: 'Confirmation du vote',
            confirmationVoteTexte: 'Vous etes sur le point de voter pour ce candidat. Cette action sera definitive.',
            confirmationVoteElection: 'Election',
            confirmationVoteAnnuler: 'Annuler',
            confirmationVoteValider: 'Confirmer mon vote',
            confirmationVoteEnvoi: 'Enregistrement...',
            verificationBulletins: 'Verification des bulletins',
            controlePublication: 'Controle de la publication',
            validationClassement: 'Validation du classement',
            preparationPhoto: 'Preparation de la photo',
            annonceVainqueur: 'Annonce du vainqueur',
            resultatsPublies: 'Resultats officiels publies',
            candidatUniversite: "Portee universite : tous les etudiants voteront, mais choisissez ici la faculte d'origine du candidat.",
            candidatFaculte: "Portee faculte : seuls les etudiants de cette faculte voteront et les candidats doivent venir de cette faculte.",
            candidatPromotion: "Election par promotion : le candidat doit appartenir exactement a cette promotion.",
            candidatDepartement: "Election par departement : le candidat doit appartenir au departement choisi.",
            candidatDefaut: "Choisissez l'election avant la faculte d'origine du candidat.",
        },
        en: {
            modeSombre: 'Dark mode',
            modeClair: 'Light mode',
            aideUniversite: 'All students vote. The candidate faculty will be chosen in the candidate form.',
            aideFaculte: 'Only students from the selected faculty will vote.',
            aidePromotion: 'Only students from the selected promotion will vote.',
            aideDepartement: 'Only students from the selected department will vote.',
            chronoIndisponible: 'Countdown unavailable',
            debutVoteDans: 'Voting starts in',
            finVoteDans: 'Voting ends in',
            voteTermine: 'Voting ended',
            voteFerme: 'Vote closed',
            confirmationVoteTitre: 'Vote confirmation',
            confirmationVoteTexte: 'You are about to vote for this candidate. This action will be final.',
            confirmationVoteElection: 'Election',
            confirmationVoteAnnuler: 'Cancel',
            confirmationVoteValider: 'Confirm my vote',
            confirmationVoteEnvoi: 'Saving...',
            verificationBulletins: 'Ballot verification',
            controlePublication: 'Publication control',
            validationClassement: 'Ranking validation',
            preparationPhoto: 'Photo preparation',
            annonceVainqueur: 'Winner announcement',
            resultatsPublies: 'Official results published',
            candidatUniversite: 'University scope: all students will vote, but choose the candidate origin faculty here.',
            candidatFaculte: 'Faculty scope: only students from this faculty will vote and candidates must come from this faculty.',
            candidatPromotion: 'Promotion election: the candidate must belong exactly to this promotion.',
            candidatDepartement: 'Department election: the candidate must belong to the selected department.',
            candidatDefaut: 'Choose the election before the candidate origin faculty.',
        },
    }[langueInterface()];
}

function preparerThemeInterface() {
    const boutons = document.querySelectorAll('[data-theme-toggle]');
    const textes = textesInterface();

    const appliquer = (theme) => {
        const sombre = theme === 'sombre';
        document.documentElement.dataset.theme = sombre ? 'sombre' : '';

        try {
            if (sombre) {
                localStorage.setItem('vote_upc_theme', 'sombre');
            } else {
                localStorage.removeItem('vote_upc_theme');
            }
        } catch (erreur) {}

        boutons.forEach((bouton) => {
            bouton.textContent = sombre ? textes.modeClair : textes.modeSombre;
            bouton.setAttribute('aria-pressed', sombre ? 'true' : 'false');
        });
    };

    const themeInitial = document.documentElement.dataset.theme === 'sombre' ? 'sombre' : 'clair';
    appliquer(themeInitial);

    boutons.forEach((bouton) => {
        bouton.addEventListener('click', () => {
            appliquer(document.documentElement.dataset.theme === 'sombre' ? 'clair' : 'sombre');
        });
    });
}

function preparerSelecteursLangue() {
    document.addEventListener('click', (evenement) => {
        document.querySelectorAll('.selecteur-langue[open]').forEach((selecteur) => {
            if (!selecteur.contains(evenement.target)) {
                selecteur.removeAttribute('open');
            }
        });
    });
}

function preparerChampsMotDePasse() {
    const langue = langueInterface();
    const textes = {
        fr: {
            afficher: 'Voir',
            masquer: 'Masquer',
            afficherTitre: 'Afficher le mot de passe',
            masquerTitre: 'Masquer le mot de passe',
        },
        en: {
            afficher: 'Show',
            masquer: 'Hide',
            afficherTitre: 'Show password',
            masquerTitre: 'Hide password',
        },
    };

    document.querySelectorAll('input[type="password"]').forEach((champ) => {
        if (champ.closest('.champ-mot-de-passe')) {
            return;
        }

        const conteneur = document.createElement('div');
        conteneur.className = 'champ-mot-de-passe';
        champ.parentNode.insertBefore(conteneur, champ);
        conteneur.appendChild(champ);

        const bouton = document.createElement('button');
        bouton.type = 'button';
        bouton.className = 'bouton-voir-mot-de-passe';
        bouton.textContent = textes[langue].afficher;
        bouton.setAttribute('aria-label', textes[langue].afficherTitre);
        bouton.title = textes[langue].afficherTitre;
        bouton.disabled = champ.disabled;

        bouton.addEventListener('click', () => {
            const visible = champ.type === 'text';
            champ.type = visible ? 'password' : 'text';
            bouton.textContent = visible ? textes[langue].afficher : textes[langue].masquer;
            bouton.setAttribute('aria-label', visible ? textes[langue].afficherTitre : textes[langue].masquerTitre);
            bouton.title = visible ? textes[langue].afficherTitre : textes[langue].masquerTitre;
        });

        conteneur.appendChild(bouton);
    });
}

function preparerPorteesElection() {
    const textes = textesInterface();

    document.querySelectorAll('[data-portee-election]').forEach((select) => {
        const formulaire = select.closest('form');
        const zoneFaculte = formulaire?.querySelector('[data-zone-faculte]');
        const zonePromotion = formulaire?.querySelector('[data-zone-promotion]');
        const zoneDepartement = formulaire?.querySelector('[data-zone-departement]');
        const aide = formulaire?.querySelector('[data-aide-portee-election]');

        const afficher = () => {
            const portee = select.value;

            if (zoneFaculte) {
                zoneFaculte.hidden = portee === 'universite';
            }

            if (zonePromotion) {
                zonePromotion.hidden = portee !== 'promotion';
            }

            if (zoneDepartement) {
                zoneDepartement.hidden = portee !== 'departement';
            }

            if (aide) {
                if (portee === 'universite') {
                    aide.textContent = textes.aideUniversite;
                } else if (portee === 'faculte') {
                    aide.textContent = textes.aideFaculte;
                } else if (portee === 'promotion') {
                    aide.textContent = textes.aidePromotion;
                } else if (portee === 'departement') {
                    aide.textContent = textes.aideDepartement;
                }
            }
        };

        select.addEventListener('change', afficher);
        afficher();
    });
}

function preparerChronos() {
    const textes = textesInterface();
    const elements = document.querySelectorAll('[data-chrono-fin]:not([data-chrono-session])');
    const sessions = document.querySelectorAll('[data-chrono-session]');

    if (!elements.length && !sessions.length) {
        return;
    }

    const lireDateLocale = (valeur) => {
        const texte = String(valeur || '').trim().replace('T', ' ');
        const morceaux = texte.match(/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})(?::(\d{2}))?/);

        if (!morceaux) {
            return new Date(NaN);
        }

        return new Date(
            Number(morceaux[1]),
            Number(morceaux[2]) - 1,
            Number(morceaux[3]),
            Number(morceaux[4]),
            Number(morceaux[5]),
            Number(morceaux[6] || 0)
        );
    };

    const lireHorodatage = (element, nomTimestamp, nomDate) => {
        const valeurTimestamp = Number(element.dataset[nomTimestamp] || 0);

        if (Number.isFinite(valeurTimestamp) && valeurTimestamp > 0) {
            return valeurTimestamp;
        }

        return lireDateLocale(element.dataset[nomDate]).getTime();
    };

    const formaterDuree = (millisecondes) => {
        const totalSecondes = Math.max(0, Math.floor(millisecondes / 1000));
        const heures = Math.floor(totalSecondes / 3600);
        const minutes = Math.floor((totalSecondes % 3600) / 60);
        const secondes = totalSecondes % 60;

        return [heures, minutes, secondes]
            .map((valeur) => String(valeur).padStart(2, '0'))
            .join(':');
    };

    const mettreAJour = () => {
        sessions.forEach((element) => {
            const debut = lireHorodatage(element, 'chronoDebutTs', 'chronoDebut');
            const fin = lireHorodatage(element, 'chronoFinTs', 'chronoFin');
            const maintenant = Date.now();
            const libelle = element.closest('.compteur-session')?.querySelector('[data-chrono-label]');

            if (!Number.isFinite(debut) || !Number.isFinite(fin)) {
                element.textContent = '00:00:00';
                if (libelle) {
                    libelle.textContent = textes.chronoIndisponible;
                }
                return;
            }

            if (maintenant < debut) {
                element.textContent = formaterDuree(debut - maintenant);
                if (libelle) {
                    libelle.textContent = textes.debutVoteDans;
                }
                element.classList.remove('termine');
                basculerVoteSession(element, false, false);
                return;
            }

            if (maintenant <= fin) {
                element.textContent = formaterDuree(fin - maintenant);
                if (libelle) {
                    libelle.textContent = textes.finVoteDans;
                }
                element.classList.remove('termine');
                basculerVoteSession(element, false, true);
                return;
            }

            element.textContent = '00:00:00';
            if (libelle) {
                libelle.textContent = textes.voteTermine;
            }
            element.classList.add('termine');
            basculerVoteSession(element, true, false);
        });

        elements.forEach((element) => {
            const fin = lireHorodatage(element, 'chronoFinTs', 'chronoFin');
            const difference = fin - Date.now();

            if (!Number.isFinite(fin) || difference <= 0) {
                element.textContent = '00:00:00';
                element.classList.add('termine');
                return;
            }

            element.textContent = formaterDuree(difference);
        });
    };

    mettreAJour();
    setInterval(mettreAJour, 1000);
}

function basculerVoteSession(elementChrono, terminee, ouverte) {
    const textes = textesInterface();
    const session = elementChrono.closest('[data-session-election]');

    if (!session) {
        return;
    }

    session.classList.toggle('session-terminee', terminee);
    session.classList.toggle('session-chrono-ouvert', ouverte && !terminee);

    const message = session.querySelector('[data-message-fin-session]');
    if (message) {
        message.hidden = !terminee;
    }

    session.querySelectorAll('[data-formulaire-vote]').forEach((formulaire) => {
        formulaire.classList.toggle('vote-expire', terminee);
        formulaire.querySelectorAll('button, input, select, textarea').forEach((controle) => {
            controle.disabled = terminee;
            if (terminee && controle.tagName === 'BUTTON') {
                controle.textContent = textes.voteFerme;
            }
        });
    });
}

function preparerConfirmationVote() {
    const formulaires = document.querySelectorAll('[data-formulaire-vote]');

    if (!formulaires.length) {
        return;
    }

    const textes = textesInterface();
    let formulaireCible = null;

    const modale = document.createElement('div');
    modale.className = 'modale-confirmation-vote';
    modale.hidden = true;
    modale.setAttribute('aria-hidden', 'true');

    const panneau = document.createElement('div');
    panneau.className = 'panneau-confirmation-vote';
    panneau.setAttribute('role', 'dialog');
    panneau.setAttribute('aria-modal', 'true');
    panneau.setAttribute('aria-labelledby', 'titre-confirmation-vote');

    const scene = document.createElement('div');
    scene.className = 'scene-confirmation-vote';

    const portrait = document.createElement('div');
    portrait.className = 'photo-confirmation-vote';

    const image = document.createElement('img');
    image.alt = '';
    image.hidden = true;

    const initiales = document.createElement('span');
    portrait.appendChild(image);
    portrait.appendChild(initiales);

    const surtitre = document.createElement('p');
    surtitre.className = 'surtitre';
    surtitre.textContent = textes.confirmationVoteTitre;

    const titre = document.createElement('h2');
    titre.id = 'titre-confirmation-vote';

    const election = document.createElement('p');
    election.className = 'confirmation-election';

    const texte = document.createElement('p');
    texte.className = 'texte-confirmation-vote';
    texte.textContent = textes.confirmationVoteTexte;

    scene.appendChild(portrait);
    scene.appendChild(surtitre);
    scene.appendChild(titre);
    scene.appendChild(election);
    scene.appendChild(texte);

    const actions = document.createElement('div');
    actions.className = 'actions-confirmation-vote';

    const boutonAnnuler = document.createElement('button');
    boutonAnnuler.type = 'button';
    boutonAnnuler.className = 'bouton-secondaire';
    boutonAnnuler.textContent = textes.confirmationVoteAnnuler;

    const boutonConfirmer = document.createElement('button');
    boutonConfirmer.type = 'button';
    boutonConfirmer.className = 'bouton-principal';
    boutonConfirmer.textContent = textes.confirmationVoteValider;

    actions.appendChild(boutonAnnuler);
    actions.appendChild(boutonConfirmer);
    panneau.appendChild(scene);
    panneau.appendChild(actions);
    modale.appendChild(panneau);
    document.body.appendChild(modale);

    const fermer = () => {
        modale.classList.remove('visible');
        document.body.classList.remove('modale-vote-ouverte');
        window.setTimeout(() => {
            modale.hidden = true;
            modale.setAttribute('aria-hidden', 'true');
            boutonConfirmer.disabled = false;
            boutonConfirmer.textContent = textes.confirmationVoteValider;
            formulaireCible = null;
        }, 180);
    };

    const ouvrir = (formulaire) => {
        formulaireCible = formulaire;
        const nomCandidat = formulaire.dataset.candidatNom || '';
        const nomElection = formulaire.dataset.electionNom || '';
        const cheminPhoto = formulaire.dataset.candidatPhoto || '';

        titre.textContent = nomCandidat;
        election.textContent = nomElection ? `${textes.confirmationVoteElection} : ${nomElection}` : '';
        image.alt = nomCandidat;

        if (cheminPhoto !== '') {
            image.src = cheminPhoto;
            image.hidden = false;
            initiales.hidden = true;
            initiales.textContent = '';
        } else {
            image.hidden = true;
            image.removeAttribute('src');
            initiales.hidden = false;
            initiales.textContent = formulaire.dataset.candidatInitiales || nomCandidat.slice(0, 2).toUpperCase();
        }

        modale.hidden = false;
        modale.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modale-vote-ouverte');

        window.requestAnimationFrame(() => {
            modale.classList.add('visible');
            boutonConfirmer.focus();
        });
    };

    formulaires.forEach((formulaire) => {
        formulaire.addEventListener('submit', (evenement) => {
            if (formulaire.dataset.voteConfirme === '1') {
                return;
            }

            evenement.preventDefault();

            if (formulaire.classList.contains('vote-expire')) {
                return;
            }

            ouvrir(formulaire);
        });
    });

    boutonAnnuler.addEventListener('click', fermer);

    boutonConfirmer.addEventListener('click', () => {
        if (!formulaireCible) {
            return;
        }

        boutonConfirmer.disabled = true;
        boutonConfirmer.textContent = textes.confirmationVoteEnvoi;
        formulaireCible.dataset.voteConfirme = '1';
        formulaireCible.requestSubmit();
    });

    modale.addEventListener('click', (evenement) => {
        if (evenement.target === modale) {
            fermer();
        }
    });

    document.addEventListener('keydown', (evenement) => {
        if (evenement.key === 'Escape' && !modale.hidden) {
            fermer();
        }
    });
}

function preparerRevelationResultats() {
    const textes = textesInterface();
    const blocs = document.querySelectorAll('[data-resultats-revelation]');

    if (!blocs.length) {
        return;
    }

    const etapes = [
        textes.verificationBulletins,
        textes.controlePublication,
        textes.validationClassement,
        textes.preparationPhoto,
        textes.annonceVainqueur,
    ];

    blocs.forEach((bloc, index) => {
        const texte = bloc.querySelector('[data-texte-suspense]');
        const rideau = bloc.querySelector('[data-rideau-resultats]');

        if (!rideau || !texte) {
            return;
        }

        bloc.classList.add('resultats-en-revelation');

        let etape = 0;
        const intervalle = setInterval(() => {
            etape = Math.min(etape + 1, etapes.length - 1);
            texte.textContent = etapes[etape];
        }, 760);

        window.setTimeout(() => {
            clearInterval(intervalle);
            texte.textContent = textes.resultatsPublies;
            bloc.classList.add('resultats-reveles');
            lancerPaillettes(bloc);

            window.setTimeout(() => {
                rideau.hidden = true;
            }, 760);
        }, 4200 + (index * 320));
    });
}

function lancerPaillettes(conteneur) {
    const zone = document.createElement('div');
    zone.className = 'paillettes-resultats';
    zone.setAttribute('aria-hidden', 'true');

    const couleurs = ['#2f4f46', '#76523b', '#c8a96a', '#fffdf9'];
    const total = 56;

    for (let index = 0; index < total; index += 1) {
        const particule = document.createElement('span');
        const gauche = Math.random() * 100;
        const delai = Math.random() * 0.45;
        const duree = 1.6 + Math.random() * 1.4;
        const taille = 5 + Math.random() * 8;

        particule.style.left = gauche + '%';
        particule.style.animationDelay = delai + 's';
        particule.style.animationDuration = duree + 's';
        particule.style.width = taille + 'px';
        particule.style.height = Math.max(4, taille * 0.52) + 'px';
        particule.style.background = couleurs[index % couleurs.length];
        particule.style.transform = 'rotate(' + Math.round(Math.random() * 180) + 'deg)';
        zone.appendChild(particule);
    }

    conteneur.appendChild(zone);

    window.setTimeout(() => {
        zone.remove();
    }, 3600);
}

function preparerElectionCandidat() {
    const textes = textesInterface();

    document.querySelectorAll('[data-election-candidat]').forEach((selectElection) => {
        const formulaire = selectElection.closest('form');

        if (!formulaire) {
            return;
        }

        const selectFaculte = formulaire.querySelector('[name="faculte_id"]');
        const selectPromotion = formulaire.querySelector('[name="promotion_id"]');
        const selectDepartement = formulaire.querySelector('[name="departement_id"]');
        const aide = formulaire.querySelector('[data-aide-election-candidat]');

        const cacherSelonValeur = (select, valeur) => {
            if (!select) {
                return;
            }

            Array.from(select.options).forEach((option) => {
                option.hidden = option.value !== '' && valeur !== '' && option.value !== valeur;
            });

            if (valeur !== '') {
                select.value = valeur;
            } else if (select.selectedOptions[0]?.hidden) {
                select.value = '';
            }
        };

        const cacherSelonFaculte = (select, faculteId, valeurFixe = '') => {
            if (!select) {
                return;
            }

            Array.from(select.options).forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }

                const mauvaiseFaculte = faculteId !== '' && option.dataset.faculteId !== faculteId;
                const mauvaiseValeur = valeurFixe !== '' && option.value !== valeurFixe;
                option.hidden = mauvaiseFaculte || mauvaiseValeur;
            });

            if (valeurFixe !== '') {
                select.value = valeurFixe;
            } else if (select.selectedOptions[0]?.hidden) {
                select.value = '';
            }
        };

        const appliquerPortee = () => {
            const option = selectElection.options[selectElection.selectedIndex];
            const portee = option?.dataset.porteeType || '';
            const faculteId = option?.dataset.faculteId || '';
            const promotionId = option?.dataset.promotionId || '';
            const departementId = option?.dataset.departementId || '';

            cacherSelonValeur(selectFaculte, faculteId);

            if (selectFaculte) {
                selectFaculte.dispatchEvent(new Event('change'));
            }

            cacherSelonFaculte(selectPromotion, faculteId, promotionId);
            cacherSelonFaculte(selectDepartement, faculteId, departementId);

            if (!aide) {
                return;
            }

            if (portee === 'universite') {
                aide.textContent = textes.candidatUniversite;
            } else if (portee === 'faculte') {
                aide.textContent = textes.candidatFaculte;
            } else if (portee === 'promotion') {
                aide.textContent = textes.candidatPromotion;
            } else if (portee === 'departement') {
                aide.textContent = textes.candidatDepartement;
            } else {
                aide.textContent = textes.candidatDefaut;
            }
        };

        selectElection.addEventListener('change', appliquerPortee);
        appliquerPortee();
    });
}

function preparerChampsMajuscules() {
    const champs = document.querySelectorAll('[data-majuscules]');

    champs.forEach((champ) => {
        champ.classList.add('champ-majuscules');

        champ.addEventListener('input', () => {
            const debut = champ.selectionStart;
            const fin = champ.selectionEnd;
            champ.value = champ.value.toLocaleUpperCase('fr-FR');

            if (typeof debut === 'number' && typeof fin === 'number') {
                champ.setSelectionRange(debut, fin);
            }
        });
    });

    document.querySelectorAll('form').forEach((formulaire) => {
        formulaire.addEventListener('submit', () => {
            formulaire.querySelectorAll('[data-majuscules]').forEach((champ) => {
                champ.value = champ.value.toLocaleUpperCase('fr-FR').trim();
            });
        });
    });
}

function preparerListesAcademiques() {
    document.querySelectorAll('[data-select-faculte]').forEach((selectFaculte) => {
        const formulaire = selectFaculte.closest('form');

        if (!formulaire) {
            return;
        }

        const champsFiltres = formulaire.querySelectorAll('[data-faculte-cible]');

        const synchroniser = () => {
            champsFiltres.forEach((select) => {
                let optionSelectionneeVisible = true;

                Array.from(select.options).forEach((option) => {
                    if (option.value === '') {
                        option.hidden = false;
                        return;
                    }

                    const visible = selectFaculte.value === '' || option.dataset.faculteId === selectFaculte.value;
                    option.hidden = !visible;

                    if (option.selected && !visible) {
                        optionSelectionneeVisible = false;
                    }
                });

                if (!optionSelectionneeVisible) {
                    select.value = '';
                }
            });
        };

        selectFaculte.addEventListener('change', synchroniser);
        synchroniser();
    });
}

# Configuration Emails - VOTE UPC ONLINE

Les emails servent a envoyer:

- le mot de passe temporaire d'un etudiant active par un appariteur;
- les codes de securite a 6 chiffres;
- les messages de mot de passe oublie;
- les notifications importantes du systeme.

## Regle de securite

Ne jamais mettre un vrai mot de passe email directement dans le code PHP.

Les secrets doivent rester dans le fichier `.env`, qui reste sur la machine ou sur le serveur.

Ne partage pas:

- ton mot de passe Gmail;
- un code de verification a 6 chiffres recu par SMS ou Gmail;
- le mot de passe d'application SMTP.

## Configuration Gmail SMTP

Pour Gmail ou Google Workspace:

```text
MAIL_TRANSPORT=smtp
MAIL_HOTE=smtp.gmail.com
MAIL_PORT=587
MAIL_CHIFFREMENT=tls
MAIL_UTILISATEUR=voteupc@gmail.com
MAIL_MOT_DE_PASSE=mot-de-passe-application-sans-espaces
MAIL_ADRESSE_EXPEDITEUR=voteupc@gmail.com
MAIL_NOM_EXPEDITEUR="VOTE UPC ONLINE"
MAIL_REPONSE_A=voteupc@gmail.com
```

Alternative possible:

```text
MAIL_PORT=465
MAIL_CHIFFREMENT=ssl
```

## Mot de passe d'application

Avec Gmail, il ne faut pas utiliser le mot de passe normal du compte dans l'application. Il faut utiliser un mot de passe d'application SMTP.

Le code de verification a 6 chiffres sert a confirmer ton identite chez Google. Il ne doit pas etre colle dans le projet.

## Configuration professionnelle finale

Au lancement public, l'ideal sera d'utiliser une adresse du domaine officiel:

```text
noreply@vote-upc-online.cd
```

Dans ce cas, l'hebergeur ou le fournisseur email donnera:

- le serveur SMTP;
- le port;
- le chiffrement;
- l'adresse email;
- le mot de passe SMTP.

## Codes a 6 chiffres dans VOTE UPC ONLINE

Les codes a 6 chiffres de VOTE UPC ONLINE seront generes par le systeme avec `random_int`.

Ils serviront pour:

- mot de passe oublie;
- verification email;
- operation sensible.

Ils seront stockes sous forme de hash dans la base de donnees, pas en texte clair.

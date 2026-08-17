# Deploiement Render - VOTE UPC ONLINE

Ce projet est une application PHP + PostgreSQL. Le deploiement Render le plus simple passe par Docker et un Blueprint `render.yaml`.

## Etapes

1. Pousse ce dossier vers GitHub : `https://github.com/vainqueurkalonji/vote-upc-online.git`.
2. Va sur https://dashboard.render.com.
3. Clique sur `New +`, puis `Blueprint`.
4. Connecte ton depot GitHub.
5. Render detecte le fichier `render.yaml` et propose un Web Service Docker gratuit avec une base PostgreSQL gratuite.
6. Valide la creation.
7. A la fin du premier deploiement, ouvre l'URL `https://...onrender.com/installation/super-administrateur`.
8. Cree le premier super administrateur.

## Ce que le blueprint fait deja

- Lance PHP 8.3 avec Apache.
- Pointe Apache vers `public`.
- Installe les extensions PHP necessaires a PostgreSQL et Excel.
- Execute les migrations SQL avant le demarrage.
- Charge les facultes et promotions initiales.
- Connecte l'application a la base Render via `DATABASE_URL`.

## Emails

Render Free bloque les ports SMTP classiques `25`, `465` et `587`. Gmail SMTP ne fonctionnera donc pas correctement sur l'offre gratuite.

Pour une demo, tu peux deployer sans email fonctionnel. Pour un usage reel avec OTP/email, prevois un service d'email avec API HTTP ou un plan permettant l'envoi SMTP.

## Limites de Render Free

- Le service peut s'endormir apres inactivite et mettre environ une minute a se reveiller.
- La base PostgreSQL gratuite Render expire apres 30 jours.
- Ce n'est pas adapte a une election officielle en production.

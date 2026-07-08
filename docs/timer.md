# Fonctionnement du Timer dans LC Bingo

Le timer dans LC Bingo est un système de chronométrage qui permet de suivre le temps restant d'une partie de bingo, avec des fonctionnalités de pause et de reprise. Voici une explication détaillée de son fonctionnement :

## Composants principaux

Le système de timer est composé de plusieurs éléments qui travaillent ensemble :

1. **TimerController (Frontend)** : Un contrôleur Stimulus qui gère l'affichage et les interactions utilisateur avec le timer.
2. **TimerStateController (Frontend)** : Un contrôleur qui écoute les mises à jour en temps réel via Mercure.
3. **GameTimerService (Backend)** : Un service PHP qui calcule et formate le temps restant.
4. **GameTimer (Composant Twig)** : Un composant Live Component qui affiche le timer dans l'interface.

## Fonctionnalités du Timer

### Durée de jeu
- Le timer est configuré pour une durée de jeu de **1 heure** (3600 secondes).
- Il affiche le temps au format `HH:MM:SS` (heures:minutes:secondes).

### États du Timer
Le timer peut être dans différents états :
- **Non démarré** : Affiche "01:00:00" (1 heure).
- **En cours** : Décompte le temps restant.
- **En pause** : Arrête le décompte et affiche un badge "PAUSE".
- **Terminé** : Affiche "00:00:00" quand le temps est écoulé.

### Calcul du temps restant
Le timer calcule le temps restant en :
1. Prenant le temps écoulé depuis le début de la partie.
2. Soustrayant le temps total de pause accumulé.
3. Si la partie est actuellement en pause, soustrayant également le temps écoulé depuis la dernière mise en pause.
4. Calculant le temps restant en soustrayant ce temps écoulé ajusté de la durée totale (3600 secondes).

### Contrôles du Timer (pour les arbitres)
Les arbitres ont accès à deux boutons :
- **Bouton Pause** : Permet de mettre le jeu en pause.
- **Bouton Reprise** : Permet de reprendre le jeu après une pause.

## Synchronisation en temps réel

Le système utilise Mercure pour la synchronisation en temps réel :
1. Quand un arbitre met le jeu en pause ou le reprend, une requête est envoyée au serveur.
2. Le serveur met à jour l'état du jeu et diffuse les changements via Mercure.
3. Tous les clients connectés reçoivent ces mises à jour et actualisent leur affichage.

Cette architecture garantit que tous les joueurs voient le même temps restant, même après des pauses ou des reprises.

## Fonctionnement technique

1. **Initialisation** : Au chargement de la page, le timer s'initialise avec les valeurs du jeu (démarré, en pause, temps écoulé).
2. **Mise à jour périodique** : Le timer se met à jour toutes les secondes pour afficher le temps restant.
3. **Gestion des pauses** :
   - Quand le jeu est mis en pause, le serveur enregistre le moment de la pause.
   - Quand le jeu est repris, le serveur calcule la durée de la pause et l'ajoute au total des pauses.
4. **Synchronisation** : Les changements d'état sont diffusés à tous les clients via Mercure pour garantir une expérience cohérente.

Ce système permet une gestion précise du temps de jeu, avec la possibilité pour les arbitres de contrôler le déroulement de la partie en la mettant en pause si nécessaire.

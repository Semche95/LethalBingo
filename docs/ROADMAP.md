# Roadmap

## Fonctionnalités terminées

### Gestion des parties

- [x] Ajouter une date où la case a été dévoilée dans la table `bingo_square_revealed`
- [x] Ajouter les coordonnées des cases dans la liste des étapes
- [x] Enlever les étapes quand les cases sont validées
- [x] À la fin du bingo, dévoiler toutes les cases et les laisser affichées
- [x] Pouvoir dévoiler des cases avant de démarrer le bingo (non visibles pour les joueurs tant que le bingo n'est pas démarré)
- [x] Ajouter un bouton permettant de régénérer le lien unique d'une partie

### Dashboard arbitre

- [x] Ajouter une zone de texte pour prendre des notes
- [x] Ajouter le bouton pour masquer les cases (état `disabled`) lorsqu'elles ne peuvent pas être masquées
- [x] Inverser l'état des boutons visible/invisible pendant la partie
- [x] Déplacer le bouton de réinitialisation à un endroit moins visible
- [x] Ajouter une confirmation pour les boutons démarrer et arrêter (mise en pause du timer)

### Timer synchronisé

- [x] Ajouter un timer synchronisé avec l'heure de validation ou de dévoilement des cases
  - [x] Mettre en pause et relancer le timer
  - [x] Ajouter ou enlever du temps
  - [x] Changer la couleur lorsque le temps restant est faible
  - [x] Jouer un son à la fin du timer
- [x] Vérifier la synchronisation de l'heure du poste de l'arbitre avec celle du serveur afin d'éviter tout décalage du timer

### Interface et expérience utilisateur

- [x] Corriger le débordement visuel du bingo dans le tableau de bord sous Google Chrome
- [x] Vérifier les messages de confirmation ou d'erreur affichés après l'enregistrement dans le tableau de bord
- [x] Ajouter un GIF Lethal Company sur chaque page d'erreur Symfony


---

## Fonctionnalités à venir

### Historique et suivi des parties

- [ ] Ajouter une fonctionnalité permettant de marquer un temps fort pendant la partie
- [ ] Conserver la date de chaque action effectuée sur une case :
  - révélation
  - masquage
  - validation
  - invalidation
- [ ] Afficher l'historique des actions à droite du bingo avec les temps forts

### Règles et scoring

- [ ] Permettre la saisie des règles dans le tableau de bord
- [ ] Permettre le choix des règles propres à chaque partie
- [ ] Ajouter un rappel des règles sur la page de la partie
- [ ] Implémenter le calcul automatique des points selon les règles sélectionnées

### Timer avancé

- [ ] Permettre l'ajout et la suppression de temps personnalisés
- [ ] Permettre la saisie d'une durée spécifique
- [ ] Permettre la configuration de la durée du timer avant le démarrage de la partie
  - durée par défaut : 1h30
  - possibilité de désactiver complètement le timer
- [ ] Ajouter un son déclenché au début de la partie

### Préparation des parties

- [ ] Pré-remplir les cases des bingos depuis un fichier Excel avec des migrations Symfony
- [ ] Permettre l'affichage des cases visibles du bingo avant le début de la partie (hors cases cachées)

### Contenu et améliorations

- [ ] Générer un aperçu des cases validées pour la vidéo de fin
- [ ] Étudier la faisabilité d'ajouter une boîte à sons


---

## Règles de scoring à définir

- Nombre de jours maximum : multiplicateur x100 ?
- Argent maximum sur le terminal : multiplicateur x1 ?
- Temps restant : 20 points par minute restante

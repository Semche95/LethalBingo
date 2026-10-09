---
id: TASK-39
title: Ajouter un mode démo pour présenter l'application sans enregistrer de données
status: To Do
assignee: []
created_date: '2026-10-09 23:03'
labels:
  - preparation
  - ui
dependencies: []
priority: medium
type: feature
ordinal: 43000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Pour présenter LethalBingo (démonstration, captures, prise en main), il faut un mode démo dans lequel toutes les actions de l'application restent faisables, côté arbitre comme côté joueur : créer ou modifier des parties, équipes et bingos, démarrer et arrêter, dévoiler ou valider des cases, etc. Aucune de ces actions ne doit être enregistrée en base de données : la démo laisse les données intactes. Le visiteur doit néanmoins voir exactement ce qu'il verrait en usage réel, en particulier les messages de confirmation, les modales et les messages d'erreur (validation de formulaire, action refusée selon l'état de la partie). Reste à définir comment on entre dans ce mode (compte démo, URL dédiée, variable d'environnement) et comment les changements sont simulés le temps de la session.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Un mode démo est accessible et clairement signalé dans l'interface
- [ ] #2 Toutes les actions arbitre et joueur sont réalisables en mode démo
- [ ] #3 Aucune action effectuée en mode démo n'écrit en base de données
- [ ] #4 Les confirmations, modales et messages d'erreur s'affichent en mode démo comme en usage réel
<!-- AC:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Toute nouvelle classe de `src/Service/`, `src/Controller/` ou `src/Repository/` est accompagnée de son test
- [ ] #2 Tout changement d'entité est accompagné de sa migration et des fixtures mises à jour
- [ ] #3 `just test` passe
- [ ] #4 `just sf lint:container` passe
- [ ] #5 `just phpstan` passe
- [ ] #6 `just cs` passe
- [ ] #7 `just rector` passe
<!-- DOD:END -->

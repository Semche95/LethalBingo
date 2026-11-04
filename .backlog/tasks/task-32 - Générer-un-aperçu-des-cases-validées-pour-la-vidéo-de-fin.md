---
id: TASK-32
title: Générer un aperçu des cases validées pour la vidéo de fin
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-04T14:31:00Z'
labels:
  - contenu
dependencies: []
priority: medium
type: feature
ordinal: 36000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Une vidéo est montée à la fin de l'événement et doit montrer le résultat de chaque partie. L'application doit générer un aperçu de la grille d'une partie terminée qui met en évidence les cases validées, prêt à être intégré au montage.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Une partie terminée dispose d'un aperçu de sa grille qui distingue les cases validées
- [ ] #2 L'aperçu peut être téléchargé sous forme d'image
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

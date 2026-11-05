---
id: TASK-42
title: Signaler une erreur quand un son du timer est manquant
status: To Do
assignee: []
created_date: '2026-11-05T14:49:00Z'
labels:
  - timer
dependencies: []
priority: low
type: bug
ordinal: 46000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Quand un son d'alerte du timer (bip des seuils ou son de fin) est introuvable ou illisible, le timer l'ignore aujourd'hui sans rien signaler : l'absence de son passe inaperçue jusqu'au jour de la partie. La spécification du timer exige qu'un son manquant déclenche une erreur, sans pour autant interrompre le timer ni la partie, le son n'étant pas critique.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Un son d'alerte introuvable ou illisible déclenche une erreur visible au lieu d'être ignoré en silence
- [ ] #2 Malgré cette erreur, le décompte, la couleur des seuils et les autres sons continuent de fonctionner
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

---
id: TASK-38
title: Ajouter une page d'administration des comptes
status: To Do
assignee: []
created_date: '2026-10-09 21:10'
updated_date: '2026-10-09 21:51'
labels:
  - comptes
dependencies: []
priority: medium
type: feature
ordinal: 42000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les comptes arbitres ne se gèrent pas depuis l'application. Une page réservée aux administrateurs, distincte du profil, doit permettre de créer un compte arbitre, de changer le rôle d'un compte et de réinitialiser son mot de passe.

**Renvois**
- TASK-37 : Restreindre la visibilité des équipes, bingos et parties à des groupes d'arbitres
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 La page n'est accessible qu'aux administrateurs
- [ ] #2 Un administrateur peut créer un compte arbitre
- [ ] #3 Un administrateur peut changer le rôle d'un compte
- [ ] #4 Un administrateur peut réinitialiser le mot de passe d'un compte
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

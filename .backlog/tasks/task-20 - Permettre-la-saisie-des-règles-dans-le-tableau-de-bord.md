---
id: TASK-20
title: Permettre la saisie des règles dans le tableau de bord
status: To Do
assignee: []
created_date: 2026-09-30 16:16
updated_date: '2026-11-05T19:56:00Z'
labels:
  - regles-scoring
dependencies: []
documentation:
  - .backlog/docs/doc-1 - Règles-de-scoring-à-définir.md
priority: medium
type: feature
ordinal: 24000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Les règles de scoring ne sont aujourd'hui recensées que dans le document « Règles de scoring à définir » : l'application ne les connaît pas. Les arbitres doivent pouvoir saisir et maintenir la liste des règles de scoring depuis leur tableau de bord, pour ensuite choisir celles qui s'appliquent à chaque partie.

Chaque règle se compose d'un libellé, du critère mesuré (par exemple le nombre de jours, l'argent sur le terminal ou le temps restant) et de son barème (par exemple 20 points par minute restante).

Une règle choisie pour une partie en cours ou terminée ne peut plus être modifiée ni supprimée.

Les règles affichées sur la page d'accueil forment un code de conduite des joueurs, sans rapport avec le scoring : cette tâche ne les concerne pas.

**Renvois**
- TASK-21 : Permettre le choix des règles propres à chaque partie
- TASK-43 : Archiver toutes les informations liées à une partie terminée
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [ ] #1 Le tableau de bord de l'arbitre liste les règles existantes
- [ ] #2 Un arbitre peut créer une règle
- [ ] #3 Un arbitre peut modifier une règle
- [ ] #4 Un arbitre peut supprimer une règle
- [ ] #5 Le formulaire d'une règle permet de saisir son libellé, le critère mesuré et son barème
- [ ] #6 La modification et la suppression d'une règle sont bloquées si elle est choisie pour une partie en cours ou terminée
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

---
name: lethalbingo-entity-change
description: Enchaîne ce qu'impose un changement d'entité Doctrine dans LethalBingo (migration, fixtures, rechargement de la base, tests). À utiliser dès qu'un fichier de src/Entity/ est créé ou modifié.
---

# Changement d'entité

1. Entité via attributs ORM ; nouvelle entité : `just sf make:entity <Nom> --no-interaction` puis compléter à la main.
2. Migration : `just sf doctrine:migrations:diff`, relire le fichier généré dans `migrations/`, puis `just migrate`.
3. Fixtures : mettre à jour `src/DataFixtures/` pour couvrir le nouveau champ ou la nouvelle entité, puis `just fixtures`.
4. Repository : DQL uniquement, résultats bruts ; nouvelle classe = test dans `tests/Repository/`.
5. `just sf doctrine:schema:validate`, puis skill `lethalbingo-checks`.

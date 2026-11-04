---
paths:
  - "src/Entity/**"
  - "src/Repository/**"
  - "src/DataFixtures/**"
  - "migrations/**"
---

# Base de données

- Doctrine ORM, PostgreSQL 18, migrations dans `migrations/`.
- Après chaque changement d'Entity : `just sf doctrine:migrations:diff`, puis mettre à jour `src/DataFixtures/`.

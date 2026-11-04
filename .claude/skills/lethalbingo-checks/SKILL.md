---
name: lethalbingo-checks
description: Lance les vérifications de LethalBingo comme la CI GitHub (PHPUnit, lint:container, PHPStan, PHP-CS-Fixer, Rector, Vitest) et résume le résultat. À utiliser avant chaque commit, pour terminer une tâche ou quand la CI échoue.
---

# Vérifications LethalBingo

1. Conteneurs démarrés : `docker compose ps` ; sinon `just up`.
2. Vider le cache de test comme sur GitHub : `docker compose exec -T php rm -rf var/cache/test`.
3. Lancer en une commande, en ne gardant que la fin de chaque sortie :

```bash
(just test 2>&1 | tail -4); echo "=== lint"; (just sf lint:container 2>&1 | tail -2); \
echo "=== phpstan"; (just phpstan 2>&1 | tail -3); echo "=== cs"; (just cs 2>&1 | tail -3); \
echo "=== rector"; (just rector 2>&1 | tail -3); echo "=== vitest"; (pnpm test 2>&1 | tail -4)
```

4. Échec : relancer seulement l'outil en échec avec sa sortie complète, corriger, puis relancer les 6.
   - Style : `just cs-fix` ; Rector : `just rector-fix`, puis relire le diff (une règle contraire au style du projet se désactive dans `rector.php` avec un commentaire).
5. Rapporter : un mot par outil (OK / échec + cause).

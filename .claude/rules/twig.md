---
paths:
  - "templates/**"
  - "src/Twig/**"
  - "src/Form/**"
---

# Twig

- Pas de `style="..."` : classes Bootstrap/Tabler ou classes dédiées dans `app.css`.
- Macros partagées de `macros.html.twig` ; templates des Live Components dans `templates/components/`.
- Fonctions `form_*` plutôt que des champs écrits à la main.
- **Formulaires (Auto-Validating Form)** : `ComponentWithFormTrait` + `DefaultActionTrait` ; `instantiateForm()` crée le formulaire, `hasValidationErrors()` teste l'état, `save()` appelle `submitForm()` puis traite. Les contraintes Assert PHP sont la seule validation (pas de doublon JS). LiveProp `fieldName: 'formData'` pour l'entité, `writable: true` pour les champs modifiables. Validation `data-model="on(change)|*"` (au change, pas à la frappe).

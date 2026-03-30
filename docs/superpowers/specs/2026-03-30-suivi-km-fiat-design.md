# Spec : Suivi Kilométrique Fiat 500e — Contrat LOA

**Date :** 2026-03-30
**Stack :** HTML/JS (vanilla) + PHP + JSON
**Cible :** Synology NAS (Web Station + PHP)

---

## Contexte

Application web de suivi kilométrique pour un contrat LOA Fiat 500e. Permet de saisir le kilométrage de temps en temps et de visualiser l'évolution par rapport au forfait contractuel.

---

## Paramètres LOA pré-remplis

| Paramètre | Valeur |
|-----------|--------|
| Véhicule | Fiat 500e (AM 2022) |
| Date début contrat | 2025-08-06 |
| Km au départ | 7 462 km |
| Durée | 50 mois |
| Km total autorisé | 41 167 km |
| Forfait mensuel | 823,34 km/mois |
| Km fin de contrat autorisés | 48 629 km (7 462 + 41 167) |
| Date fin de contrat | 2029-10-06 |

Ces valeurs sont modifiables depuis l'interface.

---

## Architecture

```
suivi-km-fiat/
├── index.html        — SPA vanilla JS (toute l'interface)
├── api.php           — API REST PHP (CRUD entrées + config LOA)
├── data/
│   └── data.json     — stockage JSON (créé automatiquement au 1er appel)
└── README.md
```

### api.php — Endpoints

| Méthode | Action (`?action=`) | Description |
|---------|---------------------|-------------|
| GET | `entries` | Retourne toutes les saisies |
| POST | `add_entry` | Ajoute une saisie |
| POST | `delete_entry` | Supprime une saisie par id |
| GET | `config` | Retourne la config LOA |
| POST | `update_config` | Met à jour la config LOA |

### data.json — Structure

```json
{
  "config": {
    "vehicle": "Fiat 500e (AM 2022)",
    "startDate": "2025-08-06",
    "startKm": 7462,
    "durationMonths": 50,
    "totalKm": 41167
  },
  "entries": [
    { "id": 1, "date": "2025-10-09", "km": 9940, "label": "Chrono 2" }
  ]
}
```

---

## Interface (index.html)

### Zone 1 — Dashboard

Calculé dynamiquement en JS à partir des entrées et de la config LOA.

- **Km actuels** : km de la dernière entrée
- **Km autorisés à ce jour** : `startKm + (moisÉcoulés × forfaitMensuel)`
- **Écart** : `kmActuels - kmAutorisésCeJour` — vert si négatif (en avance), rouge si positif (dépassement)
- **Projection fin contrat** : `kmActuels + (moisRestants × rythmeActuelMensuel)`
- **Barre de progression** : km parcourus / km total autorisé (en %)
- **Résumé texte** : "À ce jour vous avez parcouru X km sur Y autorisés (écart : ±Z km)"

### Zone 2 — Saisie

- Champ date (valeur par défaut : aujourd'hui)
- Champ km compteur (numérique, requis)
- Champ libellé (texte, optionnel — ex: "Chrono 2")
- Bouton Enregistrer → POST `add_entry` → rafraîchit le dashboard et l'historique

### Zone 3 — Historique

Tableau trié par date décroissante :

| Date | Km | Delta km | Écart LOA | Libellé | Action |
|------|----|----------|-----------|---------|--------|
| 09/10/2025 | 9 940 | +2 478 | +749 | Chrono 2 | 🗑 |

- **Delta km** : km depuis l'entrée précédente
- **Écart LOA** : km à cette date − km autorisés à cette date
- Bouton supprimer par ligne

### Zone 4 — Paramètres LOA

Formulaire éditable avec les 5 champs de config. Bouton Enregistrer → POST `update_config`.

---

## Calculs clés

```
moisÉcoulés          = (dateAujourdhui - dateDebut) en mois décimaux
kmAutorisésCeJour    = startKm + moisÉcoulés × (totalKm / durationMonths)
écart                = kmActuels - kmAutorisésCeJour
rythmeActuelMensuel  = (kmActuels - startKm) / moisÉcoulés
moisRestants         = durationMonths - moisÉcoulés
projectionFin        = kmActuels + moisRestants × rythmeActuelMensuel
```

---

## Comportements attendus

- `data.json` créé automatiquement avec la config LOA par défaut si absent
- Entrées triées par date dans le stockage
- Pas de dépendance externe (zéro CDN, zéro npm) — tout en vanilla
- Responsive (utilisable depuis smartphone sur le réseau local)
- Pas d'authentification (usage privé réseau local)

---

## Hors périmètre

- Graphiques (évolution sur courbe) — peut être ajouté plus tard
- Export CSV
- Notifications / alertes push
- Multi-utilisateurs

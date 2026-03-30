# 🚗 Suivi Kilométrique Fiat 500e — Contrat LOA

Application web de suivi kilométrique. Stack : PHP + vanilla JS. Stockage : data/data.json.

## Déploiement sur Synology NAS (Web Station)

1. Copier tous les fichiers dans le dossier web du NAS (ex: `web/suivi-km-fiat/`)
2. Accéder via `http://[IP-NAS]/suivi-km-fiat/`
3. S'assurer que le dossier data/ est accessible en écriture par le serveur web : chmod 755 data/

**PHP requis : version 7.4 minimum** (à configurer dans Web Station)

## Développement local (nécessite PHP CLI)

```bash
php -S localhost:8080
```
Puis ouvrir http://localhost:8080

## Données

Stockées dans `data/data.json` (créé automatiquement, exclu de git).

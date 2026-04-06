## Exercices — HTTP Caching & Varnish

### Architecture en place

```
Navigateur ──→ Varnish (:80, HTTP) ──→ Caddy/FrankenPHP ──→ Symfony
                 │                                              │
                 │  cache public (s-maxage)                     │  génère Cache-Control,
                 │  invalidation via BAN                        │  ETag, Cache-Tags
                 │  headers debug X-Cache: HIT/MISS             │
                 │                                              │
                 └── HTTPS (:443) direct sans Varnish (dev) ────┘
```

**Fichiers de config :**
- `api/varnish/default.vcl` — config Varnish (backend, BAN, debug headers)
- `api/config/packages/api_platform.yaml` — invalidation HTTP activée (`http_cache.invalidation`)
- `api/config/api_platform/*.yaml` — `cacheHeaders` par opération

**Tester le cache :**
```bash
# Via Varnish (HTTP) — observer X-Cache: HIT/MISS et Age
curl -s -D - -H "Accept: application/ld+json" http://localhost/courses | head -20

# Direct Caddy (HTTPS, bypass Varnish) — voir les headers bruts de Symfony
curl -sk -D - -H "Accept: application/ld+json" https://localhost/courses | head -20
```

### Concepts clés

| Header | Qui le respecte | Rôle |
|---|---|---|
| `max-age=N` | **Navigateur** (cache privé) | Le navigateur sert sa copie locale pendant N secondes |
| `s-maxage=N` | **Varnish** (cache public) | Varnish sert sa copie pendant N secondes sans appeler PHP |
| `public` | Varnish | Autorise le stockage dans un cache partagé |
| `private` | Navigateur uniquement | Interdit le stockage dans Varnish (données personnelles) |
| `ETag` | Navigateur + Varnish | Identifiant unique du contenu, permet la validation |
| `Cache-Tags` | Varnish (invalidation) | IRIs des ressources dans la réponse, pour cibler les purges |
| `Vary` | Varnish + Navigateur | Cache des versions différentes selon les headers (Accept, Authorization…) |
| `Age` | Varnish → Navigateur | Depuis combien de secondes l'objet est en cache |
| `stale-while-revalidate=N` | Varnish | Sert du cache périmé pendant N secondes le temps de revalider en arrière-plan |

**Cycle d'une requête cachée :**
```
1. GET /courses              → Varnish MISS → PHP → Response (s-maxage=3600) → Varnish stocke
2. GET /courses              → Varnish HIT (Age: 12s) → réponse directe, PHP jamais appelé
3. PATCH /courses/{uuid}     → PHP modifie → API Platform envoie BAN à Varnish → cache purgé
4. GET /courses              → Varnish MISS → PHP → nouvelles données fraîches
```

### Règles de cache par ressource

Le cours (`Course`) est déjà configuré comme exemple. Voici les règles à implémenter pour chaque ressource :

#### Ressources publiques — cache agressif

Données consultables par tous, rarement modifiées. Cache long dans Varnish, court dans le navigateur.

| Ressource | Opération | `shared_max_age` | `max_age` | Justification |
|---|---|---|---|---|
| **Course** | `GetCollection` | 3600 (1h) | 60 (1min) | Catalogue consulté souvent, change peu |
| **Course** | `Get` | 3600 (1h) | 60 (1min) | Détail d'un cours, idem |
| **Instructor** | `GetCollection` | 3600 (1h) | 120 (2min) | Liste des formateurs, quasi statique |
| **Instructor** | `Get` | 3600 (1h) | 120 (2min) | Profil formateur, change rarement |
| **Module** | `GetCollection` | 1800 (30min) | 60 (1min) | Liste des chapitres, modifiée lors de la création de cours |
| **Module** | `Get` | 1800 (30min) | 60 (1min) | Contenu d'un chapitre |
| **Review** | `GetCollection` | 600 (10min) | 30 (30s) | Avis, plus volatils (nouveaux avis fréquents) |
| **Review** | `Get` | 600 (10min) | 30 (30s) | Détail d'un avis |

> L'invalidation via BAN purge automatiquement le cache Varnish quand une ressource est modifiée.
> Le `shared_max_age` est donc un "filet de sécurité" — en pratique, le cache est purgé bien avant expiration.

#### Ressources privées — pas de cache partagé

Données personnelles (inscriptions, progression, prix payé). **Varnish ne doit jamais les stocker.**

| Ressource | Opération | Cache | Justification |
|---|---|---|---|
| **Enrollment** | `GetCollection` | `private`, `max_age: 0` | Données par étudiant, contient le prix payé et la progression |
| **Enrollment** | `Patch` | Pas de cache (écriture) | — |
| **Student** | `GetCollection` | `private`, `max_age: 0` | Liste d'étudiants, données personnelles (email) |
| **Student** | `Get` | `private`, `max_age: 60` | Profil étudiant, le navigateur peut garder 1 min |

Pour marquer une ressource comme **privée**, utilise `cacheHeaders` sans `shared_max_age` mais avec `max_age: 0` :

```yaml
# Exemple pour enrollment
cacheHeaders:
    max_age: 0       # Le navigateur re-demande à chaque fois
                     # Pas de shared_max_age → Symfony envoie "private"
                     # → Varnish ne stocke pas
```

> **Attention :** quand `shared_max_age` est défini, API Platform ajoute automatiquement `public`.
> Quand il est absent et que seul `max_age` est présent, la réponse reste `private`.

### Exercices

#### Exercice 1 — Cache public sur les ressources de lecture

Configure les `cacheHeaders` sur **toutes les opérations GET** des ressources publiques selon le tableau ci-dessus.

**Fichiers à modifier :** `config/api_platform/instructor.yaml`, `module.yaml`, `review.yaml`

**Vérification :**
```bash
# Chaque ressource publique doit afficher s-maxage et public
curl -s -D - -H "Accept: application/ld+json" http://localhost/instructors | grep "Cache-Control"
# Attendu : Cache-Control: max-age=120, public, s-maxage=3600

curl -s -D - -H "Accept: application/ld+json" http://localhost/courses/{courseUuid}/reviews | grep "Cache-Control"
# Attendu : Cache-Control: max-age=30, public, s-maxage=600
```

---

#### Exercice 2 — Cache privé sur les ressources sensibles

Configure `Enrollment` et `Student` pour que Varnish **ne cache jamais** ces réponses.

**Fichiers à modifier :** `config/api_platform/enrollment.yaml`, `student.yaml`

**Vérification :**
```bash
# Enrollment : doit être private, pas de s-maxage
curl -s -D - -H "Accept: application/ld+json" http://localhost/courses/{courseUuid}/enrollments | grep "Cache-Control"
# Attendu : Cache-Control: max-age=0, must-revalidate, private

# Via Varnish : doit toujours être MISS (jamais en cache)
curl -s -D - -H "Accept: application/ld+json" http://localhost/courses/{courseUuid}/enrollments | grep "X-Cache"
curl -s -D - -H "Accept: application/ld+json" http://localhost/courses/{courseUuid}/enrollments | grep "X-Cache"
# Attendu : X-Cache: MISS les deux fois
```

---

#### Exercice 3 — Vérifier l'invalidation croisée

L'invalidation ne purge pas que la ressource modifiée — elle purge aussi les collections qui la contiennent.

**Scénario à tester :**
```bash
# 1. Charge la liste des cours dans le cache Varnish
curl -s -D - -H "Accept: application/ld+json" http://localhost/courses | grep "X-Cache"
# → MISS (premier appel)

# 2. Vérifie que c'est en cache
curl -s -D - -H "Accept: application/ld+json" http://localhost/courses | grep "X-Cache"
# → HIT

# 3. Modifie un instructeur (pas un cours !)
curl -s -X PATCH -H "Content-Type: application/merge-patch+json" -H "Accept: application/ld+json" \
  http://localhost/instructors/{uuid} -d '{"firstName":"Nouveau nom"}'

# 4. Re-demande les cours
curl -s -D - -H "Accept: application/ld+json" http://localhost/courses | grep "X-Cache"

# Question : est-ce un HIT ou un MISS ? Pourquoi ?
# Indice : regarde les Cache-Tags de la réponse /courses — que contiennent-ils ?
```

---

#### Exercice 4 — ETags et validation (304 Not Modified)

Les ETags sont déjà générés par Caddy (`ETag: W/"..."` dans les réponses). Le but est de comprendre le mécanisme de **validation** :

1. Fais un `GET /courses` et note la valeur du header `ETag`
2. Refais la même requête en ajoutant le header `If-None-Match` avec la valeur de l'ETag :
   ```bash
   curl -s -D - -H "Accept: application/ld+json" -H 'If-None-Match: W/"valeur-etag"' http://localhost/courses
   ```
3. **Observe :** est-ce que tu reçois un `304 Not Modified` ou un `200` ? Pourquoi ?
4. **Réflexion :** quelle est la différence entre la validation (ETag) et l'expiration (max-age) ?
    - Quand le navigateur utilise-t-il l'un vs l'autre ?
    - Est-ce que la validation économise du temps serveur autant que l'expiration ?

> **Indice :** Varnish répond avant PHP. Si l'objet est en cache, Varnish compare-t-il l'ETag
> ou renvoie-t-il directement la réponse cachée (avec son propre Age) ?

---

#### Exercice 5 — `stale-while-revalidate`

Ajoute `stale_while_revalidate: 60` sur `GetCollection` de `Course` :

```yaml
cacheHeaders:
    shared_max_age: 3600
    max_age: 60
    stale_while_revalidate: 60
```

**Test :**
1. Lis la doc HTTP sur `stale-while-revalidate` et explique ce que ça change
2. Dans quel scénario c'est utile par rapport à un `shared_max_age` seul ?
3. Vérifie que le header `Cache-Control` contient bien la directive

---

#### Exercice 6 — Vary et le piège du cache fragmenté

Le header `Vary` est configuré globalement sur `['Content-Type', 'Authorization', 'Origin']`.

**Expérience :**
```bash
# Requête en JSON-LD
curl -s -D - -H "Accept: application/ld+json" http://localhost/courses | grep "X-Cache"
# → MISS puis HIT

# Même URL, mais en JSON classique
curl -s -D - -H "Accept: application/json" http://localhost/courses | grep "X-Cache"
# → MISS ou HIT ?
```

**Questions :**
1. Pourquoi Varnish stocke-t-il deux versions de la même URL ?
2. Quel header de la réponse indique à Varnish de faire ça ?
3. Si tu ajoutes `Accept-Language` dans le `Vary`, combien de variantes Varnish pourrait-il stocker pour une seule URL ?
4. Pourquoi c'est dangereux d'ajouter trop de headers dans `Vary` ?

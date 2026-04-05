# Training API — Plateforme de formation en ligne

Projet d'apprentissage API Platform v4.2 / Symfony 7.4.

## Modèle de données

```
Instructor 1──N Course N──1 (via Enrollment) Student
                  │                              │
                  1──N Module                    │
                  1──N Review N──1 ──────────────┘
                  1──N Enrollment N──1 ──────────┘
```

- **Instructor** — formateur (nom, bio, spécialité)
- **Course** — cours (titre, description, niveau, prix en centimes, maxStudents, statut draft/published/archived)
- **Module** — chapitre d'un cours (position, titre, contenu)
- **Student** — étudiant (nom, email)
- **Enrollment** — inscription pivot (date, progression 0-100, note finale, statut)
- **Review** — avis (note 1-5, commentaire, date)

## Contexte d'exposition

L'API alimente un frontend SPA (Next.js) et une future app mobile. Trois niveaux d'accès :

| Ressource | Public (anonymous) | Authenticated (student/instructor) | Admin |
|---|---|---|---|
| Course | GET list, GET detail | idem | + POST, PATCH, DELETE |
| Module | GET (via course) | idem | + POST, PATCH, DELETE |
| Instructor | GET list, GET detail | idem | + POST, PATCH, DELETE |
| Student | — | GET /me | CRUD complet |
| Enrollment | — | POST, GET own, PATCH progress | GET all |
| Review | GET (via course) | + POST, PATCH own, DELETE own | tout |

Toute la configuration API Platform est en YAML dans `config/api_platform/` (un fichier par ressource).
Les groupes de serialization sont en YAML dans `config/serialization/`.
Les entités ne contiennent que Doctrine ORM + Validator.

## Plan de features à implémenter

### 0. Exposer les ressources (API Resource)
- [ ] Déclarer les ressources en YAML (`config/api_platform/*.yaml`) avec les bonnes opérations selon la matrice d'accès ci-dessus
- [ ] Configurer les `uriTemplate` custom : `/courses/{id}/modules`, `/courses/{id}/reviews`, `/courses/{id}/enroll`
- [ ] Maîtriser `ApiProperty` : `readable`, `writable`, `identifier`, `description`
- [ ] Gérer les relations : quand exposer un IRI vs un objet embarqué vs ne rien exposer
- [ ] Configurer la pagination par ressource (items par page, max items)

### 1. Serialization & Groupes
- [ ] Groupes par opération : `GET /courses` (résumé) vs `GET /courses/{id}` (détail + modules embarqués)
- [ ] Embedding vs IRI : modules embarqués dans un cours, instructeur en IRI
- [ ] Groupes dynamiques selon le rôle (admin voit le CA, étudiant non)
- [ ] Champs calculés : `averageRating`, `studentCount`, `completionRate`

### 2. Validation avancée
- [ ] Custom constraint : `Enrollment` interdit si `maxStudents` atteint
- [ ] Validation contextuelle : `Review` uniquement si l'étudiant est inscrit au cours
- [ ] Delete d'un cours interdit s'il a des inscriptions actives

### 3. Authentification & utilisateurs
- [ ] Entité `User` avec rôles (`ROLE_STUDENT`, `ROLE_INSTRUCTOR`, `ROLE_ADMIN`) — implémente `UserInterface`
- [ ] Lier `User` aux entités métier : un User peut être un Student et/ou un Instructor
- [ ] JWT auth avec `lexik/jwt-authentication-bundle` : login `POST /auth/token`, refresh token
- [ ] Endpoint `GET /me` pour récupérer le profil de l'utilisateur connecté
- [ ] Registration : `POST /auth/register` avec validation (email unique, mot de passe fort)
- [ ] Password hashing avec `PasswordHasherInterface`
- [ ] Gestion des rôles : un admin peut promouvoir un user en instructeur
- [ ] Fixtures : créer des users avec mots de passe hashés pour chaque rôle

### 4. Security & ownership
- [ ] `security` expressions : un instructeur ne modifie que ses cours
- [ ] Property-level security : seul un admin peut modifier le `price`
- [ ] `securityPostDenormalize` + `previous_object` : empêcher transfert d'ownership
- [ ] Doctrine Extension : un étudiant ne voit que ses propres enrollments

### 5. State Processors
- [ ] `EnrollmentProcessor` : vérifier places, calculer prix (promo), envoyer email — décoration du `persist_processor`
- [ ] `CoursePublishProcessor` : `POST /courses/{id}/publish` change le statut + notification Mercure

### 6. State Providers
- [ ] Custom Provider `GET /students/{id}/dashboard` : agrège progression, cours en cours, certificats

### 7. DTOs (input/output)
- [ ] Input DTO `CreateEnrollment` : reçoit `courseId` + `promoCode`, le processor résout le reste
- [ ] Output DTO `CourseStats` : stats agrégées (nb inscrits, note moyenne, revenus)
- [ ] Transformation prix : stocké en centimes, exposé formaté (`"49.99€"`)

### 8. Filtres & sous-ressources
- [ ] `SearchFilter` sur titre, `RangeFilter` sur prix, `OrderFilter` sur date/note
- [ ] Custom Doctrine Filter : "cours auxquels je suis inscrit"
- [ ] Subresources : `GET /courses/{id}/modules`, `GET /courses/{id}/reviews`

### 9. Performance
- [ ] Eager loading contrôlé (modules oui, reviews non)
- [ ] Pagination partielle sur collections volumineuses
- [ ] HTTP Cache tags : invalider cache cours quand review ajoutée
- [ ] `forceEager: false` sur relations lourdes

### 10. Messenger / CQRS
- [ ] `POST /courses/{id}/enroll` async avec `messenger: true, status: 202`
- [ ] Handler pour traitement lourd (paiement, email, webhook)

### 11. OpenAPI & documentation
- [ ] Enrichir doc OpenAPI avec exemples requêtes/réponses
- [ ] Descriptions métier sur les opérations

### 12. Cache & stratégies de caching
- [ ] HTTP Cache headers par ressource : `Cache-Control`, `max-age`, `public`/`private` selon le rôle
- [ ] ETags et `304 Not Modified` : éviter de re-sérialiser des données inchangées
- [ ] Cache invalidation avec Caddy/Souin : tags automatiques API Platform, purge au write
- [ ] Stratégie par ressource : cours publics (cache long, public), enrollments (private, short TTL), dashboard (no-cache)
- [ ] Varnish/Souin comme reverse proxy cache devant FrankenPHP
- [ ] Cache applicatif Symfony avec Redis : `cache.app` pour les résultats de requêtes lourdes (stats, agrégats)
- [ ] Doctrine result cache sur Redis : cacher les requêtes fréquentes (liste de cours publiés, top reviews)
- [ ] Shared invalidation : invalider le cache d'un cours quand une review/enrollment est créée (cross-resource tags)
- [ ] Comprendre `Vary` header : cacher différemment selon `Accept`, `Authorization` (user-specific vs public)
- [ ] Stale-while-revalidate : servir du cache périmé pendant le refresh en arrière-plan

## Stack technique

- PHP 8.4 / Symfony 7.4
- API Platform 4.2
- Doctrine ORM 3
- PostgreSQL 16
- FrankenPHP (Caddy)
- Zenstruck Foundry 2.9 (fixtures & tests)

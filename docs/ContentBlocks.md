# Blocs de contenu éditorial (Content Builder) — Spécification

> Statut : document de conception d'origine (organisation/réflexion), pas la documentation du système livré. Étape 2 (système de blocs, bloc Titre + Texte, commande `make:cms-block`) est implémentée — voir `docs/Blocks.md` pour la doc réelle. Ce fichier reste la référence pour l'étape 3 (autres blocs), non encore implémentée.

## Contexte

Sur une resource `Page`, la `PageTranslation` doit exposer un champ `content` permettant de composer la page à partir de blocs éditoriaux réutilisables (texte, galerie, slider, liste de liens, liste de cards, etc.), avec preview, ajout/suppression/édition, réordonnancement par glisser-déposer. L'architecture doit permettre :

- une extension facile (nouveaux blocs) sans toucher au coeur du package `laravel-cms`,
- la surcharge d'un bloc existant du package depuis un projet, en cohérence avec la règle du projet ("surcharger plutôt que modifier le package"),
- l'activation/désactivation d'un bloc, au niveau du package comme du projet,
- une cohérence avec l'architecture headless (Laravel + API Platform, frontend externe).

## Stockage : JSON, pas de tables normalisées

Le contenu est stocké en JSON sur `PageTranslation` (`content`, casté en array), sous la forme d'un tableau ordonné de blocs : chaque bloc a un identifiant client (uuid), un `type`, et ses `data`.

Raisons :

- Le projet étant headless, l'API sert ce JSON quasiment tel quel au frontend — pas de jointures/reconstruction nécessaires.
- Le schéma d'un bloc évolue librement (ajout/suppression de champs) sans migration de base de données.
- L'ordre des blocs est nativement porté par l'ordre du tableau.

Limite acceptée : pas d'intégrité référentielle native sur les champs qui pointent vers d'autres ressources (ex: un `media_id` dans un bloc galerie). Décision : ne pas construire de table d'index de suivi maintenant, mais typer dès la conception du schéma de champ les champs qui sont des "références" (et vers quel type de ressource, avec leur cardinalité simple/multiple). Cette métadonnée ne sert à rien d'autre pour l'instant, mais permet un rattrapage simple plus tard (backfill ciblé) si un besoin de protection à la suppression ou de recherche transverse apparaît, sans avoir à deviner après coup quels champs contenaient des références.

## Registre de types de blocs

Basé sur le même mécanisme déjà en place dans `LaravelCmsServiceProvider` pour les `ProviderInterface` (`tagClassesFromPath`) et pour la surcharge de classes via config (`ResourceResolver` / `gingerminds-cms.resources.*`).

- Chaque bloc = une classe respectant un contrat commun (clé unique, label, icône, schéma de champs ou formulaire custom, vue de preview admin).
- Découverte automatique par scan de `src/Blocks/**` dans le package **et** d'un dossier équivalent côté projet (ex: `app/Cms/Blocks/**`) — les deux sources alimentent le même registre. Un bloc peut donc exister uniquement côté projet (cas de la galerie média, qui dépend de `laravel-media-manager`, absent des dépendances du package cms).
- Surcharge d'un bloc existant du package : mapping config `gingerminds-cms.blocks.<key> => FQCN`, comme pour `resources.menu.model`. Le projet fournit une sous-classe qui redéfinit `fields()` pour ajouter/modifier/retirer un champ.
- Activation/désactivation : une liste (ex: `disabled_blocks`) fusionnée de façon additive entre la config du package et la config republiée du projet (`mergeConfigFrom` fait un merge peu profond — un remplacement complet du tableau de blocs serait un piège à éviter).
- Ordre d'affichage dans le catalogue (étape 1 de la modale d'ajout) : chaque bloc porte un poids d'ordre par défaut (défini sur sa classe), surchargeable par une config projet (ex: `gingerminds-cms.block_order`, une map clé de bloc → poids) sans avoir à sous-classer le bloc juste pour changer son rang. Un projet peut ainsi mettre en avant les blocs qu'il utilise le plus. Les blocs non listés dans cette config gardent leur poids par défaut (ou un ordre de repli, ex: alphabétique). Ordre à plat pour l'instant — pas de catégories/groupes dans le picker (voir points ouverts).

## Schéma de champ d'un bloc

Chaque champ déclaré dans `fields()` porte : `name`, `type` (correspond à un composant Blade d'input existant : text, textarea, wysiwyg, select, media, repeater...), `label`, `required`/règles de validation, `size` (largeur/colonne, comme le prop `size` du composant wysiwyg existant), une valeur par défaut, des `options` si besoin. L'ordre d'affichage suit l'ordre du tableau — pas de placement manuel dans un Blade.

Un formulaire générique boucle sur ce schéma et instancie les composants d'input correspondants : créer un bloc simple ne demande donc ni nouveau Blade ni JS.

### Champs répétables

Type de champ `repeater` : contient sa propre sous-liste `fields()` (même structure, récursive). L'UI de répétition (ajout/suppression/réordonnancement d'une ligne) réutilise le même mécanisme que celui des blocs au niveau page, en plus simple : pas d'étape "choisir un type" puisque la forme d'une ligne est fixée par le sous-schéma du champ.

### Échappatoire (formulaire Blade custom)

Si le schéma déclaratif ne suffit pas (champs conditionnels, mise en page particulière, logique métier spécifique), le bloc déclare une `formView()` qui pointe vers un Blade custom au lieu de `fields()`. Le registre saute alors le générateur générique. Un bloc utilise soit le mode schéma, soit le mode Blade custom — pas les deux mélangés.

### Exemples de blocs identifiés

| Bloc | Emplacement | Mode |
|---|---|---|
| Liste de liens | Package | Schéma déclaratif, `repeater` de 2 champs (label, url) |
| Liste de cards | Package | Schéma déclaratif, `repeater` avec un sous-schéma plus riche |
| Galerie média | Projet (dépend de `laravel-media-manager`, absent des dépendances du package cms) | Schéma déclaratif ou custom selon le picker média disponible |
| Slider | Package ou projet | Blade custom : mode hybride (slides saisies à la main OU liaison à un modèle de données) — trop de branchement conditionnel pour rester déclaratif |

## Flux d'édition en admin

Stack existant réutilisé : JS vanilla + Bootstrap (modales) + Sortable.js (déjà utilisé pour l'arbre des menus), même logique que le composant wysiwyg (état synchronisé dans un input hidden).

1. État en mémoire côté JS : tableau `{uid, type, data}`, sérialisé dans un input hidden `content`, soumis avec le reste du formulaire de page. Rien n'est persisté tant que la page elle-même n'est pas enregistrée.
2. "Ajouter un bloc" → modale étape 1 : liste des types de blocs actifs (résolus via le registre, en tenant compte des blocs désactivés), triée selon le poids d'ordre du catalogue.
3. Sélection du type → modale étape 2 : formulaire du bloc (généré depuis le schéma ou Blade custom), rempli via un appel ajax qui récupère le fragment Blade correspondant.
4. Validation du formulaire (ajax) → le serveur valide et renvoie le fragment de preview rendu ; le JS l'injecte dans le canvas de la page et ferme la modale.
5. Le type d'un bloc est verrouillé dès sa création : éditer un bloc rouvre directement l'étape 2 (formulaire pré-rempli), jamais l'étape 1.
6. Réordonnancement du canvas via Sortable.js, resynchronise juste l'ordre du tableau en mémoire.
7. Suppression d'un bloc : retrait du canvas + du tableau en mémoire.
8. À l'enregistrement réel de la page : revalidation serveur complète de tout le `content` (ne pas se fier uniquement à la validation faite à la fermeture de chaque modale). En cas d'échec, priorité à la non-perte de contenu : soumission classique (redirect-back + `old()`), le JS doit réhydrater son état en mémoire depuis `old('content')` en priorité (avant le contenu persisté en base) au rechargement de la page ; les erreurs renvoyées par le serveur doivent être mappées à l'`uid` du bloc concerné pour le mettre en évidence dans le canvas, sans obliger le contributeur à rouvrir chaque bloc un par un pour trouver lequel est en cause.

## Multilingue : duplication de structure entre langues

`content` vit sur `PageTranslation`, donc par langue — la structure de blocs serait sinon à reconstruire indépendamment pour chaque langue même quand la mise en page est identique. Sur le même principe que les ressources traduisibles existantes (ex: `MenuItem`, dont le formulaire admin affiche déjà tous les onglets de langue dans une même page), un bouton "Dupliquer depuis [langue]" sur l'onglet de langue cible permettrait de cloner le contenu d'une autre langue déjà renseignée :

- Les onglets de langue étant déjà tous présents dans le même formulaire, l'opération peut rester entièrement côté client : lire le tableau en mémoire de blocs de la langue source, le cloner dans le canvas de la langue cible avec de nouveaux `uid`.
- Copier aussi les valeurs des champs (pas seulement les types/l'ordre) plutôt qu'un squelette vide : ça sert de base de traduction (le contributeur écrase le texte au lieu de repartir de zéro), et permet de réutiliser tel quel le mécanisme déjà construit pour valider/rendre un bloc — chaque bloc cloné repasse par le même appel de validation + rendu de preview que lors d'un ajout normal, avec les données copiées comme valeurs de départ. Pas de nouveau code serveur nécessaire pour ça.
- Opération destructive : remplace le contenu existant de la langue cible s'il y en a déjà — confirmation requise avant d'écraser.

## Blade par bloc, appelé en composant

Un bloc fournit typiquement 2 vues Blade : une preview admin et un formulaire (sauf si généré depuis le schéma). Le registre résout la vue à afficher via un composant dynamique. Pas de duplication de logique de rendu en JS — le JS orchestre seulement des appels serveur qui renvoient du HTML déjà rendu.

## Commande de scaffolding : `make:cms-block`

Sur le même modèle que `make:controller-full` déjà présent dans `laravel-core` (`Console\Commands\Make`, signature kebab-case, stubs surchargeables) :

- `php artisan make:cms-block {name}` génère, dans le namespace du projet consommateur (`App\Cms\Blocks\{Name}`) : la classe du bloc avec `key()`, `label()`, `icon()` pré-remplis et un `fields()` contenant un exemple de champ à adapter, ainsi que la vue Blade de preview admin correspondante.
- Option `--custom-form` : génère en plus un Blade de formulaire et bascule la classe générée sur `formView()` au lieu de `fields()`, pour les blocs qui sortent du schéma déclaratif (cf. Slider).
- Le registre reposant sur un scan automatique de dossier (pas de mapping à déclarer à la main), la commande n'a aucun fichier de config à modifier — contrairement à `make:controller-full` qui doit patcher `routes/web.php`. Le bloc généré est actif dès sa création.
- Mêmes garde-fous que `make:controller-full` : ne pas écraser un bloc existant, message si déjà présent.
- Stubs surchargeables : stub par défaut fourni par `laravel-cms`, publiable et remplaçable côté projet (ex: `stubs/vendor/gingerminds-cms/cms-block.stub`), cohérent avec la règle de surcharge du projet appliquée jusqu'au gabarit de génération lui-même.

À prévoir : enregistrement de la commande dans `LaravelCmsServiceProvider::boot()` (`$this->commands([...])`), absent du provider actuel.

## Exposition API (headless)

Le champ `content` est servi tel quel par l'API — cohérent avec le stockage JSON déjà choisi.

Pour les champs marqués comme "référence" dans le schéma, un résolveur générique (pas une transformation par type de bloc) : parcourt le `content` au moment de servir la page, repère les champs de référence, regroupe les IDs par type de ressource pour éviter les N+1, fait un fetch groupé par type, puis substitue l'ID par l'objet résolu.

Pour les médias en particulier : réutiliser directement le groupe de sérialisation existant `BaseMedia::GROUP_LIST` (celui utilisé par l'endpoint liste des médias, déjà partagé avec `Product::GROUP_READ` dans `App\Models\Media\Media`) plutôt que d'inventer un format dédié — garantit que le média embarqué dans un bloc a exactement la même forme que l'endpoint liste des médias, et reste synchronisé automatiquement si ce format évolue.

Le schéma de champ "référence" doit porter la cardinalité (référence simple ou multiple) pour que le résolveur sache renvoyer un objet ou un tableau d'objets.

## Plan d'implémentation

En 3 étapes :

1. **Page / PageTranslation** (hors périmètre de ce document, pris en charge séparément) : modèles, migrations, dont le champ `content` JSON sur `PageTranslation`.
2. **Système de gestion des blocs, avec un seul bloc (Titre + Texte)** :
   - contrat de bloc (clé, label, icône, `fields()`, vue de preview),
   - registre : scan automatique (package + projet), surcharge par config (`gingerminds-cms.blocks.<key>`), activation/désactivation (`disabled_blocks`), ordre du catalogue (`block_order`),
   - générateur de formulaire piloté par schéma déclaratif,
   - flux d'édition admin complet (ajout/édition/suppression/réordonnancement, modales, verrouillage du type après création, revalidation serveur à l'enregistrement de la page),
   - commande `make:cms-block` en fin d'étape, une fois le contrat éprouvé sur le bloc Titre + Texte — pour scaffolder les blocs de l'étape 3 sur un contrat réel plutôt que supposé.
   - Explicitement hors périmètre à ce stade : type de champ `repeater`, champs "référence" et résolveur API associé, mode Blade custom (`formView()`), catégories dans le picker — aucun n'est nécessaire pour Titre + Texte, à introduire à l'étape 3 quand un bloc en aura besoin.
3. **Autres blocs** (liste de liens, liste de cards, galerie média, slider, etc.), en s'appuyant sur le socle et la commande de scaffolding posés à l'étape 2.

## Décisions actées

- Pas de contrainte de placement (pas de singleton, pas de restriction par type de page) : le contributeur reste maître du contenu de la page.
- Pas de permissions par type de bloc pour le moment (le point d'extension existe côté Spatie Permission déjà en place si le besoin apparaît plus tard).
- La preview admin est une vue structurelle du contenu, pas un rendu pixel-perfect du site headless — attendu et assumé, pas une limitation à corriger.
- Pas de duplication d'un bloc au sein d'une même page pour l'instant ; voir en revanche le copier/coller entre pages en points ouverts.

## Points ouverts / à trancher plus tard

- Table d'index de suivi des références (protection suppression, recherche transverse) : pas construite maintenant, terrain préparé via le typage des champs de référence.
- Dérive de schéma dans le temps : si les champs d'un bloc changent après que des pages existantes l'utilisent, prévoir un accès défensif avec valeurs par défaut dans les previews/rendus plutôt que de la validation stricte à la lecture.
- Le mode hybride du bloc Slider (saisie manuelle vs liaison modèle de données) reste à spécifier plus finement si ce pattern doit se généraliser à d'autres blocs.
- Catégories dans le picker : non retenu pour l'instant (ordre à plat suffisant) ; à reconsidérer si le nombre de types de blocs devient important.
- Copier un bloc d'une page et le coller dans une autre (lot futur, hors étapes 1-3) : le stockage JSON rend ça naturellement simple (copier `{type, data}` via le presse-papier, revalider à la collette au cas où le type/schéma n'existe pas ou plus dans la page/le projet cible) — à spécifier plus précisément le moment venu, notamment le cas d'un bloc référençant un média qui n'existerait pas dans le contexte cible.

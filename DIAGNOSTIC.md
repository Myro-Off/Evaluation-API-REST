# Diagnostic

Une section par test en échec : renseignez ses quatre champs.

## testAddingALineToAnUnknownOrderIsNotFound

**Symptôme** :

**Cause** :

**Règle du module en jeu** :

**Correctif** :

## testAddingALineToAPaidOrderIsAConflict

**Symptôme** :

**Cause** :

**Règle du module en jeu** :

**Correctif** :

## testAddingALineToMyOrder

**Symptôme** : Le test attend un total de la commande de 2050 centimes, mais l'API retourne 2450 car les sous-totaux des lignes avec quantité > 1 ou le cumul de la commande sont faussés.

**Cause** : `src\Service\OrderService.php:48` ; `$dish->getPrice()` est affecté directement au sous-total sans être multiplié par `$line-getQuantity()`

**Règle du module en jeu** : Calcul d'agrégation d'une ligne de commande (subtotal = price * quantity)

**Correctif** : Remplacement de `$dish->getPrice()` par `$dish->getPrice() * $line->getQuantity()`

## testAddingALineWithAZeroQuantityIsUnprocessable

**Symptôme** : Le test attend qu'il soit impossible d'ajouter une ligne à sa commande avec une valeur de 0, et devrait renvoyer HTTP 422, mais l'API renvoie HTTP 201

**Cause** : `src\Dto\Order\OrderAddLineInput.php:21` ; `#[Assert\PositiveOrZero]` accepte la valeur 0 et laisse passer ça au Processor.

**Règle du module en jeu** : Il faut intercepter les mauvaises entrées avant même d'envoyer les données au Service, donc dans le Dto, et renvoyer une erreur HTTP 422 pour bien indiquer qu'il y a une des conditions qui n'est pas respectée.

**Correctif** : Remplacer `#[Assert\PositiveOrZero]` par `#[Assert\Positive]`

## testListingKitchenTicketsReturnsMine

**Symptôme** :

**Cause** :

**Règle du module en jeu** :

**Correctif** :

## testListingKitchenTicketsWithoutTokenIsUnauthorized

**Symptôme** : Le test attend qu'il soit impossible de consulter la liste des tickets sans être authentifié, et attend HTTP 401, mais l'API renvoie HTTP 200

**Cause** : `src\State\KitchenTicket\KitchenTicketCollectionProvider.php:35` la vérification du user pour s'assurer que l'utilisateur fait bien partie de l'instance User renvois un tableau vide.

**Règle du module en jeu** : Si un utilisateur essaye d'accéder à une ressource qui nécessite d'être authentifié sans l'être, alors l'API doit renvoyer HTTP 401 Unauthorized

**Correctif** : Replacer `return [];` par `throw new UnauthorizedHttpException("Must be connected as a User");`

## testOpeningAnOrderIgnoresAnAbandonedOne

**Symptôme** : Lors de l'appel à `POST /api/orders`, l'API retourne une commande existante contenant une ligne d'article (Carottes rapées), alors qu'elle aurait dû ignorer la commande abandonnée et retourner une nouvelle commande vierge (un tableau vide).

**Cause** : 

**Règle du module en jeu** :

**Correctif** :

## testPayingMyOrderMarksItPaid

**Symptôme** : Le test s'attend à ce qu'une fois la commande payée, cela change le status de celle-ci à `Paid`, mais reste à `Pending`

**Cause** : `src\Service\OrderService.php:219` ; La base de donnée n'est jamais mise à jour avec les données via `flush` 

**Règle du module en jeu** : Il faut `flush` afin d'écrire les modifications en base de donnée.

**Correctif** : Ajouter `$this->orderRepository->persist($order);` et `this->orderRepository->flush();` juste avant le return.

## testRefreshingTwiceWithTheSameTokenIsUnauthorized

**Symptôme** : Le test attend un statut HTTP 401 lors de la seconde réutilisation d'un même jeton de renouvellement, mais reçoit un code HTTP 200 avec un nouveau token.

**Cause** : `config\packages\gesdinet_jwt_refresh_token.yaml` : single_use définis à `false`

**Règle du module en jeu** : Invalidation à usage unique des jetons de rafraichissement d'authentification. 

**Correctif** : Remplacement de `single_use: false` par `single_use: true` dans le fichier en cause.

## testRemovingALineFromSomeoneElsesOrderIsForbidden

**Symptôme** : Le test attent un status HTTP 403 lorsqu'un utilisateur essaye d'accéder à la commande d'un autre utilisateur, mais reçoit un code HTTP 204.

**Cause** : Dans `Order.php:59` la sécurité a un paramètre de trop, et laisse passer tous les utilisateurs ayant le rôle `USER` pour `Delete`.

**Règle du module en jeu** : Il ne faut pas donner l'accès aux commandes d'un utilisateur spécifique à tous les utilisateurs authentifiés. Mais bien vérifier que celle-ci a été créée par l'utilisateur qui souhaite la consulter.

**Correctif** : Remplacer `security: "is_granted('ROLE_USER') or object.getCreatedBy() == user"` par `security: "object.getCreatedBy() == user"`

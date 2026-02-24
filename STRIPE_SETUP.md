# Configuration du Paiement Stripe pour ARTIUM

## 📋 Vue d'ensemble

Le système de paiement Stripe a été intégré pour gérer l'achat de tickets d'événements. Les utilisateurs peuvent payer par carte bancaire de manière sécurisée via Stripe Checkout.

## 🚀 Installation

### 1. Installer le SDK Stripe PHP

```bash
composer require stripe/stripe-php
```

Si le terminal demande une confirmation pour la recette, tapez `y` pour accepter.

### 2. Créer un compte Stripe

1. Allez sur [https://dashboard.stripe.com/register](https://dashboard.stripe.com/register)
2. Créez un compte gratuit
3. Activez le mode Test pour commencer

### 3. Récupérer vos clés API

1. Connectez-vous à [https://dashboard.stripe.com/](https://dashboard.stripe.com/)
2. Allez dans **Developers** > **API keys**
3. Copiez :
   - **Publishable key** (commence par `pk_test_`)
   - **Secret key** (commence par `sk_test_`)

### 4. Configurer vos clés dans `.env`

Ouvrez le fichier `.env` et remplacez les valeurs par vos vraies clés :

```env
STRIPE_SECRET_KEY=sk_test_VOTRE_CLE_SECRETE_ICI
STRIPE_PUBLISHABLE_KEY=pk_test_VOTRE_CLE_PUBLIQUE_ICI
STRIPE_WEBHOOK_SECRET=whsec_VOTRE_SECRET_WEBHOOK_ICI
```

### 5. Configurer le Webhook Stripe

Les webhooks permettent à Stripe de notifier votre application quand un paiement est confirmé.

#### En développement local (avec Stripe CLI)

1. Installez Stripe CLI : [https://stripe.com/docs/stripe-cli](https://stripe.com/docs/stripe-cli)

2. Connectez-vous à Stripe :
```bash
stripe login
```

3. Écoutez les webhooks localement :
```bash
stripe listen --forward-to https://127.0.0.1:8000/payment/webhook
```

4. Copiez le secret webhook (commence par `whsec_`) et mettez-le dans `.env`

#### En production

1. Allez sur [https://dashboard.stripe.com/webhooks](https://dashboard.stripe.com/webhooks)
2. Cliquez sur **Add endpoint**
3. Entrez l'URL : `https://votre-domaine.com/payment/webhook`
4. Sélectionnez l'événement : `checkout.session.completed`
5. Copiez le **Signing secret** et mettez-le dans `.env`

## 📊 Flux de paiement

### 1. L'utilisateur clique sur "Acheter ticket"

```twig
{# Dans eventdetails.html.twig #}
<form method="post" action="{{ path('app_eventdetails', { id: evenement.id }) }}">
    <input type="hidden" name="_token" value="{{ csrf_token('buy_ticket_' ~ evenement.id) }}">
    <button class="btn btn-primary" type="submit">
        <i class="bi bi-ticket-perforated me-2"></i>Acheter ticket
    </button>
</form>
```

### 2. Redirection vers Stripe Checkout

Le `EventdetailsController` forward la requête vers `PaymentController::checkout()` qui :
- Crée une session Stripe Checkout
- Redirige l'utilisateur vers la page de paiement Stripe

### 3. Paiement sur Stripe

L'utilisateur entre ses informations de carte bancaire sur la page Stripe (sécurisée).

### 4. Confirmation du paiement

Deux scénarios :

#### ✅ Paiement réussi
- Stripe redirige vers `/payment/success`
- Stripe envoie un webhook `checkout.session.completed`
- Le webhook crée le ticket en base de données
- Un email avec le ticket est envoyé à l'utilisateur

#### ❌ Paiement annulé
- Stripe redirige vers `/payment/cancel`
- L'utilisateur peut réessayer

## 🔐 Sécurité

### CSRF Protection
Chaque formulaire d'achat inclut un token CSRF :
```php
$this->isCsrfTokenValid('buy_ticket_' . $evenement->getId(), $request->request->get('_token'))
```

### Webhook Signature
Les webhooks Stripe sont vérifiés avec la signature :
```php
$event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
```

## 💰 Devise

La devise est configurée en **Dinar Tunisien (TND)**.

Pour changer la devise, modifiez dans `PaymentController.php` :
```php
'currency' => 'tnd', // Changer ici (eur, usd, etc.)
```

## 🧪 Tests

### Mode Test Stripe

Par défaut, vous êtes en mode test. Les cartes de test Stripe fonctionnent :

| Carte | Numéro | Résultat |
|-------|--------|----------|
| Succès | `4242 4242 4242 4242` | Paiement réussi |
| Échec | `4000 0000 0000 0002` | Carte refusée |
| 3D Secure | `4000 0027 6000 3184` | Authentification requise |

- **Date d'expiration** : N'importe quelle date future (ex: 12/30)
- **CVV** : N'importe quel code 3 chiffres (ex: 123)

### Tester le webhook

1. Lancez Stripe CLI :
```bash
stripe listen --forward-to https://127.0.0.1:8000/payment/webhook
```

2. Effectuez un achat de ticket

3. Vérifiez les logs Stripe CLI pour voir le webhook

## 📧 Email de confirmation

Après paiement réussi, un email est automatiquement envoyé avec :
- Les détails de l'événement
- Le numéro de ticket
- Un lien pour télécharger le PDF
- Un lien vers le QR Code

Template : `templates/emails/ticket.html.twig`

## 🐛 Dépannage

### "Erreur lors de la création du paiement"

**Vérifiez** :
- Les clés Stripe sont correctes dans `.env`
- Le prix du ticket n'est pas à 0
- Vous avez bien run `composer require stripe/stripe-php`

### "Le webhook ne fonctionne pas"

**Vérifiez** :
- Stripe CLI est lancé (`stripe listen`)
- Le secret webhook dans `.env` est correct
- Les logs Symfony : `tail -f var/log/dev.log`

### "Le ticket n'est pas créé"

**Vérifiez** :
- Le webhook a bien été reçu (logs Stripe CLI)
- Pas d'erreurs dans `var/log/dev.log`
- L'événement et l'utilisateur existent bien en DB

## 📚 Documentation Stripe

- [Stripe Checkout](https://stripe.com/docs/payments/checkout)
- [Webhooks](https://stripe.com/docs/webhooks)
- [Testing](https://stripe.com/docs/testing)
- [API Reference](https://stripe.com/docs/api)

## 🎯 Prochaines étapes

- [ ] Configurer Stripe en production
- [ ] Ajouter des notifications push
- [ ] Implémenter les remboursements
- [ ] Ajouter le support de plusieurs devises
- [ ] Statistiques de vente dans le dashboard admin

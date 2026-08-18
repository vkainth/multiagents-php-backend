# New Stripe Pricing Page

  ## What & Why
  Add a new `/pricing` page that embeds the Stripe-hosted pricing table (prctbl_1TOnscJMQ9rLXPTOrDsqyOex). The old `/subscription-plans` page is not touched. The new page is simpler, Stripe-managed, and shows the latest pricing. If the user is logged in, their email is pre-filled on the Stripe table.

  ## Done looks like
  - Visiting `/pricing` shows the Stripe pricing table embedded in the site layout
  - Logged-in users have their email pre-filled in the Stripe table via `customer-email` attribute
  - The page uses the same header/footer layout as the rest of the site
  - The old `/subscription-plans` page continues to work unchanged

  ## Out of scope
  - Removing or modifying the old subscription page
  - Changing the manage-subscription or billing portal flow
  - Webhooks or subscription status changes

  ## Steps
  1. **Add route** — Register a new GET route at `/pricing` pointing to a new controller method `showNewPricingPage` in `SubscriptionController`, with the `redirect.authenticated` middleware.
  2. **Add controller method** — Add `showNewPricingPage()` to `SubscriptionController` that passes the authenticated user's email to the view (or null for guests).
  3. **Create view** — Create `resources/views/new_pricing.blade.php` extending the default mobile layout, including the site header/footer, and embedding the Stripe pricing table script and custom element. Pass `customer-email` when the user is logged in.

  ## Relevant files
  - `laravel-app/routes/bcchv1/web.php:261-265`
  - `laravel-app/app/Http/Controllers/SubscriptionController.php`
  - `laravel-app/resources/views/subscription_plans.blade.php`
  - `laravel-app/resources/views/frontend/layouts/default_mobile.blade.php`
  
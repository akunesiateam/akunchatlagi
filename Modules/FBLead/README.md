# FBLead Module

Facebook Lead Integration module for WhatsApp marketing automation.

## Type
**Addon Module** - Can be activated, deactivated, and removed

## Features

✅ **Facebook Lead Ads Integration**
- Connect Facebook pages and collect leads automatically
- Webhook support for real-time lead collection
- Lead data stored with tenant isolation

✅ **Plan-based Access Control**
- Module activation controls visibility
- Plan features control access limits
- Proper middleware protection

✅ **Dashboard Integration**
- Usage tracking and statistics
- Sidebar navigation with restrictions
- Dashboard stats cards

## Installation

### 1. Module Setup
```bash
# Copy module to Modules/FBLead
# Activate the module
php artisan module:activate FBLead

# Install module (run migrations & seeders)
php artisan fblead:install
```

### 2. Plan Configuration
The installation automatically adds Facebook Lead feature to existing plans:
- **Plan 2**: 10 leads limit
- **Expensive Plan**: 50 leads limit
- **Very Expensive Plan**: Unlimited leads

### 3. Module Verification
```bash
# Check if feature exists
php artisan tinker --execute="DB::table('features')->where('slug', 'facebook_lead')->first()"

# Check plan assignments
php artisan tinker --execute="DB::table('plan_features')->where('slug', 'facebook_lead')->get()"
```

## Configuration

### Facebook App Setup
1. Create Facebook Developer App
2. Add WhatsApp Business API product
3. Configure webhook URL: `/webhooks/facebook/{subdomain}/verify`
4. Set webhook verify token in tenant settings

### Module Settings
Access via: **Settings → WhatsApp Settings → Facebook Lead Integration**

Required fields:
- Facebook App ID
- Facebook App Secret
- Webhook Verify Token (auto-generated)
- Lead assignment settings (status, source, assigned user)

## Usage

### For Tenants
1. **Enable in Plan**: Admin must add `facebook_lead` feature to tenant's plan
2. **Module Active**: Module must be activated in admin panel
3. **Configure Settings**: Complete Facebook app configuration
4. **Set Up Webhooks**: Configure Facebook webhook to receive leads

### Webhook Endpoints
```
GET  /webhooks/facebook/{subdomain}/verify   - Webhook verification
POST /webhooks/facebook/{subdomain}/handle   - Lead data reception
```

### Access Control
- **Sidebar Menu**: Only visible if module active + plan includes feature
- **Settings Page**: Protected by feature middleware
- **Lead Collection**: Respects plan limits automatically

## Database Schema

### Features Table
```sql
- name: "Facebook Lead Integration"
- slug: "facebook_lead"
- type: "limit"
- default: 0
```

### Plan Features Table
```sql
- feature_id: [facebook_lead feature ID]
- value: [limit number or -1 for unlimited]
```

### Facebook Leads Table
```sql
- tenant_id: [tenant identifier]
- facebook_lead_id: [FB lead ID]
- page_id: [FB page ID]
- form_id: [FB form ID]
- email, phone_number, full_name: [lead data]
- lead_data: [JSON with all fields]
```

## File Structure

```
Modules/FBLead/
├── Console/
│   └── InstallFacebookLeadModule.php    # Installation command
├── Database/
│   ├── Migrations/
│   │   └── *_add_facebook_lead_feature_to_features_table.php
│   └── Seeders/
│       ├── FacebookLeadFeatureSeeder.php
│       └── FBLeadDatabaseSeeder.php
├── Http/
│   ├── Controllers/
│   │   └── FacebookWebhookController.php
│   └── Middleware/
│       └── FBLeadMiddleware.php
├── Livewire/
│   └── Tenant/
│       ├── FacebookLeadsIndex.php
│       └── Settings/WhatsMark/FacebookLeadSettings.php
├── Models/
│   └── FacebookLead.php
├── Providers/
│   └── FBLeadServiceProvider.php    # Main module registration
├── resources/
│   ├── lang/
│   │   └── tenant_en.json          # Module translations
│   └── views/
│       └── livewire/tenant/
└── Routes/
    ├── api.php                     # Webhook routes
    └── web.php                     # Tenant routes
```

## Security

### Access Protection
- Feature middleware on all routes
- Plan validation before lead collection
- Tenant isolation for all data
- Webhook token verification

### Data Privacy
- All lead data stored per-tenant
- No cross-tenant data access
- Secure webhook verification
- HTTPS required for webhooks

## Troubleshooting

### Module Not Showing
1. Check module is activated: `php artisan module:status`
2. Verify feature in plan: Check `plan_features` table
3. Clear cache: `php artisan cache:clear`

### Webhook Issues
1. Verify webhook URL is accessible
2. Check webhook verify token matches
3. Ensure tenant settings are configured
4. Check logs: `storage/logs/laravel.log`

### Permission Errors
1. Feature not in plan: Add to `plan_features` table
2. Module not active: Run `php artisan module:activate FBLead`
3. Middleware blocking: Check `FBLeadMiddleware`

## Support

This module follows the same patterns as other addon modules (AI Assistant, Ecommerce) for consistency and maintainability.

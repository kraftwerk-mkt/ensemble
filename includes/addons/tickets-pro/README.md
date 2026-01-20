# Ensemble Tickets Pro Addon

Paid ticket sales with payment gateway integration for the Ensemble Event Management Plugin.

## Overview

Tickets Pro extends the Booking Engine with:
- Payment gateway abstraction
- PayPal integration (Stripe, Mollie planned)
- Ticket categories with pricing
- Order summary and checkout
- Payment logging

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Ensemble Plugin 3.1+
- **Booking Engine Addon** (required dependency)

## Installation

1. Copy the `tickets-pro` folder to `wp-content/plugins/ensemble/addons/`
2. Activate in Ensemble → Addons (automatic if Booking Engine is active)

## File Structure

```
tickets-pro/
├── class-es-tickets-pro-addon.php      # Main addon class
├── class-es-payment-gateway.php        # Abstract gateway base
├── class-es-payment-gateway-manager.php # Gateway registry
├── class-es-ticket-category.php        # Ticket category model
├── tickets-pro-admin.css               # Admin styles
├── tickets-pro-admin.js                # Admin scripts
├── tickets-pro-frontend.css            # Frontend styles
├── tickets-pro-frontend.js             # Frontend scripts
├── gateways/
│   └── class-es-gateway-paypal.php     # PayPal implementation
└── templates/
    ├── admin-page.php                  # Main admin page
    ├── admin-overview.php              # Dashboard overview
    ├── admin-gateways.php              # Gateway settings
    ├── admin-settings.php              # General settings
    └── wizard-ticket-categories.php    # Event wizard integration
```

## Payment Gateways

### PayPal (REST API v2)

1. Go to https://developer.paypal.com/
2. Create an App (Sandbox for testing, Live for production)
3. Copy Client ID and Client Secret
4. Configure in Ensemble → Tickets Pro → Payment Gateways → PayPal

**Webhook Setup:**
- URL: `https://your-site.com/?ensemble_payment_webhook=paypal`
- Events: `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.DENIED`, `PAYMENT.CAPTURE.REFUNDED`

### Adding Custom Gateways

```php
// 1. Create gateway class
class ES_Gateway_MyGateway extends ES_Payment_Gateway {
    
    public function __construct() {
        $this->id = 'mygateway';
        $this->title = __('My Gateway', 'ensemble');
        parent::__construct();
    }
    
    public function is_configured() {
        return !empty($this->get_setting('api_key'));
    }
    
    public function get_settings_fields() {
        return array(
            'enabled' => array('title' => 'Enable', 'type' => 'checkbox'),
            'api_key' => array('title' => 'API Key', 'type' => 'text'),
        );
    }
    
    public function create_payment($args) {
        // Implement payment creation
        // Return array with payment_id, status, redirect_url
    }
    
    public function get_payment($payment_id) {
        // Return payment details
    }
}

// 2. Register gateway
add_filter('ensemble_payment_gateways', function($gateways) {
    $gateways['mygateway'] = 'ES_Gateway_MyGateway';
    return $gateways;
});
```

## Ticket Categories

### Creating Categories

```php
$category = new ES_Ticket_Category();
$category->event_id = 123;
$category->name = 'VIP Ticket';
$category->price = 99.00;
$category->currency = 'EUR';
$category->capacity = 50;
$category->save();
```

### Getting Categories

```php
// All categories for an event
$categories = ES_Ticket_Category::get_by_event($event_id);

// Single category
$category = ES_Ticket_Category::get($category_id);

// Check availability
$available = $category->get_available_count();
$status = $category->get_availability_status(); // available, limited, sold_out
```

## Hooks & Filters

### Actions

```php
// Ticket paid
do_action('ensemble_ticket_paid', $booking_id);

// Payment completed (PayPal)
do_action('ensemble_paypal_payment_completed', $booking_id, $resource);

// Payment failed
do_action('ensemble_paypal_payment_failed', $booking_id, $resource);
```

### Filters

```php
// Register custom gateways
add_filter('ensemble_payment_gateways', function($gateways) {
    $gateways['custom'] = 'My_Custom_Gateway';
    return $gateways;
});

// Customize return URL
add_filter('ensemble_payment_return_url', function($url, $booking_id, $gateway) {
    return get_permalink($success_page_id);
}, 10, 3);
```

## Database Tables

### ensemble_payment_logs

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| booking_id | bigint | Related booking |
| gateway_id | varchar(50) | Gateway identifier |
| event | varchar(50) | Event type |
| data | longtext | JSON data |
| ip_address | varchar(45) | Client IP |
| created_at | datetime | Timestamp |

## Shortcodes

```php
// Ticket purchase form
[ensemble_ticket_form event_id="123"]

// Alias
[es_tickets event_id="123"]
```

## REST API

```
GET /wp-json/ensemble/v1/tickets/categories/{event_id}
GET /wp-json/ensemble/v1/tickets/availability/{event_id}
```

## Changelog

### 1.0.0
- Initial release
- PayPal REST API v2 integration
- Ticket category management
- Payment logging
- Admin dashboard
- Event wizard integration

# Klarna Checkout for Craft Commerce

This plugin provides [Klarna](https://www.klarna.com) integrations for [Craft Commerce](https://craftcms.com/commerce).

**Note: this plugin is still undergoing testing and improvements. Please report any and all errors trough our support channels.**
## Requirements

This plugin requires Craft Commerce 2.0.0 or later.

## Installation

You can install this plugin from the Plugin Store.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Klarna Checkout”. Then click on the “Install” button in its modal window.

## Support

Contact us on support@ellera.no or create a new issue in [GitHub](https://github.com/ellera/commerce-klarna-checkout/issues).

## Setup

1.  Make sure you have set the store location in `Commerce -> Store Settings -> Store Location`
2.  and Base URL in `Settings -> Sites -> sitename -> Base URL`.
3.  Install the plugin.
4.  Navigate to `Commerce -> System Settings -> Gateways` and `+ New Gateway`
5.  Select Klarna Checkout from the dropdown.
6.  Set your information Playground credentials in API Credentials Europe Test Username (UID)/Test Password.


#### Payment button
Since Klarna is rendering its own payment button, the craft-commerce default 'Pay' button must be removed.
You can copy and overwrite your shop default `checkout/payment.html` file with `vendor/ellera/commerce-klarna-checkout/src/templates/pages/checkout/payment.html` or simply update your existing template with
```
{% if cart.gateway.handle is not same as('klarna-checkout') %}
    <button class="button button-primary" type="submit">Pay {{ cart.totalPrice|commerceCurrency(cart.paymentCurrency,convert=true) }}</button>
{% endif %}
```

#### VAT and Taxes

Klarna requires tax to be sent per order line, not on the order in total, so for VAT and Taxes to be passed along to Klarna correctly, the taxable subject must be set to "Line item price".
If the shipping cost is taxable as well, you need to create a separate tax rate for shipping and set that to "Order total shipping cost"

## Development

- Add support non-included tax :heavy_check_mark:
- Add support for non-european stores
- Add support for Commerce Lite
# Klarna Checkout for Craft Commerce

## Updating from v1.x to v2.x
1. Take as screenshot of your gateway settings
2. Back up your database and files so you can easily reset the changes should you need to
3. Update the Klarna Checkout for Craft Commerce in the plugin panel
4. Navigate to `Commerce -> System Settings -> Gateways` and edit your current Klarna gateway(s).
All current gateways are now invalid as the previous gateway class 'KlarnaCheckout' is 
replaced with the new 'Checkout'. The functionality is identical, so what you need to do 
is to update the Gateway and chose 'Klarna Checkout' from the dropdown. You will need to 
fill in the settings again. You should then be able to capture or refund existing orders.
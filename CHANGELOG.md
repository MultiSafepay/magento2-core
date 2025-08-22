# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.13.1] - 2025-08-22
### Fixed
- PLGMAG2V2-864: Fixed automatic gateway detection switch for generic giftcards

## [3.13.0] - 2025-07-29
### Added
- PLGMAG2V2-852: Added support for Bancontact WIP in Magento Vault
- PLGMAG2V2-859: Added CODE_OF_CONDUCT.md

### Fixed
- PLGMAG2V2-860: Fixed an issue where some orders do not have correct values after invoice creation with a specific Amasty Checkout Pro configuration
- PLGMAG2V2-862: Fixed CancelOrder process error when transaction_id param can not be found, thanks to @CanvasCompanyHylke

## [3.12.0] - 2025-07-09
### Added
- PLGMAG2V2-850: Added customer company name to the order request
- PLGMAG2V2-856: Added logging for payment component requests

### Fixed
- PLGMAG2V2-854: Fixed Fooman Surcharges not being picked up for BNPL refunds
- PLGMAG2V2-853: Fixed BNPL refund processing of bundle product items with non dynamic pricing

## [3.11.0] - 2025-05-20
### Fixed
- PLGMAG2V2-846: Fix an issue with some orders going back to pending payment right after customer account creation after checkout

### Removed
- DAVAMS-901: Removed the deprecated payment method 'Alipay'

## [3.10.1] - 2025-05-01
### Fixed
- PLGMAG2V2-840: Fix Monolog issue in Magento 2.4.8, thanks to @jissereitsma
- PLGMAG2V2-841: Fix shopping cart refund issue due to version comparison bug
- PLGMAG2V2-842: Fix PHP 8.4 deprecations

### Changed
- MAGWIRE-32: Move some generic gateway logic to own util

## [3.10.0] - 2025-04-02
### Added
- PLGMAG2V2-831: Added invoice_url to invoice update request for E-invoicing
- PLGMAG2V2-832: Added setting for adding iDEAL payment page step

### Fixed
- PLGMAG2V2-835: Fixed broken Einvoicing refund

## [3.9.1] - 2025-02-24
### Fixed
- PLGMAG2V2-826: Fix uncleared orders not being able to be invoiced
- PLGMAG2V2-823: Fix Fooman surcharge rate calculation above 100%

### Changed
- PLGMAG2V2-829: Enable Sofort and Dotpay payment methods

### Removed
- DAVAMS-840: Remove gender requirement for in3

## [3.9.0] - 2025-01-09
### Added
- DAVAMS-817: Added the Bizum payment method
- DAVAMS-852: Add the Billink payment method
- PLGMAG2V2-814: Added instructions field to payment methods
- PLGMAG2V2-788: Added coupon names to item names for more clarity about which coupon was used
- PLGMAG2V2-809: Added the transaction ID to the comment in the order overview for orders canceled by MultiSafepay

### Fixed
- PLGMAG2V2-805: Fixed payment_review orders unable to be canceled
- PLGMAG2V2-647: Fixed Fast Checkout duplicated transactions

### Removed
- PLGMAG2V2-810: Removed deprecated methods: Santander, Giropay, Sofort, Request to pay and Dotpay
- DAVAMS-869: Refunding has been disabled for Multibanco

## [3.8.1] - 2024-09-20
### Fixed
- PLGMAG2V2-794: Fixed an issue that sometimes occurred after the last update related with placing an Apple Pay and Google Pay transaction

### Changed
- DAVAMS-795: Rebrand the logo's, titles and labels from AfterPay to Riverty

## [3.8.0] - 2024-08-30
### Added
- PLGMAG2V2-779: Added payment component for BNPL methods

### Changed
- PLGMAG2V2-785: Upgraded payment icon for MultiSafepay method

### Removed
- PLGMAG2V2-784: Removed issuers from iDEAL

## [3.7.1] - 2024-08-06
### Fixed
- PLGMAG2V2-753: Fixed an issue where a coupon could be decremented multiple times on multiple calls to the 'Cancel' controller

### Changed
- PLGMAG2V2-778: Coupons will now be canceled through the OrderService on order cancelation and will happen either through the Notification webhook request or when restoring the cart, depending on the type of transaction

## [3.7.0] - 2024-07-05
### Added
- PLGMAG2V2-741: Added Manual Capture support for the Card payment, Visa, Mastercard and Maestro gateways. For more information about this feature, please check the [MultiSafepay Manual Capture documentation](https://docs.multisafepay.com/docs/manual-capture).
- PLGMAG2V2-760: Add to order payment description with which type of card was paid for the Card Payment method
- PLGMAG2V2-768: Refund requests are now logged on debug mode

### Fixed
- PLGMAG2V2-759: Fixed an issue which happened because the getPath method no longer exists on DirectDebitConfigProvider
- PLGMAG2V2-769: Fixed HTTP Response code being 0 in custom MultiSafepay HTTP Client

## [3.6.0] - 2024-05-15
### Added
- DAVAMS-764: Add in3 B2B

### Fixed
- PLGMAG2V2-755: Fixed an issue where a type error can occur when retrieving the tax rate during transaction creation 

## [3.5.1] - 2024-05-01
### Fixed
- PLGMAG2V2-738: Fixed orders stuck on pending after REST API Invoice creation

### Changed
- PLGMAG2V2-739: PayPal, AliPay, AliPay Plus, CBC, KBC and Trustly now have the option to redirect to the MultiSafepay payment page when placing the order

## [3.5.0] - 2024-04-02
### Added
- PLGMAG2V2-731: Added VVV Cadeaubon gateway
- DAVAMS-733: Added Pay After Delivery (BNPL_MF) gateway
- PLGMAG2V2-736: Added browser info to the transaction
- PLGMAG2V2-734: Added more integration tests for Magento Vault

### Fixed
- PLGMAG2V2-735: Fix an issue where wrong shipping cost is sent in OrderRequest when VAT is exempted during checkout

### Changed
- DAVAMS-756: Rebrand in3 B2C

## [3.4.0] - 2024-02-16
### Added
- DAVAMS-716: Add Multibanco payment method
- DAVAMS-724: Add MB WAY payment method

### Fixed
- PLGMAG2V2-728: Add recurring data when customer_id is in additional info
- PLGMAG2V2-725: Fix broken gift card gateway ids

### Removed
- PLGMAG2V2-726: Remove startSetup and endSetup commands from UpgradeData

## [3.3.2] - 2024-01-24
### Changed
- PLGMAG2V2-716: Refresh API Token and component after expiry

## [3.3.1] - 2023-12-28
### Fixed
- PLGMAG2V2-715: Fixed an issue where pretransaction notifications would be processed for canceled orders

### Changed
- DAVAMS-700: Refactored in3 Direct to only make gender mandatory
- PLGMAG2V2-714: POST notifications will now always be logged, instead of only when the verification fails
- PLGMAG2V2-709: The DataAssignObserver for Card payments will now return early whenever the payload is already defined

### Removed
- DAVAMS-708: Removed Santander Betaal Per Maand

## [3.3.0] - 2023-11-30
### Fixed
- PLGMAG2V2-705: Fixed a bug where the notification process would not finish due to an empty 'Auth' header in the POST webhook request
- PLGMAG2V2-708: Fixed Hyva React Checkout OutOfBoundsException when it is installed through app/code
- PLGMAG2V2-688: Fixed an issue where coupons would not be restored if the payment was canceled/declined
- PLGMAG2V2-711: Fixed an issue where it was not possible to do refunds for a generic gateway that requires the shopping cart

### Added
- DAVAMS-531: Added an advanced setting for adding a template ID to be used for payment components
- DAVAMS-676: Added preset gateway filters for Zinia
- DAVAMS-596: Added a setting to choose SVG icons instead PNG.

### Changed
- PLGMAG2V2-704: For E-invoicing, there will be no invoice e-mails sent from Magento, since E-invoicing handles invoice e-mails on the payment side

## [3.2.1] - 2023-10-16
### Fixed
- PLGMAG2V2-702: Fixed an issue where in some cases the wrong transaction_type would be used for some 'Direct' payment methods

## [3.2.0] - 2023-10-11
### Added
- PLGMAG2V2-700: Added Edenred Consumption Voucher (EDENCONSUM)
- PLGMAG2V2-699: Added support for simple products that have custom options configured with no custom SKU
- DAVAMS-661: Added the Zinia payment method
- PLGMAG2V2-687: Added support for adjustment refunds

### Fixed
- PLGMAG2V2-690: Fixed direct payment methods doing redirect when direct is configured
- PLGMAG2V2-692: Fixed bundle product shopping cart refund

### Changed
- PLGMAG2V2-688: Changed notification delay to add Canceled, Void, Expired and Declined statuses
- PLGMIRAKL-58: Excluded custom total 'marketplace_shipping' from Mirakl since it will be added by the MultiSafepay Mirakl module instead
- DAVAMS-643: Refactored and improved the processing of payment component recurring tokens

## [3.1.3] - 2023-09-04
### Fixed
- PLGMAG2V2-691: Fixed an issue with Pay After Delivery Installments refunds
- PLGMAG2V2-696: Fixed an issue where notification requests would require a lot of time to process, in some cases causing a timeout

### Changed
- PLGMAG2V2-683: API tokens are now stored and retrieved through the cache

### Removed
- PLGMAG2V2-694: Removed class import dependency on Magento_Giftcardaccount for CustomTotalBuilder

## [3.1.2] - 2023-07-17
### Fixed
- PLGMAG2V2-675: Fixed an issue where cron job sales_clean_orders failed when order contains no payment method
- PLGMAG2V2-679: Fixed an issue where the integration tests were reporting the wrong code coverage percentages 

### Changed
- PLGMAG2V2-664: Refactored the CreateInvoice procedure.

## [3.1.1] - 2023-06-13
### Added
- PLGMAG2V2-671: Added the functionality to add plugin data information for other enabled third party plugins, starting with Hyva React Checkout

### Fixed
- PLGMAG2V2-670: Fixed an issue where incorrect item prices would show in combination with the Meetanshi Vat Excemption module

### Changed
- PLGMIRAKL-26: Changed the OrderItemBuilder to make the procedure of modifying the merchant item id using a plugin interceptor class easier
- DAVAMS-607: Changed the 'Credit Card' method default title to 'Card Payment' according to the latest standards
- PLGMIRAKL-2: Changed the scope of some variables in Config and SdkFactory

### Removed
- PLGMAG2V2-669: Removed the setup_version from the module.xml

## [3.1.0] - 2023-05-17
### Added
- PLGMAG2V2-661: Add payment component for Pay After Delivery installments
- PLGMAG2V2-657: Give the option to set 3 different icons for payment method Credit Card
- PLGMAG2V2-667: Add a setting field to exclude utm_nooverride from the redirect_url

### Changed
- PLGMAG2V2-632: Refactor Credit Card Payment Components
- PLGMAG2V2-653: Rebranded Sofort

## [3.0.0] - 2023-04-03
### Added
- PLGMAG2V2-573: Added new service interfaces, processes and a controller for processing notification webhooks

### Changed
- PLGMAG2V2-642: Changed to use asString methods in Customer and Delivery Builders
- PLGMAG2V2-617: Refactored notification webhook process. Notification webhooks are now processed in the core module and not in the frontend module

### Removed
- PLGMAG2V2-648: Removed the dependency for zendframework/zend-http and laminas/laminas-http, to be compatible with Magento ^2.3.0 and ^2.4.0
- PLGMAG2V2-649: Removed capture options from manual invoice in credit card methods to make that process have one manual action less

## [2.21.0] - 2023-03-07
### Added
- Added missing class variables for deprecation support with PHP 8.2
- Added Pay After Delivery installments payment method

### Fixed
- Fixed the customer ID not being able to be null, thanks to @Jade-GG
- Fixed a bug where payment methods were missing after a cancelled order
- Fixed translations for OrderPlaceAfterObserver error messages, thanks to @mlaurense

### Changed
- Gateway info objects are now passed as a string instead of an object
- Rebrand Pay After Delivery

### Removed
- Copyright mention has been removed from the files and is only mentioned from now on in the disclaimer. Please read it if you haven't already

## [2.20.0] - 2022-12-07
### Added
- Added support for optional customer city argument
- Added raw response data to Apple Pay Merchant Session request logs for better debugging possibilities
- Added improved response handling for Pay After Delivery and Klarna refunds

### Fixed
- Fixed '1000 Required Fields error' in rare cases when creating a shipping update request
- Make sure checkout loads if API is unreachable

### Changed
- Only retrieve issuers for a gateway when it has been activated
- The payment will now only be saved after redirect, whenever sensitive information has been detected and removed from the payment additional information
- Changed the POST notification process to only retrieve the order after POST validation has passed

## [2.19.1] - 2022-10-24
### Fixed
- Fixed a TypeError which happened in some instances when trying to retrieve E-invoicing dynamic checkout fields.
- Fixed an issue where overriding when to send the order confirmation e-mail on gateway level sometimes did not work.
- Fixed an issue where it was not possible to refund orders created with a generic gateway

### Changed
- Skipped E-invoicing validation if payment method is set up as redirect

## [2.19.0] - 2022-10-04
### Added
- Payment links will now always be added to the order comment history again. For backend orders it happens immediately, for frontend orders it now happens when the first MultiSafepay notification arrives.
- Added Amazon Pay.
- Added an option for E-invoicing to assign collecting flow ids to specific customer groups.
- Added an option for E-invoicing to turn on and off certain checkout fields.

### Changed
- Magento Vault stored payment methods now change to the gateway where the card was last used.

## [2.18.1] - 2022-09-12
### Fixed
- Fixed a bug where in rare instances Magento Vault would cause a 'unique constraint violation' when trying to save a payment token.
- Fixed an Uncaught TypeError when trying to log the order if the order ID can't be found.

## [2.18.0] - 2022-08-23
### Added
- Added the MyBank payment method
- Improved logging for failed POST notifications

### Fixed
- Fixed an issue where in some cases VISA transactions could not be refunded through the backend

## [2.17.0] - 2022-07-11
### Added
- Added a configuration option for overriding when to send the order confirmation e-mail for pay later methods

### Fixed
- Fixed an issue where the order state switches to 'Pending payment' after 'Completed' in rare cases 

### Removed
- Removed the payment link order comment for frontend orders, because of multiple processes trying to save the order, which causes an issue in some instances

## [2.16.0] - 2022-06-29
### Added
- Added Vault for Maestro
- Added the Alipay+ payment method

### Fixed
- Fixed an issue where placing orders through some gateways with GraphQL returns an error
- Fixed an issue where there would be duplicated tokens stored in Vault in some rare cases

## [2.15.2] - 2022-05-13
### Fixed
- Fixed an issue with the timing of request timeouts for incoming notifications

## [2.15.1] - 2022-05-10
### Fixed
- Fixed an issue where in rare cases the salable quantity would be incremented twice when a transaction is canceled or declined

## [2.15.0] - 2022-04-28
### Fixed
- Fixed an issue where MultiSafepay orders in state pending_payment could not go to processing if the order was paid through another way.
- Fixed an issue with a circular dependency in the Config, which shows up during a setup:upgrade command.
- Fixed an issue with the custom REST API endpoint for retrieving the payment URL for a specific logged in customer, where it returned an incorrect type use error.
- Fixed an issue with the Amasty_OrderStatus module where it wasn't able to correctly save an order comment after placing it.

### Changed
- Deprecated ING Home'Pay
- Added changes that are required for PHP 8.1:
  - Added function type declarations
  - Changed namespaces to be declared on a single line
- Removed the dependency for Guzzle 6 and replaced it with a custom client implementation based on the Magento Curl Adapter
- Removed the dependency for php-http/guzzle6-adapter and replaced it with nyholm/psr7

## [2.14.1] - 2022-02-22
### Changed
- Bump version

## [2.14.0] - 2022-01-11
### Fixed
- Fixed an issue where failed shopping cart refunds in rare cases wouldn't be able to log ApiException and ClientExceptionInterface errors.
- Fixed an issue where the setting order comments fails on Magento 2.4.3 (Thanks to @Tjitse-E)
- Fixed an issue where the wrong Api key is used to generate SecureToken (Thanks to @Hexmage)
- Fixed an issue related to Swagger error

### Changed
- Shipping prices excluding tax are now re-calculated based on the shipping prices including tax

### Added
- Added options for selecting separate order status for different MultiSafepay statuses
- Added options for selecting separate behaviours of cancelling MutliSafepay order payment link
- Added error when using adjustment for shopping cart refund

## [2.13.0] - 2021-11-30
### Added
- Added a separate option for when to send the order confirmation email for the Bank Transfer payment method
- Added compatibility with [Adobe Commerce Gift Card Accounts](https://docs.magento.com/user-guide/catalog/product-gift-card-accounts.html)
- Added back REST API compatibility, including an end point for retrieving the payment link after order creation.
- Added the possibility to fetch transaction data in the transaction overview.

### Fixed
- Fixed an issue where the wrong shipping tax rate was being sent when using VAT ID validation

### Changed
- Updated the credit card logo
- API Keys are now stored with encryption provided by the Encryptor from the Magento Framework
- MultiSafepay Account data is now dynamically retrieved via an API request for Google Pay Direct
- Improved logging for Magento payment gateway, client and shipping requests.
- Changed from using the Magento payment currency validator to using own for filtering by current store currency

## [2.12.2] - 2021-11-03
### Changed
- Bumped the version to match the current meta package version

## [2.12.1] - 2021-11-01
### Fixed
- Fixed an issue related to empty Edenred payment method config

## [2.12.0] - 2021-10-29
### Added
- Added iDEAL and Direct Debit Vault
- Added Edenred

### Fixed
- Fixed an issue with Vault and Manual capture

### Changed
- Changed the dependency on Guzzle6-adapter from '*' to '^2.0', because Guzzle6-adapter '^1.0' does not implement Psr-7

## [2.11.0] - 2021-10-15
### Added
- Added Apple Pay Direct
- Added Google Pay Direct/Redirect
- Added WeChat Pay Redirect

### Fixed
- Fixed an error notice when postal code was left empty (Thanks to @thlassche)

## [2.10.4] - 2021-10-07
### Fixed
- Fixed a refund issue for manual captured payments

## [2.10.3] - 2021-10-04
### Fixed
- Fixed a bug where division by zero issue appeared for custom totals in getting tax rate method.
- Fixed a bug where an error would be thrown if the locale was null.
- Fixed a bug with the FPT/WeeeTax calculation when set excluding tax.

## [2.10.2] - 2021-09-07
### Fixed
- Fixed a bug where some specific orders with other than credit card payment methods couldn't be refunded.
- Fixed an issue where invoice emails were being sent after some time for orders with Billing Suite payment methods from the legacy plugin.

## [2.10.1] - 2021-09-02
### Fixed
- Fixed a bug related to incorrect shipping tax calculation with a specific tax configuration.
- Fixed a bug which causes an 'Integrity constraint violation: 1052 Column ‘increment_id’ in where clause is ambiguous' error if there is another increment_id.

## [2.10.0] - 2021-08-27
### Added
- Added compatibility with Adobe Commerce Gift Wrapping plugin.
- Payment links can now be used for both order confirmation emails and order comment emails.

### Changed
- Code refactored for order service methods.
- We are now removing sensitive data from the payment after the customer has been redirected.
- Changed the resolution of payment icons to be more in line with Magento core payment icons
- Added date picker field for Date of Birth checkout fields to further increase the consistency of input
- Dropped support for Magento 2.2.x versions.
- Deleted unnecessary order savings for better performance

### Fixed
- Fixed PHP Mess detector issues.
- Fixed a bug where Vault cards would not be stored because of a type error.
- Fixed a bug where for some custom totals with a value of '0.0' an error would occur on transaction creation.

## [2.9.0] - 2021-07-30
### Added
- Added automatic cancellation of MultiSafepay pretransactions for non-paid canceled orders.
- Added plugin version to the system report log
- Added possibility to skip automatic invoice creation after MultiSafepay payment.

### Fixed
- Fixed a bug where manual invoices for backend created orders did not go to processing state.
- Fixed a bug where filling in certain wrong values for the Date of birth field for AfterPay and in3 caused an error
- Fixed a bug where "Unable to unserialize value" errors were logged for every product.
- Fixed a bug where the shipping description in the transaction would cause an error if it was null (thanks to @florisschreuder)

## [2.8.2] - 2021-07-20
### Fixed
- Fixed a bug where the default store url was being used for payment links in backend orders.
- Fixed a bug related to generating secure token in Magento Open Source 2.4.2
- Fixed a bug where shopping cart refunds with 0 amounts would not throw an error. (Thanks to @reense)
- Fixed a bug where if an FPT is used, it in some cases wouldn't get picked up

## [2.8.1] - 2021-06-25
### Fixed
- Fixed a bug related to TypeError in ShipmentSaveAfterObserver. (Thanks to @Davie82)
- Fixed a bug related to GET notifications where orders would stay in pending_payment status

## [2.8.0] - 2021-06-17
### Added
- Added support MultiSafepay Credit Card component support for credit card payment methods.
- (dev) Added integration test coverage for secure token class, custom total builder and additional data builders.

### Fixed
- Fixed a bug related to special cases where some invoices skip the order payment method.
- Fixed a bug related to wrong showing qty on the payment page for items with decimal qty
- Fixed issue related to preselected customer group id for customer session

### Changed
- Moved setting pending_payment status from Redirect controller to Gateway Request Builder
- Changed the notification method from 'GET' to 'POST'.
- Updated the PHP-SDK version to version 5.
- Dropped support for PHP 7.1. Because of this, Magento versions up to version 2.2.9 are not supported anymore.
- Improved the logging for the notification actions

## [2.7.0] - 2021-06-03
### Added
- (dev) Added integration test coverage for all the plugin utils, services and order request builders.
- Added check if order was paid by gifcard, then will change payment method to one of giftcard payment methods.
- Added translations for some checkout fields. (Thanks to @Davie82)
- Added possibility to translate description phrase on MultiSafepay payment page.
- Added new logo for Bancontact payment method.

### Fixed
- Fixed a bug related to combined payment filters.
- Fixed a bug where in some cases the customer group id would retrieve wrong value.
- Fixed a bug where in some cases the shipping tracks array can't contain 0 index.
- Fixed TypeError for MultiSafepay payment method on notification.

### Changed
- Deleted sensitive payment data from the transaction logs.

## [2.6.1] - 2021-05-19
### Fixed
- Fixed a bug in payment validation transaction type constant scope, changed it from private to public.
- Fixed a bug in Afterpay and in3 payment methods related to phone_number field name.

## [2.6.0] - 2021-05-12
### Added
- Added separate phone number field for Afterpay & in3, which will already be filled in if the phone number is present in the billing address.
- Added notification about new versions of plugin in admin panel.
- Added System report to downloadable log archive for improved debugging.
- Added the possibility to change direct gateway methods to redirect.

### Fixed
- Fixed a bug where 'File not found' error would occur when trying to download log files with ROOT path set to 'pub'.
- Fixed a bug where in some cases the order confirmation e-mail would be sent when a transaction is expired. (Thanks to @basvanpoppel)

### Changed
- Changed the logs zip archive to be stored temporarily inside the var/tmp directory instead of the root directory.
- Changed the retrieval of the shipping tax from a calculation based method on the amount to retrieving it via a rate request to improve the accuracy.
- Changed the info logs into debug and added transaction data to the log. Also deleted unnecessary multisafepay-debug.log file.

## [2.5.2] - 2021-04-19
### Changed
- Code refactored for gateway additional data validators.
- (dev) Orders are now being retrieved with OrderRepositoryInterface instead of OrderInterfaceFactory
- (dev) Reduced multiple orderRepository save calls

### Fixed
- Fixed an issue where the shipment status update was only being done in the adminhtml scope. It will now trigger from all scopes, including the REST API.
- Fixed validators giving an error when field for choosing specific has been left empty
- (dev) Fixed DS constant not being available and replaced it with DIRECTORY_SEPARATOR constant

## [2.5.1] - 2021-04-09
### Fixed
- Fixed an error that happened when trying to open orders with an expired transaction.
- Fixed issue related to a missed tax amount in Fooman Surcharge custom totals
- Fixed error when choosing 'miss' gender with Afterpay
- Fixed an error that happened during checkout when there is a custom total in the cart with float as a string value ("0.000")
- Fixed issue where default pending status would get used instead of the one from the MultiSafepay config

### Changed
- For bank transfer payments, the redirect to the payment page has been brought back. Like with Magento 1, customers will now get redirected to the payment page where they can see the bank transfer details.

## [2.5.0] - 2021-03-26
### Added
- Added support for disabling the shopping cart on the MultiSafepay payment page
- Added information about the Magento edition to the order request.
- Added additional quote masked_id and entity_id parameters to the cancel and success payment urls

### Fixed
- Fixed error when creating transaction without available phone number
- Fixed a bug where the wrong processing status was set for the notifications

### Removed
- Removed obsolete emandate field from Direct Debit checkout

### Changed
- Changed validation for direct payment methods to prevent 400 (Bad Request) errors on selecting payment method
- Improved the logging for the notification offline actions

## [2.4.0] - 2021-03-11
### Added
- Added 3 generic gateways and 3 generic giftcards.
- Added customizable pending_payment status when redirecting to the payment page.
- Added the possibility to modify the "Success page" and cancel payment return URLs for PWA storefronts.
- Added compatibility for the MagePrince ExtrafeePro extension. (Payment fees are now correctly displayed in the checkout)

### Fixed
- Fixed missing preselected flag for creditcard gateways
- Fixed wrong default title in the config for American Express.

### Changed
- Code refactoring in the generic gateways and transaction shopping cart parts of the plugin for code improvement, readability and better performance.
- Changed composer dependencies to support Magento 2.2.

## [2.3.0] - 2021-02-22
### Added
- Added generic gateway feature for the possibility to add a gateway, which you can customize yourself.
  For more information, please see our [Magento 2 plugin docs](https://docs.multisafepay.com/integrations/plugins/magento2/).
- Added Magento 2 Vault support for credit card payment methods. For more information about the Magento 2 Vault feature, please see [Magento DevDocs](https://devdocs.magento.com/guides/v2.4/payments-integrations/vault/vault-intro.html)
- Added support for Magento 2 Instant Purchases (Works only for Vault supported payment methods). Please see the guide how to use and configure Magento 2 Instant purchase feature in [Magento DevDocs](https://docs.magento.com/user-guide/sales/checkout-instant-purchase.html)

### Changed
- Code refactoring in big parts of the plugin for code improvement, readability and better performance

## [2.2.1] - 2021-02-16
### Fixed
- Fixed undefined index for Afterpay and in3
- Fixed wrong api key being used for MultiSafepay requests with a multi store setup
- Fixed wrong store locale being sent in orders that were created in the admin backend

## [2.2.0] - 2021-01-26
### Added
- Added support for Mageplaza Reward Points

### Fixed
- Fixed wrong store locale being sent in admin backend orders
- Fixed Uncaught TypeError with string to float conversion for Fooman Surcharge
- Fixed order e-mails not being sent in some cases for backend orders

### Changed
- Added retrieval of icons from local directory for Givacard, Wellness gift card and Winkel Cheque
- Improved custom totals and added support for exclusions

## [2.1.0] - 2020-12-10
### Added
- Added in3 payment method
- Added support for preselected default payment method in the checkout

### Fixed
- IP Address is now filtered, preventing error when retrieving 2 ip addresses from the customer.
- Fix error 'Type "" is not a known type.' when placing a backend order with a non-MultiSafepay payment method.

## [2.0.1] - 2020-11-27
### Fixed
- Fixed instantiation error of fileDriver interface in backend and after placing a transaction
- Disabled Billing Suite payment methods for admin backend orders

## [2.0.0] - 2020-11-11
### Added
- Added support for order currency
- Added Good4Fun gift card
- Added support for custom transaction description and custom refund description
- Added MultiSafepay refund_id to the refund transaction in the Magento Payment.
- Added http-factory-guzzle package as a dependency for PSR-17 factories
- Added an event for adding or changing the transaction request data before it is sent to MultiSafepay
- Added support for Magento 2.4.1 and PHP 7.4
- Added delivery details to every transaction request
- Added user-agent to every transaction request

### Fixed
- Trying to refund with 0 amount will now throw an error
- Disable Second Chance for admin backend orders
- Added support for all the street lines (Before only street line 1 and 2 were supported)
- Added dependencies in module.xml and composer.json

### Removed
- Removed the use of pending_payment order status. For new orders, the 'new' order state will be used.
- Removed version constant. Versions are now retrieved from the composer.json file
- Removed custom factories to be replaced by http-factory-guzzle factories

### Changed
- Rebrand Klarna to the latest standards
- Upgraded the PHP-SDK to version 4

## [1.0.0] - 2020-09-02
### Added
- First public release

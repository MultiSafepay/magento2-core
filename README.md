<p align="center">
  <img src="https://www.multisafepay.com/img/multisafepaylogo.svg" width="400px" position="center">
</p>

# MultiSafepay plugin for Magento 2

This is the core module of our new Magento 2 plugin. 
The new plugin brings code improvements, unit/integration testing, and it is build on top of the Magento payment provider gateway structure.

Before you get started, please read our [installation & configuration manual](https://docs.multisafepay.com/integrations/plugins/magento2/) first.

## About MultiSafepay ##
MultiSafepay is a collecting payment service provider which means we take care of the agreements, technical details and payment collection required for each payment method. You can start selling online today and manage all your transactions from one place.

## Supported Payment Methods ##
The supported Payment Methods & Giftcards for this plugin can be found over here: [Payment Methods & Giftcards](https://docs.multisafepay.com/plugins/magento2/faq/#available-payment-methods-in-magento-2)

## Requirements
- To use the plugin you need a MultiSafepay account. You can create a test account on https://testmerchant.multisafepay.com/signup
- Magento Open Source version 2.3.x & 2.4.x
- PHP 7.1+

## Module suite

The new MultiSafepay Magento 2 plugin consists of several modules:

* [multisafepay-magento2-core](https://github.com/MultiSafepay/magento2-core) (Provides core functionalities)
* [multisafepay-magento2-frontend](https://github.com/MultiSafepay/magento2-frontend) (Enables use of the payment gateways in the Magento checkout)
* [multisafepay-magento2-adminhtml](https://github.com/MultiSafepay/magento2-adminhtml) (Makes it possible to enable/disable payment gateways and change the settings in the Magento backend)
* [multisafepay-magento2-msi](https://github.com/MultiSafepay/magento2-msi) (Handles stock when MSI is enabled)
* [multisafepay-magento2-catalog-inventory](https://github.com/MultiSafepay/magento2-catalog-inventory) (Handles stock when MSI is disabled)
* [multisafepay-magento2](https://github.com/MultiSafepay/magento2) (Meta package which installs all the above)

For GraphQL support there is a separate module:
* [multisafepay-magento2-graphql](https://github.com/MultiSafepay/magento2-graphql) (Extends and adds GraphQL queries and mutations)

## Installation

This module can be installed via composer:

```shell
composer require multisafepay/magento2-core
```

Next, enable the module:
```bash
bin/magento module:enable MultiSafepay_ConnectCore
```

Next, run the following commands:
```shell
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

**Please keep in mind that after installing this module, you will only have the MultiSafepay core functionalities which do not include the Magento backend, frontend and proper stock handling.**

For a quick installation of all the modules, we recommend using [the meta package](https://github.com/MultiSafepay/magento2) instead.

## Support
You can create issues on our repository. If you need any additional help or support, please contact <a href="mailto:integration@multisafepay.com">integration@multisafepay.com</a>

We are also available on our Magento Slack channel [#multisafepay-payments](https://magentocommeng.slack.com/messages/multisafepay-payments/). 
Feel free to start a conversation or provide suggestions as to how we can refine our Magento 2 plugin.

## A gift for your contribution
We look forward to receiving your input. Have you seen an opportunity to change things for better? We would like to invite you to create a pull request on GitHub.
Are you missing something and would like us to fix it? Suggest an improvement by sending us an [email](mailto:integration@multisafepay.com) or by creating an issue.

What will you get in return? A brand new designed MultiSafepay t-shirt which will make you part of the team!

## License
[Open Software License (OSL 3.0)](https://github.com/MultiSafepay/Magento2Msp/blob/master/LICENSE.md)

## Want to be part of the team?
Are you a developer interested in working at MultiSafepay? [View](https://www.multisafepay.com/careers/#jobopenings) our job openings and feel free to get in touch with us.

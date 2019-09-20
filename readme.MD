# Magento 2 RobinHQ module

[![Build Status](https://travis-ci.com/EmicoEcommerce/Magento2RobinHq.svg?branch=master)](https://travis-ci.com/EmicoEcommerce/Magento2RobinHq)

Provides API integrations with the RobinHQ platform / dashboards

## Installation
Install package using composer
```sh
composer require emico/magento-2-robinhq
```

Run installers
```sh
php bin/magento setup:upgrade
```

## Dynamic API

This module provides 5 endpoints for the RobinHQ dynamic API integration.
This endpoints are called directly from within the RobinHQ dashboard, providing the latest up to date information from Magento.

The following endpoints are available:
 - /robinhq/api/customer?email=`mail`
 - /robinhq/api/order?orderNumber=`number`
 - /robinhq/api/customerOrders?email=`mail`
 - /robinhq/api/search?searchTerm=`search`
 - /robinhq/api/lifetime?email=`email`
   
Those are accessible from the root of your magento domain. http://my.shop.nl/robinhq/api/customer

To enable the dynamic API functionality you have to enable it in the configuration.

`Stores` -> `Configuration` -> `Emico` -> `RobinHQ` -> `Enable dynamic API`

#### Authentication

The authentication of the dynamic API is done by a pre shared key which must be communicated to RobinHQ.

You can define an API key and secret in the RobinHQ configuration.

When issueing requests to the dynamic API endpoint you have to include a Basic authentication header.
The value you have to sent is a base64-encoding of `{apiKey}:{apiSecret}`.

For example you have set up API key to `abc` and API secret to `def`.
The value would be `base64(abc:def)`, which resolves to `YWJjOmRlZg==`.
The full authorization header is:
`Authorization: Basic YWJjOmRlZg==`

#### Customize panelview and detailview

Some endpoints provide the possibility to provide custom data in the Robin dashboards.

For example when retrieving customer details using `/robinhq/api/customer` the following payload is returned.

```
{
    "naam": "Robin Doe",
    ...
    "panel_view": {
        "street": "Lovinklaan 1",
        ...
        "my_customfield1": "234",
        "loyalty_card_number": "1265645456"
    }
}
```

You can simply add custom attributes to these views in the configuration.
`Stores` -> `Configuration` -> `Emico` -> `RobinHQ` -> `Custom Attributes` section.

When you need even more control or implement your own bussiness logic you can implement the interfaces `DetailViewProviderInterface` or `CustomerPanelViewProviderInterface`.

For example:
```
namespace MyVendor\MyModule;

class MyPanelViewProvider implements CustomerPanelViewProviderInterface
{
    public function getData(CustomerInterface $customer): array
    {
        return [
            'my_custom_field' => 'some value'
        ]
    }
}
```

Register in your `di.xml`

```
<virtualType name="Emico\RobinHq\DataProvider\PanelView\CustomerPanelViewProvider" type="Emico\RobinHq\DataProvider\PanelView\AggregateProvider">
    <arguments>
        <argument name="providers" xsi:type="array">
            <item name="mydata" xsi:type="object">MyVendor\MyModule\MyPanelViewProvider</item>
        </argument>
    </arguments>
</virtualType>
```

## Dynamic API POST requests

For complete integration with Robin dynamic API the Magento module needs to issue POST requests to the RobinHQ platform.

For this functionality to work correctly it is mandatory to have a working RabbitMQ instance. You can read more about setting this up in the [Magento documentation](https://devdocs.magento.com/guides/v2.3/install-gde/prereq/install-rabbitmq.html).

Use the following command to process the message queue:
`bin/magento queue:consumers:start EmicoRobinHq`

You can configure this in a cronjob.

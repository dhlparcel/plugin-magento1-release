# DHL Parcel for Magento 1 

DHL Parcel offers a convenient [extension](https://www.dhlparcel.nl/en/business/plug-ins) for Magento 1. With this you offer delivery options directly in your online store which in turn increases service levels for your customers. This extension also creates the option to process orders, create and print DHL labels directly from your online store backend environment. This makes shipping parcels a lot easier.

Note that this extension currently can only be used in the Benelux and Switzerland.

**Features**

**Print DHL labels** for domestic and international shipping with DHL Parcel. All parcel types are supported:
- Mailbox parcel
- Small parcel (0-20 kg)
- Medium parcel (20-31 kg)
- Large parcel (31-50 kg)
- Pallet (0 - 1000KG)

**Create Auto tracking codes** automatically send tracking information to recipients

**Offer a variety of delivery services.**
Services for consumer recipients:
- Home delivery
- DHL ServicePoint delivery
- Specific delivery time slots
- Same day delivery
- Evening delivery
- No delivery at neighbors
- Signature on delivery
- Extra assured shipments
- Return label

Services for business shipments:
- Home delivery
- Delivery at a construction site
- Saturday delivery
- Signature on delivery
- Extra assured shipments
- Extra fast service
- Return label
- Hide shipper name

**Customize names and rates**
Enable/disable services and set up the handling costs for each DHL shipping service:
- Set different prices for certain shipping zones
- Offer shipment discounts depending on order amount
- Default package sizes for label printing

**Delete or reprint labels**  in case your printer experienced any issues.

**Expert support,** timely compatibility updates and regular bug fixes.

# Plugin Installation
There are currently multiple ways to install the plugin

#### Method #1 - Drag and drop files to root (recommended)
Simply unpack the `app` and `lib` directories to the root of your Magento directory.

#### Method #2 - Install with modman (advanced)
This plugin supports modman.
Unpack the entire contents of the ZIP file to a new directory (for example: `DHLParcel_Shipping`) in your .modman directory.

Run `modman deploy-all` in the parent directory to deploy the plugin to the Magento directory.

#### Optional step - Update the plugin with composer (advanced)
If you prefer to update the vendor directory (this is saved in the `lib\DHLParcel\vendor` directory) yourself before installing (either normally or through modman), simply run `composer update` from the root of the plugin. Please note this only works if you've extracted the `composer.json` file. It's not recommended to do this at your Magento root directory. Recommended in combination with modman. You can skip copying the original `lib` directory if you do this step.

## Setup
After adding the plugin to your Magento installation, you need to setup the correct information to enable the plugin for use.
In order to continue, you must already have a My DHL Platform account as a business. Not a partner yet? Please contact us at [here](https://www.dhlparcel.nl/nl/offerte-aanvragen-dhl-parcel).

To make the configuration as seamless as possible, please check the installation and configuration manual:
**English installation and configuration manual**
- https://www.dhlparcel.nl/sites/default/files/content/PDF/Handleiding_DHL_Magento1_koppeling_EN.pdf

**Dutch installation and configuration manual**
- https://www.dhlparcel.nl/sites/default/files/content/PDF/Handleiding_DHL_Magento1_koppeling_NL.pdf

**Support**
For more information and on the DHL Parcel Benelux extension for Magento 1 and an extensive [manual](https://www.dhlparcel.nl/sites/default/files/content/PDF/Handleiding_DHL_Magento1_koppeling_EN.pdf), visit our [website](https://www.dhlparcel.nl/en/business/plug-ins). Need help? We will be happy to assist you. Just send us an [e-mail](mailto:cimparcel@dhl.com) or call us at +31 (0)88 34 54 333.

### Optional - add links to tracking numbers in shipment notification emails
you wil need both a 'New Shipment' and 'New Shipment for Guest' configured. 
- go to Transactional emails, this can be found under system in the top menu (system > Transactional emails)

#### Creating a new template
- click the 'Add New Template' button
- select either 'New Shipment' or 'New Shipment for Guest' you wil need both
- decide on a recognizable name and name the template
    - for instance: 'New Shipment - DHLParcel' or 'New Shipment for Guest - DHLParcel'
- click Load template to get the default template loaded

#### Updating an existing template
- open the template by clicking on it.

#### Making the required template changes
- scroll to the bottom of template content and look for the line containing the following:
```
{{block type='core/template' area='frontend' template='email/order/shipment/track.phtml' shipment=$shipment order=$order}}
```
- replace the line you previously found with the following:
```
{{block type='core/template' area='frontend' template='dhlparcel/email/order/shipment/track.phtml' shipment=$shipment order=$order}}
```

#### Selecting the use of customized email templates
- go to the configuration page, this can be found under system in the top menu (system > Configuration)
- go to Sales Emails, found under Sales in the left hand menu (Sales > Sales Emails)
- open the Shipment tab
- Select your template for both 'Shipment Email Template' & 'Shipment Email Template for Guest'
    - make sure you have a seperate template made since they differ slightly

Hence forth, the New shipment emails will contain links to the track and trace page when clicking on their tracking numbers.
This functionality for the time being only works for DHLParcel orders.
Non DHLParcel orders should not be impacted by this change.
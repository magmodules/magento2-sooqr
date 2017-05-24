# Sooqr Connect for Magento® 2

The official extension to connect Sooqr with your Magento® 2 store and improve your Search & Navigation.

## Installation

#### Magento® Marketplace

This extension will also be available on the Magento® Marketplace once approved.

#### Install via Composer

1. Go to Magento® 2 root folder

2. Enter following commands to install module:

   ```
   composer require magmodules/magento2-sooqr
   ``` 

3. Enter following commands to enable module:

   ```
   php bin/magento module:enable Magmodules_Sooqr
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```

4. If Magento® is running in production mode, deploy static content with the following command: 

   ```
   php bin/magento setup:static-content:deploy
   ```

#### Install from GitHub

1. Download zip package by clicking "Clone or Download" and select "Download ZIP" from the dropdown.

2. Create an app/code/Magmodules/Sooqr directory in your Magento® 2 root folder.

3. Extract the contents from the "magento2-sooqr-master" zip and copy or upload everything to app/code/Magmodules/Sooqr

4. Run the following commands from the Magento® 2 root folder to install and enable the module:

   ```
   php bin/magento module:enable Magmodules_Sooqr
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```

5. If Magento® is running in production mode, deploy static content with the following command: 

   ```
   php bin/magento setup:static-content:deploy
   ```
   
## Development by Magmodules

We are a Dutch Magento® Only Agency dedicated to the development of extensions for Magento® 1 and Magento® 2. All our extensions are coded by our own team and our support team is always there to help you out. 

[Visit Magmodules.eu](https://www.magmodules.eu/)

## Developed for Sooqr Search

Sooqr Search makes site search awesome. Our two goals: - Ultimate speed, Sooqr shows results instant within milliseconds even with hundreds of thousands of SKU’s - Highest relevance, always the best matches on top of the results list For your visitor the unique responsive interface is a combination of results, filters/facets and sorting. They will easily and quickly find what they are really looking for. Sooqr handles typos, so no worries about misspelling.

[Visit Sooqr](https://www.sooqr.com/)

## Links

[Knowledgebase](https://www.magmodules.eu/help/magento2-sooqr)

[Terms and Conditions](https://www.magmodules.eu/terms.html)

[Contact Us](https://www.magmodules.eu/contact-us.html)

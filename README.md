# PHP SDK for the Webflow CMS API

[![Build Status](https://travis-ci.com/expertlead/webflow-php-sdk.svg?branch=master)](https://travis-ci.com/expertlead/webflow-php-sdk)

Implementation based on [Webflow CMS API Reference](https://developers.webflow.com/#cms-api-reference)

## Features implemented
- Get Current Authorization Info
- List Sites
- Get Specific Site
- Publish Site
- List Domains
- List Collections
- Get Collection with Full Schema
- **Get All Items for a Collection (including paginated results)**
- **Find one or Create Item by Name**
- Get Single Item
- Create New Collection Item
- Update Collection Item
- Patch Collection Item
- Remove Collection Item

## Version 2

This package is now using Version 2 of the Webflow Api.

## Usage

Check https://university.webflow.com/article/using-the-webflow-cms-api on how to generate `YOUR_WEBFLOW_API_TOKEN`

### Get Current Authorization Info
```
$webflow = new \Webflow\Api('YOUR_WEBFLOW_API_TOKEN');
$webflow->info();
```

### List Sites
```
$webflow->sites();
```

### List Collections
```
$webflow->collections($siteid);
```

### Get All Items for a Collection (including paginated results)
```
$webflow->itemsAll($collectionId);
```
### Get Single Item
```
$webflow->item($collectionId, $itemId);
```

### Create New Collection Item
```
$fields = [
    'name' => 'New item created via API',
    # ...
];
$webflow->createItem($collectionId, $fields);
```

### Update Collection Item
```
$webflow->updateItem($collectionId, $itemId, $fields);
```

### Remove Collection Item
```
$webflow->removeItem($collectionId, $itemId);
```

## Publising

### Publishing Items
Publishing an item or items can be done instead of publishing the entire site.
```php
$webflow->publishItem($collectionId, $itemIds);
```

### Publishing a Site

```php
$domains = [$domainID];
// if you wish to publish to your mydomain.webflow.io subdomain you should specify
// true.  If true $domains **must** be an empty array
$publishWebflowSubdomain = true;
$webflow->publishSite($siteId,$domains, $publishWebflowSubdomain)
```
**nb:** Webflow has very strict limits on publishing your site. Currently 1 per minute.

## Installation

```
# Install Composer
composer require expertlead/webflow-php-sdk
```
No extra dependencies! You are welcome ;)

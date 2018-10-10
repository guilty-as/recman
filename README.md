# Recman API PHP SDK

This is a simple service class for querying the [Recman API](https://help.recman.no/no/help/api/), 
a cached version of the service is provided since Recman has rate limiting on 
their API (200 requests per day).


## Install

Via Composer

``` bash
composer require guilty/recman
```

## Usage


```php

<?php

// Basic usage
$httpClient = new GuzzleHttp\Client();
$recman = new \Guilty\Recman\RecmanApi(
    "YOUR-API-KEY-HERE", 
    $httpClient
);

// Using the provided service with caching, you can use any PSR-16 compatible cache library
$cache = new Symfony\Component\Cache\Simple\FilesystemCache();

// Use DateInterval - See: http://php.net/manual/en/class.dateinterval.php
$expire = new DateInterval("P1D"); // 1 day

// Or seconds as an integer
$expire = 7200; // 2 hours
$recman = new \Guilty\Recman\CachedRecmanApi(
    "YOUR-API-KEY-HERE",
    $httpClient, $cache, $expire
 );

// Available Methods
$recman->getBranchList();
$recman->getBranchCategoryList();
$recman->getSectorList();
$recman->getExtentList();
$recman->getLocationList($field);
$recman->getJobPostList();
$recman->getDepartmentList();
$recman->getCorporation();
$recman->getCandidateList($page = 1);
$recman->getCandidate($candidateId);
$recman->getCandidateAttributeList();
$recman->getCandidateAttributes();
$recman->getCandidateLanguageList();
$recman->getUserList($departmentIds = [], $corporationIds = [], $tagIds = []);
$recman->getUserTagList();


// If you are using the cache service, but need to get "fresh" data for a 
// method call, use the fluent disableCache() method.
$recman->disableCache()->getJobPostList();

// Note that this will disable the cache for all future calls, so use 
// enableCache() if you have subsequent calls that require caching
$recman->enableCache()->getDepartmentList();

// Or you can just call it without method chaining, either way the cache 
// will be enabled for the rest of the code duration.
$recman->enableCache();

// Cache is enabled again after the previous call
$recman->getBranchList();
$recman->getBranchCategoryList();


// If your API key does not have access to a scope, or if the API key 
// is invalid, the API will throw an Exception
try {
    $recman->getCandidateList();
} catch (Exception $exception) {
    var_dump($exception->getMessage()); // string(11) "Invalid key"
    var_dump($exception->getCode());    // int(2)
}
```

## Contributing

Write good (modern, readable and PSR-2) code and be nice when creating issues.

## Security

If you discover any security related issues, please email email@helgesverre.com instead of using the issue tracker.

## Credits

- [Guilty AS](https://guilty.no)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
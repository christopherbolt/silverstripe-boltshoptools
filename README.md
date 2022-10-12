This module includes common extensions, functions etc used by christopherbolt.com developed silvershop websites.

It's not really intended for public use, so support and documentation is limited, but you are more than welcome to use it and contribute to it.

Install silverstripe (update to version required):
```
composer create-project silverstripe/installer . 4.0.3
```

Install BoltShopTools, update your composer.json as follows:
```
    "require": {
        "christopherbolt/silverstripe-bolttools": "dev-master"
    },
    "repositories": {
        "christopherbolt/silverstripe-bolttools": {
            "type": "git",
            "url": "git@github.com:christopherbolt/silverstripe-bolttools.git"
        }
    },
```

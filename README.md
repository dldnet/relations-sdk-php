# relations-sdk-php

Relations SDK for PHP (Composer package)

### Installation

Add the Relations SDK

    composer require dldnet/relations-sdk-php

### Setup

    use Relations\Config as RelationsConfig;
    use Relations\Models\Vendors;

    RelationsConfig::setURL('https://xxxxx/api/v1');
    RelationsConfig::setToken('Your Token');

- To connect to the hosted version use `https://xxxxxx/api/v1` as the URL.

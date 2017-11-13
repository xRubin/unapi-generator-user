<?php

namespace unapi\generator\user;

use GuzzleHttp\Client;

class FakeNameGeneratorClient extends Client
{
    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $config['base_uri'] = 'http://www.fakenamegenerator.com';

        parent::__construct($config);
    }
}
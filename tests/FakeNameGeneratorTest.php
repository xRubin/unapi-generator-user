<?php

require __DIR__ . '/../vendor/autoload.php';

use unapi\generator\user\UserGeneratorService;

class FakeNameGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneration()
    {
        $service = new UserGeneratorService();
        $data = $service->generate()->wait();

        $this->assertContains($data['user']['gender'], ['Male', 'Female']);
    }
}
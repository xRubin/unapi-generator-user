<?php

namespace unapi\generator\user;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use unapi\interfaces\ServiceInterface;

class UserGeneratorService implements ServiceInterface, LoggerAwareInterface
{
    /** @var ClientInterface */
    private $client;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param array $config Service configuration settings.
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['client'])) {
            $this->client = new FakeNameGeneratorClient();
        } elseif ($config['client'] instanceof ClientInterface) {
            $this->client = $config['client'];
        } else {
            throw new \InvalidArgumentException('Client must be instance of ClientInterface');
        }

        if (!isset($config['logger'])) {
            $this->logger = new NullLogger();
        } elseif ($config['logger'] instanceof LoggerInterface) {
            $this->setLogger($config['logger']);
        } else {
            throw new \InvalidArgumentException('Logger must be instance of LoggerInterface');
        }
    }

    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return ClientInterface
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * @param array $options
     * @return PromiseInterface
     */
    public function generate(array $options = []): PromiseInterface
    {
        return $this->getClient()->requestAsync('GET', '/advanced.php', ['query' => $options])->then(function (ResponseInterface $response) {
            return $this->parseUser($response);
        });
    }

    /**
     * @param ResponseInterface $response
     * @return array
     */
    protected function parseUser(ResponseInterface $response): array
    {
        $this->getLogger()->info($data = $response->getBody()->getContents());

        if (!$data)
            return [];

        $result = [
            'user' => [],
            'phone' => [],
            'online' => [],
        ];

        if (preg_match("/<h3>(.*)<\/h3>/iUs", $data, $output)) {
            $result['user']['name'] = $output[1];
        }

        if (preg_match("/<div class=\"adr\">(.*)<\/div>/iUs", $data, $output)) {
            $result['user']['address'] = trim(strip_tags($output[1]));
        }

        if (preg_match("/<dt>Phone<\/dt>(\s*)<dd>(.*)<\/dd>/iUs", $data, $output)) {
            $result['phone']['number'] = $output[2];
        }

        if (preg_match("/<dt>Birthday<\/dt>(\s*)<dd>(.*)<\/dd>/iUs", $data, $output)) {
            $result['user']['birthday'] = new \DateTime($output[2]);
        }

        if (preg_match("/<dt>Email Address<\/dt>(\s*)<dd>(\S*)(\s+)/iUs", $data, $output)) {
            $result['online']['email'] = $output[2];
        }

        if (preg_match("/<dt>Username<\/dt>(\s*)<dd>(.*)<\/dd>/iUs", $data, $output)) {
            $result['online']['username'] = $output[2];
        }

        if (preg_match("/<dt>Password<\/dt>(\s*)<dd>(.*)<\/dd>/iUs", $data, $output)) {
            $result['online']['password'] = $output[2];
        }

        if (preg_match("/<dt>Browser user agent<\/dt>(\s*)<dd>(.*)<\/dd>/iUs", $data, $output)) {
            $result['online']['browser'] = $output[2];
        }

        $result['user']['gender'] = preg_match("/alt=\"Male\"/iU", $data, $output) ? 'Male' : 'Female';

        return $result;
    }
}
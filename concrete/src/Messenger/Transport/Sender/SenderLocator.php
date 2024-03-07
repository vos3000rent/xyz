<?php
namespace Concrete\Core\Messenger\Transport\Sender;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

class SenderLocator implements ServiceProviderInterface
{

    protected $senders = [];

    public function addSender(string $handle, callable $sender)
    {
        $this->senders[$handle] = $sender;
    }

    public function get(string $id): mixed
    {
        $sender = $this->senders[$id];
        if (is_callable($sender)) {
            $sender = $sender();
        }
        return $sender;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->senders);
    }

    public function getProvidedServices(): array
    {
        return $this->senders;
    }


}
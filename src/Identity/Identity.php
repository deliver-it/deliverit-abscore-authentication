<?php

namespace ABSCore\Authentication\Identity;

use Serializable;

class Identity implements IdentityInterface, Serializable
{
    protected $data = [];

    public function set($identifier, $data) {
        $this->data[$identifier] = $data;
        return $this;
    }

    public function get($identifier)
    {
        $result = null;
        if (array_key_exists($identifier, $this->data)) {
            $result = $this->data[$identifier];
        }

        return $result;
    }

    public function getArrayCopy()
    {
        return $this->data;
    }

    public function exchangeArray($data)
    {
        $this->data = $data;
        return $this;
    }

    public function serialize()
    {
        return serialize($this->getArrayCopy());
    }

    public function unserialize($data)
    {
        $this->exchangeArray(unserialize($data));
        return $this;
    }
}

<?php

namespace ABSCore\Authentication\Adapter;

use Zend\Authentication\Adapter\Ldap as LdapAdapter;
use Zend\Authentication\Result;

class Ldap extends LdapAdapter
{
    use IdentityPrototypeTrait;
    public function authenticate() 
    {
        $result = parent::authenticate();
        $identity = $result->getIdentity();
        if (!is_null($identity)) {
            $params = explode('@', $identity);
            $identity = $this->createIdentity(['samaccountname' => $params[0]]);
            $result = new Result($result->getCode(), $identity, $result->getMessages());
        }
        return $result;
    }
}



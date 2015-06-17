<?php

namespace ABSCore\Authentication\Adapter;
use ABSCore\Authentication\Identity;

trait IdentityPrototypeTrait
{
    protected $identityPrototype;

    public function setIdentityPrototype(Identity\IdentityInterface $prototype)
    {
        $this->identityPrototype = $prototype;

        return $this;
    }

    /**
     * Obtenção da identidade do protótipo
     *
     * @access public
     * @return null
     */
    public function getIdentityPrototype()
    {
        if (is_null($this->identityPrototype)) {
            $this->setIdentityPrototype(new Identity\Identity());
        }
        return $this->identityPrototype;
    }

    /**
     * Cria a identidade de um usuário
     *
     * @param array $data Dados do usuário
     * @access protected
     * @return mixed Clone do objeto de protótipo
     */
    protected function createIdentity(array $data)
    {
        $prototype = clone $this->getIdentityPrototype();
        $prototype->exchangeArray($data);
        return $prototype;
    }
}

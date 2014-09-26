<?php

namespace ABSCore\Authentication\Adapter;

use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Result;
use ABSCore\DataAccess\DataAccessInterface;
use Closure;


/**
 * Adaptador que acessa os dados através de uma interface de DataAccess
 *
 * @uses AdapterInterface
 * @category ABSCore
 * @package Authentication
 * @subPackage Authentication\Adapter
 */
class DataAccess implements AdapterInterface
{

    /**
     * Nome do usuário
     *
     * @var string
     * @access protected
     */
    protected $username;

    /**
     * Senha do usuário
     *
     * @var string
     * @access protected
     */
    protected $password;

    /**
     * Método de autenticação
     *
     * @var \Closure
     * @access protected
     */
    protected $method;

    /**
     * Interface de acesso aos dados de usuário
     *
     * @var \ABSCore\DataAccess\DataAccessInterface
     * @access protected
     */
    protected $dataAccess;

    /**
     * Campo que identifica o nome do usuário
     *
     * @var string
     * @access protected
     */
    protected $usernameField = 'username';

    /**
     * Campo que identifica a senha do usuário
     *
     * @var string
     * @access protected
     */
    protected $passwordField = 'password';

    /**
     * Conjunto de opções a serem passadas na busca do usuário
     *
     * @var array
     * @access protected
     */
    protected $options;

    /**
     * Protótipo para a identidade do usuário
     *
     * @var mixed
     * @access protected
     */
    protected $identityPrototype;

    /**
     * Construtor da classe
     *
     * @param string $username Nome do usuário
     * @param string $password Senha do usuário
     * @param \ABSCore\DataAccess\DataAccessInterface $dataAccess Interface de acesso aos dados de usuário
     * @access public
     */
    public function __construct($username, $password, DataAccessInterface $dataAccess)
    {
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setDataAccess($dataAccess);
    }

    /**
     * Realiza a autenticação do usuário.
     *
     * @access public
     * @return \Zend\Authentication\Result
     */
    public function authenticate()
    {
        $username = $this->getUsername();
        $usernameField = $this->getUsernameField();
        $user = $this->getDataAccess()->fetchAll(
            array($usernameField => $username), $this->getOptions()
        );

        if (!is_array($user)) {
            if (is_object($user) && method_exists($user, 'toArray')) {
                $user = $user->toArray();
            } else {
                return new Result(Result::FAILURE_UNCATEGORIZED, null,
                                  array('The result of DataAccess search is not an array and cannot be converted!'));
            }
        }

        if (empty($user)) {
            return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null, array("User $username is not found!"));
        }

        if (count($user) > 1) {
            return new Result(Result::FAILURE_IDENTITY_AMBIGUOUS, null,
                                                          array("Exists more than 1 user with '$username' username!"));
        }

        $user = current($user);
        $method = $this->getAuthenticationMethod();

        $method = $method->bindTo($this, $this);
        if ($method($user, $this->getPassword())) {
            return new Result(Result::SUCCESS, $this->createIdentity($user), array('Successful authentication!'));
        }

        return new Result(Result::FAILURE_CREDENTIAL_INVALID, null, array('Invalid password!'));
    }

    /**
     * Obtêm o nome do campo que identifica o nome de usuário
     *
     * @access public
     * @return string
     */
    public function getUsernameField()
    {
        return $this->usernameField;
    }

    /**
     * Obtêm o nome do campo que identifica a senha do usuário
     *
     * @access public
     * @return string
     */
    public function getPasswordField()
    {
        return $this->passwordField;
    }

    /**
     * Define o campo da senha
     *
     * @param string $field
     * @access public
     * @return DataAccess
     */
    public function setPasswordField($field)
    {
        $this->passwordField = (string)$field;
        return $this;
    }

    /**
     * Define o campo do nome do usuário
     *
     * @param string $field
     * @access public
     * @return DataAccess
     */
    public function setUsernameField($field)
    {
        $this->usernameField = $field;
    }


    /**
     * Define o conjunto de opções a serem passadas na obtenção do usuário
     *
     * @param array $options
     * @access public
     * @return DataAccess
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Obtêm o conjunto de opções a serem passadas na obtenção do usuário
     *
     * @access public
     * @return array
     */
    public function getOptions()
    {
        return (array)$this->options;
    }

    /**
     * Obtenção da interface de acesso à dados de usuário
     *
     * @access public
     * @return \ABSCore\DataAccess\DataAccessInterface
     */
    public function getDataAccess()
    {
        return $this->dataAccess;
    }

    /**
     * Define a interface de acesso à dados de usuário
     *
     * @param \ABSCore\DataAccess\DataAccessInterface $dataAccess
     * @access public
     * @return DataAccess
     */
    public function setDataAccess(DataAccessInterface $dataAccess)
    {
        $this->dataAccess = $dataAccess;
        return $this;
    }

    /**
     * Define o método de autenticação
     *
     * @param \Closure $method
     * @access public
     * @return DataAccess
     */
    public function setAuthenticationMethod(Closure $method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Obtenção do método de autenticação do usuário
     *
     * @access public
     * @return \Closure
     */
    public function getAuthenticationMethod()
    {
        if (is_null($this->method)) {
            $this->method = $this->getDefaultAuthenticationMethod();
        }
        return $this->method;
    }

    /**
     * Define a senha do usuário
     *
     * @param string $password
     * @access public
     * @return DataAccess
     */
    public function setPassword($password)
    {
        $this->password = (string)$password;

        return $this;
    }

    /**
     * Define o nome do usuário
     *
     * @param string $username
     * @access public
     * @return DataAccess
     */
    public function setUsername($username)
    {
        $this->username = (string)$username;

        return $this;
    }

    /**
     * Define o protótipo da identidade do usuário
     *
     * @throws \RuntimeException
     * @param mixed $prototype
     * @access public
     * @return DataAccess
     */
    public function setIdentityPrototype($prototype)
    {
        if (!is_object($prototype)) {
            throw new \RuntimeException('Prototype must be an object');
        }

        if (!method_exists($prototype, 'exchangeArray')) {
            throw new \RuntimeException('The "exchangeArray" method not exists in '.get_class($prototype));
        }

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
            $this->setIdentityPrototype(new \ArrayObject);
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

    /**
     * Obtêm o nome do usuário
     *
     * @access protected
     * @return string
     */
    protected function getUsername()
    {
        return $this->username;
    }


    /**
     * Obtêm a senha do usuário
     *
     * @access protected
     * @return string
     */
    protected function getPassword()
    {
        return $this->password;
    }

    /**
     * Obtenção do método padrão para autenticação
     *
     * @access protected
     * @return \Closure
     */
    protected function getDefaultAuthenticationMethod()
    {
        return function($user, $passedPassword) {
            $password = $user[$this->getPasswordField()];
            return sha1($passedPassword) == $password;
        };
    }
}

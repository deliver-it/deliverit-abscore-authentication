<?php

namespace ABSCore\AuthenticationTest\Adapter;

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Config as ServiceConfig;
use Zend\Authentication\Result;
use Zend\Db\ResultSet\ResultSet;
use PHPUnit_Framework_TestCase;

use ABSCore\Authentication\Adapter\DataAccess as DataAccessAdapter;

class DataAccessTest extends PHPUnit_Framework_TestCase
{

    public function testSuccessfulAuthentication()
    {
        $dataAccess = $this->getMockedDataAccess();
        $resultSet = new ResultSet();
        $resultSet->initialize(
            array(array('username' => 'teste', 'password' => sha1('teste123')))
        );
        $options = array('opt' => true);
        $dataAccess->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue($resultSet))
            ->with(array('username' => 'teste'), $options);

        $adapter = new DataAccessAdapter('teste','teste123', $dataAccess);
        $adapter->setOptions($options);
        $result = $adapter->authenticate();
        $this->assertEquals($result->getCode(),Result::SUCCESS);
        $this->assertInstanceOf('ArrayObject', $result->getIdentity());
    }

    public function testDataAccessResultIsNotArray()
    {
        $dataAccess = $this->getMockedDataAccess();
        $dataAccess->method('fetchAll')->will($this->returnValue(1));
        $adapter = new DataAccessAdapter('teste','teste123', $dataAccess);
        $result = $adapter->authenticate();
        $this->assertEquals($result->getCode(),Result::FAILURE_UNCATEGORIZED);
    }

    public function testUserNotExists()
    {
        $dataAccess = $this->getMockedDataAccess();
        $dataAccess->method('fetchAll')->will($this->returnValue(array()));
        $adapter = new DataAccessAdapter('teste','teste123', $dataAccess);
        $result = $adapter->authenticate();
        $this->assertEquals($result->getCode(), Result::FAILURE_IDENTITY_NOT_FOUND);
    }

    public function testExistsMoreThanOneUser()
    {
        $dataAccess = $this->getMockedDataAccess();
        $user = array('username' => 'teste', 'password' => sha1('teste123'));
        $dataAccess->method('fetchAll')->will($this->returnValue(array($user, $user)));
        $adapter = new DataAccessAdapter('teste','teste123', $dataAccess);
        $result = $adapter->authenticate();
        $this->assertEquals($result->getCode(), Result::FAILURE_IDENTITY_AMBIGUOUS);
    }

    public function testInvalidPassword()
    {
        $dataAccess = $this->getMockedDataAccess();
        $user = array('username' => 'teste', 'password' => sha1('teste123'));
        $dataAccess->method('fetchAll')->will($this->returnValue(array($user)));
        $adapter = new DataAccessAdapter('teste','teste12', $dataAccess);
        $result = $adapter->authenticate();
        $this->assertEquals($result->getCode(), Result::FAILURE_CREDENTIAL_INVALID);
    }

    public function testChangeFields()
    {
        $dataAccess = $this->getMockedDataAccess();
        $user = array('login' => 'teste', 'pass' => sha1('teste123'));
        $dataAccess->method('fetchAll')
            ->will($this->returnValue(array($user)))
            ->with(array('login' => 'teste'),array());
        $adapter = new DataAccessAdapter('teste','teste123', $dataAccess);
        $adapter->setPasswordField('pass')->setUsernameField('login');
        $result = $adapter->authenticate();
        $this->assertEquals($result->getCode(), Result::SUCCESS);
    }

    public function testCustomClosure()
    {
        $dataAccess = $this->getMockedDataAccess();
        $user = array('username' => 'teste', 'password' => md5('teste123'));
        $dataAccess->method('fetchAll')
            ->will($this->returnValue(array($user)));
        $adapter = new DataAccessAdapter('teste','teste123', $dataAccess);

        $method = function($user, $passedPassword) {
            $password = $user[$this->getPasswordField()];
            return md5($passedPassword) == $password;
        };

        $adapter->setAuthenticationMethod($method);
        $result = $adapter->authenticate();
        $this->assertEquals($result->getCode(), Result::SUCCESS);
    }

    public function testNonObjectPrototype()
    {
        $dataAccess = $this->getMockedDataAccess();
        $adapter = new DataAccessAdapter('teste','teste123', $dataAccess);
        $this->setExpectedException('RuntimeException', 'Prototype must be an object');
        $adapter->setIdentityPrototype(array());
    }

    public function testInvalidPrototypeObject()
    {
        $dataAccess = $this->getMockedDataAccess();
        $adapter = new DataAccessAdapter('teste','teste123', $dataAccess);
        $prototype = new \StdClass;
        $this->setExpectedException('RuntimeException','The "exchangeArray" method not exists in '.get_class($prototype));
        $adapter->setIdentityPrototype($prototype);
    }

    public function testCustomIdentityPrototype()
    {
        $result = array(
            array('username' => 'teste', 'password' => sha1('teste123'))
        );

        $dataAccess = $this->getMockedDataAccess();
        $dataAccess->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue($result));

        $adapter = new DataAccessAdapter('teste','teste123', $dataAccess);

        $prototype = $this->getMockBuilder('ArrayObject')
            ->disableOriginalConstructor()
            ->setMethods(array('exchangeArray'))
            ->getMock();
        $prototype->expects($this->once())
            ->method('exchangeArray');

        $adapter->setIdentityPrototype($prototype);
        $result = $adapter->authenticate();
        $this->assertEquals($result->getCode(),Result::SUCCESS);
    }



    protected function getMockedDataAccess()
    {
        return $this->getMockBuilder('ABSCore\DataAccess\DataAccessInterface')
            ->setMethods(array('fetchAll'))
            ->getMock();
    }
}

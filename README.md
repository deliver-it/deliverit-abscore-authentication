Authetication
==============

Módulo de implementações de mecanismos de autencação

Exemplos de utilização
----------------------

#### Adaptador DataAccess

    use ABSCore\DataAccess\DBTable;
    use ABSCore\Authentication\Adapter\DataAccess as AuthAdapter;
    use Zend\Authentication\AuthenticationService;
    use Zend\Mvc\Controller\AbstractActionController;

    class TestController extends AbstractActionController
    {
        public function indexAction()
        {
            $sl = $this->getServiceLocator();
            $adapter = $sl->get('Zend\Db\Adapter\Adapter');
            $dbTable = new DBTable('users','id',$sl);
            $dbTable->setAdapter($adapter);
            $auth = new AuthenticationService();

            $authAdapter = new AuthAdapter('jose','teste123',$dbTable);
            $authAdapter->setOptions(array('paginated' => false));

            $result = $auth->authenticate($authAdapter);

            switch ($result->getCode()) {
                // Inclusão dos cases de acordo com o código do resultado
            }
        }
    }


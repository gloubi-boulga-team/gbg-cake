Gbg/CakePHP ORM must not be called before plugins_loaded `event`

### Filters :

  - `Gbg/Cake5.Log.initLogger` : called prior to \Cake5\Log\Log initialization to get full Logger config

      @param array $configurations

      @return array $configurations


  - `Gbg/Cake5.Log.initLoggerDefaultPath` : called prior to \Cake5\Log\Log initialization to get default log path

      @param string $defaultPath

      @return string $defaultPath


  - `Gbg/Cake5.Cache.initCache` : called prior to \Cake5\Cache\Cache initialization to get full Cache config

      @param array $configurations

      @return array $configurations


  - `Gbg/Cake5.Orm.initTableLocator` : called prior to \Cake5\ORM\Locator\TableLocator initialization

      @param \Gbg\Cake5\ORM\TableLocator $tableLocator

      @return \Gbg\Cake5\ORM\TableLocator $tableLocator


  - `Gbg/Cake5.Orm.initConnectionManager` : called prior to \Cake5\Datasource\ConnectionManager configuration
      https://book.cakephp.org/5/fr/orm/database-basics.html#Cake\Datasource\ConnectionManager
      
      @param array $configurations

      @return array $configurations


  - `Gbg/Cake5.Orm.initQueryLogger` : called prior to \Gbg\Cake5\Orm\Database\Log\QueryLogger configuration

      @param array $configuration

      @return array $configuration


### Actions :

  - `Gbg/Cake5.Cache.loaded` : called after `plugins_loaded` and after Gbg/Cake5 Cache have been configured


  - `Gbg/Cake5.Log.loaded` : called after `plugins_loaded` and after Gbg/Cake5 Log have been configured


  - `Gbg/Cake5.QueryLogger.loaded` : called after `plugins_loaded` and after Gbg/Cake5 QueryLogger have been configured


  - `Gbg/Cake5.Orm.loaded` : called after `plugins_loaded` and after Gbg/Cake5 Orm have been configured


  - `Gbg/Cake5.loaded` : called after all Gbg/Cake5 bootstrap file have been processed


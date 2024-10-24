<?php

require_once(INCLUDE_DIR.'class.plugin.php');
require_once('config.php');

class StaffLogin extends Plugin {
    var $config_class = "stafflogin";

    function bootstrap() {
        $config = $this->getConfig();
        $staffAccess = $config->get('HIDE_LOCAL_STAFF_LOGIN');
        if ($staffAccess) {
          require_once('stafflogin.php');
            StaffLogin::register(
                new StaffLogin($this->getConfig()));
        }
    }
}

require_once(INCLUDE_DIR.'UniversalClassLoader.php');
use Symfony\Component\ClassLoader\UniversalClassLoader_osTicket;
$loader = new UniversalClassLoader_osTicket();
$loader->registerNamespaceFallbacks(array(
    dirname(__file__).'/lib'));
$loader->register();
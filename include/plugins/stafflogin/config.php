<?php

require_once INCLUDE_DIR . 'class.plugin.php';

class AnttStaffLoginConfig extends PluginConfig {
  function translate() {
    if (!method_exists('Plugin', 'translate')) {
      return array(
        function($x) { return $x; },
        function($x, $y, $n) { return $n != 1 ? $y : $x; },
      );
    }
    return Plugin::translate('stafflogin');
  }

  function getOptions() {
    list($__, $_N) = self::translate();
    return array(
      'HIDE_LOCAL_STAFF_LOGIN' => new BooleanField(array(
        'label' => $__('Hide local login for staff accounts.')
      )),
    );
  }
}

<?php

class StaffLogin {

  var $config;
  function __construct($config) {
    $this->config = $config;
  }
}

class StaffLogin extends ExternalStaffAuthenticationBackend {
  static $id = "StaffLogin";
  function __construct($config) {
    $this->config = $config;
    if ($this->config->get('HIDE_LOCAL_STAFF_LOGIN')) {
      ?>
      <script>window.onload = function() {
        var login = document.getElementById('login');
        login.remove();
      };
      </script>
    <?php
    }
    }
}

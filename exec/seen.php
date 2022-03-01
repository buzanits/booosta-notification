<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use booosta\Framework as b;
b::croot();
b::load();

class App extends booosta\usersystem\Webappuser
{
  protected $table = 'notification';
  protected $userfield = 'user';


  public function __construct()
  {
    parent::__construct();

    if($this->user_class == 'adminuser'):
      $this->table = 'notificationadmin';
      $this->userfield = 'adminuser';
    endif;
  }

  protected function action_default()
  {
    $id = intval($this->VAR['object_id']);

    if($this->config('keep_notifications')) $this->DB->query("update `$this->table` set seen=now() where id='$id' and (`$this->userfield`='$this->user_id' or `$this->userfield` is null)");
    else $this->DB->query("delete from `$this->table` where id='$id' and (`$this->userfield`='$this->user_id' or `$this->userfield` is null)", false);  // false = do_log

    booosta\ajax\Ajax::print_response(null, ['result' => '']);
    $this->no_output = true;
  }
}

$app = new App();
$app->auth_user();
$app();

<?php
namespace booosta\notification;

use \booosta\Framework as b;
b::init_module('notification');

class Notification extends \booosta\base\Module
{
  use moduletrait_notification;

  protected $table = 'notification';
  protected $userfield = 'user';

  
  public function after_instanciation()
  {
    if(is_object($this->topobj->get_user()) && $this->topobj->get_user()->get_user_type() == 'adminuser'):
      $this->table = 'notificationadmin';
      $this->userfield = 'adminuser';
    endif;
  }
  
  public function add($text, $user, $typ = 'success', $autoseen = false)
  {
    return $this->insert($user, null, $typ, $text, null, $autoseen);
  }

  public function add_session($text, $user, $typ = 'success')
  {
    return $this->insert_session($user, null, $typ, $text, null);
  }

  public function get_unseen($user, $with_null = false)
  {
    $user = intval($user);
    $unseen = $this->getall([$this->userfield => $user, 'seen' => null]);

    if($with_null):
      $unseen_null = $this->getall([$this->userfield => null, 'seen' => null]);
      $unseen = array_merge($unseen, $unseen_null);
    endif;

    #\booosta\debug($unseen);
    return $unseen;
  }

  public function get_unseen_html($user, $with_null = false)
  {
    $elib = 'vendor/booosta/notification/exec';
    $parser = $this->makeInstance('templateparser');
    $notifications = $this->get_unseen($user, $with_null);
    $html = '';
    #\booosta\debug($notifications);

    foreach($notifications as $notification):
      $typ = $notification['typ'] ?: 'success';
      $tpl = file_get_contents("incl/notification_$typ.tpl") ?: file_get_contents("$elib/$typ.tpl") ?: file_get_contents("$typ.tpl") ?: '{%message}';
      #\booosta\debug("typ: $typ, tpl: $tpl");
      $html .= $parser->parseTemplate($tpl, $notification);
    endforeach;

    #\booosta\debug($html);
    return $html;
  }

  protected function insert($user, $dtime, $typ = 'success', $message = '', $seen = '', $autoseen = false)
  {
    $obj = $this->makeDataobject($this->table);
    $obj->set($this->userfield, $user);
    $obj->set('dtime', $dtime ?? date('Y-m-d H:i:s'));
    $obj->set('typ', $typ);
    $obj->set('message', $message);
    $obj->set('seen', $seen === '' ? null : $seen);
    $obj->set('autoseen', $autoseen ? 1 : 0);

    $newid = $obj->insert();
    if($error = $obj->get_error()) return "ERROR: $error";
    return $newid;
  }

  // insert notification that is only displayed once in this session and then destroyed
  protected function insert_session($user, $dtime, $typ, $message, $seen)
  {
    $id = null;

    $dtime = $dtime ?? date('Y-m-d H:i:s');
    $_SESSION['notifications'][] = compact('id', 'user', 'dtime', 'typ', 'message', 'seen');
  }

  protected function getall($search = [])
  {
    $clause = ' 0=0 ';
    foreach($search as $field=>$value):
      if($value === null) $clause .= " and `$field` is null ";
      else $clause .= " and `$field`='$value' ";
    endforeach;

    $notifications = $this->DB->query_arrays("select * from `$this->table` where $clause");

    #\booosta\debug($_SESSION['notifications']);
    if(is_array($_SESSION['notifications'])):
      $notifications = array_merge($notifications, $_SESSION['notifications']);
      $_SESSION['notifications'] = [];
    endif;

    if($this->config('keep_notifications')) $this->DB->query("update `$this->table` set seen=now() where $clause and autoseen='1'");
    else $this->DB->query("delete from `$this->table` where $clause and autoseen='1'", false);  // false = do_log
    
    return $notifications;
  }
}

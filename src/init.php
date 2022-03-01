<?php
namespace booosta\notification;

\booosta\Framework::add_module_trait('webapp', 'notification\webapp');

trait webapp
{
  protected function beforeparse_notification()
  {
    if(!$this->config('use_notifications')) return;

    $elib = 'vendor/booosta/notification/exec';
    
    // dont show notification in actions where page will be forwarded
    if(in_array($this->action, ['newdo', 'editdo', 'deleteyes'])) $this->skip_notification_display = true;

    $this->show_notifications();
    $postfix = $this->user_class == 'adminuser' ? '_admin' : '';
    
    if($this->TPL['_notifications']):
      $code = "var res = this.id.split('_'); var id = res[1]; \$.ajax({ url: '$elib/seen{$postfix}.php?object_id=' + id });";
      $this->add_jquery_ready("$('.booosta-notification').on('closed.bs.alert', function () { $code });");
    endif;
  }

  protected function show_notifications()
  {
    #\booosta\debug("skip_notification_display: $this->skip_notification_display");
    if($this->skip_notification_display) return;

    $with_null = $this->config('ignore_null_notifications') ? false : true;
    $noti = $this->makeInstance('notification');
    $this->TPL['_notifications'] .= $noti->get_unseen_html($this->user_id, $with_null);
    #\booosta\debug($this->TPL['_notifications']);
  }

  protected function add_notification($text, $user_id = null, $type = 'success', $autoseen = false)
  {
    $noti = $this->makeInstance('notification');
    $result = $noti->add($text, $user_id ?? $this->user_id, $type, $autoseen);
    #\booosta\debug($result);
  }

  protected function add_session_notification($text, $user_id = null, $type = 'success')
  {
    $noti = $this->makeInstance('notification');
    $result = $noti->add_session($text, $user_id ?? $this->user_id, $type);
  }
}

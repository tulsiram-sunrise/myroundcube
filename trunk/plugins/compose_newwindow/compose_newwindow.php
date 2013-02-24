<?php
/**
* compose_newwindow
* @version 2.8.8 - 10.02.2013
* @author Roland 'rosali' Liebl - myroundcube@mail4us.net
* @url http://myroundcube.googlecode.com
*
* based on
* @version 2.05 (20100218)
* @author Karl McMurdo (user xrxca on roundcubeforum.net)
* @url http://github.com/xrxca/cnw
* @copyright (c) 2010 Karl McMurdo
*
*/
class compose_newwindow extends rcube_plugin
{
    public $task = '?(?!login|logout).*';
    public $noajax = true;
    private $rc;
    
    /* unified plugin properties */
    static private $plugin = 'compose_newwindow';
    static private $author = 'myroundcube@mail4us.net';
    static private $authors_comments = '<a href="http://myroundcube.com/myroundcube-plugins/compose_newwindow-plugin" target="_new">Documentation</a>';
    static private $download = 'http://myroundcube.googlecode.com';
    static private $version = '2.8.8';
    static private $date = '10-02-2013';
    static private $licence = 'GPL';
    static private $requirements = array(
      'Roundcube' => '0.8',
      'PHP' => '5.2.1'
    );

    function init()
    {
      $this->rc = &rcmail::get_instance();
      $this->add_texts('localization/', true);
      $this->include_script('composenewwindow.js');
      $this->include_script('popupwarning.js');
      $this->rc->output->add_label('compose_newwindow.popupblockerwarning');
      $this->add_hook('render_page', array($this, 'render_page'));
      $this->register_action('plugin.composenewwindow_abooksend', array($this, 'composenewwindow_abooksend'));
      $this->add_hook('send_page', array($this, 'send'));
    }
    
    static public function about($keys = false){
      $requirements = self::$requirements;
      foreach(array('required_', 'recommended_') as $prefix){
        if(is_array($requirements[$prefix.'plugins'])){
          foreach($requirements[$prefix.'plugins'] as $plugin => $method){
            if(class_exists($plugin) && method_exists($plugin, 'about')){
            /* PHP 5.2.x workaround for $plugin::about() */
            $class = new $plugin(false);
            $requirements[$prefix.'plugins'][$plugin] = array(
              'method' => $method,
              'plugin' => $class->about($keys),
            );
            }
            else{
              $requirements[$prefix.'plugins'][$plugin] = array(
                'method' => $method,
                'plugin' => $plugin,
              );
            }
          }
        }
      }
      $rcmail_config = array();
      $ret = array(
        'plugin' => self::$plugin,
        'version' => self::$version,
        'date' => self::$date,
        'author' => self::$author,
        'comments' => self::$authors_comments,
        'licence' => self::$licence,
        'download' => self::$download,
        'requirements' => $requirements,
      );
      if(is_array($keys)){
        $return = array('plugin' => self::$plugin);
        foreach($keys as $key){
          $return[$key] = $ret[$key];
        }
        return $return;
      }
      else{
        return $ret;
      }
    }
  
    function render_page($args)
    {
      if($args['template'] == 'compose'){
        $temparr = array();
        foreach($_GET as $key => $val){
          $temparr[] = $key . "=" . $val;
        }
        $this->rc->output->add_script('opencomposewindowcaller("' . './?' . join($temparr,'&') . '")','foot');
        $skin = $this->rc->config->get('skin');
        $this->include_stylesheet('skins/' . $skin . '/compose_newwindow.css');
      }
      $content = $args['content'];
      $content = str_replace(
        array(
          "return rcmail.command('compose'",
          "return rcmail.command('reply'", 
          "return rcmail.command('reply-all'",
          "return rcmail.command('reply-list'",
          "return rcmail.command('forward'",
          "return rcmail.command('forward-attachment'",
          "return rcmail.command('identities')",
        ),
        array(
          "return composenewwindowcommandcaller('compose'",
          "return composenewwindowcommandcaller('reply'",
          "return composenewwindowcommandcaller('reply-all'",
          "return composenewwindowcommandcaller('reply-list'",
          "return composenewwindowcommandcaller('forward'",
          "return composenewwindowcommandcaller('forward-attachment'",
          "return opener.location.href='./?_task=settings&_action=identities'",
        ),
        $content
      );
      $this->rc->output->set_env('compose_newwindow', true);
      if($this->rc->task == 'mail'){
        $content = str_replace(
          array(
            "rcmail.command('edit'"
          ),
          array(
            "composenewwindowcommandcaller('edit'",
          ),
          $content
        );
      }
      $args['content'] = $content;
      return $args;
    }
    
    function send($args)
    {
      if($this->rc->task == 'mail'){
        $content = $args['content'];
        $content = str_replace('plugins/contextmenu/contextmenu.js', 'plugins/compose_newwindow/contextmenu.js', $content);
        $args['content'] = $content;
      }
      return $args;
    }
    
}
?>
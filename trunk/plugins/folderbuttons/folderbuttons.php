<?php
/**
 * folderbuttons
 *
 * Plugin to add expandall and collapseall links to mailboxoptions container
 *
 * @version 1.6.1 - 27.08.2012
 * @author Mike Maraghy, Roland 'rosali' Liebl (http://myroundcube.googlecode.com)
 * 
 **/
 
/**
 *
 * Usage: http://mail4us.net/myroundcube/
 *
 **/
 
/**
 * 
 * Notice: This plugin is redundant if you use contextmenu plugin
 *
 **/
class folderbuttons extends rcube_plugin
{
  public $task = 'mail|settings';
  
  /* unified plugin properties */
  static private $plugin = 'folderbuttons';
  static private $author = 'myroundcube@mail4us.net';
  static private $authors_comments = '<a href="http://myroundcube.com/myroundcube-plugins/folderbuttons-plugin" target="_new">Documentation</a>';
  static private $download = 'http://myroundcube.googlecode.com';
  static private $version = '1.6.1';
  static private $date = '27-08-2012';
  static private $licence = 'GPL';
  static private $requirements = array(
    'Roundcube' => '0.7.1',
    'PHP' => '5.2.1'
  );
  static private $prefs = null;
  static private $config_dist = null;
  
  function init(){
    $this->include_script('folderbuttons.js');
    $this->add_hook('template_container', array($this, 'html_output'));
    $this->add_texts('localization', true);
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
    if(is_string(self::$config_dist)){
      if(is_file($file = INSTALL_PATH . 'plugins/' . self::$plugin . '/' . self::$config_dist))
        include $file;
      else
        write_log('errors', self::$plugin . ': ' . self::$config_dist . ' is missing!');
    }
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
    if(is_array(self::$prefs))
      $ret['config'] = array_merge($rcmail_config, array_flip(self::$prefs));
    else
      $ret['config'] = $rcmail_config;
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

  function html_output($p) {
    if ($p['name'] == "mailboxoptions") {
      $c .= "<li class=' separator_above'><a href='#' class='active' onclick='return expandall()'>" . $this->gettext("expandall") . "</a></li>\r\n";
      $d .= "<li><a href='#' class='active' onclick='return collapseall()'>" .$this->gettext("collapseall") . "</a></li>";
      $p['content'] = $c . $d . $p['content'];
    }
    return $p;
  }
}
?>
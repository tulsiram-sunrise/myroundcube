<?php

/**
 * Save password plugin
 *
 *
 * @version 1.3 - 22.04.2012
 * @author Roland 'rosali' Liebl
 * @website http://myroundcube.googlecode.com
 *
 **/
 
/**
 *
 * Usage: http://mail4us.net/myroundcube
 *
 *        Get the password when user is not logged in:
 *     
 *        $password = savepassword::getpw("myuser@mydomain.com");
 *
 * NOTICE:
 * main.inc.php:
 * $rcmail_config['des_key'] = 'rcmail-!24ByteDESkey*Str';
 *
 * If you change the key after first time usage, saved passwords gets invalid !!!
 * Also make sure to use same key on server rcube installation with access to a common database
 * 
 **/
 
class savepassword extends rcube_plugin
{
    /* unified plugin properties */
    static private $plugin = 'savepassword';
    static private $author = 'myroundcube@mail4us.net';
    static private $authors_comments = null;
    static private $download = 'http://myroundcube.googlecode.com';
    static private $version = '1.3';
    static private $date = '22-04-2012';
    static private $licence = 'GPL';
    static private $requirements = array(
      'Roundcube' => '0.7.1',
      'PHP' => '5.2.1'
    );
    static private $prefs = null;
    static private $config_dist = null;
    
    function init()
    {
      $this->add_hook('login_after', array($this, 'savepw'));
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
    
    function savepw($args)
    {
    
        $rcmail = rcmail::get_instance();

        if($_SESSION['user_id']){ // user has been authenticated successfully
          $query = "UPDATE ".get_table_name('users')."
                SET  password=? 
                WHERE user_id=?;";
                  
          $ret = $rcmail->db->query($query,$_SESSION['password'],$_SESSION['user_id']);
        
        }

        return $args;
    }
    
    function getpw($username){
    
        $rcmail = rcmail::get_instance();    
    
        $rcmail->db->query("
                SELECT * FROM ".get_table_name('users')."
                WHERE  username=?",
                $username);
            
        $user = $rcmail->db->fetch_assoc();
        
        if(isset($user['password'])){
          return $rcmail->decrypt($user['password']);
        }
        else{
          return false;
        }
    }

}

?>
<?php
/**
 * jappix4roundcube
 *
 * @version 2.0.7 - 16.11.2013
 * @author Roland 'rosali' Liebl
 * @website http://myroundcube.googlecode.com
 *
 *
 * Forked from: see below
 *
 **/

/**
 * jappix4roundcube
 *
 * Plugin to integrate Mini jappix in roundcube
 * Mini jappix : https://mini.jappix.com/get
 *
 * @version 1.0
 * @author RD
 * @url https://code.google.com/p/jappix4roundcube/
 */
 
class jappix4roundcube extends rcube_plugin {

  public $task = 'mail|settings|addressbook|jappix|dummy|logout';
  
  public $ttl = 604800;
  
  /* unified plugin properties */
  static private $plugin = 'jappix4roundcube';
  static private $author = 'myroundcube@mail4us.net';
  static private $authors_comments = '';
  static private $version = '2.0.7';
  static private $date = '16-11-2013';
  static private $licence = 'GPL';
  static private $requirements = array(
    'Roundcube' => '0.9',
    'PHP' => '5.2.1',
    'required_plugins' => array(
      'http_request' => 'require_plugins',
      'db_version' => 'require_plugin',
    ),
  );
  static private $tables = array(
    'jappix'
  );
  static private $db_version = array(
    'initial',
  );
  static private $prefs = null;
  static private $config_dist = 'config.inc.php.dist';
  
  function init() {
    $rcmail = rcmail::get_instance();
    
    /* DB versioning */
    if(is_dir(INSTALL_PATH . 'plugins/db_version')){
      $this->require_plugin('db_version');
      if(!$load = db_version::exec(self::$plugin, self::$tables, self::$db_version)){
        return;
      }
    }
    
    if($rcmail->action == 'compose' && $_GET['_extwin'] == 1){
      return;
    }

    $this->load_config();
    $this->add_texts('localization/', true);
    
    if($rcmail->task == 'settings'){
      $this->add_hook('preferences_sections_list', array($this, 'preferences_section'));
      $this->add_hook('preferences_list', array($this, 'preferences_list'));
      $this->add_hook('preferences_save', array($this, 'preferences_save'));
      $this->add_hook('password_change', array($this, 'password_change'));
    }

    if($rcmail->action != 'jappix.loadmini'){
      $this->include_stylesheet('skins/' . $rcmail->config->get('skin', 'larry') . '/jappix4roundcube.css');
    }
    
    $this->register_task('jappix');
    $this->register_action('jappix.getfile', array($this, 'getfile'));

    if(!$rcmail->config->get('jabber_username') || !$rcmail->config->get('jabber_domain', 'jappix.com')){
      return;
    }

    $this->register_action('index', array($this, 'action'));
    $this->register_action('jappix.loginframe', array($this, 'loginframe'));
    $this->register_action('jappix.loadmini', array($this, 'loadmini'));

    $skin = $rcmail->config->get('skin', 'larry');

    $this->add_button(array(
      'command' => 'jappix',
      'class' => 'button-jappix4roundcube',
      'classsel' => 'button-jappix4roundcube button-selected',
      'innerclass' => 'button-inner',
      'label' => 'jappix4roundcube.task',
    ), 'taskbar');
    
    if($rcmail->action == 'jappix.loginkey'){
      $this->loginkey();
    }
    
    if($rcmail->action == 'jappix.return_loginkey'){
      $this->return_loginkey();
    }
    
    $this->add_hook('render_page', array($this, 'redirect'));
    $this->add_hook('logout_after', array($this, 'breakframe'));
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
      'db_version' => self::$db_version,
      'date' => self::$date,
      'author' => self::$author,
      'comments' => self::$authors_comments,
      'licence' => self::$licence,
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
  
  function breakframe($args){
    if(class_exists('tabbed')){
      return $p;
    }
    $rcmail = rcmail::get_instance();
    $query = '?';
    foreach($_GET as $key => $val){
      $query .= $key . '=' . $val . '&';
    }
    $query = substr($query, 0, strlen($query) - 1);
    $rcmail->output->add_script('parent.location.href="./' . $query . '";');
    $rcmail->output->send('iframe');
    exit;
  }
  
  function redirect($p){
    if(class_exists('tabbed')){
      $this->cache();
      return $p;
    }
    $rcmail = rcmail::get_instance();
    if($p['template'] != 'summary.summary'){
      if($rcmail->config->get('jappix_mini', false) && (
                                                          $p['template'] == 'mail' ||
                                                          $p['template'] == 'addressbook' ||
                                                          $p['template'] == 'settings' ||
                                                          $p['template'] == 'compose' ||
                                                          $p['template'] == 'calendar.calendar' ||
                                                          $p['template'] == 'sticky_notes.sticky_notes' ||
                                                          $p['template'] == 'planner.planner' ||
                                                          $p['template'] == 'jappix4roundcube.jappix4roundcube'
                                                                                                                ) && !$_SESSION['jappixminiframe']){
        if($rcmail->action != 'plugin.summary' && !isset($_GET['_framed'])){
          $_SESSION['jappixminiframe'] = true;
          header('Location: ./?_task=jappix&_action=jappix.loadmini&_origtask=' . $rcmail->task . '&_origaction=' . $rcmail->action);
          exit;
        }
      }
      else if($p['template'] != 'login' && $p['template'] != 'jappix4roundcube.jappixmini4roundcube'){
        $rcmail->output->add_script('function sync_parent(){ var t = document.title; t = t.replace("' . $rcmail->config->get('product_name', '') . ' :: ' . '", ""); parent.document.title = t; parent.rc_task = rcmail.env.task; parent.rc_action = rcmail.env.action; window.setTimeout("sync_parent();", 1000); }; sync_parent();');
      }
      $rcmail->output->add_script("if(rcmail.env.task == 'jappix'){ parent.$('.jm_position').hide(); } else { if(parent.rcmail.env.jabber_mini == 1) { parent.$('.jm_position').show(); } };");
    }
    return $p;
  }
    
  function loadmini(){
    $rcmail = rcmail::get_instance();
    if(!$_SESSION['jappixmini']){
      $this->cache();
    }
    if($_SESSION['jappixmini']){
      $lg = explode('_', $_SESSION['language']);
      $lg = $lg[0];
      $rcmail->output->add_header(html::tag('script', array('type'=>'text/javascript', 'src' => 'https://' . $_SERVER['HTTP_HOST'] . '/?_task=jappix&_action=jappix.getfile&_file=mini.js&_l=' . $lg)));
      $rcmail->output->set_env('jabber_username', $rcmail->config->get('jabber_username'));
      $rcmail->output->set_env('jabber_domain', $rcmail->config->get('jabber_domain'));
      $rcmail->output->set_env('jabber_enc', $rcmail->config->get('jabber_enc'));
      $rcmail->output->set_env('jabber_mini', $rcmail->config->get('jappix_mini'));
      $rcmail->output->set_env('jabber_autologon', $rcmail->config->get('jappix_mini_autologon'));
      $this->include_script('jappix.js');
      $this->include_script('gibberish.js');
      $file = 'mini.css';
      $browser = new rcube_browser();
      if($browser->ie && $browser->ver < 7){
        $file = 'mini-ie.css';
      }
      $this->include_stylesheet('https://' . $_SERVER['HTTP_HOST'] . '/?_task=jappix&_action=jappix.getfile&_file=' . $file . '&_l=' . $lg);
      $this->include_stylesheet('jappix.css');
    }
    else{
      $rcmail->output->show_message('jappix4roundcube.failure', 'warning');
    }
    if(!$rcmail->config->get('jappix_mini')){
      $rcmail->output->add_script('parent.location.href="./?_task=' . $rcmail->task . '&_action=' . $rcmail->action . '"');
    }
    $rcmail->output->send('jappix4roundcube.jappixmini4roundcube');
  }
  
  function getfile(){
    $rcmail = rcmail::get_instance();
    $file = get_input_value('_file', RCUBE_INPUT_GET);
    $lang = get_input_value('_l', RCUBE_INPUT_GET);
    $sql = 'SELECT * FROM ' . get_table_name('jappix') . ' WHERE file=? AND lang=? LIMIT 1';
    $res = $rcmail->db->query($sql, $file, $lang);
    $content = $rcmail->db->fetch_assoc($res);
    header('Content-Type: ' . $content['contenttype']);
    header('Content-Length: ' . strlen($content['content']));
    $rcmail->output->future_expire_header(strtotime($content['ts']) - time() + $this->ttl);
    echo $content['content'];
    exit;
  }

  function preferences_section($args){
    $args['list']['jabber'] = array(
      'id'      => 'jabber',
      'section' => Q($this->gettext('jappixSection'))
    );
    return($args);
  }

  function preferences_list($args){
    if($args['section'] == 'jabber'){
      $rcmail = rcmail::get_instance();
      $jabber_username = $rcmail->config->get('jabber_username');
      $field_id_user = 'rcmfd_username';
      $input_user = new html_inputfield(array('name' => '_jabber_username', 'id' => $field_id_user, 'size' => 25));
      $jabber_domain = $rcmail->config->get('jabber_domain', 'jappix.com');
      $field_id_domain = 'rcmfd_domain';
      $input_domain = new html_inputfield(array('name' => '_jabber_domain', 'id' => $field_id_domain, 'size' => 25, 'readonly' => true));
      $args['blocks']['jabber']['options']['jabber_username'] = array(
        'title' => html::label($field_id, Q($this->gettext('jappixUsername'))),
        'content' => $input_user->show($jabber_username) . '&nbsp;@&nbsp;' . $input_domain->show($jabber_domain),
      );
      
      $jabber_enc = $rcmail->config->get('jabber_enc');
      $field_id = 'rcmfd_enc';
      $input = new html_passwordfield(array('name' => '_jabber_password', 'id' => $field_id, 'size' => 25, 'placeholder' => $jabber_enc ? $this->gettext('passwordisset') : $this->gettext('pleaseenterpassword')));
      $args['blocks']['jabber']['options']['jabber_password'] = array(
        'title' => html::label($field_id, Q($this->gettext('jappixPassword'))),
        'content' => $input->show(),
      );

      $jappix_mini = $rcmail->config->get('jappix_mini', 1);
      $field_id = 'rcmfd_use_mini';
      $checkbox = new html_checkbox(array('name' => '_jappix_mini', 'value' => 1, 'id' => $field_id, 'onclick' => "if(this.checked){ parent.parent.$('.jm_position').show(); } else { parent.parent.$('.jm_position').hide(); }; document.forms.form.submit()"));
      $args['blocks']['jabber']['options']['jappix_mini'] = array(
        'title' => html::label($field_id, Q($this->gettext('useminijappix'))),
        'content' => $checkbox->show($jappix_mini ? 1:0),
      );
      
      $jappix_mini_autologon = $rcmail->config->get('jappix_mini_autologon', 1);
      $field_id = 'rcmfd_use_mini_autologon';
      $checkbox = new html_checkbox(array('name' => '_jappix_mini_autologon', 'value' => 1, 'id' => $field_id, 'onclick' => "if(this.checked){ parent.parent.$('.jm_pane').trigger('click'); } else { parent.parent.disconnectMini(); }; document.forms.form.submit()"));
      $args['blocks']['jabber']['options']['jappix_mini_autologon'] = array(
        'title' => html::label($field_id, Q($this->gettext('minijappixautologon'))),
        'content' => $checkbox->show($jappix_mini_autologon ? 1:0),
      );
      
       /*
      $field_id = 'rcmfd_use_manager';
      $args['blocks']['jabber']['options']['jabber_manager'] = array(
        'title' => html::label($field_id, Q($this->gettext('manager'))),
        'content' => '<a target=\'_blank\' href=\''.$rcmail->config->get('jappix_url').'/?m=manager\'>'.Q($this->gettext('manager')).'</a>',
      );
      */
    }
    return $args;
  }

  function preferences_save($args){
    if($args['section'] == 'jabber'){
      include 'plugins/jappix4roundcube/GibberishAES.php';
      $rcmail = rcmail::get_instance();
      $username = trim(get_input_value('_jabber_username', RCUBE_INPUT_POST));
      if(preg_match('/[@\/\\ ]/', $username)){
        $rcmail->output->show_message('jappix4roundcube.usernameinvalid', 'error');
        $rcmail->output->set_env('jabber_username', $rcmail->config->get('jabber_username', ''));
        $args['abort'] = true;
      }
      else{
        $args['prefs']['jabber_username'] = $username;
        $rcmail->output->set_env('jabber_username', $args['prefs']['jabber_username']);
      }
      $domain = trim(get_input_value('_jabber_domain', RCUBE_INPUT_POST));
      if(!$this->is_valid_domain_name($domain)){
        $rcmail->output->show_message('jappix4roundcube.domaininvalid', 'error');
        $rcmail->output->set_env('jabber_domain', $rcmail->config->get('jabber_domain', 'jappix.com'));
        $args['abort'] = true;
      }
      else{
        $args['prefs']['jabber_domain'] = $domain;
        $rcmail->output->set_env('jabber_domain', $args['prefs']['jabber_domain']);
      }
      $password = trim(get_input_value('_jabber_password', RCUBE_INPUT_POST));
      if($password){
        $key = md5($rcmail->user->data['username'] . ':' . $rcmail->decrypt($_SESSION['password']));
        $enc = GibberishAES::enc($password, $key);
        $args['prefs']['jabber_enc'] = $enc;
        $rcmail->output->set_env('jabber_enc', $enc);
      }
      $args['prefs']['jappix_mini'] = get_input_value('_jappix_mini', RCUBE_INPUT_POST);
      $args['prefs']['jappix_mini'] = $args['prefs']['jappix_mini'] ? 1 : 0;
      $args['prefs']['jappix_mini_autologon'] = get_input_value('_jappix_mini_autologon', RCUBE_INPUT_POST);
      $args['prefs']['jappix_mini_autologon'] = $args['prefs']['jappix_mini_autologon'] ? 1 : 0;
      $rcmail->output->set_env('jabber_mini',  $args['prefs']['jappix_mini'] ? true : false);
      $rcmail->output->set_env('jabber_autologon', $args['prefs']['jappix_mini_autologon'] ? true : false);
    }
    return $args;
  }
  
  function password_change($args){
    include 'plugins/jappix4roundcube/GibberishAES.php';
    $rcmail = rcmail::get_instance();
    $key = md5($rcmail->user->data['username'] . ':' . $args['old_pass']);
    $dec = GibberishAES::dec($rcmail->config->get('jabber_enc'), $key);
    $key = md5($rcmail->user->data['username'] . ':' . $args['new_pass']);
    $enc = GibberishAES::enc($args['new_pass'], $key);
    $rcmail->user->save_prefs(array('jabber_enc' => $enc));
    return $args;
  }
  
  function loginkey(){
    $rcmail = rcmail::get_instance();
    $key = md5($rcmail->user->data['username'] . ':' . $rcmail->decrypt($_SESSION['password']));
    $rcmail->output->command('jappix_loginkey', $key);
    $rcmail->output->send('mail');
  }
  
  function return_loginkey(){
    $rcmail = rcmail::get_instance();
    $key = md5($rcmail->user->data['username'] . ':' . $rcmail->decrypt($_SESSION['password']));
    echo $key;
    exit;
  }
  
  function loginframe(){
    $rcmail = rcmail::get_instance();
    $lg = explode('_', $_SESSION['language']);
    $lg = $lg[0];
    $src  = slashify($rcmail->config->get('jappix_url', 'https://jappix.com'));
    $html = '<!DOCTYPE html>' .
    html::tag('html', null, 
      html::tag('head', null,
        html::tag('title', null, 'Jappix') .
        html::tag('script', array('type' => 'text/javascript', 'src' => './program/js/jquery.min.js')) . 
        html::tag('script', array('type' => 'text/javascript', 'src' => './plugins/jappix4roundcube/gibberish.js')) .
        html::tag('script', array('type' => 'text/javascript'),
          '$.get("./", { _task : "jappix", _action : "jappix.return_loginkey" }, function(key){
            GibberishAES.size(256);
            var dec = GibberishAES.dec("' . $rcmail->config->get('jabber_enc') . '", $.trim(key));
            document.getElementById("q").value = dec;
            document.forms.form.submit();
          });'
        )
      ) .
      html::tag('body', null,
        html::tag('form', array('method' => 'get', 'action' => $src, 'name' => 'form'), 
          html::tag('input', array('type' => 'hidden', 'name' => 'u', 'id' => 'u', 'type' => 'hidden', 'value' => $rcmail->config->get('jabber_username') . '@' . $rcmail->config->get('jabber_domain'))) .
          html::tag('input', array('type' => 'hidden', 'name' => 'q', 'id' => 'q', 'type' => 'hidden')) .
          html::tag('input', array('type' => 'hidden', 'name' => 'l', 'value' => $lg)) .
          html::tag('input', array('type' => 'hidden', 'name' => 'h', 'value' => 1))
        )
      )
    );
    header('Content-Type: text/html');
    header('Content-Length: ' . strlen($html));
    send_nocacheing_headers();
    echo $html;
    exit;
  }

  function action(){
    $rcmail = rcmail::get_instance();
    $rcmail->output->add_handlers(array('jappix4roundcubeframe' => array($this, 'frame')));
    $rcmail->output->set_pagetitle($this->gettext('title'));
    $rcmail->output->send('jappix4roundcube.jappix4roundcube');
  }

  function frame(){
    $rcmail = rcmail::get_instance();
    $this->load_config();
    $user = $rcmail->config->get('jabber_username');
    $pass = $rcmail->config->get('jabber_password');
    $domain = $rcmail->config->get('jabber_domain');
    if($rcmail->config->get('skin', 'larry') != 'classic'){
      $this->include_script('minimalmode.js');
    }
    else{
      $rcmail->output->add_header(html::tag('style', array('type' => 'text/css'), '#mainscreen { top: 55px; }'));
    }
    $rcmail->output->add_footer(html::tag('div', array('id' => 'hidelogout'), '[' . html::tag('a', array('title' => $this->gettext('openinextwin'), 'href' => '#', 'onclick' => 'self.location.href="./?_task=mail"; window.open("' . $rcmail->config->get('jappix_url', 'https://jappix.com') . '")'), '+') . ']&nbsp;'));
    $output = html::tag('iframe', array('id' => 'jappixframe', 'scrolling' => 'no', 'src' => './?_task=jappix&_action=jappix.loginframe', 'width' => '100%', 'height' => '100%', 'frameborder' => '0'));
    return $output;
  }
  
  function cache(){
    $rcmail = rcmail::get_instance();
    $lg = explode('_', $_SESSION['language']);
    $lg = $lg[0];
    $sql = 'SELECT * FROM ' . get_table_name('jappix') . ' WHERE file=? AND lang=? LIMIT 1';
    $res = $rcmail->db->query($sql, 'mini.js', $lg);
    $props = $rcmail->db->fetch_assoc($res);
    if(is_array($props)){
       if(strtotime($props['ts']) >= time() - $this->ttl){
        $_SESSION['jappixmini'] = true;
      }
    }
    else{
      $this->require_plugin('http_request');
      $domain = slashify($rcmail->config->get('jappix_url', 'https://jappix.com'));
      $url = $domain . 'php/get.php?l=' . $lg . '&t=js&g=mini.xml';
      $http = new MyRCHttp;
      $httpConfig['method'] = 'GET';
      $httpConfig['referrer'] = 'http' . (rcube_https_check() ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
      $httpConfig['target'] = $url;
      $http->initialize($httpConfig); 
      $http->useCurl(true);
      if(ini_get('safe_mode') || ini_get('open_basedir')){
        $http->useCurl(false);
      }
      $http->SetTimeout(3);
      $http->execute();
      if($minijs = $http->result){
        $minijs = str_replace("jQuery('head').append('<link ", "\r\n//jQuery('head').append('<link ", $minijs);
        $minijs = str_replace('type="text/css" media="all" />\');', 'type="text/css" media="all" />\');' . "\r\n", $minijs);
        $minijs = trim($minijs);
        if(is_array($props)){
          if(strtotime($props['ts']) < time() - $this->ttl){
            $sql = 'UPDATE ' . get_table_name('jappix') . ' SET lang=?, ts=?, content=? WHERE file=?';
            $res = $rcmail->db->query($sql, date($lg, 'Y-m-d H:i:s', time()), $minijs, 'mini.js');
            $res = $rcmail->db->affected_rows($res);
          }
          else{
            $res = 1;
          }
        }
        else{
          $sql = 'INSERT INTO ' . get_table_name('jappix') . ' (lang, ts, file, contenttype, content) VALUES (?, ?, ?, ?, ?)';
          $rcmail->db->query($sql, $lg, date('Y-m-d H:i:s', time()), 'mini.js', 'application/javascript', $minijs);
          $res = $rcmail->db->affected_rows();
        }
        if($res > 0){
          $_SESSION['jappixmini'] = true;
        }
        $url = $domain . 'php/get.php?t=css&f=mini.css';
        $httpConfig['target'] = $url;
        $http->initialize($httpConfig);
        $http->execute();
        if($minicss = $http->result){
          $minicss = str_replace('./get.php?', 'plugins/jappix4roundcube/get.php?', $minicss);
          $sql = 'SELECT * FROM ' . get_table_name('jappix') . ' WHERE file=? AND lang=? LIMIT 1';
          $res = $rcmail->db->query($sql, 'mini.css', $lg);
          $props = $rcmail->db->fetch_assoc($res);
          if(is_array($props)){
            if(strtotime($props['ts']) < time() - $this->ttl){
              $sql = 'UPDATE ' . get_table_name('jappix') . ' SET ts=?, content=? WHERE file=?';
              $res = $rcmail->db->query($sql, date('Y-m-d H:i:s', time()), $minijs, 'mini.css');
              $res = $rcmail->db->affected_rows($res);
            }
            else{
              $res = 1;
            }
          }
          else{
            $sql = 'INSERT INTO ' . get_table_name('jappix') . ' (lang, ts, file, contenttype, content) VALUES (?, ?, ?, ?, ?)';
            $rcmail->db->query($sql, $lg, date('Y-m-d H:i:s', time()), 'mini.css', 'text/css', $minicss);
            $res = $rcmail->db->affected_rows();
          }
          if($res == 0){
            $_SESSION['jappixmini'] = false;
          }
          $url = $domain . 'php/get.php?t=css&f=mini-ie.css';
          $httpConfig['target'] = $url;
          $http->initialize($httpConfig);
          $http->execute();
          if($minicss = $http->result){
            $minicss = str_replace('./get.php?', 'https://jappix.myroundcube.com/php/get.php?', $minicss);
            $sql = 'SELECT * FROM ' . get_table_name('jappix') . ' WHERE file=? AND lang=? LIMIT 1';
            $res = $rcmail->db->query($sql, 'mini-ie.css', $lg);
            $props = $rcmail->db->fetch_assoc($res);
            if(is_array($props)){
              if(strtotime($props['ts']) < time() - $this->ttl){
                $sql = 'UPDATE ' . get_table_name('jappix') . ' SET ts=?, content=? WHERE file=?';
                $res = $rcmail->db->query($sql, date('Y-m-d H:i:s', time()), $minicss, 'mini-ie.css');
                $res = $rcmail->db->affected_rows($res);
              }
              else{
                $res = 1;
              }
            }
            else{
              $sql = 'INSERT INTO ' . get_table_name('jappix') . ' (lang, ts, file, contenttype, content) VALUES (?, ?, ?, ?, ?)';
              $rcmail->db->query($sql, $lg, date('Y-m-d H:i:s', time()), 'mini-ie.css', 'text/css', $minicss);
              $res = $rcmail->db->affected_rows();
            }
            if($res == 0){
              $_SESSION['jappixmini'] = false;
              $sql = 'DELETE FROM ' . get_table_name('jappix') . ' WHERE lang=?';
              $rcmail->db->query($sql, $lg);
            }
          }
        }
      }
    }
  }
  
  function is_valid_domain_name($domain_name){
    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
            && preg_match("/^.{1,253}$/", $domain_name) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
  }
}
?>
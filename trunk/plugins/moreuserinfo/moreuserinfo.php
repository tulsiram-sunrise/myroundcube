<?php
/**
 * moreuserinfo
 *
 *
 * @version 4.0.2 - 21.01.2013
 * @author Roland 'rosali' Liebl
 * @website http://myroundcube.googlecode.com
 *
 **/

/**
 *
 * Usage: http://mail4us.net/myroundcube/
 *
 **/ 

class moreuserinfo extends rcube_plugin
{

  public $task = 'mail|settings|addressbook|dummy';
  public $noajax = true;
  
  /* unified plugin properties */
  static private $plugin = 'moreuserinfo';
  static private $author = 'myroundcube@mail4us.net';
  static private $authors_comments = 'Since version 3.0 re-configuration required<br /><a href="http://myroundcube.com/myroundcube-plugins/moreuserinfo-plugin" target="_new">Documentation</a>';
  static private $download = 'http://myroundcube.googlecode.com';
  static private $version = '4.0.2';
  static private $date = '21-01-2013';
  static private $licence = 'GPL';
  static private $requirements = array(
    'Roundcube' => '0.8.1',
    'PHP' => '5.2.1'
  );
  static private $prefs = null;
  static private $config_dist = 'config.inc.php.dist';

  function init()
  {
    $this->add_texts('localization/');  
    $rcmail = rcmail::get_instance();
    if(!in_array('global_config', $rcmail->config->get('plugins'))){
      $this->load_config();
    }
    $this->register_action('plugin.moreuserinfo_show', array($this, 'frame'));
    $this->register_action('plugin.moreuserinfo', array($this, 'infostep'));
    $this->add_hook('render_page', array($this, 'showuser'));
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
  
  function frame()
  {
    $rcmail = rcmail::get_instance();
    $rcmail->output->add_script("rcmail.add_onload(\"rcmail.sections_list.select('accountlink')\");");
    $rcmail->output->send("settings");
    exit;
  }
  
  function infostep()
  {
    $this->register_handler('plugin.moreuserinfo_html', array($this, 'infohtml'));
    rcmail::get_instance()->output->send('moreuserinfo.moreuserinfo');
  }

  function showuser($p)
  {
    $rcmail = rcmail::get_instance();
    if($rcmail->config->get('skin') == 'larry'){
      $href = './?_task=settings&_action=plugin.moreuserinfo_show';
      $rcmail->output->add_script('$(".topleft").html($(".topleft").html() + "<a id=\'accountinformationlink\' href=\'' . $href . '\'>' . $this->gettext('accountinformation') . '</a>");', 'docready');
    }
    if($p['template'] == 'settingsedit'){
      $rcmail->output->add_script('if(parent.rcmail.env.action == "plugin.moreuserinfo_show"){ parent.rcmail.env.action = ""; document.location.href="./?_task=settings&_action=plugin.moreuserinfo&_framed=1" };', 'docready');
    }
    if($p['template'] != "mail")
      return $p;

    if(isset($_SESSION['temp']) || strtolower($rcmail->task) != "mail")
      return $p; 

    $skin  = $rcmail->config->get('skin');
    $_skin = get_input_value('_skin', RCUBE_INPUT_POST);

    if($_skin != "")
      $skin = $_skin;

    // abort if there are no css adjustments
    if(!file_exists('plugins/moreuserinfo/skins/' . $skin . '/moreuserinfo.css')){
      if(!file_exists('plugins/moreuserinfo/skins/classic/moreuserinfo.css'))   
        return $p;
      else
        $skin = "classic";
    }

    $this->include_stylesheet('skins/' . $skin . '/moreuserinfo.css');
    $browser = new rcube_browser;
    if($browser->ie && $browser->ver == 6){
      $this->include_stylesheet('skins/' . $skin . '/ie6.css');	
    }
    
    $user = $rcmail->user->data['username'];
    if(strlen($user) > 20)
      $user = substr($user,0,20) . "...";

    $skin = $rcmail->config->get('skin', 'classic');
    if(!class_exists('accounts') && ($skin == 'classic' || $skin == 'groupvice4' || $skin == 'meh' || $skin == 'litecube-f'))
      $rcmail->output->add_footer('<div id="showusername"><a title="' . $this->gettext('userinfo', 'moreuserinfo') . '" href="./?_task=settings&_action=plugin.moreuserinfo_show">' . $user . '&nbsp;</a></div>');

    return $p;

  }

  function infohtml()
  {
    $rcmail = rcmail::get_instance();

    $user = $rcmail->user;
    $username = $user->data['username'];
    $temp = explode('@', $username);
    $domainpart = $temp[1] ? $temp[1] : 'default';
    $skin = $rcmail->config->get('skin', 'classic');
    $icon = '&nbsp;' . html::tag('img', array('src' => 'plugins/moreuserinfo/skins/' . $skin . '/images/clipboard.png', 'title' => $this->gettext('copytoclipboardtitle'), 'alt' => $this->gettext('copytoclipboardtitle'), 'align' => 'baseline', 'onclick' => 'window.prompt ("'. $this->gettext('copytoclipboard') . '", $(this).prev().text())'));
    $table = new html_table(array('cols' => 2, 'cellpadding' => 3));
    
    $table->add('title', html::tag('h3', null, Q($this->gettext('mainoptions') . ':')));
    $table->add('', '');
    $date_format = $rcmail->config->get('date_format', 'm/d/Y') . ' ' . $rcmail->config->get('time_format', 'H:i');
    $created = new DateTime($user->data['created']);
    $table->add('title', '&raquo;&nbsp;' . Q($this->gettext('created') . ':'));
    $table->add('', Q(date_format($created, $date_format)));
    $lastlogin = new DateTime($user->data['last_login']);
    $table->add('title', '&raquo;&nbsp;' . Q($this->gettext('lastlogin') . ':'));
    $table->add('', Q(date_format($lastlogin, $date_format)));
    $identity = $user->get_identity();
    $table->add('title', '&raquo;&nbsp;' . Q($this->gettext('defaultidentity') . ':'));
    $table->add('', Q($identity['name'] . ' <' . $identity['email'] . '>'));
    
    $table->add('title', html::tag('h3', null, Q($this->gettext('mailbox') . ':')));
    $table->add('', '');
    $conf = $rcmail->config->get('moreuserinfo');
    foreach($conf as $service => $domains){
      $table->add('title', html::tag('b', null, '&raquo;&nbsp;' . Q($service . ':')));
      $table->add('', '&nbsp;');
      foreach($domains as $domain => $details){
        $i = 0;
        if($domainpart == $domain){
          foreach($details as $detail => $setting){
            $i ++;
            $label = $this->gettext($detail);
            if(substr($label, 0, 1) == '['){
              $label = $detail;
            }
            $table->add('title', Q('&nbsp;&raquo;&nbsp;' . $label . ':'));
            $label = $this->gettext($setting);
            if(substr($label, 0, 1) == '['){
              $label = $setting;
            }
            $table->add('', html::tag('span', null, Q($label)) . (($i ==2 || $i ==3) ? $icon : ''));
          }
        }
      } 
    }

    $cals = $rcmail->config->get('caldavs', array());
    $clients = '';
    $user = $username;
    if(isset($_SESSION['global_alias'])){
      $user = $_SESSION['global_alias'];
    }
    if(count($cals) > 0){
      $i = 1;
      $table->add('title', html::tag('h3', null, $this->gettext('calendars') . '&nbsp;(CalDAV-URLs)&sup' . $i . ';:'));
      $table->add('', '');
      foreach($cals as $key => $caldav){
        $default = str_ireplace($key, 'events', $caldav['url']);
        $table->add('title', '&raquo; ' . $this->gettext('default'));
        $temp = explode('?', $default, 2);
        $url = slashify($temp[0]) . ($temp[1] ? ('?' . $temp[1]) : '');
        $table->add('', html::tag('span', null, Q(str_replace('@', urlencode('@'), str_replace('%u', $user, $url)))) . $icon);
        if($temp[1] && strpos($temp[1], 'access=') !== false && class_exists('sabredav')){
          $user = $caldav['user'];
          $access = $this->gettext('calendar.readwrite');
          if(strpos($temp[1], 'access=2') !== false){
            $access = $this->gettext('calendar.readonly');
          }
          $temp = parse_url($url);
          $table->add('', '');
          $table->add('', html::tag('i', null, '&raquo;&nbsp;' . $this->gettext('username') . ':&nbsp;' . html::tag('span', null, $user) . $icon . '&nbsp;|&nbsp;' . $this->gettext('password') . ':&nbsp;' . html::tag('span', null, $rcmail->decrypt($caldav['pass'])) . $icon . '&nbsp;|&nbsp;' . $access));
        }
        break;
      }
      ksort($cals);
      foreach($cals as $key => $caldav){
        $temp = explode('?', $caldav['url'], 2);
        $url = slashify($temp[0]) . ($temp[1] ? ('?' . $temp[1]) : '');
        $table->add('title', '&raquo; ' . $key);
        $table->add('', html::tag('span', null, Q(str_replace('@', urlencode('@'), str_replace('%u', $user, $url)))) . $icon);
        if($temp[1] && strpos($temp[1], 'access=') !== false && class_exists('sabredav')){
          $user = $caldav['user'];
          $access = $this->gettext('calendar.readwrite');
          if(strpos($temp[1], 'access=2') !== false){
            $access = $this->gettext('calendar.readonly');
          }
          $temp = parse_url($url);
          $table->add('', '');
          $table->add('', html::tag('i', null, '&raquo;&nbsp;' . $this->gettext('username') . ':&nbsp;' . html::tag('span', null, $user) . $icon . '&nbsp;|&nbsp;' . $this->gettext('password') . ':&nbsp;' . html::tag('span', null, $rcmail->decrypt($caldav['pass'])) . $icon . '&nbsp;|&nbsp;' . $access));
        }
      }
      $clients .= html::tag('hr') . '&sup' . $i . ';&nbsp;' . sprintf($this->gettext('clients'), 'CalDAV') . ':' . html::tag('br') . '&nbsp;&nbsp;- ' . html::tag('a', array('href' => 'http://www.mozilla.org/en-US/thunderbird/organizations/all-esr.html', 'target' => '_new'), 'Thunderbird ESR');
      $clients .= ' + ' . html::tag('a', array('href' => 'http://www.sogo.nu/english/downloads/frontends.html', 'target' => '_new'), 'Lightning');
      $url = $rcmail->config->get('caldav_thunderbird','http://myroundcube.com/myroundcube-plugins/thunderbird-caldav');
      $clients .= html::tag('a', array('href' => $url, 'target' =>'_new'), html::tag('div', array('style' => 'display:inline;float:right;'), 'Thunderbird ' . $this->gettext('tutorial')));
      $url = $rcmail->config->get('caldav_android_app','https://play.google.com/store/apps/details?id=org.dmfs.caldav.lib&hl=en');
      $clients .= html::tag('br') . '&nbsp;&nbsp;- ' . html::tag('a', array('href' => 'http://www.android.com/', 'target' => '_new'), 'Android') . ' + ' . html::tag('a', array('href' => $url, 'target' => '_new'), 'CalDAV-sync');
      $url = $rcmail->config->get('caldav_android','http://myroundcube.com/myroundcube-plugins/android-caldav');
      $clients .= html::tag('a', array('href' => $url, 'target' =>'_new'), html::tag('div', array('style' => 'display:inline;float:right;'), 'Android ' . $this->gettext('tutorial')));
      $url = $rcmail->config->get('caldav_iphone','http://myroundcube.com/myroundcube-plugins/iphone-caldav');
      $clients .= html::tag('br') . '&nbsp;&nbsp;- ' . html::tag('a', array('href' => 'http://www.apple.com/iphone/', 'target' => '_new'), 'iPhone') . html::tag('a', array('href' => $url, 'target' => '_new'), html::tag('div', array('style' => 'display:inline;float:right;'), 'iPhone ' . $this->gettext('tutorial'))) . html::tag('br');
    }
    $addressbooks = array();
    $query = "SELECT user_id, password, url, label from " . get_table_name('carddav_server') . " WHERE user_id=?";
    $sql_result = $rcmail->db->query($query, $rcmail->user->ID);
    while ($sql_result && ($sql_arr = $rcmail->db->fetch_assoc($sql_result))) {
      $addressbooks[$sql_arr['label']] = $sql_arr;
    }
    if(count($addressbooks) > 0){
      $i ++;
      $table->add('title', html::tag('h3', null, $this->gettext('addressbooks') . '&nbsp;(CardDAV-URLs)&sup' . $i . ';:'));
      $table->add('', '');
      ksort($addressbooks);
      foreach($addressbooks as $key => $addressbook){
        $temp = explode('?', $addressbook['url'], 2);
        $url = str_replace('@', urlencode('@'), slashify($temp[0]) . ($temp[1] ? ('?' . $temp[1]) : ''));
        $table->add('title', '&raquo; ' . $key);
        $table->add('', html::tag('span', null, Q(str_replace('%u', $user, $url))) . $icon);
        if($temp[1] && strpos($temp[1], 'access=') !== false && class_exists('sabredav')){
          $sql = 'SELECT username from ' . get_table_name('users') . ' WHERE user_id=?';
          $res = $rcmail->db->query($sql, $addressbook['user_id']);
          $user = $rcmail->db->fetch_assoc($res);
          $user = $user['username'];
          $access = $this->gettext('carddav.readwrite');
          if(strpos($temp[1], 'access=2') !== false){
            $access = $this->gettext('carddav.readonly');
          }
          $temp = parse_url($url);
          $table->add('', '');
          $table->add('', html::tag('i', null, '&raquo;&nbsp;' . $this->gettext('username') . ':&nbsp;' . html::tag('span', null, $user) . $icon . '&nbsp;|&nbsp;' . $this->gettext('password') . ':&nbsp;' . html::tag('span', null, $rcmail->decrypt($addressbook['password'])) . $icon . '&nbsp;|&nbsp;' . $access));
        }
      }
      if($clients == ''){
        $clients = html::tag('hr');
      }
      $clients .= '&sup' . $i . ';&nbsp;' . sprintf($this->gettext('clients'), 'CardDAV') . ':' . html::tag('br') . '&nbsp;&nbsp;- ' . html::tag('a', array('href' => 'http://www.mozilla.org/en-US/thunderbird/organizations/all-esr.html', 'target' => '_new'), 'Thunderbird ESR');
      $url = $rcmail->config->get('carddav_thunderbird','http://myroundcube.com/myroundcube-plugins/thunderbird-carddav');
      $clients .= ' + ' . html::tag('a', array('href' => 'http://www.sogo.nu/english/downloads/frontends.html', 'target' => '_new'), 'SOGo Connector') . html::tag('a', array('href' => $url, 'target' =>'_new'), html::tag('div', array('style' => 'display:inline;float:right;'), 'Thunderbird ' . $this->gettext('tutorial')));
      $url = $rcmail->config->get('carddav_android_app','https://play.google.com/store/apps/details?id=org.dmfs.carddav.sync&hl=en');
      $clients .= html::tag('br') . '&nbsp;&nbsp;- ' . html::tag('a', array('href' => 'http://www.android.com/', 'target' => '_new'), 'Android') . ' + ' . html::tag('a', array('href' => $url, 'target' => '_new'), 'CardDAV-sync');
      $url = $rcmail->config->get('carddav_android_app_editor','https://play.google.com/store/apps/details?id=org.dmfs.android.contacts&hl=en');
      if($url){
        $clients .=  ' + ' . html::tag('a', array('href' => $url, 'target' => '_new'), 'Contact Editor');
      }
      $url = $rcmail->config->get('carddav_android','http://myroundcube.com/myroundcube-plugins/android-carddav');
      $clients .= html::tag('a', array('href' => $url, 'target' =>'_new'), html::tag('div', array('style' => 'display:inline;float:right;'), 'Android ' . $this->gettext('tutorial')));
      $url = $rcmail->config->get('carddav_iphone','http://myroundcube.com/myroundcube-plugins/iphone-carddav');
      $clients .= html::tag('br') . '&nbsp;&nbsp;- ' . html::tag('a', array('href' => 'http://www.apple.com/iphone/', 'target' => '_new'), 'iPhone') . html::tag('a', array('href' => $url, 'target' => '_new'), html::tag('div', array('style' => 'display:inline;float:right;'), 'iPhone ' . $this->gettext('tutorial')));
    }
    $out  = $out .= html::tag('fieldset', null, html::tag('legend',  null, $this->gettext('userinfo') . ' ::: ' . $_SESSION['username']) . $table->show() . $clients);
    $out .= html::tag('br') . html::tag('div', array('id' => 'formfooter'),
      html::tag('div', array('class' => 'footerleft formbuttons'),
        html::tag('input', array('type' => 'button',  'onclick' => 'document.location.href=\'./?_task=settings&_action=edit-prefs&_section=accountlink&_framed=1\'', 'class' => 'button mainaction',  'value' => Q($this->gettext('back'))))
      )
    );
    return $out;
  }

}

?>
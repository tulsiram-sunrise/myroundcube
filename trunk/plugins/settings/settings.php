<?php
/**
 * settings
 *
 * @version 4.3 - 30.12.2013
 * @author Roland 'rosali' Liebl
 * @website http://myroundcube.com
 */

class settings extends rcube_plugin
{
  public $task = 'settings';
  public $noajax = true;
  
  private $sections = array('general','mailbox','compose','mailview','addressbook','folders','server');
  
  /* unified plugin properties */
  static private $plugin = 'settings';
  static private $author = 'myroundcube@mail4us.net';
  static private $authors_comments = '';
  static private $version = '4.3';
  static private $date = '30-12-2013';
  static private $licence = 'All Rights reserved';
  static private $requirements = array(
    'Roundcube' => '1.0',
    'PHP' => '5.3'
  );
  static private $prefs = null;
  static private $config_dist = 'config.inc.php.dist';

  function init(){

    $this->_load_config();

    $rcmail = rcmail::get_instance();
   
    $this->register_handler('plugin.account_sections', array($this, 'account_sections'));
    $this->add_hook('preferences_sections_list', array($this, 'account_link'));
    $this->add_hook('preferences_save', array($this, 'preferences_save'));
    $this->add_hook('preferences_list', array($this, 'prefs_table'));
    $this->add_hook('render_page', array($this, 'render_page'));
    
    $skin  = $rcmail->config->get('skin');
    $_skin = get_input_value('_skin', RCUBE_INPUT_POST);

    if($_skin != "")
      $skin = $_skin;

    // abort if there are no css adjustments
    if(!file_exists('plugins/settings/skins/' . $skin . '/settings.css')){
      if(!file_exists('plugins/settings/skins/classic/settings.css'))
        return;
      else
        $skin = "classic";
    }
    $this->include_stylesheet('skins/' . $skin . '/settings.css');
    $browser = new rcube_browser();
    if($browser->ie){
      if($browser->ver < 8)
        $this->include_stylesheet('skins/' . $skin . '/iehacks.css');
      if($browser->ver < 7)
        $this->include_stylesheet('skins/' . $skin . '/ie6hacks.css');
    }

    $this->add_hook('template_object_userprefs', array($this, 'userprefs'));
    
    $this->add_texts('localization/'); 
    $rcmail->output->add_label('settings.account');
    
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
    $config = array();
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
      'requirements' => $requirements,
    );
    if(is_array(self::$prefs))
      $ret['config'] = array_merge($config, array_flip(self::$prefs));
    else
      $ret['config'] = $config;
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
  
  function _load_config()
  {
    $rcmail = rcmail::get_instance();
    $this->require_plugin('qtip');
    if(!in_array('global_config', $rcmail->config->get('plugins'))){
      $this->load_config();
    }
  }
  
  function render_page($p)
  {
    if($p['template'] == 'settings'){
      if(get_input_value('_accountsettings', RCUBE_INPUT_GET)){
        rcmail::get_instance()->output->add_script('$("#rcmrowaccountlink").trigger("mousedown").trigger("mouseup");', 'docready');
      }
    }
    return $p;
  }
  
  function account_link($args)
  {
    $rcmail = rcmail::get_instance();
    $skin = $rcmail->config->get('skin');
    $temparr = array();
    foreach($this->sections as $key => $section){
      $temparr[$section] = $args['list'][$section];
      unset($args['list'][$section]);
    }
    $args['list']['general'] = $temparr['general'];
    $args['list']['mailbox'] = $temparr['mailbox'];
    $args['list']['compose'] = $temparr['compose'];
    $args['list']['mailview'] = $temparr['mailview'];
    $args['list']['mh_preferences'] = array();
    $args['list']['identitieslink'] = array();
    $args['list']['addressbook'] = $temparr['addressbook'];
    $args['list']['addressbookcarddavs'] = array();
    $args['list']['addressbooksharing'] = array();
    $args['list']['jabber'] = array();
    $args['list']['folderslink'] = array();
    $args['list']['folders'] =  $temparr['folders'];
    if($skin == 'classic'){
      $args['list']['folders']['section'] = $args['list']['folders']['section'];
    }
    $args['list']['calendarlink'] = array();
    $args['list']['calendarcategories'] = array();
    $args['list']['calendarfeeds'] = array();
    $args['list']['calendarsharing'] = array();
    $args['list']['nabblelink'] = array();
    $args['list']['plugin_manager'] = array();
    $args['list']['plugin_manager_settings'] = array();
    $args['list']['plugin_manager_admins'] = array();
    $args['list']['plugin_manager_customer'] = array();
    $args['list']['plugin_manager_update'] = array();
    $args['list']['accountslink'] = array();
    $args['list']['server'] = $temparr['server'];

    $parts = (array) $GLOBALS['settingsnav'];
    $temp = array();
    foreach($parts as $plugin => $props){
      if(class_exists($plugin)){
        $temp[$this->gettext($plugin . '.' . $props['label'])][$plugin] = $props;
      }
      else{
        unset($GLOBALS['settingsnav'][$plugin]);
      }
    }
    ksort($temp);
    $parts = $temp;
    $GLOBALS['settingsnav'] = array();
    foreach($parts as $label => $props){
      foreach($props as $plugin => $settings){
        if(class_exists($plugin)){
          $GLOBALS['settingsnav'][$plugin] = $settings;
        }
      }
    }
    $parts = (array) $rcmail->config->get('settingsnav', $GLOBALS['settingsnav']);
    foreach($parts as $plugin => $props){
      if(!class_exists($plugin)){
        unset($parts[$plugin]);
      }
    }
    $active = $rcmail->config->get('plugin_manager_active', array());
    $defaults = $_SESSION['plugin_manager_defaults'];
    if(is_array($defaults)){
      foreach($defaults as $section => $plugins){
        foreach($plugins as $plugin => $props){
          if($props['active']){
            $active[$plugin] = 1;
          }
        }
      }
    }
    foreach($parts as $plugin => $props){
      if($active[$plugin] != 1){
        unset($parts[$plugin]);
      }
    }
    if(class_exists('mysqladmin') && strtolower($rcmail->user->data['username']) == $rcmail->config->get('mysql_admin')){
      $hm = array('autoban', 'autoresponder', 'forwarding', 'login', 'accounts', 'signature', 'spamfilter');
      foreach($hm as $dsn){
        $c = $rcmail->config->get('db_hmail_' . $dsn . '_dsn');
        if(is_string($c)){
          $t = parse_url($c);
          if($t['user'] && $t['pass']){
            $parts = array_merge($parts, array( 'mysqladmin' =>
                array('part' => '', 'label' => 'pluginname', 'href' => './?_action=plugin.mysqladmin&pma_login=1&db=db_hmail_' . $dsn . '_dsn', 'onclick' => 'rcmail.set_cookie("PMA_referrer", document.location.href);', 'descr' => 'mysqladmin')
              )
            );
            break;
          }
        }
      }
    }
    if(count($parts) > 0){
      $_SESSION['settingsnav'] = $parts;
      $args['list']['accountlink']['id'] = 'accountlink';
      $args['list']['accountlink']['section'] = $this->gettext('account');
      if(strtolower($rcmail->user->data['username']) != strtolower($_SESSION['username'])){
        unset($args['list']['accountlink']);
      }
    }
    return $args;
  } 

  function account_sections()
  {
    $rcmail = rcmail::get_instance();

    //display a message if required by url
    if(isset($_GET['_msg'])){
      $rcmail->output->command('display_message', urldecode($_GET['_msg']), $_GET['_type']);
    }
    $parts = (array) $_SESSION['settingsnav'];
    $out = "<div id=\"userprefs-accountblocks\">\n";
    foreach($parts as $key => $part){
      if(!class_exists($key)){
        continue;
      }
      if(!empty($part['descr'])){
        $i++;
        $out .= "<div class=\"userprefs-accountblock\" id='accountsblock_$i'>\n";
        $out .= "<div class=\"userprefs-accountblock-border\">\n";
        $out .= "&raquo;&nbsp;<a class=\"plugin-description-link\" href=\"" . $part['href'] . "\" onclick='" . $part['onclick'] . "'>" . $this->gettext($part['descr'] . '.' . $part['label']) . "</a>\n";
        $out .= "</div>\n";
        $out .= "</div>\n";
        $out .= '
<script>
var element = $("#accountsblock_' . $i . '");
element.qtip({
  content: {title:\'' . addslashes($this->gettext($part['descr'] . '.' . $part['label'])) . '\', text: \'' . addslashes($this->gettext($part['descr'] . '.description')) . '\'},
  position: {
    my: "top left",
    at: "left bottom",
    target: element,
    viewport: $(window)
  },
  hide: {
    effect: function () { $(this).slideUp(5, function(){ $(this).dequeue(); }); }
  },
  style: {
    classes: "ui-tooltip-light"
  }
});
</script>
';
      }
    }

    $out .= "</div>\n<style>fieldset{border: none;}</style>\n";
    return $out;

  }

  function prefs_table($args)
  {
    if(!get_input_value('_framed', RCUBE_INPUT_GPC) && $args['section'] == 'accountlink'){
      $args['blocks'][$args['section']]['options'] = array(
        'title'   => '',
        'content' => html::tag('div', array('id' => 'pm_dummy'), '')
      );
      return $args;
    }
    if ($args['section'] == 'accountlink') {
      $args['blocks']['main']['options']['accountlink']['title'] = "";
      $args['blocks']['main']['options']['accountlink']['content'] = $this->account_sections("");
      $this->include_script('settings.js');
    }

    return $args;

  }

  function userprefs($p)
  {
    $rcmail = rcmail::get_instance();
    (array)$temparr = explode("<fieldset>",$p['content']);
    for($i=1;$i<count($temparr);$i++){
      $langs = $rcmail->list_languages();
      $limit_langs = array_flip($rcmail->config->get('limit_languages',array()));
      if(count($limit_langs) > 0){
        foreach($langs as $key => $val){
          if(!isset($limit_langs[$key])){
            $temparr[$i] = str_replace("<option value=\"$key\">$val</option>\n","",$temparr[$i]);
          }
        }     
      }
      $skins = rcmail_get_skins();
      $selected_skin = strtolower($rcmail->config->get('skin','classic'));
      $limit_skins = array_flip($rcmail->config->get('limit_skins',array()));
      if(count($limit_skins) > 0){
        foreach($skins as $key => $val){
          if(!isset($limit_skins[$val])){
            $rcmail->output->add_script('$("#rcmfd_skin' . $val . '").parent().parent().parent().hide();', 'docready');
          }
        }
      }
      $temparr[$i] = "<div class=\"settingsplugin\"><fieldset>" . str_replace("</fieldset>","</fieldset></div>",$temparr[$i]);
      if($_GET['_section'] == "folders" || $_POST['_section'] == "folders"){
        $user = $_SESSION['username'];
        $temparr[$i] = str_replace("</legend>"," ::: " . $user . "</legend>",$temparr[$i]);
        $temparr[$i] = str_replace("remotefolders :::", $this->gettext('remotefolders') . " :::", $temparr[$i]);
      }
    }
    
    $p['content'] = implode('', $temparr);
    if(!in_array('skin', $rcmail->config->get('dont_override', array()))){
      if($_GET['_section'] == "general" || $_POST['_section'] == "general"){
        $p['content'] .= html::tag('br') . 
          html::tag('fieldset', null, html::tag('legend', null, $this->gettext('skin_preview')) . 
            html::tag('div', array('id' => 'skin_preview', 'align' => 'center'),
              html::tag('img', array('id' => 'skin_preview_img', 'src' => './plugins/settings/skins/' . $rcmail->config->get('skin','classic') . '/images/' . $rcmail->config->get('skin','classic') . '.png'))
            )
          ) . html::tag('script', array('type' => 'text/javascript'), '$(document).ready(function(){$(".skinitem").click(function(){ $("#skin_preview_img").attr("src", "./plugins/settings/skins/" + $(this).children().val() + "/images/" + $(this).children().val() + ".png"); }); });');
      }
    }
    return $p;

  }
  
  function preferences_save($prefs){
    $rcmail = rcmail::get_instance();
    if($prefs['section'] == 'general'){
      if($prefs['prefs']['skin'] != $rcmail->config->get('skin','classic')){
        $rcmail->output->add_script("parent.location.href = './?_task=settings';");
      }
    }
    return $prefs;
  }
}

?>
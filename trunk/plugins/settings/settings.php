<?php
/**
 * settings
 *
 * @version 3.9.1 - 19.11.2012
 * @author Roland 'rosali' Liebl
 * @website http://myroundcube.googlecode.com
 */

/**
 *
 * Usage: http://mail4us.net/myroundcube
 *
 * Requirements: qtip plugin (do not register, plugin is loaded automatically)
 *
 **/

class settings extends rcube_plugin
{
  public $task = 'settings';
  public $noajax = true;
  
  private $sections = array('general','mailbox','compose','mailview','addressbook','folders','server');
  
  /* unified plugin properties */
  static private $plugin = 'settings';
  static private $author = 'myroundcube@mail4us.net';
  static private $authors_comments = 'Please adjust config key "limit_skins". "default" has to become "classic" since Roundcube 0.8.x.';
  static private $download = 'http://myroundcube.googlecode.com';
  static private $version = '3.9.1';
  static private $date = '19-11-2012';
  static private $licence = 'All Rights reserved';
  static private $requirements = array(
    'Roundcube' => '0.8.1',
    'PHP' => '5.2.1'
  );
  static private $prefs = null;
  static private $config_dist = 'config.inc.php.dist';

  function init(){
    $this->task = 'settings';
    $this->_load_config();

    $rcmail = rcmail::get_instance();
   
    $this->register_handler('plugin.account_sections', array($this, 'account_sections'));
    $this->add_hook('preferences_sections_list', array($this, 'account_link'));
    $this->add_hook('preferences_save', array($this, 'preferences_save'));
    $this->add_hook('preferences_list', array($this, 'prefs_table'));
    
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
  
  function _load_config()
  {
    $rcmail = rcmail::get_instance();
    if(!in_array('global_config', $rcmail->config->get('plugins'))){
      $this->load_config();
      $this->require_plugin('qtip');
    }
  }

  function account_link($args)
  {
    $rcmail = rcmail::get_instance();
    $skin = $rcmail->config->get('skin');
    if(in_array('global_config', $rcmail->config->get('plugins'))){
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
      $args['list']['folderslink'] = array();
      $args['list']['folders'] =  $temparr['folders'];
      if($skin != 'larry'){
        $args['list']['folders']['section'] = $args['list']['folders']['section'];
      }
      $args['list']['calendarlink'] = array();
      $args['list']['calendarcategories'] = array();
      $args['list']['calendarfeeds'] = array();
      $args['list']['nabblelink'] = array();
      $args['list']['plugin_manager'] = array();
      $args['list']['plugin_manager_update'] = array();
      $args['list']['plugin_manager_customer'] = array();
      $args['list']['accountslink'] = array();
      $args['list']['server'] = $temparr['server'];
    }
    $parts = $GLOBALS['settingsnav'];
    if(is_array($rcmail->config->get('settingsnav')))
      $parts = array_merge($parts, $rcmail->config->get('settingsnav'));
    if(count($parts) > 0){
      $args['list']['accountlink']['id'] = 'accountlink';
      $args['list']['accountlink']['section'] = $this->gettext('account');
    }
    if(strtolower($rcmail->user->data['username']) != strtolower($_SESSION['username'])){
      unset($args['list']['accountlink']);
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
    $parts = (array) $GLOBALS['settingsnav'];
    if(is_array($rcmail->config->get('settingsnav')))
      $parts = array_merge($parts, $rcmail->config->get('settingsnav'));
    $out = "<div id=\"userprefs-accountblocks\">\n";
    foreach($parts as $key => $part){
      if(!class_exists($key))
        continue;
      if(!empty($part['descr'])){
        $i++;
        $out .= "<div class=\"userprefs-accountblock\" id='accountsblock_$i'>\n";
        $out .= "<div class=\"userprefs-accountblock-border\">\n";
        $out .= "&raquo;&nbsp;<a class=\"plugin-description-link\" href=\"" . $part['href'] . "\">" . $this->gettext($part['descr'] . '.' . $part['label']) . "</a>\n";
        $out .= "</div>\n";
        $out .= "</div>\n";
        $out .= '
<script>
var element = $("#accountsblock_' . $i . '");
element.qtip({
  content: {title:\'' . $this->gettext($part['descr'] . '.' . $part['label']) . '\', text: \'' . $this->gettext($part['descr'] . '.description') . '\'},
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
    $user = $rcmail->user->data['username'];
    if($_SESSION['global_alias'])
      $user = $_SESSION['global_alias'];
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
            $temparr[$i] = str_replace("<option value=\"$val\">$val</option>\n","",$temparr[$i]);
          }
          else{
            $temparr[$i] = str_replace("<option value=\"$val\">$val</option>\n","<option value=\"$val\">" . rcube_label($val,'settings') . "</option>\n",$temparr[$i]);            
          }
          if(strtolower($val) == $selected_skin)
            $selected = "selected=\"selected\"";
          else
            $selected = "";
          $temparr[$i] = str_replace("<option value=\"$val\" selected=\"selected\">$val</option>\n","<option value=\"$val\" $selected>" . rcube_label($val,'settings') . "</option>\n",$temparr[$i]);
        }         
      }
      $temparr[$i] = "<div class=\"settingsplugin\" id=\"" . $parts[$i-1] . "\"><fieldset>" . str_replace("</fieldset>","</fieldset></div>",$temparr[$i]);
      if($_GET['_section'] == "remotefolders" || $_POST['_section'] == "remotefolders"){
        $temparr[$i] = str_replace("</legend>"," ::: " . $user . "</legend>",$temparr[$i]);
        $temparr[$i] = str_replace("remotefolders :::", $this->gettext('remotefolders') . " :::", $temparr[$i]);
      }
      else if($_GET['_section'] == "accountlink" || $_POST['_section'] == "accountlink"){
        $temparr[$i] = str_replace("<legend />","<legend>" . $this->gettext('settings.account') . " ::: " . $user . "</legend>",$temparr[$i]);
      }        
      else{
        $temparr[$i] = str_replace("</legend>\n<table"," ::: " . $user . "</legend>\n<table",$temparr[$i]);
      }
    }
    
    $p['content'] = implode($temparr);
    if($_GET['_section'] == "general" || $_POST['_section'] == "general"){
      $p['content'] .= 
'<br /><fieldset><legend>' . $this->gettext('skin_preview') . ' ::: ' . $user . '</legend><br /><div id="skin_preview" align="center">
<iframe border="0" frameborder="0" id="skin_preview_frame" src="./plugins/settings/skins/' . $rcmail->config->get('skin','classic') . '/images/' . $rcmail->config->get('skin','classic') . '.png" width="540px" height="280px"></iframe>
</div></fieldset>
<script type="text/javascript">
/* <![CDATA[ */
$("select").change(function () {
  var skin = document.getElementById("rcmfd_skin").value;
  document.getElementById("skin_preview_frame").src = "./plugins/settings/skins/" + skin + "/images/" + skin + ".png";  
});
/* ]]> */
</script>';
    }    
    return $p;

  }
  
  function preferences_save($prefs){
    $rcmail = rcmail::get_instance();
    if($prefs['section'] == 'general'){
      if($prefs['prefs']['skin'] != $rcmail->config->get('skin','classic'))
        $rcmail->output->add_script("parent.location.href = './?_task=settings';");
    }
    return $prefs;
  }
}

?>
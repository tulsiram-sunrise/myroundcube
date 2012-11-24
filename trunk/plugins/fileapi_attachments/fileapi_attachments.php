<?php
/**
 * fileapi_attachments
 *
 * @version 1.6.2 - 11.09.2012
 * @author Roland 'rosali' Liebl, Matthias Krauser
 * @website http://myroundcube.googlecode.com 
 */

/**
 * FileApi Attachments
 *
 * Use's the HTML5-FileApi to upload Attachments
 * (only supported in modern Browsers, Testet in FF 3.6 on Ubuntu)
 *
 * For now, this is only a proof-of-concept, there a many hacks in the code
 * If you have any hints or suggestions, feel free to contact me.
 *
 * @author Matthias Krauser <matthias@krauser.eu>
 *
 */
class fileapi_attachments extends rcube_plugin
{

  public $task = 'mail';
  public $noframe = true;
  
  /* unified plugin properties */
  static private $plugin = 'fileapi_attachments';
  static private $author = 'myroundcube@mail4us.net';
  static private $authors_comments = 'Since v1.4 the plugin requires database_attachments (bundled with Roundcube). Make sure the plugin is present in plugins folder.';
  static private $download = 'http://myroundcube.googlecode.com';
  static private $version = '1.6.2';
  static private $date = '11-09-2012';
  static private $licence = 'GPL';
  static private $requirements = array(
    'Roundcube' => '0.8.1',
    'PHP' => '5.2.1',
    'required_plugins' => array(
      'database_attachments' =>  'require_plugin',
    ),
  );
  static private $prefs = null;
  static private $config_dist = false;
  
  function init()
  {
    $this->require_plugin('database_attachments');
    $this->add_hook('render_page', array($this, 'compose'));
    $this->register_action('plugin.upload_fileapi', array($this, 'handleUpload'));
    $rand = rand(ini_get('session.gc_probability') ,ini_get('session.gc_divisor'));
    $match = round((ini_get('session.gc_divisor') - ini_get('session.gc_probability') + 1) /  2, 0);
    if($rand == $match){
      $this->gc();
    }
  }
  
  static public function about($keys = false)
  {
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
  
  function gc(){
    $temp_dir = slashify(ini_get('upload_tmp_dir'));
    if(!$temp_dir){
      $temp_dir = slashify($rcmail->config->get('temp_dir','temp'));
    }
    $files = scandir($temp_dir);
    foreach($files as $file){
      $temp = explode('.', $file);
      if(strtolower($temp[count($temp) - 1]) == 'tmp'){ 
        if(time() - filemtime($temp_dir . $file) > 86400){
          @unlink($temp_dir . $file);
        }
      }
    }
  }

  /**
   * add a fileapi implementation to the compose template
   */
  function compose($args)
  {
    // find the compose template
    if ($args['template'] == 'compose')
    {
      $rcmail = rcmail::get_instance();
      $skin = $rcmail->config->get('skin', 'classic');
      $this->include_script('fileapi.js');
      $this->include_stylesheet('skins/' . $skin . '/fileapi.css');
      $this->add_texts('localization/', true);
    }
    return $args;
  }

  function handleUpload($args = null)
  {
    $rcmail = rcmail::get_instance();

    $uploadid = get_input_value('_uploadid', RCUBE_INPUT_GET);
    $group = get_input_value('_id', RCUBE_INPUT_GET);
    $temp_dir = ini_get('upload_tmp_dir');
    if(!$temp_dir){
      $temp_dir = slashify($rcmail->config->get('temp_dir','temp'));
    }
    $tmpfname = tempnam($temp_dir, 'php');

    $fd = fopen("php://input", "r");
    $data = '';

    while ($data = fread($fd, 1000000))
    {
      file_put_contents($tmpfname, $data, FILE_APPEND);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
      $attachment = array(
          'path' => $tmpfname,
          'size' => filesize($tmpfname),
          'name' => get_input_value('_name', RCUBE_INPUT_GET),
          'mimetype' => rc_mime_content_type($tmpfname, get_input_value('_name', RCUBE_INPUT_GET)),
          'group' => $group,
      );

      $attachment = $rcmail->plugins->exec_hook('attachment_upload', $attachment);

      $COMPOSE = null;

      if ($group && $_SESSION['compose_data_'.$group])
        $COMPOSE =& $_SESSION['compose_data_'.$group];
      
      $id = $attachment['id'];

      // store new attachment in session
      unset($attachment['status'], $attachment['abort']);
      $COMPOSE['attachments'][$id] = $attachment;

      if (($icon = $_SESSION['compose_data_'.$group]['deleteicon']) && is_file($icon))
      {
        $button = html::img(array(
                    'src' => $icon,
                    'alt' => rcube_label('delete')
                ));
      }
      else
      {
        $button = Q(rcube_label('delete'));
      }

      $content = html::a(array(
                  'href' => "#delete",
                  'onclick' => sprintf("return %s.command('remove-attachment','rcmfile%s', this)", JS_OBJECT_NAME, $id),
                  'title' => rcube_label('delete'),
                      ), $button);

      $content .= Q($attachment['name']);
      
      $rcmail->output->command('add2attachment_list', "rcmfile$id", array(
          'html' => $content,
          'name' => $attachment['name'],
          'mimetype' => $attachment['mimetype'],
          'classname' => rcmail_filetype2classname($attachment['mimetype'], $attachment['name']) . " fauploadhidden",
          'complete' => true), $id);
    }

    // send html page with JS calls as response
    // theres no way to use a raw-template, can this be added to the core?
    $rcmail->output->send('iframe');
  }

  private function file_id()
  {
    $userid = rcmail::get_instance()->user->ID;
    list($usec, $sec) = explode(' ', microtime());
    return preg_replace('/[^0-9]/', '', $userid . $sec . $usec);
  }

}
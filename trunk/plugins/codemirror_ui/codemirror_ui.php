<?php
/**
 * codemirror_ui
 *
 * @version 1.0 - 27.07.2013
 * @author Roland 'rosali' Liebl
 * @website http://myroundcube.com
 *
 **/

class codemirror_ui extends rcube_plugin{
  
  /* unified plugin properties */
  static private $plugin = 'codemirror_ui';
  static private $author = 'myroundcube@mail4us.net';
  static private $authors_comments = null;
  static private $version = '1.0';
  static private $date = '27-07-2013';
  static private $licence = 'GPL';
  static private $requirements = array(
    'Roundcube' => '0.8.6',
    'PHP' => '5.2.1'
  );
  
  private $rcmail;
  
  function init(){
    $this->rcmail = rcmail::get_instance();
    $this->rcmail->output->add_header('<style type="text/css">.CodeMirror {height: 93%} .CodeMirror-scroll {height: 100%} </style>');
    $this->include_stylesheet('lib/CodeMirror-2.3/lib/codemirror.css');
    $this->include_script('lib/CodeMirror-2.3/lib/codemirror.js');
    $this->include_script('lib/CodeMirror-2.3/lib/util/searchcursor.js');
    switch($GLOBALS['codemirror']['mode']){
      case 'PHP':
        $this->PHP($GLOBALS['codemirror']['elem']);
    }
    $this->include_stylesheet('css/codemirror-ui.css');
    $this->include_script('js/codemirror-ui.js');
  }
  
  function PHP($elem){
    $this->include_script('lib/CodeMirror-2.3/mode/htmlmixed/htmlmixed.js');
    $this->include_script('lib/CodeMirror-2.3/mode/xml/xml.js');
    $this->include_script('lib/CodeMirror-2.3/mode/javascript/javascript.js');
    $this->include_script('lib/CodeMirror-2.3/mode/css/css.js');
    $this->include_script('lib/CodeMirror-2.3/mode/clike/clike.js');
    $this->include_script('lib/CodeMirror-2.3/mode/php/php.js');
    $this->rcmail->output->add_script('
      var textarea = document.getElementById("' . $elem . '");
      var uiOptions = {
        path : "js/",
        searchMode : "popup",
        mode: "php",
        imagePath : "plugins/codemirror_ui/images/silk",
        buttons : ' . $GLOBALS['codemirror']['buttons'] . ',
        saveCallback : ' . $GLOBALS['codemirror']['save'] . '
      }
      var codeMirrorOptions = {
        readOnly: ' . ($GLOBALS['codemirror']['readonly'] ? 'true' : 'false') . ',
        lineNumbers: true,
        matchBrackets: true,
        mode: "application/x-httpd-php",
        indentUnit: 2,
        indentWithTabs: true,
        enterMode: "keep",
        tabMode: "shift",
        tabSize: 2
      }
      var editor = new CodeMirrorUI(textarea, uiOptions, codeMirrorOptions);
    ', 'docready'
    );

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
    return array(
      'plugin' => self::$plugin,
      'version' => self::$version,
      'date' => self::$date,
      'author' => self::$author,
      'comments' => self::$authors_comments,
      'licence' => self::$licence,
      'requirements' => $requirements,
    );
  }
}

?>
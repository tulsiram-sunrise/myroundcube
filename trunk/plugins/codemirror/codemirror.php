<?php

class codemirror extends rcube_plugin{

  function init(){
    $this->include_stylesheet('lib/codemirror.css');
    $this->include_script('lib/codemirror.js');
    $this->include_script('addon/edit/matchbrackets.js');
    $this->include_script('mode/htmlmixed/htmlmixed.js');
    $this->include_script('mode/xml/xml.js');
    $this->include_script('mode/javascript/javascript.js');
    $this->include_script('mode/css/css.js');
    $this->include_script('mode/clike/clike.js');
    $this->include_script('mode/php/php.js');
    rcmail::get_instance()->output->add_header('<style type="text/css">.CodeMirror {border-top: 1px solid black; border-bottom: 1px solid black; height: 95%}</style>');
    rcmail::get_instance()->output->add_script('
      var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
        lineNumbers: true,
        matchBrackets: true,
        mode: "application/x-httpd-php",
        indentUnit: 2,
        indentWithTabs: true,
        enterMode: "keep",
        tabMode: "shift",
        tabSize: 2
      });
      editor.on("change", function(i, o){document.getElementById("code").value = editor.getValue();});
    ', 'docready');
  }
}

?>
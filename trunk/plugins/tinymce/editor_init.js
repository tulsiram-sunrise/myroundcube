// Initialize HTML editor
function rcmail_editor_init(config)
{
  var ret, conf = {
      script_url: 'plugins/tinymce/tiny_mce/tiny_mce_gzip.php',
      mode: 'textareas',
      editor_selector: 'mce_editor',
      apply_source_formatting: true,
      theme: 'advanced',
      language: config.lang,
      content_css: config.skin_path + '/editor_content.css',
      theme_advanced_toolbar_location: 'top',
      theme_advanced_toolbar_align: 'left',
      theme_advanced_buttons3: '',
      extended_valid_elements: 'font[face|size|color|style],span[id|class|align|style]',
      relative_urls: false,
      remove_script_host: false,
      gecko_spellcheck: true,
      convert_urls: false, // #1486944
      external_image_list: window.rcmail_editor_images,
      rc_client: rcmail
    };

  if (config.mode == 'identity') {
    $.extend(conf, {
      plugins:  eval(rcmail.env.tinymce_plugins_identity),
      theme_advanced_buttons1: eval(rcmail.env.tinymce_buttons_identity_row1),
      theme_advanced_buttons2: eval(rcmail.env.tinymce_buttons_identity_row2)
    });
  }
  else { // mail compose
    $.extend(conf, {
      plugins: eval(rcmail.env.tinymce_plugins_compose),
      theme_advanced_buttons1: eval(rcmail.env.tinymce_buttons_compose_row1),
      theme_advanced_buttons2: eval(rcmail.env.tinymce_buttons_compose_row2),
      spellchecker_languages: (rcmail.env.spellcheck_langs ? rcmail.env.spellcheck_langs : 'Dansk=da,Deutsch=de,+English=en,Espanol=es,Francais=fr,Italiano=it,Nederlands=nl,Polski=pl,Portugues=pt,Suomi=fi,Svenska=sv'),
      spellchecker_rpc_url: '?_task=utils&_action=spell_html',
      spellchecker_enable_learn_rpc: config.spelldict,
      accessibility_focus: false,
      oninit: 'rcmail_editor_callback'
    });

    // add handler for spellcheck button state update
    conf.setup = function(ed) {
      ed.onSetProgressState.add(function(ed, active) {
        if (!active)
          rcmail.spellcheck_state();
      });
    }
  }

  // support external configuration settings e.g. from skin
  if (window.rcmail_editor_settings)
    $.extend(conf, window.rcmail_editor_settings);
  window.rcmail_editor_settings = conf;

  if($('textarea.mce_editor').get(0)){
    rcmail.env.tinymce_initialized = true;
    try{
      $('textarea.mce_editor').tinymce(conf);
    }
    catch(e){
      tinyMCE.init(conf);
    }
  }
}

rcmail.toggle_editor = function(props){
  rcmail.stop_spellchecking();

  if (props.mode == 'html') {
    rcmail.plain2html($('#'+props.id).val(), props.id);
    if(!rcmail.env.tinymce_initialized){
      $('#' + props.id).addClass('mce_editor');
      if($('textarea.mce_editor').get(0)){
        if($('textarea.mce_editor').tinymce){
          $('textarea.mce_editor').tinymce(window.rcmail_editor_settings);
        }
        else{
          tinyMCE.init(window.rcmail_editor_settings);
        }
      }
    }
    else{
      tinyMCE.execCommand('mceAddControl', false, props.id);
    }

    if (rcmail.env.default_font)
      setTimeout(function() {
        $(tinyMCE.get(props.id).getBody()).css('font-family', rcmail.env.default_font);
      }, 500);
  }
  else {
    var thisMCE = tinyMCE.get(props.id), existingHtml;

    if (existingHtml = thisMCE.getContent()) {
      if (!confirm(rcmail.get_label('editorwarning'))) {
        return false;
      }
      rcmail.html2plain(existingHtml, props.id);
    }
    tinyMCE.execCommand('mceRemoveControl', false, props.id);
  }
}

// editor callbeck for images listing
function rcmail_editor_images()
{
  var i, files = rcmail.env.attachments, list = [];

  for (i in files) {
    att = files[i];
    if (att.complete && att.mimetype.indexOf('image/') == 0) {
      list.push([att.name, rcmail.env.comm_path+'&_action=display-attachment&_file='+i+'&_id='+rcmail.env.compose_id]);
    }
  }

  return list;
};

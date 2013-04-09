<?php

/**
  * This plugin parses email headers looking for mailinglist specific
  * information. If headers are found, it uses them to create an additional
  * header in the header box to easily perform certain mailinglist commands
  * like subscribing, unsubscribing, asking for help, and sending a new mail.
  *
  * @version 2.1
  * @author Cor Bosman
  * 
  */

class listcommands extends rcube_plugin
{
  public $task = 'mail';

  function init()
  {
    $rcmail = rcmail::get_instance();
    if (!$rcmail->action || in_array($rcmail->action, array('list', 'show', 'preview'))) {
      $this->add_hook('storage_init', array($this, 'storage_init'));
      $this->add_hook('message_headers_output', array($this, 'listcommands_output'));
    }
  }

  function storage_init($p)
  {
    $rcmail = rcmail::get_instance();
    $mailinglist_headers = array_keys($this->get_list_headers());
    $p['fetch_headers'] .= trim($p['fetch_headers']. ' ' . strtoupper(join(' ', $mailinglist_headers)));
    return($p);
  }

  function listcommands_output($p)
  {
    $list_output = "";
    $rcmail = rcmail::get_instance();
    $this->add_texts('localization/', false);
    $mailinglist_headers = $this->get_list_headers();

    foreach ($mailinglist_headers as $header => $title) {
      $key = strtolower($header);
	  
      if($value = $p['headers']->others[$key]) {
        if(is_string($value)){
          $list_output .= $this->create_link($key, $value, $title) . '&nbsp;&nbsp;';
        }
        else{
          $list_output .= $this->create_link($key, $value[0], $title) . '&nbsp;&nbsp;';
        }
      }
    }
    if($list_output != "")
      $p['output']['Mailinglist'] = array(
        'title' => $this->gettext('listcommands_mailinglist'), 'value' => $list_output);
    return($p);
  }

  private function get_list_headers()
  {
    $mailinglist_headers = array(
      'List-Help'        => $this->gettext('listcommands_help'),
      'List-Subscribe'   => $this->gettext('listcommands_subscribe'),
      'List-Unsubscribe' => $this->gettext('listcommands_unsubscribe'),
      'List-Post'        => $this->gettext('listcommands_post'),
      'List-Owner'       => $this->gettext('listcommands_admin'),
      'List-Archive'	 => $this->gettext('listcommands_archive')
      );
    return($mailinglist_headers);
  }

  private function create_link($key, $value, $title)
  {
    $proto = "";

    // some headers have multiple targets
    $targets = explode(',', $value);

    // only use 1 of the targets
    $target = strip_quotes($targets[0]);

    // first strip angle brackets
    $link = trim($target, "<>");

    if(preg_match('/^(mailto|http|https)(:\/\/|:)(.*)$/', $link, $matches)) {
      $proto = $matches[1];
      $target = $matches[3];
    }

    // use RC for emailing instead of relying on the mailto header
    if($proto == "mailto") {
      $onclick = "return rcmail.command('compose','$target',this)";
    } else {
      $onclick = "";
    }

    $a = html::a(array('href' => $link ,
      'target' => '_blank',
      'onclick' => $onclick
      ), $title);

    return($a);
  }
}
?>

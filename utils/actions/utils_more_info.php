<?php

class Utils_More_Info {

  public static function get_more_info_title($type, $id){
    $title = "";

    $title .= "Ask for more details of " . $type;

    switch ($type){
      case "node":
        $node_mod = node_load($id);

        $title .= " \"" . $node_mod->title . "\"";

        break;

      case "user":
        $user_mod = user_load($id);

        $title .= " \"" . $user_mod->name . "\"";

        break;
    }

    $title .= ":";

    return $title;
  }

  public static function get_more_info_form(){
    $form = array();

    $form['message'] = array(
      '#type' => 'textarea',
      '#title' => t('Message'),
      '#description' => t('Message to send for asking more info about the entity to moderate.'),
      '#required' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Send mail'),
    );

    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#href' => '/admin/structure/entity/moderation/list',
    );

    return $form;
  }

  public static function send_more_info_mail($type, $id, $message){
    switch ($type){
      case "node":
        Utils_Node::send_more_info_node_mail($id, $message);
        break;
    }
  }
}
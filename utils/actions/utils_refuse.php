<?php

class Utils_Refuse {

  public static function get_refuse_title($type, $id){
    $title = "";

    $title .= "Are you sure you want to refuse the " . $type;

    switch ($type){
      case "node":
        $node_mod = node_load($id);

        $title .= " \"" . $node_mod->title . "\" ";

        break;

      case "user":
        $user_mod = user_load($id);

        $title .= " \"" . $user_mod->name . "\" ";

        break;
    }

    $title .= "?";

    return $title;
  }

  public static function get_refuse_form(){
    $form = array();

    $form['message'] = array(
      '#type' => 'textarea',
      '#title' => t('Reason (Optional)'),
      '#description' => t('Optional message to explain why is refused the entity.'),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Refuse'),
    );

    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#href' => '/admin/structure/entity/moderation/list',
    );

    return $form;
  }

  public static function send_refuse_mail($type, $id, $message){
    switch ($type){
      case "node":
        Utils_Node::send_refuse_node_mail($id, $message);
        break;
    }
  }
}
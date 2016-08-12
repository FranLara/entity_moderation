<?php

class Utils_Moderation {

  public static function get_mail_templates(){
    $mail_templates = array();

    $mail_templates = Utils_Node::get_node_mails();

    return $mail_templates;
  }

  public static function check_moderation_entity($entity, $type){
    switch($type){
      case "node":
        Utils_Node::check_moderation_entity_node($entity);
        break;
    }
  }

  public static function moderate_entity($type, $id, $action){
    switch($type){
      case "node":
        Utils_Node::moderate_entity_node($id, $action);
        break;
    }
  }

  public static function execute_action($type, $id, $action, $message){
    $redirect = "";

    switch ($action){
      case "refuse":
        Utils_Refuse::send_refuse_mail($type, $id, $message);
        $redirect = "/entity/moderation/" . $type . "/" . $id . "/" . $action;
        break;
      case "more_info":
        Utils_More_Info::send_more_info_mail($type, $id, $message);
        $redirect = '/admin/structure/entity/moderation/list';
        break;
    }

    return $redirect;
  }
}
?>
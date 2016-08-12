<?php

class Utils_Node {

  /**
   * This function recovers the mail templates used to moderate nodes.
   */
  public static function get_node_mails(){
    $node_mails = array();

    $node_mails[] = self::get_moderation_node_mail();
    $node_mails[] = self::get_accept_node_mail();
    $node_mails[] = self::get_refuse_node_mail();
    $node_mails[] = self::get_more_info_node_mail();

    return $node_mails;
  }

  /**
   * This function recovers the content types of the platform.
   */
  public static function get_node_types(){

    $node_types_result = db_select("node_type","nt")
      ->fields("nt", array("type","name"))
      ->condition("disabled", 0, "=")
      ->condition("locked", 0, "=")
      ->execute();

    $node_types = array();

    foreach ($node_types_result as $node_type){
      $node_types [$node_type->type] = t($node_type->name);
    }

    return $node_types;
  }

  /**
   * This function recovers the fields associated to a content type
   * and can be used as a preview field.
   *
   * @param $type
   */
  public static function get_node_preview_fields($type){

    $allowed_types = array("text","text_long","text_with_summary","number_integer","number_decimal","number_float");

    $preview_fields = array();
    $node_settings = field_info_instances("node", $type);

    foreach ($node_settings as $setting){

      $field = field_info_field($setting["field_name"]);

      if(($field["cardinality"] == 1)&&(in_array($field["type"],$allowed_types))){
        $preview_fields[$setting["field_name"]] = $setting["label"];
      }
    }

    return $preview_fields;
  }

  /**
   * This function recovers the fields "email" associated to
   * a content type and can be used to request more info.
   *
   * @param $type
   */
  public static function get_node_mail_fields($type){

    $node_mails = array();
    $node_settings = field_info_instances("node", $type);

    foreach ($node_settings as $setting){
      if($setting["widget"]["type"] == "email_textfield"){
        $node_mails[$setting["field_name"]] = $setting["label"];
      }
    }

    return $node_mails;
  }

  /**
   * This function checks if the node must be moderated and
   * sends the advice mail to the moderators.
   *
   * @param $node
   */
  public static function check_moderation_entity_node($node){

    if(in_array($node->type, variable_get('entity_moderation_nodes'), TRUE)){

      $author = user_load($node->uid);

      if(!user_access("bypass entity moderation", $author)){

        $info_node = array (
            "nid" => $node->nid,
            "subject" => t("Moderation of node ") . $node->title,
        );

        $query = db_select("users", "u");
        $query->join("users_roles", "ur", "u.uid = ur.uid");
        $query->join("role", "r", "ur.rid = r.rid");
        $query->join("role_permission", "rp", "r.rid = rp.rid");
        $query->fields("u", array("mail"))
              ->condition("rp.permission", "moderation entity moderation", "=");

        $moderators = $query->execute();

        if(!empty($moderators)){

          $mail_address = array();

          foreach ($moderators as $moderator_mail){
            $mail_address[] = array("mail" => $moderator_mail->mail,"uid" => "");
          }

          pet_send_mail(MODERATION_NODE_MAIL, $mail_address , $info_node);
        }
      }
    }
  }

  /**
   * This function recovers the the nodes that must be moderated
   * from the database.
   */
  public static function get_node_entities_from_db(){

    $isModeration = FALSE;
    $entities_moderate = array();

    if(!empty(variable_get('entity_moderation_nodes'))){

      $query_nodes = db_select("node", "n");
      $query_nodes->join("node_type", "nt", "n.type = nt.type");
      $query_nodes->join("users", "u", "n.uid = u.uid");
      $query_nodes->fields("n", array("nid","uid","title","type","created"))
                  ->fields("nt", array("name"))
                  ->fields("u", array("name"))
                  ->condition("n.status", 0, "=")
                  ->orderBy("n.created", "DESC");

      $or = db_or();
      foreach(variable_get('entity_moderation_nodes') as $entity_moderate){
        if($entity_moderate != "0"){
          $isModeration = TRUE;
          $or->condition("n.type",$entity_moderate,"=");
        }
      }
      $query_nodes->condition($or);
    }

    if($isModeration){
      $moderation_nodes = $query_nodes->execute();

      foreach ($moderation_nodes as $node_mod){
        //dsm($node_mod);
        //TODO: Cambiar la query, recuperar los autores con el bypass y evitar que la query recupere
        //esos nodos. Con esto se optimiza el rendimiento al cambiar una llamada a base de datos, en vez
        //una llamada por cada nodo a recuperar.
        $author = user_load($node_mod->uid);

        if(!user_access("bypass entity moderation", $author)){

          $final_node = new stdClass();
          $final_node->id = $node_mod->nid;
          $final_node->uid = $node_mod->uid;
          $final_node->title = $node_mod->title;
          $final_node->type = $node_mod->type;
          $final_node->type_name = $node_mod->name;
          $final_node->created = $node_mod->created;
          $final_node->type_entity = "node";

          if(empty($author->name)){
            $final_node->author = t("Anonymous");
          }
          else{
            $final_node->author = $author->name;
          }

          if (variable_get("preview_node_" . $node_mod->type) != "-"){
            $node_mod = node_load($node_mod->nid);
            $preview_info = field_get_items("node",$node_mod,variable_get("preview_node_" . $node_mod->type));
            $final_node->preview = drupal_html_to_text($preview_info[0]["value"]);
          }

          $entities_moderate [] = $final_node;
        }
      }
    }

    return $entities_moderate;
  }

  public static function moderate_entity_node($id, $action){
    switch($action){

      case "accept":
        self::accept_node($id);
        break;

      case "refuse":
        self::refuse_node($id);
        break;
    }
  }

  public static function send_refuse_node_mail($id, $message){
    $node_deleted = node_load($id);

    if(!((variable_get(MAIL_ENTITY . $node_deleted->type) == AUTHOR_MAIL)&&($node_deleted->uid == 0))){
      $info_node = array (
          "nid" => $node_deleted->nid,
      );

      if(!empty($message)){
        $body = db_select("pets","p")
          ->fields("p", array("mail_body"))
          ->condition("name", REFUSE_NODE_MAIL, "=")
          ->execute()
          ->fetchObject();

        $info_node["body"] = $body->mail_body . t(" Due to: ") . $message;
      }

      $addressee = variable_get(MAIL_ENTITY . $node_deleted->type);

      if($addressee == AUTHOR_MAIL){
        $author = user_load($node_deleted->uid);
        $mail_address [] = array("mail" => $author->mail,"uid" => "");
      }
      else{
        $destiny = field_get_items("node",$node_deleted,$addressee);
        $mail_address [] = array("mail" => $destiny[0]["email"],"uid" => "");
      }

      pet_send_mail(REFUSE_NODE_MAIL, $mail_address , $info_node);
    }
  }

  public static function send_more_info_node_mail($id, $message){

    global $user;

    $node_ask = node_load($id);

    if(!((variable_get(MAIL_ENTITY . $node_ask->type) == AUTHOR_MAIL)&&($node_ask->uid == 0))){
      $info_node = array (
          "nid" => $node_ask->nid,
          "from" => $user->mail,
      );

      if(!empty($message)){
        $body = db_select("pets","p")
          ->fields("p", array("mail_body"))
          ->condition("name", MORE_INFO_MAIL, "=")
          ->execute()
          ->fetchObject();

        $info_node["body"] = $body->mail_body . t("due to: ") . $message;
      }

      $addressee = variable_get(MAIL_ENTITY . $node_ask->type);

      if($addressee == AUTHOR_MAIL){
        $author = user_load($node_ask->uid);
        $mail_address [] = array("mail" => $author->mail,"uid" => "");
      }
      else{
        $destiny = field_get_items("node",$node_ask,$addressee);
        $mail_address [] = array("mail" => $destiny[0]["email"],"uid" => "");
      }

      pet_send_mail(MORE_INFO_MAIL, $mail_address , $info_node);
    }
  }

  public static function get_node_more_info_mail($mail, $id){
    $final_mail = $mail;

    $node_ask = node_load($id);

    $final_mail->setSubject(t("We need more information about") . " " . $node_ask->title);

    $addressee = variable_get(MAIL_ENTITY . $node_ask->type);

    if($addressee == AUTHOR_MAIL){
      $author = user_load($node_ask->uid);
      $final_mail->setTo($author->mail);
    }
    else{
      $destiny = field_get_items("node",$node_ask,$addressee);
      $final_mail->setTo($destiny[0]["email"]);
    }

    return $final_mail;
  }

  private static function get_moderation_node_mail(){

    global $base_url;

    $moderation_mail = array();

    $moderation_mail["name"] = MODERATION_NODE_MAIL;
    $moderation_mail["status"] = 1;
    $moderation_mail["title"] = "Node moderation mail";
    $moderation_mail["subject"] = t("The node [node:title] ([node:content-type]) must be moderated.");
    $moderation_mail["body"] = t("The node <a href='" . $base_url . "/node/[node:nid]'><i>[node:title]</i></a> type <i>[node:content-type]</i> ");
    $moderation_mail["body"] .=  t("has been added by the user <a href='" . $base_url . "/user/[current-user:uid]'><i>[current-user:name]</i></a>.<br/><br/>");
    $moderation_mail["body"] .=  t("From here you can"). ": <a href='" . $base_url . "/entity/moderation/node/[node:nid]/accept'>" . t("accept it") . "</a>, ";
    $moderation_mail["body"] .=  "<a href='" . $base_url . "/admin/structure/entity/moderation/confirm/node/[node:nid]/refuse'>" . t("rechazarlo") . "</a> " . t("or") . " ";
    $moderation_mail["body"] .=  "<a href='" . $base_url . "/'>" . t("ask for more info") ."</a>.";

    return $moderation_mail;
  }

  private static function get_accept_node_mail(){

    global $base_url;

    $accept_mail = array();

    $accept_mail["name"] = ACCEPT_NODE_MAIL;
    $accept_mail["status"] = 1;
    $accept_mail["title"] = "Accepted node mail";
    $accept_mail["subject"] = t("Your node [node:title] has been accepted.");
    $accept_mail["body"] = t("Your node <a href='" . $base_url . "/node/[node:nid]'><i>[node:title]</i></a> has been");
    $accept_mail["body"] .= " <b>" . t("accepted") . "</b>.";

    return $accept_mail;
  }

  private static function get_refuse_node_mail(){

    $refuse_mail = array();

    $refuse_mail["name"] = REFUSE_NODE_MAIL;
    $refuse_mail["status"] = 1;
    $refuse_mail["title"] = "Refused node mail";
    $refuse_mail["subject"] = t("Your node [node:title] has been refused.");
    $refuse_mail["body"] = t("Your node <i>[node:title]</i> has been");
    $refuse_mail["body"] = " <b>" . t("refused") . "</b>.";

    return $refuse_mail;
  }

  private static function get_more_info_node_mail(){

    $more_info_mail = array();

    $more_info_mail["name"] = MORE_INFO_MAIL;
    $more_info_mail["status"] = 1;
    $more_info_mail["title"] = "More info node mail";
    $more_info_mail["subject"] = t("We need more info about your node ") . " [node:title]";
    $more_info_mail["body"] = t("We need more details about your node") . " <i>[node:title]</i> ";

    return $more_info_mail;
  }

  private static function accept_node($id){

    $node_updated = node_load($id);
    $node_updated->status = 1;

    if(!((variable_get(MAIL_ENTITY . $node_updated->type) == AUTHOR_MAIL)&&($node_updated->uid == 0))){
      $info_node = array (
          "nid" => $node_updated->nid,
      );

      $addressee = variable_get(MAIL_ENTITY . $node_updated->type);

      if($addressee == AUTHOR_MAIL){
        $author = user_load($node_updated->uid);
        $mail_address [] = array("mail" => $author->mail,"uid" => "");
      }
      else{
        $destiny = field_get_items("node",$node_updated,$addressee);
        $mail_address [] = array("mail" => $destiny[0]["email"],"uid" => "");
      }

      pet_send_mail(ACCEPT_NODE_MAIL, $mail_address , $info_node);
    }

    drupal_set_message(t("The node") . " " . $node_updated->title  . " " . t("has been accepted."),"status");

    node_save($node_updated);
  }

  private static function refuse_node($id){
    $node_deleted = node_load($id);

    db_delete("node")
      ->condition("nid", $id, "=")
      ->execute();

    drupal_set_message(t("The node") . " " . $node_deleted->title  . " " . t("has been deleted."),"status");
  }
}
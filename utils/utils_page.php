<?php

class Utils_Page {

  public static function get_moderation_list(){
    $table = new Table_Page();

    $table->setHeader(self::get_header_moderation_table());
    $table->setRows(self::get_rows_moderation_table());
    $table->setEmpty("There are no entities to moderate.");

    $list = theme_table($table->get_table());

    return $list;
  }

  public static function get_action_title($type, $id, $action){
    $title = "";

    switch ($action){
      case "refuse":
        $title = Utils_Refuse::get_refuse_title($type, $id);
        break;

      case "more_info":
        $title = Utils_More_Info::get_more_info_title($type, $id);
        break;
    }

    return $title;
  }

  public static function get_action_form($action){
    $form = array();

    switch($action){
      case "refuse":
        $form = Utils_Refuse::get_refuse_form();
        break;
      case "more_info":
        $form = Utils_More_Info::get_more_info_form();
    }

    return $form;
  }

  private static function get_header_moderation_table(){

    $header = array(
      array(
        "data" => t("Entity"),
        "class" => "entity_column"
      ),
      t("Type"),
      t("Author"),
      t("Date"),
      array (
        "data" => t("Operations"),
        "colspan" => 3,
        "class" => "operations_column"
      )
    );

    return $header;
  }

  private static function get_rows_moderation_table(){

    $rows = array();

    $entities = Utils_Node::get_node_entities_from_db();

    foreach ($entities as $entity){

      $rows [] = array(
        array("data" => self::get_entity_column($entity)),
        array("data" => $entity->type_name),
        array("data" => $entity->author),
        array("data" => date("d.m.Y", $entity->created)),
        array("data" => "<a href='/entity/moderation/" . $entity->type_entity . "/" . $entity->id . "/accept'>" . t("Accept") . "</a>"),
        array("data" => "<a href='/admin/structure/entity/moderation/action/" . $entity->type_entity . "/" . $entity->id . "/refuse'>" . t("Refuse") . "</a>"),
        array("data" => self::get_more_info_column($entity)),
      );
    }

    return $rows;
  }

  private static function get_entity_column($entity){
    $entity_column = "<a href='/". drupal_get_path_alias($entity->type_entity . "/" . $entity->id) . "'>" . t($entity->title) . "</a>";

    if (!empty($entity->preview)){

      if(strlen($entity->preview) <= variable_get("preview_node_size_" . $entity->type)){
        $info = $entity->preview;
      }
      else{
        $info = substr($entity->preview, 0, variable_get("preview_node_size_" . $entity->type)) . "...";
      }

      $entity_column .= "<br/><b>" . t("Preview info") . ":</b> <i>" . t($info) . "</i>";
    }

    return $entity_column;
  }

  private static function get_more_info_column($entity){
    $more_info_column = "";

    if((variable_get(MAIL_ENTITY . $entity->type) == AUTHOR_MAIL)&&($entity->uid == 0)){
      $more_info_column = t("No contact possibility");
    }
    else{
      $more_info_column = "<a href='/admin/structure/entity/moderation/action/" . $entity->type_entity . "/" . $entity->id . "/more_info'>" . t("Ask for more details") . "</a>";
    }

    return $more_info_column;
  }
}
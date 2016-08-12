<?php

function entity_moderation_main_settings() {

  $form['administration'] = array(
    '#type' => 'vertical_tabs',
    '#title' => t('Advanced settings'),
  );

  $form['nodes'] = array(
    '#type' => 'fieldset',
    '#title' => t('Nodes'),
    '#group' => 'administration',
  );
  $form["nodes"]['entity_moderation_nodes'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Node types to be moderated'),
    '#options' => Utils_Node::get_node_types(),
  );
  $form["nodes"]["entity_moderation_nodes"]['#default_value'] = array_keys(array_filter(variable_get('entity_moderation_nodes', array())));

  /* Pendiente de implementacion
  $form['users'] = array(
    '#type' => 'fieldset',
    '#title' => t('Users'),
    '#group' => 'administration',
  );*/

  return system_settings_form($form);
}

function entity_moderation_node_settings() {

  $form = array();

  if(!empty(variable_get('entity_moderation_nodes'))){

    foreach (variable_get('entity_moderation_nodes') as $node_name){

      if($node_name != "0"){

        $node_type = node_type_load($node_name);

        $form[$node_type->type] = array(
          "#type" => "fieldset",
          "#title" => t("Node: " . $node_type->name),
        );

        $form[$node_type->type]["preview_node_" . $node_type->type] = array(
          "#type" => "select",
          "#title" => t("Preview field"),
          "#description" => t('This the field that will be showed as a node preview info. (Only allowed type fields "text", "text_long",
              "text_with_summary", "number_integer", "number_decimal", "number_float" and fields with cardinality 1)'),
          "#options" => Utils_Node::get_node_preview_fields($node_name),
          "#empty_option" => t("None"),
          "#empty_value" => "-",
          "#default_value" => variable_get("preview_node_" . $node_type->type, "-"),
        );

        $form[$node_type->type]["preview_node_size_" . $node_type->type] = array(
          "#type" => "textfield",
          "#title" => t("Size of characters to show in the preview info"),
          "#description" => t("Number of characters will be shown in the preview info."),
          "#size" => 7,
          "#default_value" => variable_get("preview_node_size_" . $node_type->type, 300),
        );

        $form[$node_type->type][MAIL_ENTITY . $node_type->type] = array(
          "#type" => "select",
          "#title" => t("Mail field"),
          "#description" => t("This the field that will be used to request more ifo about the node. (Only allowed email type fields)"),
          "#options" => Utils_Node::get_node_mail_fields($node_name),
          "#empty_option" => t("Author"),
          "#empty_value" => AUTHOR_MAIL,
          "#default_value" => variable_get(MAIL_ENTITY . $node_type->type, "author"),
        );
      }
    }
  }

  return system_settings_form($form);
}
<?php

class Table_Page {

  private $header;
  private $rows;
  private $attributes;
  private $caption;
  private $colgroups;
  private $sticky;
  private $empty;

  public function __construct(){
    $this->header = array();
    $this->rows = array();
    $this->attributes = array();
    $this->caption = "";
    $this->colgroups = array();
    $this->sticky = FALSE;
    $this->empty = "This is the default empty message.";
  }

  public function getHeader(){
    return $this->header;
  }

  public function setHeader($header){
    $this->header = $header;
  }

  public function getRows(){
    return $this->rows;
  }

  public function setRows($rows){
    $this->rows = $rows;
  }

  public function getAttributes(){
    return $this->attributes;
  }

  public function setAttributes($attributes){
    $this->attributes = $attributes;
  }

  public function getCaption(){
    return $this->caption;
  }

  public function setCaption($caption){
    $this->caption = $caption;
  }

  public function getColgroups(){
    return $this->colgroups;
  }

  public function setColgroups($colgroups){
    $this->colgroups = $colgroups;
  }

  public function isSticky(){
    return $this->sticky;
  }

  public function setSticky($sticky){
    $this->sticky = $sticky;
  }

  public function getEmpty(){
    return $this->empty;
  }

  public function setEmpty($empty){
    $this->empty = $empty;
  }

  public function get_table(){
    $table = array();

    $table["header"] = $this->header;
    $table["rows"] = $this->rows;
    $table["attributes"] = $this->attributes;
    $table["caption"] = $this->caption;
    $table["colgroups"] = $this->colgroups;
    $table["sticky"] = $this->sticky;
    $table["empty"] = $this->empty;

    return $table;
  }
}
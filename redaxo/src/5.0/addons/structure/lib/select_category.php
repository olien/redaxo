<?php

class rex_category_select extends rex_select
{
  private $ignore_offlines;
  private $clang;
  private $check_perms;
  private $rootId;

  public function __construct($ignore_offlines = false, $clang = false, $check_perms = true, $add_homepage = true)
  {
    $this->ignore_offlines = $ignore_offlines;
    $this->clang = $clang;
    $this->check_perms = $check_perms;
    $this->add_homepage = $add_homepage;
    $this->rootId = null;

    parent::__construct();
  }

  /**
   * Kategorie-Id oder ein Array von Kategorie-Ids als Wurzelelemente der Select-Box.
   *
   * @param $rootId mixed Kategorie-Id oder Array von Kategorie-Ids zur Identifikation der Wurzelelemente.
   */
  public function setRootId($rootId)
  {
    $this->rootId = $rootId;
  }

  protected function addCatOptions()
  {
    if($this->add_homepage)
      $this->addOption('Homepage', 0);

    if($this->rootId !== null)
    {
      if(is_array($this->rootId))
      {
        foreach($this->rootId as $rootId)
        {
          if($rootCat = rex_ooCategory::getCategoryById($rootId, $this->clang))
          {
            $this->addCatOption($rootCat, 0);
          }
        }
      }
      else
      {
        if($rootCat = rex_ooCategory::getCategoryById($this->rootId, $this->clang))
        {
          $this->addCatOption($rootCat, 0);
        }
      }
    }
    else
    {
      if(!$this->check_perms || rex_core::getUser()->isAdmin() || rex_core::getUser()->hasPerm('csw[0]'))
      {
        if($rootCats = rex_ooCategory :: getRootCategories($this->ignore_offlines, $this->clang))
        {
          foreach($rootCats as $rootCat)
          {
            $this->addCatOption($rootCat);
          }
        }
      }
      elseif(rex_core::getUser()->hasMountpoints())
      {
        $mountpoints = rex_core::getUser()->getMountpoints();
        foreach($mountpoints as $id)
        {
          $cat = rex_ooCategory::getCategoryById($id, $this->clang);
          if ($cat && !rex_core::getUser()->hasCategoryPerm($cat->getParentId()))
            $this->addCatOption($cat, 0);
        }
      }
    }
  }

  protected function addCatOption(rex_ooCategory $cat, $group = null)
  {
    if(!$this->check_perms ||
        $this->check_perms && rex_core::getUser()->hasCategoryPerm($cat->getId(),FALSE))
    {
      $cid = $cat->getId();
      $cname = $cat->getName();

      if(rex_core::getUser()->hasPerm('advancedMode[]'))
        $cname .= ' ['. $cid .']';

      if($group === null)
        $group = $cat->getParentId();

      $this->addOption($cname, $cid, $cid, $group);
      $childs = $cat->getChildren($this->ignore_offlines, $this->clang);
      if (is_array($childs))
      {
        foreach ($childs as $child)
        {
          $this->addCatOption($child);
        }
      }
    }
  }

  public function get()
  {
    static $loaded = false;

    if(!$loaded)
    {
      $this->addCatOptions();
    }

    return parent::get();
  }

  private function _outGroup($re_id, $level = 0)
  {
		if ($level > 100)
    {
      // nur mal so zu sicherheit .. man weiss nie ;)
      echo "select->_outGroup overflow ($groupname)";
      exit;
    }

    $ausgabe = '';
    $group = $this->_getGroup($re_id);
    foreach ($group as $option)
    {
      $name = $option[0];
      $value = $option[1];
      $id = $option[2];
      if($id==0 || !$this->check_perms || ($this->check_perms && rex_core::getUser()->hasCategoryPerm($option[2],TRUE)))
      {
          $ausgabe .= $this->_outOption($name, $value, $level);
      }elseif(($this->check_perms && rex_core::getUser()->hasCategoryPerm($option[2],FALSE)))
      {
      	$level--;
      }

      $subgroup = $this->_getGroup($id, true);
      if ($subgroup !== false)
      {
        $ausgabe .= $this->_outGroup($id, $level +1);
      }
    }
    return $ausgabe;
  }

}

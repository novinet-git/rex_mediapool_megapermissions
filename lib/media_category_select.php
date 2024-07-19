<?php

/**
 * Class MediaKategorie Select.
 *
 * @package redaxo\mediapool
 */
class rex_media_category_select extends rex_select
{
    /** @var bool */
    private $checkPerms;

    /** @var bool */
    private $checkReadPerms = false;

    /** @var int|list<int>|null */
    private $rootId;

    /** @var bool */
    private $select2;

    /** @var bool */
    private $loaded = false;

    public function __construct($checkPerms = true, $checkReadPerms = false, $select2 = false)
    {
        $this->checkPerms = $checkPerms; // check for read and write categories
        $this->checkReadPerms = $checkReadPerms; // check for only read categories
        if ($checkPerms === false && $checkReadPerms === true) {
            $this->checkPerms = true;
        }
        $this->rootId = null;
        $this->select2 = $select2;

        parent::__construct();
    }

    /**
     * Kategorie-Id oder ein Array von Kategorie-Ids als Wurzelelemente der Select-Box.
     *
     * @param int|list<int>|null $rootId Kategorie-Id oder Array von Kategorie-Ids zur Identifikation der Wurzelelemente
     * @return void
     */
    public function setRootId($rootId)
    {
        $this->rootId = $rootId;
    }

    /**
     * @return void
     */
    protected function addCatOptions()
    {
        if (null !== $this->rootId) {
            if (is_array($this->rootId)) {
                foreach ($this->rootId as $rootId) {
                    if ($rootCat = rex_media_category::get($rootId)) {
                        $this->addCatOption($rootCat);
                    }
                }
            } else {
                if ($rootCat = rex_media_category::get($this->rootId)) {
                    $this->addCatOption($rootCat);
                }
            }
        } else {
            if ($rootCats = rex_media_category::getRootCategories()) {
                foreach ($rootCats as $rootCat) {
                    $this->addCatOption($rootCat);
                }
            }
        }
    }

    /**
     * @return void
     */
    protected function addCatOption(rex_media_category $mediacat, int $parentId = 0)
    {
        $childWithPermission = false;
        $parentWithPermission = false;
        $categoryPermission = false;

        if (rex::requireUser()->getComplexPerm('media')->hasAll()) {
            $this->checkPerms = false;
        }

        if ($this->checkPerms) {
            $childWithPermission = rex_media_category_perm_helper::getMediaCategoryChildren($mediacat, $this->checkReadPerms);
            $parentWithPermission = rex_media_category_perm_helper::getMediaCategoryParent($mediacat, $this->checkReadPerms);
            $categoryPermission = rex::requireUser()->getComplexPerm('media')->hasCategoryPerm($mediacat->getId());
        }

        if (!$this->checkPerms || ($categoryPermission || $parentWithPermission || $childWithPermission)) {
            $mid = $mediacat->getId();
            $mname = $mediacat->getName();
            $attributes = [];

            if (!$categoryPermission && $childWithPermission) {
                $attributes['disabled'] = '1';
            }

            $this->addOption($mname, $mid, $mid, $parentId, $attributes);

            $parentId = $mediacat->getId();
        }
        if (is_array($mediacat->getChildren()) && count($mediacat->getChildren()) > 0) {
            foreach ($mediacat->getChildren() as $child) {
                $this->addCatOption($child, $parentId);
            }
        }
    }

    public function get()
    {
        if (!$this->loaded) {
            $this->addCatOptions();
            $this->loaded = true;
        }

        if (true === $this->select2) {
            $this->setAttribute('class', 'selectpicker w-100');
            $this->setAttribute('data-live-search', 'true');
        }

        return parent::get();
    }
}

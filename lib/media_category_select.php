<?php

/**
 * Class MediaKategorie Select.
 *
 * @package redaxo\mediapool
 */
class rex_media_category_select extends rex_select
{
    public const WRITE = 1;
    public const READ = 2;

    /**
     * @var bool
     */
    private $checkPerms;

    /**
     * @var bool
     */
    private $checkReadPerms = false;

    /**
     * @var int|int[]|null
     */
    private $rootId;

    /**
     * @var bool
     */
    private $select2;

    private $loaded = false;

    public function __construct($checkPerms = true, $checkReadPerms = false, $select2 = false)
    {
        $this->checkPerms = $checkPerms;
        $this->checkReadPerms = $checkReadPerms;
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
     * @param int|int[]|null $rootId Kategorie-Id oder Array von Kategorie-Ids zur Identifikation der Wurzelelemente
     */
    public function setRootId($rootId)
    {
        $this->rootId = $rootId;
    }

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

    protected function addCatOption(rex_media_category $mediacat, int $parentId = 0)
    {
        $childWithPermission = false;
        $parentWithPermission = false;

        if (rex::getUser()->getComplexPerm('media')->hasAll()) {
            $this->checkPerms = false;
        }

        if ($this->checkPerms) {
            $childWithPermission = rex_media_category_perm_helper::getMediaCategoryChildren($mediacat, $this->checkReadPerms);
            $parentWithPermission = rex_media_category_perm_helper::getMediaCategoryParent($mediacat, $this->checkReadPerms);
        }

        if (!$this->checkPerms ||
            $this->checkPerms && (
                rex::getUser()->getComplexPerm('media')->hasCategoryPerm($mediacat->getId())
                || ($this->checkReadPerms && rex::getUser()->getComplexPerm('media_read')->hasCategoryPerm($mediacat->getId()))
                || $parentWithPermission instanceof rex_media_category // check all parents
                || $childWithPermission instanceof rex_media_category // check children
            )
        ) {
            $categoryId = $mediacat->getId();
            $parentCategoryId = $mediacat->getParentId();
            $value = $categoryId;
            $attributes = [];

            // no permission for parent set as id for parent the id from the first child with permission
            if ($this->checkPerms && $childWithPermission instanceof rex_media_category && (
                    $value != $childWithPermission->getId() // my id is not the id of the child with the permission
                    && (
                        true === rex_media_category_perm_helper::isIdParentInPath($childWithPermission, $value) // and my id is in the path
                        && true !== rex_media_category_perm_helper::isIdParentInPath($mediacat, $childWithPermission->getId()) // and the child id is not in my path!
                    )
                )
            ) {
                $value = $childWithPermission->getId();
                $attributes['disabled'] = '1';
            }

            $categoryName = $mediacat->getName() . ' [' . $categoryId . ']';
            $this->addOption($categoryName, $value, $categoryId, $parentCategoryId, $attributes);

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
            $this->setAttribute('class', 'selectpicker');
            $this->setAttribute('data-live-search', 'true');
        }

        return parent::get();
    }
}

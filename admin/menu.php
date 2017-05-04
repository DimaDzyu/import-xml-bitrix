<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
$aMenu = array(
    array(
        'parent_menu' => 'global_menu_store',
        'sort' => 800,
        'text' => "Импорт товаров XML",
        'title' => "Импорт товаров XML",
        'url' => 'loadxml.php',
        'items_id' => 'menu_references',
    ),
);
return $aMenu;
?>
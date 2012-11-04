<?php
/**
 * Start file for the Default Sort Omeka plugin
 *
 * The plugin.php file is included by Omeka and must add any plugin
 * hooks or filters which we want Omeka to know about.
 *
 * This plugin sorts items by an admin configurable field whenever 'sort_field' has not
 * been specified in some other way (for example, in a URL's query string).
 */

add_plugin_hook('item_browse_sql', 'default_sort_item_browse_sql');
add_plugin_hook('install', 'default_sort_install');
add_plugin_hook('uninstall', 'default_sort_uninstall');
add_plugin_hook('config_form', 'default_sort_config_form');
add_plugin_hook('config', 'default_sort_config');

function default_sort_install()
{
    set_option('default_sort_field', 'Dublin Core,Identifier');
    set_option('default_sort_direction', 'ASC');
}

function default_sort_uninstall()
{
    delete_option('default_sort_field');
    delete_option('default_sort_direction');
}

function default_sort_config()
{
    set_option('default_sort_field', $_POST['fieldSelect']);
    set_option('default_sort_direction', $_POST['directionSelect']);
}

function default_sort_config_form() {
    $select = get_db()->select()
        ->distinct()
        ->from(array('Texts'=>'omeka_elements'), array('name'))
        ->join(array('Sets'=>'omeka_element_sets'),'Sets.id=Texts.element_set_id', array())
        ->where('Sets.name = "Dublin Core"');

    $dublinCoreElements = get_db()->fetchCol($select);
    
    $fieldSelect = new Zend_Form_Element_Select('fieldSelect');
    foreach ($dublinCoreElements as $element) {
        $fieldSelect->addMultiOption('Dublin Core,' . $element, $element);
    }    
    $fieldSelect->setLabel(__('Sort field (Dublin Core)'))
        ->setValue(get_option('default_sort_field'));
    echo $fieldSelect;    
    
    $directionSelect = new Zend_Form_Element_Select('directionSelect');
    $directionSelect->addMultiOption('ASC', 'Ascending')
                    ->addMultiOption('DESC', 'Descending')
                    ->setLabel(__('Sort direction'))
                    ->setValue(get_option('default_sort_direction'));
    echo $directionSelect;
}

function default_sort_item_browse_sql($select, $params)
{
    if (array_key_exists('sort_field', $params)) {
        return;
    }
    
    if (array_key_exists('sort_dir', $params)) {
        if ($params['sort_dir'] == 'a') {
            $sort_dir = 'ASC';
        } else {
            $sort_dir = 'DESC';
        }
    } else {
        $sort_dir = get_option('default_sort_direction');
    }
    
    $select->reset(Zend_Db_Select::ORDER);
    $dummy_table = new ItemTable('Item', get_db());
    $dummy_table->applySorting($select, get_option('default_sort_field'), $sort_dir);
}
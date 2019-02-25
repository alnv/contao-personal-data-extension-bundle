<?php

$GLOBALS['TL_DCA']['tl_module']['palettes']['personalDataExtension'] = '{title_legend},name,headline,type;{config_legend},editable;{redirect_legend},jumpTo;{template_legend:hide},memberExtendedTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

if ( class_exists( 'NotificationCenter\tl_nc_notification' ) ) {

    if ( strpos( $GLOBALS['TL_DCA']['tl_module']['palettes']['personalDataExtension'], 'newsletters' ) ) {

        $GLOBALS['TL_DCA']['tl_module']['palettes']['personalDataExtension'] = str_replace( 'newsletters;', 'newsletters,nc_notification;', $GLOBALS['TL_DCA']['tl_module']['palettes']['personalDataExtension'] );
    }

    else {

        $GLOBALS['TL_DCA']['tl_module']['palettes']['personalDataExtension'] = str_replace( 'editable;', 'editable,nc_notification;', $GLOBALS['TL_DCA']['tl_module']['palettes']['personalDataExtension'] );
    }
}

$GLOBALS['TL_DCA']['tl_module']['fields']['memberExtendedTpl'] = [

    'label' => &$GLOBALS['TL_LANG']['tl_module']['memberTpl'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ 'Alnv\PersonalDataExtensionBundle\DataContainer\Module', 'getMemberTemplates' ],
    'eval' => [ 'tl_class'=>'w50' ],
    'sql' => "varchar(64) NOT NULL default ''"
];
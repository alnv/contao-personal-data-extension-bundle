<?php

$GLOBALS['TL_DCA']['tl_module']['palettes']['personalDataExtension'] = '{title_legend},name,headline,type;{config_legend},editable;{redirect_legend},jumpTo;{template_legend:hide},memberExtendedTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['memberExtendedTpl'] = [

    'label' => &$GLOBALS['TL_LANG']['tl_module']['memberTpl'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [ 'Alnv\PersonalDataExtensionBundle\DataContainer\Module', 'getMemberTemplates' ],
    'eval' => [ 'tl_class'=>'w50' ],
    'sql' => "varchar(64) NOT NULL default ''"
];
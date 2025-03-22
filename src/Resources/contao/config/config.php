<?php

use Alnv\PersonalDataExtensionBundle\Modules\ModulePersonalData;
use Contao\ArrayUtil;

ArrayUtil::arrayInsert($GLOBALS['FE_MOD'], 3, [
    'user' => [
        'personalDataExtension' => ModulePersonalData::class
    ]
]);
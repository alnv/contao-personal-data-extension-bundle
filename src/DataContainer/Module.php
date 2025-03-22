<?php

namespace Alnv\PersonalDataExtensionBundle\DataContainer;

use Contao\Backend;

class Module extends Backend
{

    public function getMemberTemplates()
    {
        return $this->getTemplateGroup('member_extended_');
    }
}
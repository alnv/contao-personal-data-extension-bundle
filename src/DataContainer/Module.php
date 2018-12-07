<?php

namespace Alnv\PersonalDataExtensionBundle\DataContainer;


class Module extends \Backend {


    public function getMemberTemplates() {

        return $this->getTemplateGroup( 'member_extended_' );
    }
}
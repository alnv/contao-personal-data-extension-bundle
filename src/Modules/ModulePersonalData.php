<?php

namespace Alnv\PersonalDataExtensionBundle\Modules;


class ModulePersonalData extends \Module {


    protected $strTemplate = 'member_extended_default';


    public function generate() {

        if (TL_MODE == 'BE') {

            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['personalDataExtension'][0] . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        $this->editable = \StringUtil::deserialize( $this->editable );

        if ( empty( $this->editable ) || !\is_array( $this->editable ) || !FE_USER_LOGGED_IN ) {

            return '';
        }

        if ( $this->memberExtendedTpl != '' ) {

            $this->strTemplate = $this->memberExtendedTpl;
        }

        return parent::generate();
    }


    protected function compile() {

        global $objPage;

        $this->import('FrontendUser', 'User');

        $GLOBALS['TL_LANGUAGE'] = $objPage->language;

        \System::loadLanguageFile('tl_member');
        $this->loadDataContainer('tl_member');

        if ( is_array( $GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] ) ) {

            foreach ($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] as $arrCallback ) {

                if ( is_array( $arrCallback )) {

                    $this->import( $arrCallback[0] );
                    $this->{$arrCallback[0]}->{$arrCallback[1]}();

                } elseif (is_callable($arrCallback)) {

                    $arrCallback();
                }
            }
        }

        $this->Template->fields = '';

        $intRow = 0;
        $arrFields = [];
        $blnHasUpload = false;
        $blnDoNotSubmit = false;

        $arrGroups = [

            'personal' => [],
            'address'  => [],
            'contact'  => [],
            'login'    => [],
            'profile'  => []
        ];

        $blnModified = false;
        $objMember = \MemberModel::findByPk($this->User->id);
        $strTable = $objMember->getTable();
        $strFormId = 'tl_member_' . $this->id;
        $objSession = \System::getContainer()->get('session');
        $objFlashBag = $objSession->getFlashBag();

        $objVersions = new \Versions( $strTable, $objMember->id );
        $objVersions->setUsername( $objMember->username );
        $objVersions->setUserId(0);
        $objVersions->setEditUrl('contao/main.php?do=member&act=edit&id=%s&rt=1');
        $objVersions->initialize();

        foreach ( $this->editable as $strField ) {

            $arrData = &$GLOBALS['TL_DCA']['tl_member']['fields'][ $strField ];

            if ($arrData['inputType'] == 'checkboxWizard') {

                $arrData['inputType'] = 'checkbox';
            }

            if ($arrData['inputType'] == 'fileTree') {

                $arrData['inputType'] = 'upload';
            }

            $strClass = $GLOBALS['TL_FFL'][ $arrData['inputType'] ];

            if ( !$arrData['eval']['feEditable'] || !class_exists( $strClass ) ) {

                continue;
            }

            $strGroup = $arrData['eval']['feGroup'];
            $arrData['eval']['required'] = false;

            if ( $arrData['eval']['mandatory'] ) {

                if ( is_array( $this->User->$strField ) ) {

                    if ( empty( $this->User->$strField ) ) {

                        $arrData['eval']['required'] = true;
                    }
                }

                else {

                    if ( !strlen($this->User->$strField ) ) {

                        $arrData['eval']['required'] = true;
                    }
                }
            }

            $varValue = $this->User->$strField;

            if ( \Input::post('FORM_ONCHANGE') == $strFormId ) {

                $varValue = \Input::post( $strField );
            }

            if ( isset( $arrData['load_callback'] ) && is_array( $arrData['load_callback'] ) ) {

                foreach ( $arrData['load_callback'] as $arrCallback ) {

                    if ( is_array( $arrCallback ) ) {

                        $this->import( $arrCallback[0] );
                        $varValue = $this->{$arrCallback[0]}->{$arrCallback[1]}( $varValue, $this->User, $this );

                    } elseif ( is_callable( $arrCallback ) ) {

                        $varValue = $arrCallback( $varValue, $this->User, $this );
                    }
                }
            }

            $objWidget = new $strClass( $strClass::getAttributesFromDca( $arrData, $strField, $varValue, $strField, $strTable, $this ) );

            $objWidget->id .= '_' . $this->id;
            $objWidget->storeValues = true;
            $objWidget->rowClass = 'row_' . $intRow . (($intRow == 0) ? ' row_first' : '') . ((($intRow % 2) == 0) ? ' even' : ' odd');

            if ( $arrData['eval']['submitOnChange'] ) {

                $objWidget->addAttributes( [ 'onchange' => 'this.form.submit()' ] );
            }

            if ( $objWidget instanceof \FormPassword ) {

                if ($objMember->password != '') {

                    $objWidget->mandatory = false;
                }

                $objWidget->rowClassConfirm = 'row_' . ++$intRow . ((($intRow % 2) == 0) ? ' even' : ' odd');
            }

            if ( \Input::post('FORM_SUBMIT') == $strFormId ) {

                $objWidget->validate();
                $varValue = $objWidget->value;
                $strRgxp = $arrData['eval']['rgxp'];

                if ( $varValue != '' && \in_array( $strRgxp, array('date', 'time', 'datim') ) ) {

                    try {

                        $objDate = new \Date( $varValue, \Date::getFormatFromRgxp( $strRgxp ) );
                        $varValue = $objDate->tstamp;
                    }

                    catch ( \OutOfBoundsException $e ) {

                        $objWidget->addError( sprintf( $GLOBALS['TL_LANG']['ERR']['invalidDate'], $varValue ) );
                    }
                }

                if ( $arrData['eval']['unique'] && $varValue != '' && !$this->Database->isUniqueValue( 'tl_member', $strField, $varValue, $this->User->id ) ) {

                    $objWidget->addError( sprintf( $GLOBALS['TL_LANG']['ERR']['unique'], $arrData['label'][0] ?: $strField ) );
                }

                if ( $objWidget->submitInput() && !$objWidget->hasErrors() && is_array( $arrData['save_callback'] ) ) {

                    foreach ( $arrData['save_callback'] as $arrCallback ) {

                        try {

                            if ( is_array( $arrCallback ) ) {

                                $this->import( $arrCallback[0] );
                                $varValue = $this->{$arrCallback[0]}->{$arrCallback[1]}( $varValue, $this->User, $this );

                            } elseif ( is_callable( $arrCallback ) ) {

                                $varValue = $arrCallback( $varValue, $this->User, $this );
                            }
                        }

                        catch (\Exception $e) {

                            $objWidget->class = 'error';
                            $objWidget->addError($e->getMessage());
                        }
                    }
                }

                if ( $objWidget->hasErrors() ) {

                    $blnDoNotSubmit = true;
                }

                elseif ( $objWidget->submitInput() ) {

                    $_SESSION['FORM_DATA'][ $strField ] = $varValue;

                    if ( $varValue === '' ) {

                        $varValue = $objWidget->getEmptyValue();
                    }

                    if ($arrData['eval']['encrypt']) {

                        $varValue = \Encryption::encrypt($varValue);
                    }

                    if ( $varValue !== $this->User->$strField ) {

                        $this->User->$strField = $varValue;

                        $blnModified = true;
                        $objMember->$strField = $varValue;
                    }
                }
            }

            if ($objWidget instanceof \uploadable) {

                $blnHasUpload = true;
            }

            $strTempField = $objWidget->parse();
            $this->Template->fields .= $strTempField;
            $arrFields[ $strGroup ][ $strField ] .= $strTempField;
            ++$intRow;
        }

        if ( $blnModified ) {

            $objMember->tstamp = time();
            $objMember->save();

            if ( $GLOBALS['TL_DCA'][$strTable]['config']['enableVersioning'] ) {

                $objVersions->create();
            }
        }

        $this->Template->hasError = $blnDoNotSubmit;

        if ( \Input::post('FORM_SUBMIT') == $strFormId && !$blnDoNotSubmit ) {

            if ( isset( $GLOBALS['TL_HOOKS']['updatePersonalData']) && is_array($GLOBALS['TL_HOOKS']['updatePersonalData'] ) ) {

                foreach ( $GLOBALS['TL_HOOKS']['updatePersonalData'] as $arrCallback ) {

                    $this->import( $arrCallback[0] );
                    $this->{$arrCallback[0]}->{$arrCallback[1]}( $this->User, $_SESSION['FORM_DATA'], $this );
                }
            }

            if ( is_array( $GLOBALS['TL_DCA']['tl_member']['config']['onsubmit_callback'] ) ) {

                foreach ( $GLOBALS['TL_DCA']['tl_member']['config']['onsubmit_callback'] as $arrCallback ) {

                    if ( is_array( $arrCallback ) ) {

                        $this->import( $arrCallback[0] );
                        $this->{$arrCallback[0]}->{$arrCallback[1]}( $this->User, $this );
                    }
                    elseif ( is_callable( $arrCallback ) )
                    {
                        $arrCallback( $this->User, $this );
                    }
                }
            }

            if ( ( $objJumpTo = $this->objModel->getRelated('jumpTo') ) instanceof \PageModel ) {

                $this->jumpToOrReload( $objJumpTo->row() );
            }

            $objFlashBag->set( 'mod_personal_data_confirm', $GLOBALS['TL_LANG']['MSC']['savedData'] );
            $this->reload();
        }

        $this->Template->loginDetails = $GLOBALS['TL_LANG']['tl_member']['loginDetails'];
        $this->Template->personalData = $GLOBALS['TL_LANG']['tl_member']['personalData'];
        $this->Template->addressDetails = $GLOBALS['TL_LANG']['tl_member']['addressDetails'];
        $this->Template->contactDetails = $GLOBALS['TL_LANG']['tl_member']['contactDetails'];

        foreach ( $arrFields as $strKey => $strValue ) {

            $this->Template->$strKey = $strValue;
            $strFieldset = $strKey . (($strKey == 'personal') ? 'Data' : 'Details');
            $arrGroups[ $GLOBALS['TL_LANG']['tl_member'][ $strFieldset ] ] = $strValue;
        }

        if ( $objSession->isStarted() && $objSession->has( 'mod_personal_data_confirm' ) ) {

            $arrMessages = $objFlashBag->get( 'mod_personal_data_confirm' );
            $this->Template->message = $arrMessages[0];
        }

        $this->Template->categories = $arrGroups;
        $this->Template->formId = $strFormId;
        $this->Template->slabel = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['saveData']);
        $this->Template->action = \Environment::get('indexFreeRequest');
        $this->Template->enctype = $blnHasUpload ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
        $this->Template->rowLast = 'row_' . $intRow . ((($intRow % 2) == 0) ? ' even' : ' odd');
    }
}
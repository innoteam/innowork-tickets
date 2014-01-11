<?php
// ----- Initialization -----
//

require_once('innowork/tickets/InnoworkTicket.php');
require_once('innowork/tickets/InnoworkTicketField.php');
require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/widgets/WuiWidget.php');
require_once('innomatic/wui/widgets/WuiContainerWidget.php');
require_once('innomatic/wui/dispatch/WuiEventsCall.php');
require_once('innomatic/wui/dispatch/WuiEvent.php');
require_once('innomatic/wui/dispatch/WuiEventRawData.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');
require_once('innomatic/locale/LocaleCatalog.php');
require_once('innomatic/locale/LocaleCountry.php'); 

    global $gLocale, $gPage_title, $gXml_def, $gPage_status;

require_once('innowork/core/InnoworkCore.php');
$gInnowork_core = InnoworkCore::instance('innoworkcore', 
    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    );

$gLocale = new LocaleCatalog(
    'innowork-tickets::innoworktickets_domain_prefs',
    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
    );

$gWui = Wui::instance('wui');
$gWui->loadWidget( 'xml' );
$gWui->loadWidget( 'innomaticpage' );
$gWui->loadWidget( 'innomatictoolbar' );

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr( 'preferences.title' );
$gCore_toolbars = $gInnowork_core->getMainToolBar();

$gToolbars['prefs'] = array(
    'prefs' => array(
        'label' => $gLocale->getStr( 'preferences.toolbar' ),
        'themeimage' => 'settings1',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( 'innoworkticketsprefs', array( array(
            'view',
            'default',
            '' ) ) )
        ),
    'newfield' => array(
        'label' => $gLocale->getStr( 'newfield.toolbar' ),
        'themeimage' => 'filenew',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( 'innoworkticketsprefs', array( array(
            'view',
            'newfield',
            '' ) ) )
        )    );

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher( 'action' );

$gAction_disp->addEvent(
    'addfield',
    'action_addfield'
    );
function action_addfield( $eventData )
{
    global $gPage_status, $gLocale;

    $field = new InnoworkTicketField(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        $eventData['fieldtype']
        );

    if ( $field->NewValue( $eventData['value'] ) ) $gPage_status = $gLocale->getStr( 'field_added.status' );
    else $gPage_status = $gLocale->getStr( 'field_not_added.status' );
}

$gAction_disp->addEvent(
    'editfield',
    'action_editfield'
    );
function action_editfield( $eventData )
{
    global $gPage_status, $gLocale;

    $field = new InnoworkTicketField(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        '',
        $eventData['id']
        );

    if ( $field->EditValue( 
        $eventData['value'],
        $eventData['fieldtype']
        ) ) $gPage_status = $gLocale->getStr( 'field_updated.status' );
    else $gPage_status = $gLocale->getStr( 'field_not_updated.status' );
}

$gAction_disp->addEvent(
    'removefield',
    'action_removefield'
    );
function action_removefield( $eventData )
{
    global $gPage_status, $gLocale;

    $field = new InnoworkTicketField(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        $eventData['fieldtype'],
        $eventData['id']
        );

    if ( $field->RemoveValue( $eventData['value'] ) ) $gPage_status = $gLocale->getStr( 'field_removed.status' );
    else $gPage_status = $gLocale->getStr( 'field_not_removed.status' );
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher( 'view' );

function fields_tab_action_builder( $tab )
{
    return WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            array( 'tab' => $tab )
        ) ) );
}

$gMain_disp->addEvent(
    'default',
    'main_default' );
function main_default( $eventData )
{
    global $gLocale, $gPage_title, $gXml_def, $gPage_status;

    $tabs[0]['label'] = $gLocale->getStr( 'status.tab' );
    $tabs[1]['label'] = $gLocale->getStr( 'priority.tab' );
    $tabs[2]['label'] = $gLocale->getStr( 'type.tab' );
    $tabs[3]['label'] = $gLocale->getStr( 'source.tab' );
    $tabs[4]['label'] = $gLocale->getStr( 'channel.tab' );

    $headers[0]['label'] = $gLocale->getStr( 'fieldvalue.header' );

    $gXml_def =
'<vertgroup><name>settings</name>
  <children>

    <label><name>fields</name>
      <args>
        <bold>true</bold>
        <label type="encoded">'.urlencode( $gLocale->getStr( 'fieldvalues.label' ) ).'</label>
      </args>
    </label>

    <tab><name>fieldsvalues</name>
      <args>
        <tabs type="array">'.WuiXml::encode( $tabs ).'</tabs>
        <tabactionfunction>fields_tab_action_builder</tabactionfunction>
        <activetab>'.( isset($eventData['tab'] ) ? $eventData['tab'] : '' ).'</activetab>
      </args>
      <children>';

    $gXml_def .=
'        <table><name>types</name>
          <args>
            <headers type="array">'.WuiXml::encode( $headers ).'</headers>
          </args>
          <children>';

    $row = 0;
    $statuses = InnoworkTicketField::getFields( InnoworkTicketField::TYPE_STATUS );
    while ( list( $id, $field ) = each( $statuses ) )
    {
        $gXml_def .=
'<label row="'.$row.'" col="0"><name>field</name>
  <args>
    <label type="encoded">'.urlencode( $field ).'</label>
  </args>
</label>
<innomatictoolbar row="'.$row.'" col="1"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">'.WuiXml::encode( array(
        'view' => array(
            'show' => array(
                'label' => $gLocale->getStr( 'editfield.button' ),
                'themeimage' => 'pencil',
                'themeimagetype' => 'mini',        
                'horiz' => 'true',
                'action' => WuiEventsCall::buildEventsCallString( '', array( array(
                    'view',
                    'editfield',
                    array( 'id' => $id ) ) ) )
                ),
            'remove' => array(
                'label' => $gLocale->getStr( 'removefield.button' ),
                'themeimage' => 'trash',
                'themeimagetype' => 'mini',
                'horiz' => 'true',
                'needconfirm' => 'true',
                'confirmmessage' => $gLocale->getStr( 'removefield.confirm' ),
                'action' => WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                    ),
                    array(
                        'action',
                        'removefield',
                        array(
                            'id' => $id,
                            'fieldtype' => InnoworkTicketField::TYPE_STATUS
                            ) ) ) )
        ) ) ) ).'</toolbars>
  </args>
</innomatictoolbar>';

            $row++;
        }

            $gXml_def .=
'          </children>
        </table>';

        $gXml_def .=
'        <table><name>types</name>
          <args>
            <headers type="array">'.WuiXml::encode( $headers ).'</headers>
          </args>
          <children>';

        $row = 0;
        $priorities = InnoworkTicketField::getFields( InnoworkTicketField::TYPE_PRIORITY );
        while ( list( $id, $field ) = each( $priorities ) )
        {
            $gXml_def .=
'<label row="'.$row.'" col="0"><name>priority</name>
  <args>
    <label type="encoded">'.urlencode( $field ).'</label>
  </args>
</label>
<innomatictoolbar row="'.$row.'" col="1"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">'.WuiXml::encode( array(
        'view' => array(
            'show' => array(
                'label' => $gLocale->getStr( 'editfield.button' ),
                'themeimage' => 'pencil',
                'themeimagetype' => 'mini',
            	'horiz' => 'true',
                'action' => WuiEventsCall::buildEventsCallString( '', array( array(
                    'view',
                    'editfield',
                    array( 'id' => $id ) ) ) )
                ),
            'remove' => array(
                'label' => $gLocale->getStr( 'removefield.button' ),
                'themeimage' => 'trash',
                'themeimagetype' => 'mini',
                'horiz' => 'true',
                'needconfirm' => 'true',
                'confirmmessage' => $gLocale->getStr( 'removefield.confirm' ),
                'action' => WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                    ),
                    array(
                        'action',
                        'removefield',
                        array(
                            'id' => $id,
                            'fieldtype' => InnoworkTicketField::TYPE_PRIORITY
                            ) ) ) )
        ) ) ) ).'</toolbars>
  </args>
</innomatictoolbar>';

            $row++;
        }

            $gXml_def .=
'          </children>
        </table>';

        $gXml_def .=
'        <table><name>types</name>
          <args>
            <headers type="array">'.WuiXml::encode( $headers ).'</headers>
          </args>
          <children>';

        $row = 0;
        $types = InnoworkTicketField::getFields( InnoworkTicketField::TYPE_TYPE );
        while ( list( $id, $field ) = each( $types ) )
        {
            $gXml_def .=
'<label row="'.$row.'" col="0"><name>field</name>
  <args>
    <label type="encoded">'.urlencode( $field ).'</label>
  </args>
</label>
<innomatictoolbar row="'.$row.'" col="1"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">'.WuiXml::encode( array(
        'view' => array(
            'show' => array(
                'label' => $gLocale->getStr( 'editfield.button' ),
                'themeimage' => 'pencil',
                'themeimagetype' => 'mini',
            	'horiz' => 'true',
                'action' => WuiEventsCall::buildEventsCallString( '', array( array(
                    'view',
                    'editfield',
                    array( 'id' => $id ) ) ) )
                ),
            'remove' => array(
                'label' => $gLocale->getStr( 'removefield.button' ),
                'themeimage' => 'trash',
                'themeimagetype' => 'mini',
                'horiz' => 'true',
                'needconfirm' => 'true',
                'confirmmessage' => $gLocale->getStr( 'removefield.confirm' ),
                'action' => WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                    ),
                    array(
                        'action',
                        'removefield',
                        array(
                            'id' => $id,
                            'fieldtype' => InnoworkTicketField::TYPE_TYPE
                            ) ) ) )
        ) ) ) ).'</toolbars>
  </args>
</innomatictoolbar>';

            $row++;
        }

            $gXml_def .=
'          </children>
        </table>';

        $gXml_def .=
'        <table><name>sources</name>
          <args>
            <headers type="array">'.WuiXml::encode( $headers ).'</headers>
          </args>
          <children>';

        $row = 0;
        $sources = InnoworkTicketField::getFields( InnoworkTicketField::TYPE_SOURCE );
        while ( list( $id, $field ) = each( $sources ) )
        {
            $gXml_def .=
'<label row="'.$row.'" col="0"><name>source</name>
  <args>
    <label type="encoded">'.urlencode( $field ).'</label>
  </args>
</label>
<innomatictoolbar row="'.$row.'" col="1"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">'.WuiXml::encode( array(
        'view' => array(
            'show' => array(
                'label' => $gLocale->getStr( 'editfield.button' ),
                'themeimage' => 'pencil',
                'themeimagetype' => 'mini',
            	'horiz' => 'true',
                'action' => WuiEventsCall::buildEventsCallString( '', array( array(
                    'view',
                    'editfield',
                    array( 'id' => $id ) ) ) )
                ),
            'remove' => array(
                'label' => $gLocale->getStr( 'removefield.button' ),
                'themeimage' => 'trash',
                'themeimagetype' => 'mini',
                'horiz' => 'true',
                'needconfirm' => 'true',
                'confirmmessage' => $gLocale->getStr( 'removefield.confirm' ),
                'action' => WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                    ),
                    array(
                        'action',
                        'removefield',
                        array(
                            'id' => $id,
                            'fieldtype' => InnoworkTicketField::TYPE_SOURCE
                            ) ) ) )
        ) ) ) ).'</toolbars>
  </args>
</innomatictoolbar>';

            $row++;
        }

            $gXml_def .=
'          </children>
        </table>';

        $gXml_def .=
'        <table><name>channels</name>
          <args>
            <headers type="array">'.WuiXml::encode( $headers ).'</headers>
          </args>
          <children>';

        $row = 0;
        $sources = InnoworkTicketField::getFields( InnoworkTicketField::TYPE_CHANNEL );
        while ( list( $id, $field ) = each( $sources ) )
        {
            $gXml_def .=
'<label row="'.$row.'" col="0"><name>channel</name>
  <args>
    <label type="encoded">'.urlencode( $field ).'</label>
  </args>
</label>
<innomatictoolbar row="'.$row.'" col="1"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">'.WuiXml::encode( array(
        'view' => array(
            'show' => array(
                'label' => $gLocale->getStr( 'editfield.button' ),
                'themeimage' => 'pencil',
                'themeimagetype' => 'mini',
            	'horiz' => 'true',
                'action' => WuiEventsCall::buildEventsCallString( '', array( array(
                    'view',
                    'editfield',
                    array( 'id' => $id ) ) ) )
                ),
            'remove' => array(
                'label' => $gLocale->getStr( 'removefield.button' ),
                'themeimage' => 'trash',
                'themeimagetype' => 'mini',
                'horiz' => 'true',
                'needconfirm' => 'true',
                'confirmmessage' => $gLocale->getStr( 'removefield.confirm' ),
                'action' => WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                    ),
                    array(
                        'action',
                        'removefield',
                        array(
                            'id' => $id,
                            'fieldtype' => InnoworkTicketField::TYPE_CHANNEL
                            ) ) ) )
        ) ) ) ).'</toolbars>
  </args>
</innomatictoolbar>';

            $row++;
        }

            $gXml_def .=
'          </children>
        </table>';

            $gXml_def .=
'      </children>
    </tab>
  </children>
</vertgroup>';
}

$gMain_disp->addEvent(
    'newfield',
    'main_newfield'
    );
function main_newfield( $eventData )
{
    global $gXml_def, $gLocale, $gPage_title;

    $field_types[InnoworkTicketField::TYPE_STATUS] = $gLocale->getStr( 'field_status.label' );
    $field_types[InnoworkTicketField::TYPE_PRIORITY] = $gLocale->getStr( 'field_priority.label' );
    $field_types[InnoworkTicketField::TYPE_TYPE] = $gLocale->getStr( 'field_type.label' );
    $field_types[InnoworkTicketField::TYPE_SOURCE] = $gLocale->getStr( 'field_source.label' );
    $field_types[InnoworkTicketField::TYPE_CHANNEL] = $gLocale->getStr( 'field_channel.label' );

    $gXml_def .=
'<vertgroup><name>newfield</name>
  <children>

    <table><name>field</name>
      <args>
        <headers type="array">'.WuiXml::encode(
            array( '0' => array(
                'label' => $gLocale->getStr( 'newfield.label' )
                ) ) ).'</headers>
      </args>
      <children>

    <form row="0" col="0"><name>field</name>
      <args>
        <method>post</method>
        <action type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    ''
                    ),
                array(
                    'action',
                    'addfield',
                    '' )
            ) ) ).'</action>
      </args>
      <children>

        <grid><name>field</name>
          <children>

            <label row="0" col="0"><name>type</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'fieldtype.label' ) ).'</label>
              </args>
            </label>

            <combobox row="0" col="1"><name>fieldtype</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $field_types ).'</elements>
              </args>
            </combobox>

            <label row="1" col="0"><name>value</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'fieldvalue.label' ) ).'</label>
              </args>
            </label>

            <string row="1" col="1"><name>value</name>
              <args>
                <disp>action</disp>
                <size>30</size>
              </args>
            </string>

          </children>
        </grid>

      </children>
    </form>

        <button row="1" col="0"><name>apply</name>
          <args>
            <themeimage>buttonok</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        'addfield',
                        '' )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->getStr( 'newfield.submit' ) ).'</label>
            <formsubmit>field</formsubmit>
          </args>
        </button>

      </children>
    </table>
  </children>
</vertgroup>';
}

$gMain_disp->addEvent(
    'editfield',
    'main_editfield'
    );
function main_editfield( $eventData )
{
    global $gXml_def, $gLocale, $gPage_title;

    $field_types[InnoworkTicketField::TYPE_STATUS] = $gLocale->getStr( 'field_status.label' );
    $field_types[InnoworkTicketField::TYPE_PRIORITY] = $gLocale->getStr( 'field_priority.label' );
    $field_types[InnoworkTicketField::TYPE_TYPE] = $gLocale->getStr( 'field_type.label' );
    $field_types[InnoworkTicketField::TYPE_SOURCE] = $gLocale->getStr( 'field_source.label' );
    $field_types[InnoworkTicketField::TYPE_CHANNEL] = $gLocale->getStr( 'field_channel.label' );

    $field = new InnoworkTicketField(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        '',
        $eventData['id']
        );

    $gXml_def .=
'<vertgroup><name>editfield</name>
  <children>

    <table><name>field</name>
      <args>
        <headers type="array">'.WuiXml::encode(
            array( '0' => array(
                'label' => $gLocale->getStr( 'editfield.label' )
                ) ) ).'</headers>
      </args>
      <children>

    <form row="0" col="0"><name>field</name>
      <args>
        <method>post</method>
        <action type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    ''
                    ),
                array(
                    'action',
                    'editfield',
                    array( 'id' => $eventData['id'] ) )
            ) ) ).'</action>
      </args>
      <children>

        <grid><name>field</name>
          <children>

            <label row="0" col="0"><name>type</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'fieldtype.label' ) ).'</label>
              </args>
            </label>

            <combobox row="0" col="1"><name>fieldtype</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $field_types ).'</elements>
                <default>'.$field->mFieldType.'</default>
              </args>
            </combobox>

            <label row="1" col="0"><name>value</name>
              <args>
                <label type="encoded">'.urlencode( $gLocale->getStr( 'fieldvalue.label' ) ).'</label>
              </args>
            </label>

            <string row="1" col="1"><name>value</name>
              <args>
                <disp>action</disp>
                <size>30</size>
                <value type="encoded">'.urlencode( $field->mFieldValue ).'</value>
              </args>
            </string>

          </children>
        </grid>

      </children>
    </form>

        <button row="1" col="0"><name>apply</name>
          <args>
            <themeimage>buttonok</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action type="encoded">'.urlencode( WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        'editfield',
                        array( 'id' => $eventData['id']  ) )
                ) ) ).'</action>
            <label type="encoded">'.urlencode( $gLocale->getStr( 'editfield.submit' ) ).'</label>
            <formsubmit>field</formsubmit>
          </args>
        </button>

      </children>
    </table>
  </children>
</vertgroup>';
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//
$gWui->addChild( new WuiInnomaticPage( 'page', array(
    'pagetitle' => $gPage_title,
    'icon' => 'settings1',
    'toolbars' => array(
        new WuiInnomaticToolbar(
            'view',
            array(
                'toolbars' => $gToolbars, 'toolbar' => 'true'
                ) ),
        new WuiInnomaticToolBar(
            'core',
            array(
                'toolbars' => $gCore_toolbars, 'toolbar' => 'true'
                ) ),
            ),
    'maincontent' => new WuiXml(
        'page', array(
            'definition' => $gXml_def
            ) ),
    'status' => $gPage_status
    ) ) );

$gWui->render();

?>

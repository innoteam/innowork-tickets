<?php
// ----- Initialization -----
//
require_once('innowork/tickets/InnoworkTicket.php');
require_once('innowork/projects/InnoworkProject.php');
require_once('innowork/projects/InnoworkProjectField.php');
require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/widgets/WuiWidget.php');
require_once('innomatic/wui/widgets/WuiContainerWidget.php');
require_once('innomatic/wui/dispatch/WuiEventsCall.php');
require_once('innomatic/wui/dispatch/WuiEvent.php');
require_once('innomatic/wui/dispatch/WuiEventRawData.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');
require_once('innomatic/locale/LocaleCatalog.php'); require_once('innomatic/locale/LocaleCountry.php');

    global $gLocale, $gPage_title, $gXml_def, $gPage_status, $gToolbars, $gInnowork_core, $customers;

function tickets_cdata($data)
{
    return '<![CDATA['.$data.']]>';
}

require_once('innowork/core/InnoworkCore.php');
$gInnowork_core = InnoworkCore::instance('innoworkcore',
    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    );

$gLocale = new LocaleCatalog(
    'innowork-tickets::innoworktickets_domain_main',
    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
    );

$gWui = Wui::instance('wui');
$gWui->loadWidget( 'xml' );
$gWui->loadWidget( 'innomaticpage' );
$gWui->loadWidget( 'innomatictoolbar' );
$gWui->loadWidget( 'table' );

// Companies

require_once('innowork/groupware/InnoworkCompany.php');
$innowork_companies = new InnoworkCompany(
    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    );
$search_results = $innowork_companies->Search(
    '',
    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()
    );
$customers[0] = $gLocale->getStr( 'nocustomer.label' );
while ( list( $id, $fields ) = each( $search_results ) ) {
    $customers[$id] = $fields['companyname'];
}

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr( 'tickets.title' );
$gCore_toolbars = $gInnowork_core->getMainToolBar();
$gToolbars['mail'] = array(
    'tickets' => array(
        'label' => $gLocale->getStr( 'tickets.toolbar' ),
        'themeimage' => 'listbulletleft',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            array( 'done' => 'false' ) ) ) )
        ),
    'donetickets' => array(
        'label' => $gLocale->getStr( 'donetickets.toolbar' ),
        'themeimage' => 'drawer',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            array( 'done' => 'true' ) ) ) )
        ),
    'newticket' => array(
        'label' => $gLocale->getStr( 'newticket.toolbar' ),
        'themeimage' => 'mathadd',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'newticket',
            '' ) ) )
        )
    );

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher( 'action' );

//$GLOBALS['innowork-billing']['newinvoiceid'] = $innowork_project->mItemId;

$gAction_disp->addEvent(
    'newticket',
    'action_newticket'
    );
function action_newticket(
    $eventData
    )
{
    global $gPage_status, $gLocale;

    $ticket = new InnoworkTicket(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );

    if ( $ticket->Create( $eventData ) ) {
        $GLOBALS['innowork-tickets']['newticketid'] = $ticket->mItemId;
        $gPage_status = $gLocale->getStr( 'ticket_created.status' );
    } else $gPage_status = $gLocale->getStr( 'ticket_not_created.status' );
}

$gAction_disp->addEvent(
    'editticket',
    'action_editticket'
    );
function action_editticket(
    $eventData
    )
{
    global $gPage_status, $gLocale;

    $ticket = new InnoworkTicket(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        $eventData['id']
        );

    if ( $ticket->Edit( $eventData ) ) $gPage_status = $gLocale->getStr( 'ticket_updated.status' );
    else $gPage_status = $gLocale->getStr( 'ticket_not_updated.status' );
}

$gAction_disp->addEvent(
    'trashticket',
    'action_trashticket'
    );
function action_trashticket(
    $eventData
    )
{
    global $gPage_status, $gLocale;

    $ticket = new InnoworkTicket(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        $eventData['id']
        );

    if ( $ticket->Trash( \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId() ) ) $gPage_status = $gLocale->getStr( 'ticket_trashed.status' );
    else $gPage_status = $gLocale->getStr( 'ticket_not_trashed.status' );
}

$gAction_disp->addEvent(
    'newmessage',
    'action_newmessage'
    );
function action_newmessage(
    $eventData
    )
{
    global $gPage_status, $gLocale;

    $ticket = new InnoworkTicket(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        $eventData['ticketid']
        );

    if ( $ticket->AddMessage(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName(),
            $eventData['content']
            ) )
    {
        $gPage_status = $gLocale->getStr( 'message_created.status' );
    } else $gPage_status = $gLocale->getStr( 'message_not_created.status' );
}

$gAction_disp->addEvent(
    'removemessage',
    'action_removemessage'
    );
function action_removemessage(
    $eventData
    )
{
    global $gPage_status, $gLocale;

    $ticket = new InnoworkTicket(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        $eventData['ticketid']
        );

    if ( $ticket->RemoveMessage( $eventData['messageid'] ) ) $gPage_status = $gLocale->getStr( 'message_removed.status' );
    else $gPage_status = $gLocale->getStr( 'message_not_removed.status' );
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher( 'view' );

function tickets_list_action_builder($pageNumber)
{
    return WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            array( 'pagenumber' => $pageNumber )
        ) ) );
}

$gMain_disp->addEvent(
    'default',
    'main_default'
    );
function main_default($eventData)
{
    global $gLocale, $gPage_title, $gXml_def, $gPage_status, $gToolbars, $gInnowork_core, $customers;

    require_once('shared/wui/WuiSessionkey.php');

    $innowork_projects = new InnoworkProject(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );
    $search_results = $innowork_projects->Search(
        '',
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()
        );

    $projects['0'] = $gLocale->getStr( 'allprojects.label' );
    while ( list( $id, $fields ) = each( $search_results ) ) {
        $projects[$id] = $fields['name'];
    }

    $customers_filter = $customer;
    $customers_filter[0] = $gLocale->getStr( 'allcustomers.label' );

    $statuses = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_STATUS );
    $statuses['0'] = $gLocale->getStr( 'allstatuses.label' );

    $priorities = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_PRIORITY );
    $priorities['0'] = $gLocale->getStr( 'allpriorities.label' );

    $sources = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_SOURCE );
    $sources['0'] = $gLocale->getStr( 'allsources.label' );

    $channels = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_CHANNEL );
    $channels['0'] = $gLocale->getStr( 'allchannels.label' );

    // Filtering

    if ( isset($eventData['filter'] ) ) {
        // Customer

        $customer_filter_sk = new WuiSessionKey(
            'customer_filter',
            array(
                'value' => $eventData['filter_customerid']
                )
            );

        if ( $eventData['filter_customerid'] != 0 ) $search_keys['customerid'] = $eventData['filter_customerid'];

        // Project

        $project_filter_sk = new WuiSessionKey(
            'project_filter',
            array(
                'value' => $eventData['filter_projectid']
                )
            );

        if ( $eventData['filter_projectid'] != 0 ) $search_keys['projectid'] = $eventData['filter_projectid'];
        
        // Priority

        $priority_filter_sk = new WuiSessionKey(
            'priority_filter',
            array(
                'value' => $eventData['filter_priorityid']
                )
            );

        if ( $eventData['filter_priorityid'] != 0 ) $search_keys['priorityid'] = $eventData['filter_priorityid'];

        // Status

        $status_filter_sk = new WuiSessionKey(
            'status_filter',
            array(
                'value' => $eventData['filter_statusid']
                )
            );

        if ( $eventData['filter_statusid'] != 0 ) $search_keys['statusid'] = $eventData['filter_statusid'];

        // Source

        $source_filter_sk = new WuiSessionKey(
            'source_filter',
            array(
                'value' => $eventData['filter_sourceid']
                )
            );

        if ( $eventData['filter_sourceid'] != 0 ) $search_keys['sourceid'] = $eventData['filter_sourceid'];

        // Channel

        $channel_filter_sk = new WuiSessionKey(
            'channel_filter',
            array(
                'value' => $eventData['filter_channelid']
                )
            );

        if ( $eventData['filter_channelid'] != 0 ) $search_keys['channelid'] = $eventData['filter_channelid'];

        // Year

        if ( isset($eventData['filter_year'] ) ) $_filter_year = $eventData['filter_year'];

        $year_filter_sk = new WuiSessionKey(
            'year_filter',
            array(
                'value' => isset($eventData['filter_year'] ) ? $eventData['filter_year'] : ''
                )
            );

        // Month

        if ( isset($eventData['filter_month'] ) ) $_filter_month = $eventData['filter_month'];

        $month_filter_sk = new WuiSessionKey(
            'month_filter',
            array(
                'value' => isset($eventData['filter_month'] ) ? $eventData['filter_month'] : ''
                )
            );

        // Day

        if ( isset($eventData['filter_day'] ) ) $_filter_day = $eventData['filter_day'];

        $day_filter_sk = new WuiSessionKey(
            'day_filter',
            array(
                'value' => isset($eventData['filter_day'] ) ? $eventData['filter_day'] : ''
                )
            );

        // Opened by
        $openedby_filter_sk = new WuiSessionKey('openedby_filter', array('value' => isset($eventData['filter_openedby']) ? $eventData['filter_openedby'] : ''));
        if ($eventData['filter_openedby'] != 0) {
        	$search_keys['openedby'] = $eventData['filter_openedby'];
        }
        
        // Assigned to
        $assignedto_filter_sk = new WuiSessionKey('assignedto_filter', array('value' => isset($eventData['filter_assignedto']) ? $eventData['filter_assignedto'] : ''));
        if ($eventData['filter_assignedto'] != 0) {
        	$search_keys['assignedto'] = $eventData['filter_assignedto'];
        }
    } else {
        // Customer

        $customer_filter_sk = new WuiSessionKey( 'customer_filter' );
        if (
            strlen( $customer_filter_sk->mValue )
            and $customer_filter_sk->mValue != 0
           ) $search_keys['customerid'] = $customer_filter_sk->mValue;
        $eventData['filter_customerid'] = $customer_filter_sk->mValue;

        // Project

        $project_filter_sk = new WuiSessionKey( 'project_filter' );
        if (
            strlen( $project_filter_sk->mValue )
            and $project_filter_sk->mValue != 0
           ) $search_keys['projectid'] = $project_filter_sk->mValue;
        $eventData['filter_projectid'] = $project_filter_sk->mValue;

        // Priority

        $priority_filter_sk = new WuiSessionKey( 'priority_filter' );
        if (
            strlen( $priority_filter_sk->mValue )
            and $priority_filter_sk->mValue != 0
           ) $search_keys['priorityid'] = $priority_filter_sk->mValue;
        $eventData['filter_priorityid'] = $priority_filter_sk->mValue;

        // Status

        $status_filter_sk = new WuiSessionKey( 'status_filter' );
        if (
            strlen( $status_filter_sk->mValue )
            and $status_filter_sk->mValue != 0
           ) $search_keys['statusid'] = $status_filter_sk->mValue;
        $eventData['filter_statusid'] = $status_filter_sk->mValue;

        // Source

        $source_filter_sk = new WuiSessionKey( 'source_filter' );
        if (
            strlen( $source_filter_sk->mValue )
            and $source_filter_sk->mValue != 0
           ) $search_keys['sourceid'] = $source_filter_sk->mValue;
        $eventData['filter_sourceid'] = $source_filter_sk->mValue;

        // Channel

        $channel_filter_sk = new WuiSessionKey( 'channel_filter' );
        if (
            strlen( $channel_filter_sk->mValue )
            and $channel_filter_sk->mValue != 0
           ) $search_keys['channelid'] = $channel_filter_sk->mValue;
        $eventData['filter_channelid'] = $channel_filter_sk->mValue;

        // Year

        $year_filter_sk = new WuiSessionKey( 'year_filter' );
        if ( strlen( $year_filter_sk->mValue ) and $year_filter_sk->mValue != 0 ) $_filter_year = $year_filter_sk->mValue;
        $eventData['filter_year'] = $year_filter_sk->mValue;

        // Month

        $month_filter_sk = new WuiSessionKey( 'month_filter' );
        if ( strlen( $month_filter_sk->mValue ) and $month_filter_sk->mValue != 0 ) $_filter_month = $month_filter_sk->mValue;
        $eventData['filter_month'] = $month_filter_sk->mValue;

        // Day

        $day_filter_sk = new WuiSessionKey( 'day_filter' );
        if ( strlen( $day_filter_sk->mValue ) and $day_filter_sk->mValue != 0 ) $_filter_day = $day_filter_sk->mValue;
        $eventData['filter_day'] = $day_filter_sk->mValue;

        // Opened by
        $openedby_filter_sk = new WuiSessionKey('openedby_filter');
        $eventData['filter_openedby'] = $openedby_filter_sk->mValue;

        // Assigned to
        $assignedto_filter_sk = new WuiSessionKey('assignedto_filter');
        $eventData['filter_assignedto'] = $assignedto_filter_sk->mValue;
    }

    if (
        isset($_filter_year )
        or
        isset($_filter_month )
        or
        isset($_filter_day )
       )
    {
        $search_keys['creationdate'] =
            ( ( isset($_filter_year ) and strlen( $_filter_year ) ) ? str_pad( $_filter_year, 4, '0', STR_PAD_LEFT ) : '%' ).'-'.
            ( ( isset($_filter_month ) and strlen( $_filter_month ) ) ? str_pad( $_filter_month, 2, '0', STR_PAD_LEFT ) : '%' ).'-'.
            ( ( isset($_filter_day ) and strlen( $_filter_day ) ) ? str_pad( $_filter_day, 2, '0', STR_PAD_LEFT ) : '%' );
    }

    $users_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute(
    		'SELECT id,fname,lname '.
    		'FROM domain_users '.
    		'WHERE username<>'.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText(User::getAdminUsername(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId())).' '.
    		'ORDER BY lname,fname' );
    
    $users = array();
    $users[''] = $gLocale->getStr('filter_allusers.label');
    
    while (!$users_query->eof) {
    	$users[$users_query->getFields('id')] = $users_query->getFields('lname').' '.$users_query->getFields('fname');
    	$users_query->moveNext();
    }
    
    if ( !isset($search_keys ) or !count( $search_keys ) ) $search_keys = '';

    // Sorting

    $tab_sess = new WuiSessionKey( 'innoworkticketstab' );

    if ( !isset($eventData['done'] ) ) $eventData['done'] = $tab_sess->mValue;
    if ( !strlen( $eventData['done'] ) ) $eventData['done'] = 'false';

    $tab_sess = new WuiSessionKey(
        'innoworkticketstab',
        array(
            'value' => $eventData['done']
            )
        );

    $country = new LocaleCountry(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry()
        );

    $summaries = $gInnowork_core->getSummaries();

    $table = new WuiTable( 'tickets_done_'.$eventData['done'], array(
        'sessionobjectusername' => $eventData['done'] == 'true' ? 'done' : 'undone'
        ) );
    $sort_by = 0;
    if ( strlen( $table->mSortDirection ) ) $sort_order = $table->mSortDirection;
    else $sort_order = 'down';

    if ( isset($eventData['sortby'] ) ) {
        if ( $table->mSortBy == $eventData['sortby'] ) {
            $sort_order = $sort_order == 'down' ? 'up' : 'down';
        } else {
            $sort_order = 'down';
        }

        $sort_by = $eventData['sortby'];
    } else {
        if ( strlen( $table->mSortBy ) ) $sort_by = $table->mSortBy;
    }

    $tickets = new InnoworkTicket(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );

    switch ( $sort_by ) {
    case '1':
        $tickets->mSearchOrderBy = 'id'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '2':
        $tickets->mSearchOrderBy = 'customerid'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '3':
        $tickets->mSearchOrderBy = 'title'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '4':
        $tickets->mSearchOrderBy = 'openedby'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '5':
        $tickets->mSearchOrderBy = 'assignedto'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '6':
        $tickets->mSearchOrderBy = 'priorityid'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '7':
        $tickets->mSearchOrderBy = 'statusid'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    case '8':
        $tickets->mSearchOrderBy = 'sourceid'.( $sort_order == 'up' ? ' DESC' : '' );
        break;
    }

        if (
            isset($eventData['done'] )
            and $eventData['done'] == 'true'
            )
        {
            $done_check = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmttrue;
            $done_icon = 'misc3';
            $done_action = 'false';
            $done_label = 'setundone.button';
        } else {
            $done_check = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse;
            $done_icon = 'drawer';
            $done_action = 'true';
            $done_label = 'setdone.button';
        }

    $search_keys['done'] = $done_check;

    $tickets_search = $tickets->Search(
        $search_keys,
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId(),
        false,
        false,
        0,
        0
        );

    $num_tickets = count( $tickets_search );

    $headers[0]['label'] = $gLocale->getStr( 'ticket.header' );
    $headers[0]['link'] = WuiEventsCall::buildEventsCallString( '',
            array( array(
                    'view',
                    'default',
                    array( 'sortby' => '1' )
                    ) ) );
    $headers[1]['label'] = $gLocale->getStr( 'customer.header' );
    $headers[1]['link'] = WuiEventsCall::buildEventsCallString( '',
            array( array(
                    'view',
                    'default',
                    array( 'sortby' => '2' )
                    ) ) );
    $headers[2]['label'] = $gLocale->getStr( 'title.header' );
    $headers[2]['link'] = WuiEventsCall::buildEventsCallString( '',
            array( array(
                    'view',
                    'default',
                    array( 'sortby' => '3' )
                    ) ) );
    $headers[3]['label'] = $gLocale->getStr('openedby.header');
    $headers[3]['link'] = WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('sortby' => '4'))));

    $headers[4]['label'] = $gLocale->getStr('assignedto.header');
    $headers[4]['link'] = WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('sortby' => '5'))));
    
    $headers[5]['label'] = $gLocale->getStr( 'priority.header' );
    $headers[5]['link'] = WuiEventsCall::buildEventsCallString( '',
            array( array(
                    'view',
                    'default',
                    array( 'sortby' => '6' )
                    ) ) );
    $headers[6]['label'] = $gLocale->getStr( 'status.header' );
    $headers[6]['link'] = WuiEventsCall::buildEventsCallString( '',
            array( array(
                    'view',
                    'default',
                    array( 'sortby' => '7' )
                    ) ) );
    $headers[7]['label'] = $gLocale->getStr( 'source.header' );
    $headers[7]['link'] = WuiEventsCall::buildEventsCallString( '',
            array( array(
                    'view',
                    'default',
                    array( 'sortby' => '8' )
                    ) ) );

    $gXml_def =
'
<vertgroup>
  <children>

    <label><name>filter</name>
      <args>
        <bold>true</bold>
        <label>'.$gLocale->getStr( 'filter.label' ).'</label>
      </args>
    </label>

    <form><name>filter</name>
      <args>
            <action>'.tickets_cdata( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    array(
                        'filter' => 'true'
                        )
                    )
            ) ) ).'</action>
      </args>
      <children>

        <grid>
          <children>

    <label row="0" col="0">
      <args>
        <label>'.$gLocale->getStr( 'filter_date.label' ).'</label>
      </args>
    </label>
    <horizgroup row="0" col="1">
      <children>

    <string><name>filter_day</name>
      <args>
        <disp>view</disp>
        <size>2</size>
        <value>'.( isset($eventData['filter_day'] ) ? $eventData['filter_day'] : '' ).'</value>
      </args>
    </string>

    <string row="0" col="1"><name>filter_month</name>
      <args>
        <disp>view</disp>
        <size>2</size>
        <value>'.( isset($eventData['filter_month'] ) ? $eventData['filter_month'] : '' ).'</value>
      </args>
    </string>

    <string row="0" col="1"><name>filter_year</name>
      <args>
        <disp>view</disp>
        <size>4</size>
        <value>'.( isset($eventData['filter_year'] ) ? $eventData['filter_year'] : '' ).'</value>
      </args>
    </string>

      </children>
    </horizgroup>

        <button row="0" col="4"><name>filter</name>
          <args>
            <themeimage>zoom</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <formsubmit>filter</formsubmit>
            <label>'.$gLocale->getStr( 'filter.button' ).'</label>
            <action>'.tickets_cdata( WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    array(
                        'filter' => 'true'
                        )
                    )
            ) ) ).'</action>
          </args>
        </button>

    <label row="1" col="0"><name>customer</name>
      <args>
        <label>'.$gLocale->getStr( 'filter_customer.label' ).'</label>
      </args>
    </label>
    <combobox row="1" col="1"><name>filter_customerid</name>
      <args>
        <disp>view</disp>
        <elements type="array">'.WuiXml::encode( $customers_filter ).'</elements>
        <default>'.( isset($eventData['filter_customerid'] ) ? $eventData['filter_customerid'] : '' ).'</default>
      </args>
    </combobox>

    <label row="2" col="0"><name>project</name>
      <args>
        <label>'.$gLocale->getStr( 'filter_project.label' ).'</label>
      </args>
    </label>
    <combobox row="2" col="1"><name>filter_projectid</name>
      <args>
        <disp>view</disp>
        <elements type="array">'.WuiXml::encode( $projects ).'</elements>
        <default>'.( isset($eventData['filter_projectid'] ) ? $eventData['filter_projectid'] : '' ).'</default>
      </args>
    </combobox>

        <label row="3" col="0">
          <args>
            <label>'.$gLocale->getStr('openedby.label').'</label>
          </args>
        </label>

        <combobox row="3" col="1"><name>filter_openedby</name>
          <args>
            <disp>view</disp>
            <elements type="array">'.WuiXml::encode($users).'</elements>
            <default>'.$eventData['filter_openedby'].'</default>
          </args>
        </combobox>

        <label row="4" col="0">
          <args>
            <label>'.$gLocale->getStr('assignedto.label').'</label>
          </args>
        </label>

        <combobox row="4" col="1"><name>filter_assignedto</name>
          <args>
            <disp>view</disp>
            <elements type="array">'.WuiXml::encode($users).'</elements>
            <default>'.$eventData['filter_assignedto'].'</default>
          </args>
        </combobox>
            		
    <label row="0" col="2">
      <args>
        <label>'.$gLocale->getStr( 'filter_priority.label' ).'</label>
      </args>
    </label>
    <combobox row="0" col="3"><name>filter_priorityid</name>
      <args>
        <disp>view</disp>
        <elements type="array">'.WuiXml::encode( $priorities ).'</elements>
        <default>'.( isset($eventData['filter_priorityid'] ) ? $eventData['filter_priorityid'] : '' ).'</default>
      </args>
    </combobox>

    <label row="1" col="2">
      <args>
        <label>'.$gLocale->getStr( 'filter_status.label' ).'</label>
      </args>
    </label>
    <combobox row="1" col="3"><name>filter_statusid</name>
      <args>
        <disp>view</disp>
        <elements type="array">'.WuiXml::encode( $statuses ).'</elements>
        <default>'.( isset($eventData['filter_statusid'] ) ? $eventData['filter_statusid'] : '' ).'</default>
      </args>
    </combobox>

    <label row="2" col="2">
      <args>
        <label>'.$gLocale->getStr( 'filter_source.label' ).'</label>
      </args>
    </label>
    <combobox row="2" col="3"><name>filter_sourceid</name>
      <args>
        <disp>view</disp>
        <elements type="array">'.WuiXml::encode( $sources ).'</elements>
        <default>'.( isset($eventData['filter_sourceid'] ) ? $eventData['filter_sourceid'] : '' ).'</default>
      </args>
    </combobox>

    <label row="3" col="2">
      <args>
        <label>'.$gLocale->getStr( 'filter_channel.label' ).'</label>
      </args>
    </label>
    <combobox row="3" col="3"><name>filter_channelid</name>
      <args>
        <disp>view</disp>
        <elements type="array">'.WuiXml::encode( $channels ).'</elements>
        <default>'.( isset($eventData['filter_channelid'] ) ? $eventData['filter_channelid'] : '' ).'</default>
      </args>
    </combobox>

          </children>
        </grid>

      </children>
    </form>

    <horizbar/>

    <label><name>title</name>
      <args>
        <bold>true</bold>
        <label>'.( $gLocale->getStr(
            ( isset($eventData['done'] )
            and $eventData['done'] == 'true' ) ? 'donetickets.label' : 'tickets.label' ) ).'</label>
      </args>
    </label>

    <table><name>tickets_done_'.$eventData['done'].'</name>
      <args>
        <headers type="array">'.WuiXml::encode( $headers ).'</headers>
        <rowsperpage>15</rowsperpage>
        <pagesactionfunction>tickets_list_action_builder</pagesactionfunction>
        <pagenumber>'.( isset($eventData['pagenumber'] ) ? $eventData['pagenumber'] : '' ).'</pagenumber>
        <sessionobjectusername>'.( $eventData['done'] == 'true' ? 'done' : 'undone' ).'</sessionobjectusername>
        <sortby>'.$sort_by.'</sortby>
        <sortdirection>'.$sort_order.'</sortdirection>
        <rows>'.$num_tickets.'</rows>
      </args>
      <children>';

    $row = 0;

    $statuses = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_STATUS );
    $statuses['0'] = $gLocale->getStr( 'nostatus.label' );

    $priorities = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_PRIORITY );
    $priorities['0'] = $gLocale->getStr( 'nopriority.label' );

    $sources = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_SOURCE );
    $sources['0'] = $gLocale->getStr( 'nosource.label' );

    $channels = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_CHANNEL );
    $channels['0'] = $gLocale->getStr( 'nochannel.label' );

    $page = 1;

                    if ( isset($eventData['pagenumber'] ) ) {
                        $page = $eventData['pagenumber'];
                    } else {
                        require_once('shared/wui/WuiTable.php');

                        $table = new WuiTable(
                            'tickets_done_'.$eventData['done'],
                            array(
                                'sessionobjectusername' => $eventData['done'] == 'true' ? 'done' : 'undone'
                                )
                            );

                        $page = $table->mPageNumber;
                    }

                    if ( $page > ceil( $num_tickets / 15 ) ) $page = ceil( $num_tickets /15 );

                    $from = ( $page * 15 ) - 15;
                    $to = $from + 15 - 1;

    foreach ( $tickets_search as $ticket ) {
        if ( $row >= $from and $row <= $to ) {
        if ( $ticket['done'] == $done_check ) {
                switch ( $ticket['_acl']['type'] ) {
                case InnoworkAcl::TYPE_PRIVATE:
                    $image = 'personal';
                    break;

                case InnoworkAcl::TYPE_PUBLIC:
                case InnoworkAcl::TYPE_ACL:
                    $image = 'kuser';
                    break;
                }

            $tmp_customer = new InnoworkCompany(
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
                $ticket['customerid']
                );

            $tmp_customer_data = $tmp_customer->getItem();

            $tmp_project = new InnoworkProject(
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
                $ticket['projectid']
                );

            $tmp_project_data = $tmp_project->getItem();

            $users[''] = $gLocale->getStr('noone.label');
            $users[0] = $gLocale->getStr('noone.label');
            
            $gXml_def .=
'<horizgroup row="'.$row.'" col="0">
  <args>
  </args>
  <children>
    <link>
      <args>
        <label>'.tickets_cdata($ticket['id'].
            ' - '.
            $country->FormatShortArrayDate(
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->getDateArrayFromTimestamp(
                    $ticket['creationdate'] )
                ).' '.$country->FormatArrayTime(
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->getDateArrayFromTimestamp(
                    $ticket['creationdate']
                    )
                )
            ).'</label>
        <compact>true</compact>
        <link>'.tickets_cdata(
            WuiEventsCall::buildEventsCallString(
                '',
                array(
                    array(
                        'view',
                        'showticket',
                        array(
                            'id' => $ticket['id']
                            )
                        )
                    )
                )
            ).'</link>
        </args>
    </link>
  </children>
</horizgroup>
<vertgroup row="'.$row.'" col="1" halign="" valign="top">
  <children>

<link><name>customer</name>
  <args>
    <link>'.tickets_cdata( WuiEventsCall::buildEventsCallString(
        $summaries['directorycompany']['domainpanel'],
        array(
            array(
                $summaries['directorycompany']['showdispatcher'],
                $summaries['directorycompany']['showevent'],
                array( 'id' => $ticket['customerid'] )
                )
            )
        ) ).'</link>
    <label>'.tickets_cdata( '<strong>'.$tmp_customer_data['companyname'].'</strong>' ).'</label>
    <compact>true</compact>
  </args>
</link>

<link><name>project</name>
  <args>
    <link>'.tickets_cdata( WuiEventsCall::buildEventsCallString(
        $summaries['project']['domainpanel'],
        array(
            array(
                $summaries['project']['showdispatcher'],
                $summaries['project']['showevent'],
                array( 'id' => $ticket['projectid'] )
                )
            )
        ) ).'</link>
    <label>'.tickets_cdata( $tmp_project_data['name'] ).'</label>
    <compact>true</compact>
    <nowrap>false</nowrap>
  </args>
</link>

  </children>
</vertgroup>
<label row="'.$row.'" col="2">
  <args>
    <label>'.tickets_cdata( $ticket['title'] ).'</label>
    <nowrap>false</nowrap>
  </args>
</label>
<label row="'.$row.'" col="3">
  <args>
    <label>'.tickets_cdata($users[$ticket['openedby']]).'</label>
    <nowrap>false</nowrap>
  </args>
</label>
<label row="'.$row.'" col="4">
  <args>
    <label>'.tickets_cdata($users[$ticket['assignedto']]).'</label>
    <nowrap>false</nowrap>
  </args>
</label>
<label row="'.$row.'" col="5">
  <args>
    <label>'.tickets_cdata( $priorities[$ticket['priorityid']] ).'</label>
    <nowrap>false</nowrap>
  </args>
</label>
<label row="'.$row.'" col="6">
  <args>
    <label>'.tickets_cdata( $statuses[$ticket['statusid']] ).'</label>
    <nowrap>false</nowrap>
  </args>
</label>
<label row="'.$row.'" col="7">
  <args>
    <label>'.tickets_cdata( $sources[$ticket['sourceid']] ).'</label>
    <nowrap>false</nowrap>
  </args>
</label>
<innomatictoolbar row="'.$row.'" col="8"><name>tools</name>
  <args>
    <frame>false</frame>
    <toolbars type="array">'.WuiXml::encode( array(
        'view' => array(
            'show' => array(
                'label' => $gLocale->getStr( 'showticket.button' ),
                'themeimage' => 'zoom',
                'horiz' => 'true',
                'action' => WuiEventsCall::buildEventsCallString( '', array( array(
                    'view',
                    'showticket',
                    array( 'id' => $ticket['id'] ) ) ) )
                ),
            'done' => array(
                'label' => $gLocale->getStr( $done_label ),
                'themeimage' => $done_icon,
                'horiz' => 'true',
                'action' => WuiEventsCall::buildEventsCallString( '', array(
                array(
                    'view',
                    'default',
                    ''
                ),
                array(
                    'action',
                    'editticket',
                    array( 'id' => $ticket['id'], 'done' => $done_action ) ) ) )
                ),
            'trash' => array(
                'label' => $gLocale->getStr( 'trashticket.button' ),
                'themeimage' => 'trash',
                'horiz' => 'true',
                'action' => WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                    ),
                    array(
                        'action',
                        'trashticket',
                        array( 'id' => $ticket['id'] ) ) ) )
        ) ) ) ).'</toolbars>
  </args>
</innomatictoolbar>';

        }
        }
        $row++;
    }

    $gXml_def .=
'      </children>
    </table>

  </children>
</vertgroup>';
}

$gMain_disp->addEvent(
    'newticket',
    'main_newticket'
    );
function main_newticket(
    $eventData
    )
{
    global $gXml_def, $gLocale, $customers;

    $headers[0]['label'] = $gLocale->getStr( 'newticket.header' );

    $gXml_def =
'
<vertgroup>
  <children>

    <table>
      <args>
        <headers type="array">'.WuiXml::encode( $headers ).'</headers>
      </args>
      <children>

        <form row="0" col="0"><name>newticket</name>
          <args>
                <action>'.tickets_cdata(
                    WuiEventsCall::buildEventsCallString(
                        '',
                        array(
                            array(
                                'view',
                                'showticket'
                                ),
                            array(
                                'action',
                                'newticket'
                                )
                            )
                        )
                    ).'</action>
          </args>
          <children>
            <grid>
              <children>

                <label row="0" col="0">
                  <args>
                    <label>'.$gLocale->getStr( 'customer.label' ).'</label>
                  </args>
                </label>

                <combobox row="0" col="1"><name>customerid</name>
                  <args>
                    <disp>action</disp>
                    <elements type="array">'.WuiXml::encode( $customers ).'</elements>
                  </args>
                </combobox>

              </children>
            </grid>
          </children>
        </form>

        <horizgroup row="1" col="0">
          <children>

            <button>
              <args>
                <themeimage>buttonok</themeimage>
                <label>'.$gLocale->getStr( 'new_ticket.button' ).'</label>
                <formsubmit>newticket</formsubmit>
                <frame>false</frame>
                <horiz>true</horiz>
                <action>'.tickets_cdata(
                    WuiEventsCall::buildEventsCallString(
                        '',
                        array(
                            array(
                                'view',
                                'showticket'
                                ),
                            array(
                                'action',
                                'newticket'
                                )
                            )
                        )
                    ).'</action>
              </args>
            </button>

          </children>
        </horizgroup>

      </children>
    </table>

  </children>
</vertgroup>';
}

$gMain_disp->addEvent(
    'showticket',
    'main_showticket'
    );
function main_showticket(
    $eventData
    )
{
    global $gXml_def, $gLocale, $customers;

    $locale_country = new LocaleCountry(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getCountry()
        );

    if ( isset($GLOBALS['innowork-tickets']['newticketid'] ) ) {
        $eventData['id'] = $GLOBALS['innowork-tickets']['newticketid'];
    }

    $innowork_ticket = new InnoworkTicket(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        $eventData['id']
        );

    $ticket_data = $innowork_ticket->getItem( \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId() );

    // Projects list

    $innowork_projects = new InnoworkProject(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );
    $search_results = $innowork_projects->Search(
        array( 'customerid' => $ticket_data['customerid'] ),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()
        );

    $projects['0'] = $gLocale->getStr( 'noproject.label' );

    while ( list( $id, $fields ) = each( $search_results ) ) {
        $projects[$id] = $fields['name'];
    }

    // "Assigned to" user
    if ($ticket_data['assignedto'] != '') {
    	$assignedto_user = $ticket_data['assignedto'];
    } else {
    	$assignedto_user = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId();
    }

    // "Opened by" user
    if ($ticket_data['openedby'] != '') {
    	$openedby_user = $ticket_data['openedby'];
    } else {
    	$openedby_user = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId();
    }

    $users_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute(
    		'SELECT id,fname,lname '.
    		'FROM domain_users '.
    		'WHERE username<>'.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText(User::getAdminUsername(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId())).' '.
    		'ORDER BY lname,fname' );
    
    $users = array();
    $users[0] = $gLocale->getStr('noone.label');
    
    while (!$users_query->eof) {
    	$users[$users_query->getFields('id')] = $users_query->getFields('lname').' '.$users_query->getFields('fname');
    	$users_query->moveNext();
    }
    
    $statuses = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_STATUS );
    $statuses['0'] = $gLocale->getStr( 'nostatus.label' );

    $priorities = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_PRIORITY );
    $priorities['0'] = $gLocale->getStr( 'nopriority.label' );

    $sources = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_SOURCE );
    $sources['0'] = $gLocale->getStr( 'nosource.label' );

    $channels = InnoworkProjectField::getFields( INNOWORKPROJECTS_FIELDTYPE_CHANNEL );
    $channels['0'] = $gLocale->getStr( 'nochannel.label' );

        if ( $ticket_data['done'] == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmttrue ) {
            $done_icon = 'misc3';
            $done_action = 'false';
            $done_label = 'setundone.button';
        } else {
            $done_icon = 'drawer';
            $done_action = 'true';
            $done_label = 'setdone.button';
        }

    $headers[0]['label'] = sprintf( $gLocale->getStr( 'showticket.header' ), $ticket_data['id'] );

    $gXml_def =
'
<horizgroup>
  <children>

    <table><name>ticket</name>
      <args>
        <headers type="array">'.WuiXml::encode( $headers ).'</headers>
      </args>
      <children>

        <form row="0" col="0"><name>ticket</name>
          <args>
                <action>'.tickets_cdata(
                    WuiEventsCall::buildEventsCallString(
                        '',
                        array(
                            array(
                                'view',
                                'showticket',
                                array(
                                    'id' => $eventData['id']
                                    )
                                ),
                            array(
                                'action',
                                'editticket',
                                array(
                                    'id' => $eventData['id']
                                    )
                                )
                            )
                        )
                    ).'</action>
          </args>
          <children>

            <vertgroup>
              <children>

                <horizgroup>
                  <args>
                    <align>middle</align>
                    <width>0%</width>
                  </args>
                  <children>

                    <label>
                      <args>
                        <label>'.$gLocale->getStr( 'customer.label' ).'</label>
                      </args>
                    </label>

                    <combobox><name>customerid</name>
                      <args>
                        <disp>action</disp>
                        <elements type="array">'.WuiXml::encode( $customers ).'</elements>
                        <default>'.$ticket_data['customerid'].'</default>
                      </args>
                    </combobox>

                    <label>
                      <args>
                        <label>'.$gLocale->getStr( 'project.label' ).'</label>
                      </args>
                    </label>

                    <combobox><name>projectid</name>
                      <args>
                        <disp>action</disp>
                        <elements type="array">'.WuiXml::encode( $projects ).'</elements>
                        <default>'.$ticket_data['projectid'].'</default>
                      </args>
                    </combobox>

                  </children>
                </horizgroup>
                        		
                <horizgroup><args><width>0%</width></args><children>

            <label><name>openedby</name>
              <args>
                <label>'.WuiXml::cdata($gLocale->getStr('openedby.label')).'</label>
              </args>
            </label>
            <combobox><name>openedby</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode($users).'</elements>
        		<default>'.$openedby_user.'</default>
              </args>
            </combobox>
                		
            <label><name>assignedto</name>
              <args>
                <label>'.WuiXml::cdata($gLocale->getStr('assignedto.label')).'</label>
              </args>
            </label>
            <combobox><name>assignedto</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode($users).'</elements>
        		<default>'.$assignedto_user.'</default>
              </args>
            </combobox>
                		
                </children></horizgroup>

                <horizgroup>
                  <args>
                    <align>middle</align>
                    <width>0%</width>
                  </args>
                  <children>

                    <label>
                      <args>
                        <label>'.$gLocale->getStr( 'status.label' ).'</label>
                      </args>
                    </label>

                    <combobox><name>statusid</name>
                      <args>
                        <disp>action</disp>
                        <elements type="array">'.WuiXml::encode( $statuses ).'</elements>
                        <default>'.$ticket_data['statusid'].'</default>
                      </args>
                    </combobox>

                    <label>
                      <args>
                        <label>'.$gLocale->getStr( 'priority.label' ).'</label>
                      </args>
                    </label>

                    <combobox><name>priorityid</name>
                      <args>
                        <disp>action</disp>
                        <elements type="array">'.WuiXml::encode( $priorities ).'</elements>
                        <default>'.$ticket_data['priorityid'].'</default>
                      </args>
                    </combobox>

                    <label>
                      <args>
                        <label>'.$gLocale->getStr( 'source.label' ).'</label>
                      </args>
                    </label>

                    <combobox><name>sourceid</name>
                      <args>
                        <disp>action</disp>
                        <elements type="array">'.WuiXml::encode( $sources ).'</elements>
                        <default>'.$ticket_data['sourceid'].'</default>
                      </args>
                    </combobox>

                    <label>
                      <args>
                        <label>'.$gLocale->getStr( 'channel.label' ).'</label>
                      </args>
                    </label>

                    <combobox><name>channelid</name>
                      <args>
                        <disp>action</disp>
                        <elements type="array">'.WuiXml::encode( $channels ).'</elements>
                        <default>'.$ticket_data['channelid'].'</default>
                      </args>
                    </combobox>

                  </children>
                </horizgroup>

                <horizbar/>

                <horizgroup><args><width>0%</width></args>
                  <children>

                    <label>
                      <args>
                        <label>'.$gLocale->getStr( 'title.label' ).'</label>
                      </args>
                    </label>

                    <string><name>title</name>
                      <args>
                        <disp>action</disp>
                        <size>50</size>
                        <value>'.tickets_cdata( $ticket_data['title'] ).'</value>
                      </args>
                    </string>

                  </children>
                </horizgroup>

                <label>
                  <args>
                    <label>'.$gLocale->getStr( 'description.label' ).'</label>
                  </args>
                </label>

                <text><name>description</name>
                  <args>
                    <disp>action</disp>
                    <rows>5</rows>
                    <cols>60</cols>
                    <value>'.tickets_cdata( $ticket_data['description'] ).'</value>
                  </args>
                </text>

                <label>
                  <args>
                    <label>'.$gLocale->getStr( 'solution.label' ).'</label>
                  </args>
                </label>

                <text><name>solution</name>
                  <args>
                    <disp>action</disp>
                    <rows>5</rows>
                    <cols>60</cols>
                    <value>'.tickets_cdata( $ticket_data['solution'] ).'</value>
                  </args>
                </text>

              </children>
            </vertgroup>

          </children>
        </form>

        <horizgroup row="1" col="0">
          <children>
            <button>
              <args>
                <themeimage>buttonok</themeimage>
                <label>'.tickets_cdata( $gLocale->getStr( 'update_ticket.button' ) ).'</label>
                <formsubmit>ticket</formsubmit>
                <frame>false</frame>
                <horiz>true</horiz>
                <action>'.tickets_cdata(
                    WuiEventsCall::buildEventsCallString(
                        '',
                        array(
                            array(
                                'view',
                                'showticket',
                                array(
                                    'id' => $eventData['id']
                                    )
                                ),
                            array(
                                'action',
                                'editticket',
                                array(
                                    'id' => $eventData['id']
                                    )
                                )
                            )
                        )
                    ).'</action>
              </args>
            </button>

            <button>
              <args>
                <themeimage>attach</themeimage>
                <label>'.$gLocale->getStr( 'add_message.button' ).'</label>
                <frame>false</frame>
                <horiz>true</horiz>
                <target>messages</target>
                <action>'.tickets_cdata(
                    WuiEventsCall::buildEventsCallString(
                        '',
                        array(
                            array(
                                'view',
                                'addmessage',
                                array(
                                    'ticketid' => $eventData['id']
                                    )
                                )
                            )
                        )
                    ).'</action>
              </args>
            </button>

        <button><name>setdone</name>
          <args>
            <themeimage>'.$done_icon.'</themeimage>
            <horiz>true</horiz>
            <frame>false</frame>
            <action>'.tickets_cdata( WuiEventsCall::buildEventsCallString( '', array(
                    array(
                        'view',
                        'default',
                        ''
                        ),
                    array(
                        'action',
                        'editticket',
                        array(
                            'id' => $eventData['id'],
                            'done' => $done_action
                            ) )
                ) ) ).'</action>
            <label>'.$gLocale->getStr( $done_label ).'</label>
            <formsubmit>ticket</formsubmit>
          </args>
        </button>

            <button>
              <args>
                <themeimage>trash</themeimage>
                <label>'.$gLocale->getStr( 'trash_ticket.button' ).'</label>
                <frame>false</frame>
                <horiz>true</horiz>
                <action>'.tickets_cdata(
                    WuiEventsCall::buildEventsCallString(
                        '',
                        array(
                            array(
                                'view',
                                'default'
                                ),
                            array(
                                'action',
                                'trashticket',
                                array(
                                    'id' => $eventData['id']
                                    )
                                )
                            )
                        )
                    ).'</action>
              </args>
            </button>

          </children>
        </horizgroup>

        <iframe row="2" col="0"><name>messages</name>
          <args>
            <width>450</width>
            <height>200</height>
            <source>'.tickets_cdata(
                WuiEventsCall::buildEventsCallString(
                    '',
                    array(
                        array(
                            'view',
                            'ticketmessages',
                            array(
                                'ticketid' => $eventData['id']
                                )
                            )
                        )
                    )
                ).'</source>
            <scrolling>auto</scrolling>
          </args>
        </iframe>

      </children>
    </table>

  <innoworkitemacl><name>itemacl</name>
    <args>
      <itemtype>ticket</itemtype>
      <itemid>'.$eventData['id'].'</itemid>
      <itemownerid>'.$ticket_data['ownerid'].'</itemownerid>
      <defaultaction>'.tickets_cdata( WuiEventsCall::buildEventsCallString( '', array(
        array( 'view', 'showticket', array( 'id' => $eventData['id'] ) ) ) ) ).'</defaultaction>
    </args>
  </innoworkitemacl>

  </children>
</horizgroup>';
}

$gMain_disp->addEvent(
    'ticketmessages',
    'main_ticketmessages'
    );
function main_ticketmessages(
    $eventData
    )
{
    global $gXml_def, $gLocale;

    $innowork_ticket = new InnoworkTicket(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        $eventData['ticketid']
        );

    $messages = $innowork_ticket->getMessages();

    $headers[0]['label'] = $gLocale->getStr( 'date.header' );
    $headers[1]['label'] = $gLocale->getStr( 'message.header' );

    $gXml_def =
'
<page>
  <args>
    <border>false</border>
  </args>
  <children>
<table><name>ticketmessages</name>
  <args>
    <headers type="array">'.WuiXml::encode( $headers ).'</headers>
  </args>
  <children>';

    $row = 0;

    $country = new LocaleCountry(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry()
        );

    foreach ( $messages as $message ) {
        $gXml_def .=
'<vertgroup row="'.$row.'" col="0" halign="" valign="top">
  <args>
  </args>
  <children>
    <label>
      <args>
        <label>'.tickets_cdata(
            $country->FormatShortArrayDate( $message['creationdate'] )
            ).'</label>
        <compact>true</compact>
      </args>
    </label>
    <label>
      <args>
        <label>'.tickets_cdata(
            $country->FormatArrayTime( $message['creationdate'] )
            ).'</label>
        <compact>true</compact>
      </args>
    </label>
    <label>
      <args>
        <label>'.tickets_cdata( '('.$message['username'].')' ).'</label>
        <compact>true</compact>
      </args>
    </label>
  </children>
</vertgroup>
<vertgroup row="'.$row.'" col="1" halign="" valign="top">
  <children>
<label>
  <args>
    <label>'.tickets_cdata( nl2br( $message['content'] ) ).'</label>
    <nowrap>false</nowrap>
  </args>
</label>

  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>buttoncancel</themeimage>
      <themeimagetype>mini</themeimagetype>
      <label>'.$gLocale->getStr( 'remove_message.button' ).'</label>
      <needconfirm>true</needconfirm>
      <confirmmessage>'.$gLocale->getStr( 'remove_message.confirm' ).'</confirmmessage>
      <action>'.tickets_cdata(
                WuiEventsCall::buildEventsCallString(
                    '',
                    array(
                        array(
                            'view',
                            'ticketmessages',
                            array(
                                'ticketid' => $eventData['ticketid']
                                )
                            ),
                        array(
                            'action',
                            'removemessage',
                            array(
                                'ticketid' => $eventData['id'],
                                'messageid' => $message['id']
                                )
                            )
                        )
                    )
                ).'</action>
    </args>
  </button>

  </children>
</vertgroup>';
        $row++;
    }

    $gXml_def .=
'  </children>
</table>
  </children>
</page>';

    $wui = new WuiXml( '', array( 'definition' => $gXml_def ) );
    $wui->Build(new WuiDispatcher('wui'));
    echo $wui->render();

    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->halt();
}

$gMain_disp->addEvent(
    'addmessage',
    'main_addmessage'
    );
function main_addmessage(
    $eventData
    )
{
    global $gXml_def, $gLocale;

    $innowork_ticket = new InnoworkTicket(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        $eventData['ticketid']
        );

    $headers[0]['label'] = $gLocale->getStr( 'message.header' );

    $gXml_def =
'
<page>
  <args>
    <border>false</border>
  </args>
  <children>
<table><name>message</name>
  <args>
    <headers type="array">'.WuiXml::encode( $headers ).'</headers>
  </args>
  <children>
    <form row="0" col="0"><name>message</name>
      <args>
                <action>'.tickets_cdata(
                    WuiEventsCall::buildEventsCallString(
                        '',
                        array(
                            array(
                                'view',
                                'ticketmessages',
                                array(
                                    'ticketid' => $eventData['ticketid']
                                    )
                                ),
                            array(
                                'action',
                                'newmessage',
                                array(
                                    'ticketid' => $eventData['ticketid']
                                    )
                                )
                            )
                        )
                    ).'</action>
      </args>
      <children>

        <text><name>content</name>
          <args>
            <disp>action</disp>
            <rows>5</rows>
            <cols>55</cols>
          </args>
        </text>

      </children>
    </form>

        <horizgroup row="1" col="0">
          <children>

            <button>
              <args>
                <themeimage>buttonok</themeimage>
                <label>'.$gLocale->getStr( 'add_message.button' ).'</label>
                <formsubmit>message</formsubmit>
                <frame>false</frame>
                <horiz>true</horiz>
                <action>'.tickets_cdata(
                    WuiEventsCall::buildEventsCallString(
                        '',
                        array(
                            array(
                                'view',
                                'ticketmessages',
                                array(
                                    'ticketid' => $eventData['ticketid']
                                    )
                                ),
                            array(
                                'action',
                                'newmessage',
                                array(
                                    'ticketid' => $eventData['ticketid']
                                    )
                                )
                            )
                        )
                    ).'</action>
              </args>
            </button>

          </children>
        </horizgroup>

  </children>
</table>
  </children>
</page>';

    $wui = new WuiXml( '', array( 'definition' => $gXml_def ) );
    $wui->Build(new WuiDispatcher('wui'));
    echo $wui->render();

    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->halt();
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//
$gWui->addChild( new WuiInnomaticPage( 'page', array(
    'pagetitle' => $gPage_title,
    'icon' => 'folder',
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
                ) )
            ),
    'maincontent' => new WuiXml(
        'page', array(
            'definition' => $gXml_def
            ) ),
    'status' => $gPage_status
    ) ) );

$gWui->render();

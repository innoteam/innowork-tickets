<?php

require_once('innowork/core/InnoworkItem.php');

class InnoworkTicket extends InnoworkItem
{
    public $mTable = 'innowork_tickets';
    public $mNewDispatcher = 'view';
    public $mNewEvent = 'newticket';
    public $mNoTrash = false;
    public $mConvertible = true;
    public $mNoAcl = true;
    public $mTags = array('task');
    const ITEM_TYPE = 'ticket';

    //var $mNoAcl = true;
    //var $mNoLog = true;
    //var $_mCreationAcl = InnoworkAcl::TYPE_PRIVATE;

    public function __construct($rrootDb, $rdomainDA, $mailId = 0)
    {
        parent::__construct($rrootDb, $rdomainDA, InnoworkTicket::ITEM_TYPE, $mailId);

        $this->mKeys['title'] = 'text';
        $this->mKeys['description'] = 'text';
        $this->mKeys['solution'] = 'text';
        $this->mKeys['projectid'] = 'table:innowork_projects:name:integer';
        $this->mKeys['customerid'] = 'table:innowork_directory_companies:companyname:integer';
        $this->mKeys['statusid'] = 'table:innowork_tickets_fields_values:fieldvalue:integer';
        $this->mKeys['priorityid'] = 'table:innowork_tickets_fields_values:fieldvalue:integer';
        $this->mKeys['sourceid'] = 'table:innowork_tickets_fields_values:fieldvalue:integer';
        $this->mKeys['channelid'] = 'table:innowork_tickets_fields_values:fieldvalue:integer';
        $this->mKeys['typeid'] = 'table:innowork_tickets_fields_values:fieldvalue:integer';
        $this->mKeys['creationdate'] = 'timestamp';
        $this->mKeys['done'] = 'boolean';
        $this->mKeys['openedby'] = 'integer';
        $this->mKeys['assignedto'] = 'integer';

        $this->mSearchResultKeys[] = 'title';
        $this->mSearchResultKeys[] = 'projectid';
        $this->mSearchResultKeys[] = 'customerid';
        $this->mSearchResultKeys[] = 'typeid';
        $this->mSearchResultKeys[] = 'statusid';
        $this->mSearchResultKeys[] = 'priorityid';
        $this->mSearchResultKeys[] = 'sourceid';
        $this->mSearchResultKeys[] = 'channelid';
        $this->mSearchResultKeys[] = 'creationdate';
        $this->mSearchResultKeys[] = 'done';
        $this->mSearchResultKeys[] = 'openedby';
        $this->mSearchResultKeys[] = 'assignedto';
        
        $this->mViewableSearchResultKeys[] = 'id';
        $this->mViewableSearchResultKeys[] = 'title';
        $this->mViewableSearchResultKeys[] = 'projectid';
        $this->mViewableSearchResultKeys[] = 'customerid';
        $this->mViewableSearchResultKeys[] = 'typeid';
        $this->mViewableSearchResultKeys[] = 'statusid';
        $this->mViewableSearchResultKeys[] = 'priorityid';
        $this->mViewableSearchResultKeys[] = 'sourceid';
        $this->mViewableSearchResultKeys[] = 'channelid';
        $this->mViewableSearchResultKeys[] = 'creationdate';
        $this->mViewableSearchResultKeys[] = 'openedby';
        $this->mViewableSearchResultKeys[] = 'assignedto';

        $this->mSearchOrderBy = 'id DESC';
        $this->mShowDispatcher = 'view';
        $this->mShowEvent = 'showticket';

        $this->mGenericFields['companyid'] = 'customerid';
        $this->mGenericFields['projectid'] = 'projectid';
        $this->mGenericFields['title'] = 'title';
        $this->mGenericFields['content'] = 'description';
        $this->mGenericFields['binarycontent'] = '';
    }

    public function doCreate(
        $params,
        $userId
        )
    {
        $result = false;

            if ( $params['done'] == 'true' ) $params['done'] = $this->mrDomainDA->fmttrue;
            else $params['done'] = $this->mrDomainDA->fmtfalse;

            if (
                !isset($params['customerid'] )
                or !strlen( $params['customerid'] )
                ) $params['customerid'] = '0';

            if (
                !isset($params['projectid'] )
                or !strlen( $params['projectid'] )
                ) $params['projectid'] = '0';

            if (
                !isset($params['statusid'] )
                or !strlen( $params['statusid'] )
                ) $params['statusid'] = '0';

            if (
                !isset($params['priorityid'] )
                or !strlen( $params['priorityid'] )
                ) $params['priorityid'] = '0';

            if (
                !isset($params['sourceid'] )
                or !strlen( $params['sourceid'] )
                ) $params['sourceid'] = '0';

            if (
                !isset($params['channelid'] )
                or !strlen( $params['channelid'] )
                ) $params['channelid'] = '0';

            if (!isset($params['typeid']) or !strlen($params['typeid'])) {
            	$params['typeid'] = '0';
            }
            
            if (!isset($params['openedby']) or !strlen($params['openedby'])) {
            	$params['openedby'] = '0';
            }
            
            if (!isset($params['assignedto']) or !strlen($params['assignedto'])) {
            	$params['assignedto'] = '0';
            }
                        
        if (count($params)) {
            $item_id = $this->mrDomainDA->getNextSequenceValue( $this->mTable.'_id_seq' );

            $params['trashed'] = $this->mrDomainDA->fmtfalse;
            $params['creationdate']['year'] = date( 'Y' );
            $params['creationdate']['mon'] = date( 'n' );
            $params['creationdate']['mday'] = date( 'd' );
            $params['creationdate']['hours'] = date( 'H' );
            $params['creationdate']['minutes'] = date( 'i' );
            $params['creationdate']['seconds'] = date( 's' );

            $timestamp = $this->mrDomainDA->getTimestampFromDateArray( $date );

            $key_pre = $value_pre = $keys = $values = '';

            while ( list( $key, $val ) = each( $params ) ) {
                $key_pre = ',';
                $value_pre = ',';

                switch ( $key ) {
                case 'title':
                case 'description':
                case 'solution':
                case 'done':
                case 'trashed':
                    $keys .= $key_pre.$key;
                    $values .= $value_pre.$this->mrDomainDA->formatText( $val );
                    break;

                case 'creationdate':
                    $val = $this->mrDomainDA->getTimestampFromDateArray( $val );
                    unset( $date_array );

                    $keys .= $key_pre.$key;
                    $values .= $value_pre.$this->mrDomainDA->formatText( $val );
                    break;

                case 'projectid':
                case 'customerid':
                case 'statusid':
                case 'priorityid':
                case 'sourceid':
                case 'channelid':
                case 'typeid':
                case 'openedby':
                case 'assignedto':
                    if ( !strlen( $key ) ) $key = 0;
                    $keys .= $key_pre.$key;
                    $values .= $value_pre.$val;
                    break;

                default:
                    break;
                }
            }

            if ( strlen( $values ) ) {
                if ( $this->mrDomainDA->Execute( 'INSERT INTO '.$this->mTable.' '.
                                               '(id,ownerid'.$keys.') '.
                                               'VALUES ('.$item_id.','.
                                               $userId.
                                               $values.')' ) )
                {
                    $result = $item_id;
                }
            }
        }

        //$this->_mCreationAcl = InnoworkAcl::TYPE_PRIVATE;

        return $result;
    }

    public function doEdit(
        $params
        )
    {
        $result = false;

        if ( $this->mItemId ) {
            if ( count( $params ) ) {
                $start = 1;
                $update_str = '';

                if ( isset($params['done'] ) ) {
                    if ( $params['done'] == 'true' ) $params['done'] = $this->mrDomainDA->fmttrue;
                    else $params['done'] = $this->mrDomainDA->fmtfalse;
                }

                while ( list( $field, $value ) = each( $params ) ) {
                    if ( $field != 'id' ) {
                        switch ( $field ) {
                        case 'title':
                        case 'description':
                        case 'solution':
                        case 'done':
                        case 'trashed':
                            if ( !$start ) $update_str .= ',';
                            $update_str .= $field.'='.$this->mrDomainDA->formatText( $value );
                            $start = 0;
                            break;

                        case 'creationdate':
                            $value = $this->mrDomainDA->getTimestampFromDateArray( $value );
                            unset( $date_array );

                            if ( !$start ) $update_str .= ',';
                            $update_str .= $field.'='.$this->mrDomainDA->formatText( $value );
                            $start = 0;
                            break;

                        case 'projectid':
                        case 'customerid':
                        case 'statusid':
                        case 'priorityid':
                        case 'sourceid':
                        case 'channelid':
                        case 'typeid':
                		case 'openedby':
                		case 'assignedto':
                        	if ( !strlen( $value ) ) $value = 0;
                            if ( !$start ) $update_str .= ',';
                            $update_str .= $field.'='.$value;
                            $start = 0;
                            break;

                        default:
                            break;
                        }
                    }
                }

                $query = &$this->mrDomainDA->Execute(
                    'UPDATE '.$this->mTable.' '.
                    'SET '.$update_str.' '.
                    'WHERE id='.$this->mItemId );

                if ( $query ) $result = true;
            }
        }

        return $result;
    }

    public function doTrash()
    {
        return true;
    }

    public function doRemove(
        $userId
        )
    {
        $result = false;

        $result = $this->mrDomainDA->Execute(
            'DELETE FROM '.$this->mTable.' '.
            'WHERE id='.$this->mItemId
            );

        if ( $result ) {
            $this->mrDomainDA->Execute(
                'DELETE FROM innowork_tickets_messages '.
                'WHERE ticketid='.$this->mItemId
                );
        }

        return $result;
    }

    public function AddMessage(
        $username,
        $content
        )
    {
        $result = false;

        if ( $this->mItemId ) {
            $date['year'] = date( 'Y' );
            $date['mon'] = date( 'n' );
            $date['mday'] = date( 'd' );
            $date['hours'] = date( 'H' );
            $date['minutes'] = date( 'i' );
            $date['seconds'] = date( 's' );

            $timestamp = $this->mrDomainDA->getTimestampFromDateArray( $date );

            if ( strlen( $username ) ) {
                $result = $this->mrDomainDA->Execute(
                    'INSERT INTO innowork_tickets_messages VALUES('.
                    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->getNextSequenceValue( 'innowork_tickets_messages_id_seq' ).','.
                    $this->mItemId.','.
                    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText( $username ).','.
                    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText( $content ).','.
                    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText( $timestamp ).')'
                    );

                if ( $result ) {
                    require_once('innowork/core/InnoworkItemLog.php');
                    $log = new InnoworkItemLog(
                        $this->mItemType,
                        $this->mItemId
                        );

                    $log->LogChange( \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName() );
                }
            }
        }

        return $result;
    }

    public function RemoveMessage(
        $messageId
        )
    {
        $result = false;
        $messageId = (int)$messageId;

        if ( $messageId ) {
            $result = $this->mrDomainDA->Execute(
                'DELETE FROM innowork_tickets_messages '.
                'WHERE id='.$messageId
                );

                if ( $result ) {
                    require_once('innowork/core/InnoworkItemLog.php');
                    $log = new InnoworkItemLog(
                        $this->mItemType,
                        $this->mItemId
                        );

                    $log->LogChange( \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName() );
                }
        }

        return $result;
    }

    public function getMessages()
    {
        $result = array();

        if ( $this->mItemId ) {
            $messages_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->Execute(
                'SELECT id,username,content,creationdate '.
                'FROM innowork_tickets_messages '.
                'WHERE ticketid='.$this->mItemId.' '.
                'ORDER BY creationdate'
                );

            while ( !$messages_query->eof ) {
                $result[] = array(
                    'id' => $messages_query->getFields( 'id' ),
                    'username' => $messages_query->getFields( 'username' ),
                    'content' => $messages_query->getFields( 'content' ),
                    'creationdate' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->getDateArrayFromTimestamp(
                        $messages_query->getFields( 'creationdate' )
                        )
                    );

                $messages_query->moveNext();
            }
        }

        return $result;
    }

    public function doGetSummary()
    {
        $result = false;

        $tickets = new InnoworkTicket(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
            );
        $tickets_search = $tickets->Search(
            array(
                'done' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse
                ),
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId(),
            false,
            false,
            10,
            0
            );

        $result =
'<vertgroup>
  <children>';

        if ( count( $tickets_search ) ) {
            $locale = new LocaleCatalog(
                'innowork-tickets::innoworktickets_domain_main',
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
                );

            $innowork_companies = new InnoworkCompany(
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
                );
            $search_results = $innowork_companies->Search(
                '',
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()
                );
            $customers[0] = $locale->getStr( 'nocustomer.label' );
            while ( list( $id, $fields ) = each( $search_results ) ) {
                $customers[$id] = $fields['companyname'];
            }
            unset( $locale );
            unset( $search_results );
        }

        foreach ( $tickets_search as $ticket ) {
            $customer = strlen( $customers[$ticket['customerid']] ) > 25 ?
                substr( $customers[$ticket['customerid']], 0, 22 ).'...' :
                $customers[$ticket['customerid']];

            $result .=
'<link>
  <args>
    <label type="encoded">'.urlencode( '- '.$ticket['id'].' ('.$customer.')' ).'</label>
    <link type="encoded">'.urlencode(
        WuiEventsCall::buildEventsCallString( 'innoworktickets', array(
                array(
                    'view',
                    'showticket',
                    array( 'id' => $ticket['id'] )
                )
            ) )
        ).'</link>
    <compact>true</compact>
  </args>
</link>';
        }

        $result .=
'  </children>
</vertgroup>';

        return $result;
    }
}

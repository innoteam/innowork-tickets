<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Locale\LocaleCatalog;
use \Innomatic\Domain\User;
use \Shared\Wui;

require_once('innowork/tickets/InnoworkTicket.php');
require_once('innowork/projects/InnoworkProject.php');


class InnoworkticketsPanelActions extends \Innomatic\Desktop\Panel\PanelActions
{
    private $localeCatalog;
    private $innomaticContainer;

    public $status;

    public function __construct(\Innomatic\Desktop\Panel\PanelController $controller)
    {
        parent::__construct($controller);
    }

    public function beginHelper()
    {
        $this->innomaticContainer = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
        $this->localeCatalog = new LocaleCatalog(
            'innowork-tickets::innoworktickets_domain_main',
            $this->innomaticContainer->getCurrentUser()->getLanguage()
        );
    }

    public function endHelper()
    {
    }

    public function executeNewticket($eventData)
    {
        $ticket = new InnoworkTicket(
            $this->innomaticContainer->getDataAccess(),
            $this->innomaticContainer->getCurrentDomain()->getDataAccess()
        );

        $eventData['openedby'] = $this->innomaticContainer->getCurrentUser()->getUserId();
        $eventData['assignedto'] = $this->innomaticContainer->getCurrentUser()->getUserId();

        if ($ticket->Create($eventData)) {
            $GLOBALS['innowork-tickets']['newticketid'] = $ticket->mItemId;
            $this->status = $this->localeCatalog->getStr('ticket_created.status');
        } else {
            $this->status = $this->localeCatalog->getStr('ticket_not_created.status');
        }

        $this->setChanged();
        $this->notifyObservers('status');
    }   

    public function executeEditticket($eventData)
    {
        $ticket = new InnoworkTicket(
            $this->innomaticContainer->getDataAccess(),
            $this->innomaticContainer->getCurrentDomain()->getDataAccess(),
            $eventData['id']
        );

        if ($ticket->Edit($eventData)) {
            $this->status = $this->localeCatalog->getStr('ticket_updated.status');
        } else {
            $this->status = $this->localeCatalog->getStr('ticket_not_updated.status');
        }

        $this->setChanged();
        $this->notifyObservers('status');
    }

    public function executeTrashticket($eventData)
    {
        $ticket = new InnoworkTicket(
            $this->innomaticContainer->getDataAccess(),
            $this->innomaticContainer->getCurrentDomain()->getDataAccess(),
            $eventData['id']
        );

        if ($ticket->trash($this->innomaticContainer->getCurrentUser()->getUserId())) {
            $this->status = $this->localeCatalog->getStr('ticket_trashed.status');
        } else {
            $this->status = $this->localeCatalog->getStr('ticket_not_trashed.status');
        }

        $this->setChanged();
        $this->notifyObservers('status');
    }

    public function executeNewmessage($eventData)
    {
        $ticket = new InnoworkTicket(
            $this->innomaticContainer->getDataAccess(),
            $this->innomaticContainer->getCurrentDomain()->getDataAccess(),
            $eventData['ticketid']
        );

        if ($ticket->addMessage(
            $this->innomaticContainer->getCurrentUser()->getUserName(),
            $eventData['content']
        )) {
            $this->status = $this->localeCatalog->getStr('message_created.status');
        } else {
            $this->status = $this->localeCatalog->getStr('message_not_created.status');
        }

        $this->setChanged();
        $this->notifyObservers('status');
    }

    public function executeRemovemessage($eventData)
    {
        $ticket = new InnoworkTicket(
            $this->innomaticContainer->getDataAccess(),
            $this->innomaticContainer->getCurrentDomain()->getDataAccess(),
            $eventData['ticketid']
        );

        if ($ticket->removeMessage($eventData['messageid'])) $this->status = $this->localeCatalog->getStr('message_removed.status');
        else $this->status = $this->localeCatalog->getStr('message_not_removed.status');

        $this->setChanged();
        $this->notifyObservers('status');
    }

    public function executeErasefilter($eventData)
    {
        $filter_sk = new WuiSessionKey('customer_filter', array('value' => ''));
        $filter_sk = new WuiSessionKey('project_filter', array('value' => ''));
        $filter_sk = new WuiSessionKey('priority_filter', array('value' => ''));
        $filter_sk = new WuiSessionKey('status_filter', array('value' => ''));
        $filter_sk = new WuiSessionKey('source_filter', array('value' => ''));
        $filter_sk = new WuiSessionKey('channel_filter', array('value' => ''));
        $filter_sk = new WuiSessionKey('type_filter', array('value' => ''));
        $filter_sk = new WuiSessionKey('year_filter', array('value' => ''));
        $filter_sk = new WuiSessionKey('month_filter', array('value' => ''));
        $filter_sk = new WuiSessionKey('day_filter', array('value' => ''));
        $filter_sk = new WuiSessionKey('openedby_filter', array('value' => ''));
        $filter_sk = new WuiSessionKey('assignedto_filter', array('value' => ''));
    }

        /**
     * Ajax for change view with new list of projects.
     *
     * @param string $customer_id_selected identify customer selected.
     * 
     * @return void
     */
    public function ajaxOnChangeListProjects($customer_id_selected) 
    {
        $objResponse = new XajaxResponse();

        $innomaticContainer = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
        $localeCatalog = new LocaleCatalog(
            'innowork-tickets::innoworktickets_domain_main',
            $innomaticContainer->getCurrentUser()->getLanguage()
        );

        // Projects list
        $projects_search_conditions = array('customerid' => $customer_id_selected);

        // If this is a new ticket or a ticket without project, do not show archived projects
        if ($ticket_data['projectid'] == '' or $ticket_data['projectid'] == 0) {
            $projects_search_conditions['done'] = $innomaticContainer->getDataAccess()->fmtfalse;
        }

        $innowork_projects = new InnoworkProject(
            $innomaticContainer->getDataAccess(),
            $innomaticContainer->getCurrentDomain()->getDataAccess()
        );
        $search_results = $innowork_projects->search(
            $projects_search_conditions,
            $innomaticContainer->getCurrentUser()->getUserId()
        );
        unset($projects_search_conditions);

        $projects['0'] = $localeCatalog->getStr('noproject.label');

        while (list($id, $fields) = each($search_results)) {
            $projects[$id] = $fields['name'];
        }

        $xml = '<horizgroup>
                  <args>
                    <align>middle</align>
                    <width>0%</width>
                  </args>
                  <children>

                    <label>
                      <args>
                        <label>'.$localeCatalog->getStr('project.label').'</label>
                      </args>
                    </label>

                    <combobox><name>projectid</name>
                      <args>
                        <disp>action</disp>
                        <elements type="array">'.WuiXml::encode($projects).'</elements>
                        <default>'.$ticket_data['projectid'].'</default>
                      </args>
                    </combobox>

                  </children>
                </horizgroup>';

        $html = WuiXml::getContentFromXml('', $xml);

        $objResponse->addAssign("div_list_projects", "innerHTML", $html);

        return $objResponse;
    }
}

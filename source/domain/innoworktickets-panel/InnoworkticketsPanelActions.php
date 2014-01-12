<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Locale\LocaleCatalog;
use \Innomatic\Domain\User;
use \Shared\Wui;

class InnoworkticketsPanelActions extends \Innomatic\Desktop\Panel\PanelActions
{
    private $localeCatalog;
    public $status;

    public function __construct(\Innomatic\Desktop\Panel\PanelController $controller)
    {
        parent::__construct($controller);
    }

    public function beginHelper()
    {
        $this->localeCatalog = new LocaleCatalog(
            'innowork-tickets::innoworktickets_domain_main',
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );
    }

    public function endHelper()
    {
    }

    public function executeNewticket($eventData)
    {
    	require_once('innowork/tickets/InnoworkTicket.php');
    	$ticket = new InnoworkTicket(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    	);
    
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
    	require_once('innowork/tickets/InnoworkTicket.php');
    	
    	$ticket = new InnoworkTicket(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
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
    	require_once('innowork/tickets/InnoworkTicket.php');
    	
    	$ticket = new InnoworkTicket(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
    		$eventData['id']
    	);
    
    	if ($ticket->trash(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId())) {
    		$this->status = $this->localeCatalog->getStr('ticket_trashed.status');
    	} else {
    		$this->status = $this->localeCatalog->getStr('ticket_not_trashed.status');
    	}
    	
    	$this->setChanged();
    	$this->notifyObservers('status');
    }
    
    public function executeNewmessage($eventData)
    {
    	require_once('innowork/tickets/InnoworkTicket.php');
    	
    	$ticket = new InnoworkTicket(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
    		$eventData['ticketid']
    	);
    
    	if ($ticket->addMessage(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName(),
    		$eventData['content'])
		) {
    		$this->status = $this->localeCatalog->getStr('message_created.status');
    	} else {
    		$this->status = $this->localeCatalog->getStr('message_not_created.status');
    	}

    	$this->setChanged();
    	$this->notifyObservers('status');
    }
    
    public function executeRemovemessage($eventData)
    {
    	require_once('innowork/tickets/InnoworkTicket.php');
    	
    	$ticket = new InnoworkTicket(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
    		$eventData['ticketid']
    	);
    
    	if ($ticket->removeMessage($eventData['messageid'])) $this->status = $this->localeCatalog->getStr('message_removed.status');
    	else $this->status = $this->localeCatalog->getStr('message_not_removed.status');

    	$this->setChanged();
    	$this->notifyObservers('status');
    }
    
    public function executeErasefilter($eventData) {
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
}

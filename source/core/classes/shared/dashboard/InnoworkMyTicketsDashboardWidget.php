<?php

namespace Shared\Dashboard;

use \Innomatic\Core\InnomaticContainer;
use \Shared\Wui;
use \Innomatic\Wui\Dispatch;

class InnoworkMyTicketsDashboardWidget extends \Innomatic\Desktop\Dashboard\DashboardWidget
{
    public function getWidgetXml()
    {
        $locale_catalog = new \Innomatic\Locale\LocaleCatalog(
            'innowork-tickets::innoworktickets_dashboard',
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );

    	$locale_country = new \Innomatic\Locale\LocaleCountry(
			InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry()
        );

    	require_once('innowork/tickets/InnoworkTicket.php');

		$tickets = new InnoworkTicket(
			\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
			\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
		);

		$tickets->mSearchOrderBy = 'id DESC';

		$search_result = $tickets->search(
			array('done' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse, 'assignedto' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()),
			\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()
		);

        $xml =
        '<vertgroup>
           <children>';

        $search_result_count = count($search_result);

        switch ($search_result_count) {
        	case 0:
        		$tickets_number_label = $locale_catalog->getStr('no_tickets.label');
        		break;

        	case 1:
        		$tickets_number_label = sprintf($locale_catalog->getStr('ticket_number.label'), count($search_result));
        		break;

        	default:
        		$tickets_number_label = sprintf($locale_catalog->getStr('tickets_number.label'), count($search_result));
        }

        $xml .= '<label>
               <args>
        		 <label>'.WuiXml::cdata($tickets_number_label).'</label>
        	   </args>
        	 </label>';

        if ($search_result_count > 0) {
        	$xml .= '<label>
               <args>
        		 <label>'.WuiXml::cdata($locale_catalog->getStr('last_opened_tickets.label')).'</label>
        	   </args>
        	 </label>

        	<grid><children>';

        	$row = 0;
        	foreach ($search_result as $ticket) {
        		$xml .= '<link row="'.$row.'" col="0" halign="left" valign="top">
               <args>
        		 <label>'.WuiXml::cdata($ticket['id']).'</label>
        <compact>true</compact>
        <nowrap>false</nowrap>
        <link>'.WuiXml::cdata(\Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('innoworktickets', array(array('view', 'showticket', array('id' => $ticket['id']))))).'</link>
        	   </args>
        	 </link>
        	<link row="'.$row.'" col="1" halign="left" valign="top">
               <args>
        		 <label>'.WuiXml::cdata($ticket['title']).'</label>
        <compact>true</compact>
        <nowrap>false</nowrap>
        <link>'.WuiXml::cdata(\Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('innoworktickets', array(array('view', 'showticket', array('id' => $ticket['id']))))).'</link>
        	   </args>
        	 </link>';
        		if (++$row == 5) {
        			break;
        		}
        	}

        	$xml .= '</children></grid>';
        }

        $xml .= '<horizbar/>';

        $xml .= '<horizgroup><args><width>0%</width></args><children>';

        if (count($search_result) > 0) {
        	$xml .= '  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>zoom</themeimage>
      <label>'.$locale_catalog->getStr('show_all_my_tickets.button').'</label>
      <action>'.WuiXml::cdata(\Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('innoworktickets', array(array('view', 'default', array('filter' => 'true', 'filter_assignedto' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()))))).'</action>
    </args>
  </button>';
        }

        $xml .= '
  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>mathadd</themeimage>
      <mainaction>true</mainaction>
      <label>'.$locale_catalog->getStr('new_ticket.button').'</label>
      <action>'.WuiXml::cdata(\Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('innoworktickets', array(array('view', 'newticket', array())))).'</action>
    </args>
  </button>';

  $xml .= '</children></horizgroup>

           </children>
         </vertgroup>';

        return $xml;
    }

    public function getWidth()
    {
        return 1;
    }

    public function getHeight()
    {
        return 60;
    }
}

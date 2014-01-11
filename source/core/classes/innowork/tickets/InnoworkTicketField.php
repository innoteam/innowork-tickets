<?php

class InnoworkTicketField {
	const TYPE_STATUS = 1;
	const TYPE_PRIORITY = 2;
	const TYPE_TYPE = 3;
	const TYPE_SOURCE = 4;
	const TYPE_CHANNEL = 5;
	var $mLog;
	var $mrDomainDA;
	var $mFieldType;
	var $mId;

	public function __construct($rdb, $fieldType = '', $id = '')
	{
		$this->mLog = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getLogger();

		if (is_object($rdb)) $this->mrDomainDA = $rdb;
		else $this->mLog->LogDie(
            'innoworkprojects.innoworkprojects.projectfield.projectfield',
            'Invalid domain database handler'
           );

        if ($id) {
          	$query = &$this->mrDomainDA->execute(
        	    'SELECT fieldid,fieldvalue '.
                'FROM innowork_tickets_fields_values '.
                'WHERE id='.$id);

			if ($query->getNumberRows()) {
				$this->mId = $id;

            	$this->mFieldValue = $query->getFields('fieldvalue');
            	$this->mFieldType = $query->getFields('fieldid');
            }
        } else {
			$this->mFieldType = $fieldType;
		}

		if (empty($this->mFieldType)) {
			$this->mLog->LogDie(
		        'innoworktickets.innoworktickets.innoworkticketfield.ticketfield',
        	    'No field type supplied'
          	);
        }
	}

	public function newValue($value)
	{
		$result = false;

		if ($this->mrDomainDA and !$this->mId) {
			$result = $this->mrDomainDA->execute(
                'INSERT INTO innowork_tickets_fields_values '.
                'VALUES ('.
				$this->mrDomainDA->getNextSequenceValue('innowork_tickets_fields_values_id_seq').','.
				$this->mFieldType.','.
				$this->mrDomainDA->formatText($value).')'
			);

			if ($result) {
				$this->mFieldValue = $value;
			}
		}

		return $result;
	}

	public function editValue($newValue, $newType = '')
	{
		$result = false;

		if ($this->mrDomainDA and $this->mId) {
			$result = $this->mrDomainDA->execute(
                'UPDATE innowork_tickets_fields_values '.
                'SET fieldvalue='.$this->mrDomainDA->formatText($newValue).
			(strlen($newType) ? ',fieldid='.$newType : '').
                ' WHERE id='.$this->mId
			);

			if ($result) {
				$this->mFieldValue = $newValue;
			}
		}

		return $result;
	}

	public function removeValue()
	{
		$result = false;

		if ($this->mrDomainDA and $this->mId) {
			$result = $this->mrDomainDA->execute(
                'DELETE FROM innowork_tickets_fields_values '.
                'WHERE id='.$this->mId
			);

			if ($result) {
				$update_projects = false;

				switch ($this->mFieldType) {
					case InnoworkTicketField::TYPE_STATUS:
						$field = 'status';
						$update_projects = true;
						break;

					case InnoworkTicketField::TYPE_PRIORITY:
						$fields = 'priority';
						$update_projects = true;
						break;

					case InnoworkTicketField::TYPE_TYPE:
						$fields = 'type';
						$update_projects = true;
						break;

					case InnoworkTicketField::TYPE_SOURCE:
					case INNOWORKPROJECTS_FIELDYTPE_CHANNEL:
						break;
				}

				if ($update_projects) {
					$this->mrDomainDA->execute(
                        'UPDATE innowork_projects '.
                        'SET '.$field.'=0 '.
                        'WHERE '.$field.'='.$this->mId);
				}

				$this->mId = 0;
				$this->mFieldValue = '';
			}
		}

		return $result;
	}

	public static function getFields($type)
	{
		$query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute(
        'SELECT
        	id,fieldvalue
        FROM 
        	innowork_tickets_fields_values 
        WHERE
        	fieldid='.$type.' 
        ORDER BY
        	fieldvalue');

		$fields = array();

		while (!$query->eof) {
			$fields[$query->getFields('id')] = $query->getFields('fieldvalue');
			$query->moveNext();
		}

		return $fields;
	}
}

?>
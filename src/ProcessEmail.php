<?php

require_once 'Initial_Const.php';
require_once 'EmailStruct.php';
require_once 'LogClass.php';
require_once 'Gmail_API.php';
//require_once 'SendEmail.php';

class ProcessEmail implements Initial_Const
{
	protected $mbox;
	protected $noMessage;
	protected $num_recent;

	protected $messages;
	protected $ignoreMessages;

	protected $config;

	//--------------------------------------------------------------------------
	public function __construct()
	//--------------------------------------------------------------------------
	{
		$this->messages = array();
		$this->ignoreMessages = array();
		
		if(self::verbose)
			echo "\nProcessEmail constructor " . ' File: ' . __FILE__ . ' line_number: ' . __LINE__; 
			
	}

	//--------------------------------------------------------------------------
	public function process()
	//--------------------------------------------------------------------------
	{

		$log = new LogClass();
		$log->starttime = date("d/m/Y H:i:s");
		//create an object for Gmail connection and API Calls 
		$Gmail = new Gmail_API();
		//get all message ids from INBOX
		$ids = $Gmail->getMessageIDs(array("INBOX"));

		//no message in INBOX
		if (empty($ids))
		{
			$this->noMessage = true;
			return;
		}
		//Process messages in INBOX: read content and setup labels
		$this->noMessage = false;
		echo "There are ".sizeof($ids)." unread email(s).".PHP_EOL;
		foreach ($ids as $id)
		{
			$emailStruct = new EmailStruct();
			$headers = $Gmail->getMessageHeader($id);
			$subject = $Gmail->getHeaderSubject($headers);
			$received_date = $Gmail->getHeaderDate($headers);
			$from = $Gmail->getHeaderFrom($headers);
			// echo $subject.PHP_EOL;
			// echo $received_date.PHP_EOL;

			if ($subject === self::expect_subject)
			{
				$body = $Gmail->getMessageBody($id);

				$emailStruct->received_date = $received_date;
				$emailStruct->subject = $subject;
				$emailStruct->from = $from;
				$emailStruct->text_orig = $body;
				$ignore = true;
				foreach(self::expect_content as $content)
				{
					if (strpos($body, $content) !== false)
					{
						$this->messages[] = $emailStruct;
						$emailStruct->process();
						$Gmail->setLabel($id, array("processed"));
						$ignore = false;
						break;
					}
				}
				if ($ignore)
				{
					$this->ignoreMessages[] = $emailStruct;
					$Gmail->setLabel($id, array("ignore"));
				}
			}
			else{
				$Gmail->setLabel($id, array("other"));
			}
			
			// print_r($body);
			// echo PHP_EOL;
		}
	
		//modify labels for all messages
		$Gmail->changeLabels();
				
		$text = "There are " . sizeof($this->messages) . " emails that have been processed. Check processed folder in Gmail.".PHP_EOL;
		$text .= "There are " . sizeof($this->ignoreMessages) . " emails that have been ignored. Check ignore folder in Gmail.".PHP_EOL;

		if(self::verbose) {
			echo $text;
		}
		$log->endtime = date("d/m/Y H:i:s");
		$log->type = "email";
		$log->title = "Email messages process";
		$log->error_str = $text;
		return $log;
	}

	public function processMessage($msgid)
	{
		$body = imap_body($this->mbox, $msgid, FT_PEEK);
		$header = imap_headerinfo($this->mbox, $msgid);
		$emailStruct = new EmailStruct;
		$headerdate = $header->date;

		if(isset($headerdate))
		{
			
			$myDate = date("d-m-Y H:i:s", strtotime($headerdate));
			
			
			$emailStruct->received_date = $myDate;
			// echo $emailStruct->received_date.PHP_EOL;
		}

		$emailStruct->subject = isset($header->subject) ? $header->subject : '';
		//echo $emailStruct->subject.PHP_EOL;
		$emailStruct->from = isset($header->fromaddress) ? $header->fromaddress : '';
		//echo $emailStruct->from.PHP_EOL;
		$emailStruct->text_orig = $body;
		
		//-----
		//--work out if we should process this email, or move it to the ignore folder 
		//-----
		if ($emailStruct->from && $emailStruct->subject === self::expect_subject)
		{
			if (strpos($body, self::encode_delimiter) !== false)
			{
				$b = explode(self::encode_delimiter, $body);
				$b = explode("

				", $b[1]);
				$body = base64_decode(trim($b[0]));
				$emailStruct->text_orig = $body;
				//echo $emailStruct->text_orig.PHP_EOL;
			}
			$ignore = true;
			foreach(self::expect_content as $content)
			{
				if (strpos($body, $content) !== false)
				{
					$this->messages[] = $emailStruct;
					$emailStruct->process();
					imap_mail_move($this->mbox, $msgid, "processed");
					$ignore = false;
					break;
				}
			}
			if ($ignore)
			{
				$this->ignoreMessages[] = $emailStruct;
				imap_mail_move($this->mbox, $msgid, "ignore");
			}
		}
		else //if ($emailStruct->subject == "test")
		{
			imap_mail_move($this->mbox, $msgid, "other");
			echo $emailStruct->subject.": email has been moved".PHP_EOL;
		}
		//echo $emailStruct->text_orig.PHP_EOL;
	}

	//public function 

	//--------------------------------------------------------------------------
	public function getMessages()
	//--------------------------------------------------------------------------
	{
		if(!$this->noMessage)
			return $this->messages;
		else
			return false;
	}

	//--------------------------------------------------------------------------
	public function getIgnoreMessages()
	//--------------------------------------------------------------------------
	{
		if(!$this->noMessage)
			return $this->ignoreMessages;
		else
			return false;
	}

};


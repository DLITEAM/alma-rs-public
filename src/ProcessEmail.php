<?php

require_once 'Initial_Const.php';
require_once 'EmailStruct.php';
require_once 'LogClass.php';
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
		$this->mbox = imap_open(self::email_domain, 
														self::email_address, 
														self::email_password);
		
		if($this->mbox == false)
		{
			if(self::verbose)
				echo "\nCould not connect to email server..." . ' File: ' . __FILE__ . ' line_number: ' . __LINE__; 
			
			$message  = "\nCan not connect to Gmail from the LADD process";
			$message .= "\nPlease check the configuration options and try again...";
			//No, don't send.  Not fair for Clare...
			//SendEmail::send('Can not connect to Gmail', $message);
			exit;
		}
		
		//-----
		//--if there are no messages, then we get this notice.
		//--return from the script if that is the case.
		//-----
		if(imap_last_error() == 'Mailbox is empty' ) 
		{
			$this->noMessage = true;
		} 
		else 
		{
			$this->noMessage = false;
			//--get the number of messages in the mailbox.
			$this->num_msgs = imap_num_msg($this->mbox);
			echo $this->num_msgs.PHP_EOL;
			if(self::verbose) {
				echo "\nThere were " . $this->num_msgs . " email messages found " . ' File: ' . __FILE__ . ' line_number: ' . __LINE__; 
			}	
		}
		//-----

		if(self::verbose)
		{
			$t = $this->noMessage == true ? 'true' : 'false';
			echo "\nValue of noMessage: " . $t;
		}

		if($this->noMessage)
			return;

		//-----
		//--process emails
		//-----
		$message_UID = Array();
		for($i = 1; $i <= $this->num_msgs; $i++)
		{
			$message_UID[] = imap_uid($this->mbox, $i);
			// echo imap_uid($this->mbox, $i).PHP_EOL;
			$this->processMessage($i);
		}

		// foreach ($message_UID as $UID)
		// {
		// 	$this->processMessage($UID);
		// }

		//--this clears the errors / notices. I.e. when there is no messages.	
		$errors = imap_errors();
		
		imap_expunge($this->mbox);     //called just prior to imap_close.
		imap_close($this->mbox);
		
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
			// if(self::verbose)
			// 	echo "\nheader->date: " . $headerdate;

			$myDate = date("d-m-Y H:i:s", strtotime($headerdate));
			
			
			// if(self::verbose)
			// 	echo "\nmyDate: " . $myDate;
			$emailStruct->received_date = $myDate;
			// echo $emailStruct->received_date.PHP_EOL;
		}

		$emailStruct->subject = isset($header->subject) ? $header->subject : '';
		//echo $emailStruct->subject.PHP_EOL;
		$emailStruct->from = isset($header->fromaddress) ? $header->fromaddress : '';
		//echo $emailStruct->from.PHP_EOL;
		$emailStruct->text_orig = $body;
		
		//echo $emailStruct->text_orig.PHP_EOL;

		// echo "\nemail loop, process function.  EmailNo: $msgid " . ' File: ' . __FILE__ . ' line_number: ' . __LINE__.PHP_EOL; 
		
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


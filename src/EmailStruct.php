<?php

require_once 'Initial_Const.php';
require_once 'Suspension.php';
require_once 'Contact.php';
require_once 'File_Func.php';
require_once 'Alma_API.php';

class EmailStruct implements Initial_Const
{
	public $received_date;
	public $text;
	public $text_orig;
	public $from;
	public $subject;

	public function __construct($emailStruct = null)
	{
		if(isset($emailStruct) && $emailStruct instanceof EmailStruct)
		{
			$this->received_date 	= $emailStruct->received_date;
			$this->text	 			= $emailStruct->text;
			$this->text_orig 		= $emailStruct->text_orig;
			$this->from 			= $emailStruct->from;
			$this->subject 			= $emailStruct->subject;
		}
	}

	public function Email_display()
	{
		echo $this->received_date.PHP_EOL;
		echo $this->subject.PHP_EOL;
		echo $this->from.PHP_EOL;
		echo $this->text_orig.PHP_EOL;
	}

	//---------------------------------------------------------------------------
	//--strip out the lines that have ### and ISO in it
	//---------------------------------------------------------------------------

	private function stripStuff()
	{
		$lines = explode("\n", $this->text_orig);
		$text_array = array();

		foreach($lines as $line) {
			if(!preg_match("/^###/", $line) && 
				!preg_match("/^ISO/", $line)) {
				$text_array[] = $line;
			}
		}

		$this->text = implode("\n", $text_array);
		return $text_array;
	}

	//---------------------------------------------------------------------------
	//--find add locations
	//---------------------------------------------------------------------------
	private function findLocations($text_array)
	{
		$index = 0;
		$add_symbols = array();
		$search = "LOCATION_DESCRIPTION";
		
		//-----
		//--add lineNo to add_symbol array for later processing
		//-----
		// print_r($text_array);
		// echo PHP_EOL;
		$add_array = preg_grep('/'.self::expect_content[0].'\s.*/', $text_array);
		if (sizeof($add_array) > 0)
		{
			// print_r($add_array);
			// echo PHP_EOL;
			$temp = reset($add_array);
			$temp = explode(" ", $temp);
			$code = Alma_API::code_convert(trim($temp[1]), trim($temp[2]));
			$prefix = trim($temp[2]);
			$name = $code;
			$desc_array = preg_grep('/'.$search.'\s.*/', $text_array);
			if (sizeof($desc_array) > 0)
			{
				// print_r($desc_array);
				// echo PHP_EOL;
				$temp = reset($desc_array);
				$temp = explode($search, $temp); 
				$name = trim($temp[1]); 				
			}
			else
			{
				$name_array = preg_grep('/HOUSE_NAME\s.*/', $text_array);
				if (sizeof($name_array) > 0)
				{
					$temp = reset($name_array);
					$temp = explode("HOUSE_NAME", $temp); 
					$name = trim($temp[1]);				
				}
			}
			$location = array($code, $name, $prefix);
			$header = array("code", "name", "prefix");
			File_Func::saveto_CSV(self::add_partner, $header, $location);
		}
		// while(true) 
		// {
		// 	//$pos = strpos($this->text, self::expect_content[0], $index); 
		// 	if(strpos($this->text, self::expect_content[0], $index) === false)
		// 		break;

		// 	//echo $this->text.PHP_EOL;
			
		// 	$pos = strpos($this->text, $search, $index);
		// 	if ($pos !== false)
		// 	{
		// 		$index = $pos + 1;
		// 		$lineNo = substr_count($this->text, "\n", 0, $index);
				
		// 		//echo "\r\npos: $pos lineNo: $lineNo".PHP_EOL;
			
		// 		$add_symbols[] = $lineNo;
		// 	}
		// 	else
		// 	{
		// 		break;
		// 	}
		// }
		
		// //-----
		// //--find descriptions for libraries
		// //-----
		// //$this->add_locations = array();
		// if(sizeof($add_symbols) > 0)
		// {
		// 	$location =  array();
		// 	foreach($add_symbols as $value)
		// 	{
		// 		// echo $value.PHP_EOL;
		// 		// $search = "LOCATION_DESCRIPTION";
		// 		// if (strpos($value, $search) !== false)
		// 		// {
		// 		$temp = trim($text_array[$value]);  //remove the \r
		// 		$temp = explode($search, $temp); 
		// 		$temp_1 = explode(' ', trim($temp[0]));
		// 		$code = Alma_API::code_convert($temp_1[1], $temp_1[2]);
		// 		$location = array($code, trim($temp[1]), $temp_1[2]);
		// 		$header = array("code", "name", "prefix");
		// 		File_Func::saveto_CSV(self::add_partner, $header, $location);
		// 		// }
					
		// 	}
		// }
		//-----
	}


	//---------------------------------------------------------------------------
	//--find any suspensions
	//---------------------------------------------------------------------------

	private function findSuspensions($text_array)
	{
		if(self::verbose)
			echo "\nInside findSuspensions..." . ' File: ' . __FILE__ . ' line_number: ' . __LINE__; 
		
		$suspensions_array = array();
		$start_array = array();
		$end_array = array();

		$suspend_filter = array("SUSPENDED_REQUESTING_OK", "SUSPENDED_NO_REQUESTING");


		$index = 0;

		//-----
		//--get the starting and ending line numbers for the suspension lists
		//-----
		while(true) 
		{
			$pos_start = strpos($this->text, 'BEGIN SUSPENSION LIST', $index); 
			$pos_end   = strpos($this->text, 'END SUSPENSION LIST', $index); 
		
			if($pos_start === false)
				break;
		
			$lineStartNo = substr_count($this->text, "\n", 0, $pos_start);
			$lineEndNo   = substr_count($this->text, "\n", 0, $pos_end);
		
			$index = $pos_end + 1;
			//test
			//echo "\r\npos_start: $pos_start pos_end: $pos_end lineStartNo: $lineStartNo lineEndNo: $lineEndNo";
		
			$start_array[] = $lineStartNo;
			$end_array[]   = $lineEndNo;
		}
		//-----


		//-----
		//--get the suspension line numbers
		//-----
		$size = sizeof($start_array);
		for($i = 0; $i < $size; $i++)
		{
			for($j = $start_array[$i] + 1; $j < $end_array[$i]; $j++)
			{
				$suspensions_array[] = $j;
			}
		}	
		//-----
			
		//-----
		//--proceed with suspensions
		//-----
		//$this->suspensions = array();

		foreach($suspensions_array as $value)
		{
			$line = $text_array[$value];
			$temp = explode(' ', $line);

			$suspension = new Suspension();
			//$suspension_list = array();
			foreach ($suspend_filter as $s_filter)
			{
				if(strpos($line, $s_filter)) 
				{
					$comment = "Suspended from " . $temp[3] . " until " . $temp[4] . ": " . preg_replace("/\W/", "", $temp[6]);
					if(self::verbose)
					{
						echo "\nSuspension line: $line";
						echo "\nSuspension dates: $comment";
					}

					$code = Alma_API::code_convert($temp[1], $temp[2]);
					
					$suspension->code = $code;
					$suspension->prefix = $temp[2];
					$suspension->received_date = $this->received_date;
					$suspension->update_date = date("d-m-Y");
					$suspension->status = $suspension::status_list["p"];
					$suspension->start = date("d-m-Y", strtotime($temp[3]));
					$suspension->end = date('d-m-Y', strtotime($temp[4]));
					$suspension->note = $comment;
					$header = $suspension::header;
					$field = $suspension->suspension_toarray();
					File_Func::saveto_CSV(self::update_suspension, $header, $field);
					break;
				} //if(strpos($line...
			}
		} //foreach($supensions_array...

	//---------------------------------------------------------------------------

	}

	public function findContact($text_array)
	{
		$contact_check_array = array('ADDRESSEE', 
																 'HOUSE_NAME',
																 'STREET',
																 'POBOX',
																 'CITY',
																 'REGION',
																 'POSTCODE',
																 'TEL_AREACODE',
																 'TELEPHONE',
																 'TEL_EXTENSION');
		$address_map_array = array('ADDRESSEE' 			=> 'line1',
															 'HOUSE_NAME' 		=> 'line2',
															 'STREET' 				=> 'line3',
															 'POBOX' 					=> 'line4',
															 'CITY' 					=> 'city', 
															 'REGION'  				=> 'state_province',
															 'POSTCODE'				=> 'postal_code',
															 'TEL_AREACODE'		=> 'phone_p1',
															 'TELEPHONE'			=> 'phone_p2',
															 'TEL_EXTENSION'	=> 'phone_p3');

		if (strpos($this->text, $contact_check_array[0]) !== false && strpos($this->text,$contact_check_array[1]) !== false)
		{
			$contact = new Contact();
			foreach($text_array as $text)
			{
				$parts = explode(" ", $text);
				if ($parts[0] == self::expect_content[2] && in_array($parts[3], $contact_check_array))
				{
					if (!$contact->code || !$contact->prefix)
					{
						$contact->code = Alma_API::code_convert($parts[1], $parts[2]);
						$contact->prefix = $parts[2];
					}
					$content = explode($parts[3], $text);
					$content = trim($content[1]);
					$variable = $address_map_array[$parts[3]];
					$contact->$variable = $content;
					
				}
			}
			$contact->build_phone();
			$contact->build_email();
			$contact->build_address();
			File_Func::saveto_CSV(self::update_contact, $contact::header, $contact->contact_toarray());
			// print_r($contact);
			// echo PHP_EOL;
		}
	}

	public function process()
	{
		$text_array = $this->stripStuff();
		// echo "text progress".PHP_EOL;
		$this->findLocations($text_array);
		$this->findSuspensions($text_array);
		$this->findContact($text_array);
	}

}
?>
<?php
require_once("Initial_Partner.php");

class Partner_Data
{
  const LADD_MemberURL = "https://www.nla.gov.au/librariesaustralia/connect/find-library/ladd-members-and-suspensions";
  //Const LADD_DatafilePath = ROOT_PATH."scr/data/";
  const DNZL_MemberURL = "https://natlib.govt.nz/directory-of-new-zealand-libraries.csv";

  static public function LADD_Data()
  {
    //echo "start";
    libxml_use_internal_errors(TRUE);
    $htmlContent = file_get_contents(self::LADD_MemberURL);
    $DOM = new DOMDocument();
    $DOM->loadHTML($htmlContent);

    $Tablehtml = $DOM->getElementsByTagName("table");

    $TableDate = array();

    $Data_return = array();

    //$i=0;

    if (sizeof($Tablehtml) !== 1)
    {
      echo "Not table or more than one table in the DOM. Please check.";
    }
    else
    {
      
      foreach($Tablehtml as $NodeTable)
      {
        $Headerhtml = $NodeTable->getElementsByTagName("thead");
        $Tbodyhtml = $NodeTable->getElementsByTagName("tbody");

        foreach($Headerhtml as $NodeHeader)
        {
          $ThList = $NodeHeader->getElementsByTagName("th");
          foreach($ThList as $NodeTh)
          {
            $HeadData[] = trim($NodeTh->nodeValue);
          }
          //print_r($HeadData);
          //echo PHP_EOL;
          array_splice($HeadData, 2, 0, array("prefix", "Post address", "Email", "Phone"));
          //print_r($HeadData);
          $TableDate[] = $HeadData;
        }
        foreach($Tbodyhtml as $NodeTbody)
        {
          $Trowhtml = $NodeTbody->getElementsByTagName("tr");
          foreach($Trowhtml as $NodeTrow)
          {
            //$i++;
            $Tdlist = $NodeTrow->getElementsByTagName("td");
            $TdDate = array();
            foreach($Tdlist as $NodeTd)
            {
              $TdDate[] = trim($NodeTd->nodeValue);
            }
            array_splice($TdDate, 2, 0, "NLA");

            //echo $TdDate[0].PHP_EOL;
            $contact = self::LADD_contact($TdDate[0]);
            // print_r($contact);
            // echo PHP_EOL;
            array_splice($TdDate, 3, 0, $contact);
            // print_r($TdDate);
            // echo PHP_EOL;
            $TableDate[] = $TdDate;
            $Data_return[] = array($TdDate[0], $TdDate[1], $TdDate[2], $TdDate[4]);
            // print_r($TableDate);
            // echo PHP_EOL;
            sleep(1);

            // if ($i === 5)
            // {
            //   break;
            // }
          }
          // if ($i === 5)
          // {
          //   break;
          // }
        }
      }
      //print_r($TableDate);
      //echo sizeof($TableDate).PHP_EOL;
      
      $filepath = Initial_Partner::init_folder."ladd_data_".date("Ymd").".csv";
      File_Func::create_CSV($filepath, $TableDate);
      echo "LADD data has been stored in $filepath".PHP_EOL;
    }
    return $Data_return;
  }

  static private function LADD_contact($code)
  {
    // $code = "DOC";
    $url = 'http://www.nla.gov.au/apps/ilrs/?action=IlrsSearch&nuc=%1$s&term=&termtype=Keyword&state=All&dosearch=Search&chunk=20';
    $url = sprintf($url, $code);
    $contact = array("", "", "");

    $address_main = "Main address:";
    $address_post = "Postal address:";
    $other_title = "ILL email:";

    libxml_use_internal_errors(TRUE);
    $htmlContent = file_get_contents($url);
    $DOM = new DOMDocument();
    $DOM->loadHTML($htmlContent);

    $pHtml = $DOM->getElementsByTagName("p");
    foreach ($pHtml as $pNode)
    {
      $text = trim($pNode->nodeValue);
      
      if (strpos($text, $address_main) !== false)
      {
        $contact[0] = self::format_LADDaddress($text, $address_main); 
        continue;
      }

      if (strpos($text, $address_post) !== false)
      {
        $post_a = self::format_LADDaddress($text, $address_post); 
        if (strpos($post_a, "Same as Main address") === false)
        {
          $contact[0] = $post_a;
        }
        continue;
      }

      if (strpos($text, $other_title) !== false)
      {
        $email_phone = self::format_LADDothers($text); 
        $contact[1] = $email_phone[0];
        $contact[2] = $email_phone[1];
        continue;
      }
    }
    // print_r($contact);
    // echo PHP_EOL;
    //if (empty($contact))
    return $contact;
  }

  static private function format_LADDaddress ($text, $title)
  {
    //var_dump($text);
    $text = trim(str_replace($title, "", $text));
    $text = preg_replace("/\R/", "", $text);//\r\n
    //$address = trim($text);
    //echo $text.PHP_EOL;
    $address = explode("    ", $text);
    if (sizeof($address) > 1)
    {
      //$address[count($address)-3] = preg_replace("/\W+/", "", $address[count($address)-3]);
      $city_post = $address[count($address)-2];
      $city_post = preg_replace("/\W+/", " ", $city_post);
      $city_post = explode(" ", $city_post);
      array_splice($address, count($address)-2, 1, $city_post);
    }
    $address = array_map("trim", $address);
    $address = implode(", ", $address);
    //echo $address.PHP_EOL;
    // echo PHP_EOL;
    //echo "&nbsp;";
    return $address;
  }

  static private function format_LADDothers($text)
  {
    //var_dump($text);
    $text = preg_replace("/\R/", "*#*", $text);
    $text = preg_replace("/\s{2,}/", "", $text);
    $others = explode("*#*", $text);

    $email_phone = array("", "");
    foreach($others as $v)
    {
      $email_title = "ILL email: ";
      $phone_title = "ILL phone: ";
      if (strpos($v, $email_title) !== false)
      {
        $email_addr = trim(str_replace($email_title, "", $v));
        $email_phone[0] = $email_addr;
        continue;
      }
      
      if (strpos($v, $phone_title) !== false)
      {
        $phone = trim(str_replace($phone_title, "", $v));
        $email_phone[1] = $phone;
        continue;
      }
      // else
      // {
      //   $email_phone["phone"] = "";
      //   continue;
      // }
    }
    // print_r($email_phone);
    // echo PHP_EOL;
    return $email_phone;
    
  }

  static public function DNZL_Data()
  {
    $Data_return = array();
    $CSVcontent = file_get_contents(self::DNZL_MemberURL);
    $filepath = Initial_Partner::init_folder."dnzl_data_".date("Ymd").".csv";
    file_put_contents($filepath, $CSVcontent);
    echo "DNZL data has been stored in $filepath".PHP_EOL;
    
    $content = File_Func::read_CSV($filepath);
    foreach($content as $data)
    {
      //$data = array_splice($data, 0, 3);
      $Data_return[] = array("NLNZ:".$data[0], $data[2], "NLNZ", $data[6]);
    }
    return $Data_return;
  }

  static public function Partner_Init ($content)
  {
    $initdata_path = Initial_Partner::init_folder."init_records_".date("Ymd").".csv";
    //$content = Initial_Partner::read_CSV($filename);
    $init_data = array();
    $init_data[] = array("code", "name", "prefix", "email");
    $init_data = array_merge($init_data, $content);
    // foreach($content as $data)
    // {
    //   $data = array_splice($data, 0, 3);
    //   $init_data[] = $data;
    // }

    File_Func::create_CSV($initdata_path, $init_data);

    return $initdata_path;
  }

  // static public function DNZL_Init($filename)
  // {
    
  // }
}

?>
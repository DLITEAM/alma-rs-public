<?php

require_once 'Initial_Const.php';
require_once 'File_Func.php';

class Contact implements Initial_Const
{
  public const header = array("code", "prefix", "line1", "line2", "line3", "line4", "line5", "city", "state_province", "postal_code", "country", "phone_number", "email_address");

  public $code;
  public $prefix;
  Public $name;

  //address
  public $line1;
  public $line2;
  public $line3;
  public $line4;
  public $line5;
  public $city;
  public $state_province;
  public $postal_code;
  public $country = array("desc" => "",
                          "value" => "");

  //phone
  public $phone_p1;
  public $phone_p2;
  public $phone_p3;
  public $phone_number;

  //email
  public $email_address;

  public static function del_addfile()
  {
    $filename = self::add_partner;
    File_Func::del_file($filename);
  }

  public static function del_contactfile()
  {
    $filename = self::update_contact;
    File_Func::del_file($filename);
  }

  public function saveto_CSV($filename = null)
  {
    if (!$filename)
    {
      $filename = self::add_partner;
    }
    if (!file_exists($filename))
    {
      $file = fopen($filename, "w");
      $field = array("code", "name", "prefix");
      fputcsv($file, $field);
      fclose($file);
    }
    if ($this->prefix !== "NLA" && strpos($this->code, $this->prefix) === false)
    {
      $this->code = $this->prefix.":".$this->code;
    }
    $file = fopen($filename, "a");
    $field = array($this->code, $this->name, $this->prefix);
    fputcsv($file, $field);
    fclose($file);
  }

  public function build_address()
  {

    $this->country['desc'] = self::country_desc[$this->prefix];
    $this->country['value'] = self::country_code[$this->prefix];

    //clean up line1, remove email address and other special chars. if final result is null, then keep original line1
    $email = $this->email_address;
    $line1 = $this->line1;
    if ($email && strpos($line1, $email) !== false)
    {
      $line1 = str_ireplace($email, "", $line1);
      $line1 = str_ireplace("email", "", $line1);
    }
    $line1 = trim(preg_replace("/[^a-zA-Z0-9\s]+/", "", $line1));
    if ($line1)
    {
      $this->line1 = $line1;
    }

    //clean up line2, remove phone number and other special chars
    $line2 = $this->line2;
    $needle = array("ph", "phone");
    $delimiter = "$%#";
    $line2 = str_ireplace($needle, $delimiter, $line2);
    $line2 = explode($delimiter, $line2);
    $this->line2 = trim(preg_replace("/[^a-zA-Z0-9\s]+/", "", $line2[0]));
  }

  public function build_phone()
  {
    if ($this->phone_p2)
    {
      $this->phone_number = self::tel_countrycode[$this->prefix]." ".$this->phone_p1." ".$this->phone_p2;
      if ($this->phone_p3)
      {
        $this->phone_number .= "Ext ".$this->phone_p3;
      }
      //$this->phone_number = ;
    }
  }

  public function build_email()
  {
    $char = "@";
    $text = null;
    if (strpos($this->line1, $char) !== false)
    {
      $text = $this->line1;
    }
    else if (strpos($this->line2, $char) !== false)
    {
      $text = $this->line2;
    }
    if ($text)
    {
      $parts = explode(" ", $text);
      foreach($parts as $val)
      {
        if (strpos($val, $char))
        {
          $this->email_address = trim($val);
        }
      }
    }
  }

  public function contact_toarray()
  {
    $contact = array();
    foreach (self::header as $col)
    {
      $item = $this->$col;
      $contact[] = is_array($item)?implode(" /-/ ", $item):$item;
    }
    return $contact;
  }

  public static function add_location($alma_type)
  {
    $log = new LogClass();
    $log->starttime = date("d/m/Y H:i:s");
    $apikey = self::api_key[$alma_type];
    $initial_template = file_get_contents(self::template_file);
    $ill_port = self::ill_port[$alma_type];
    $initial_fixed = json_decode(file_get_contents(self::template_fixedfile), true);

    $filename = self::add_partner;
    $create_failed = array();
    $create_existed = array();

    if (file_exists($filename))
    {
      $init_values = File_Func::read_CSV($filename);

      foreach ($init_values as $partner)
      {
        $code = trim($partner[0]);
        $name = trim($partner[1]);
        $prefix = trim($partner[2]);
        // $email = trim($partner[3]);

        //comparing exception list, if code on list then move to the next code.
        $except = self::exception_list;
        if (in_array($code, $except[$prefix]))
        {
          continue;
        }

        if ($prefix === "NLA")
        {
          $iso_symbol = $prefix.":".$code;
        }
        else
        {
          $iso_symbol = $code;
        }

        $log->total++;

        //build get request url
        $get_url = self::server_path."/$code"."?apikey=".$apikey;

        //call get function and have return with http code and response
        $get_response = Alma_API::partner_APIget($get_url);

        if (!empty($get_response))
        {
          $get_code = $get_response['code'];
          $get_value = $get_response['resp'];

          //not record, then create a new partner
          if ($get_code === 400)
          {
            //replace record values
            $init_record = sprintf($initial_template, $code, $name, $iso_symbol, $ill_port);
            //replace fixed values
            $init_record = File_Func::array_Replacevalue(json_decode($init_record, true), $initial_fixed);

            //add email to initial data
            // if ($email)
            // {
            //   $init_record["contact_info"]["email"][] = Alma_API::email_Builder($email);
            // }
            //encode in json format
            $init_record = json_encode($init_record);
            // echo $code.PHP_EOL;

            //build post url
            $post_url = self::server_path."?apikey=".$apikey;
            //echo $post_url.PHP_EOL;
            //call post function and have return with http code and response
            $post_response = Alma_API::partner_APIpost($post_url, $init_record);

            if (!empty($post_response))
            {
              $post_code = $post_response['code'];
              $post_value = $post_response['resp'];
              //create sucessfully
              if ($post_code === 200)
              {
                $log->successful++; 
              }
              //error
              else 
              {
                //bad request
                if ($post_code === 400)
                {
                  $log->failed++;
                }
                //Internal Server Error
                else
                {
                  $log->internal_error++;
                }
                $log->error_Log($post_value, json_encode($partner));
                $partner[] = $post_code;
                $create_failed[] = $partner;
              }
              
            }
          }
          //find record in system
          else if ($get_code === 200)
          {
            $log->existing++;
            $create_existed[] = $partner;
            //echo "Find in system: ".$code.PHP_EOL;
          }
          //Internal Server Error
          else
          {
            $log->internal_error++;
            $log->error_Log($get_value, json_encode($partner));
            $partner[] = $get_value;
            $create_failed[] = $partner;
            //echo "Internal Server Error";
          }
        }      
      }
      //print_r($this->init_count);
      
      //$this->progress_Log("Add Initial Partners");
      if (!empty($create_failed))
      {
        array_unshift($create_failed, array("code", "name", "Prefix"));
        File_Func::create_CSV(self::update_folder."add_partner_failed_".date("Y-m-d H.i.s").".csv", $create_failed);
      }
      if (!empty($create_existed))
      {
        // print_r($create_existed);
        // echo PHP_EOL;
        array_unshift($create_existed, array("code", "name", "Prefix"));
        // print_r($create_existed);
        File_Func::create_CSV(self::update_folder."add_partner_existed_".date("Y-m-d H.i.s").".csv", $create_existed);
      }
    }
    $log->endtime = date("d/m/Y H:i:s");
    $log->title = "Adding New Records ($alma_type)";
    return $log;
  }

  public static function update_contact($alma_type)
  {
    $log = new LogClass();
    $log->starttime = date("d/m/Y H:i:s");
    $apikey = self::api_key[$alma_type];
    $non_empty = array("phone" => "phone_number", "email" => "email_address");
    
    //$ill_port = self::ill_port[$alma_type];
    //$initial_fixed = json_decode(file_get_contents(self::template_fixedfile), true);

    $filename = self::update_contact;
    $update_failed = array();
    $not_existed = array();

    if (file_exists($filename))
    {
      $init_values = File_Func::read_CSV($filename);
      foreach ($init_values as $partner)
      {
        $code = trim($partner[0]);
        //$name = trim($partner[1]);
        $prefix = trim($partner[1]);
        $contact_formated = self::format_contact($partner);
        $log->total++;

        //build get request url
        $get_url = self::server_path."/$code"."?apikey=".$apikey;

        //call get function and have return with http code and response
        $get_response = Alma_API::partner_APIget($get_url);

        if (!empty($get_response))
        {
          $get_code = $get_response['code'];
          $get_value = $get_response['resp'];

          //get record, then update the partner
          if ($get_code === 200)
          {
            $record = json_decode($get_value, true);
            // print_r($record);
            // echo PHP_EOL;
            foreach($record["contact_info"] as $key => $val)
            {
              if (empty($val))
              {
                $record["contact_info"][$key] = $contact_formated[$key];
                //continue;
              }
              else
              {
                foreach($val[0] as $k => $v)
                {
                  if (!empty($contact_formated[$key][0][$k]))
                  {
                    $val[0][$k] = $contact_formated[$key][0][$k];
                  }
                }
                $record["contact_info"][$key] = $val;
              }
            }
            // print_r($record);
            // echo PHP_EOL;

            $record = json_encode($record);
            $put_response = Alma_API::partner_APIput($get_url, $record);
            if (!empty($put_response))
            {
              $put_code = $put_response['code'];
              $put_value = $put_response['resp'];
              //create sucessfully
              if ($put_code === 200)
              {
                $log->successful++; 
              }
              //error
              else 
              {
                //bad request
                if ($put_code === 400)
                {
                  $log->failed++;
                }
                //Internal Server Error
                else
                {
                  $log->internal_error++;
                }
                //echo $put_value.PHP_EOL;
                $log->error_Log($put_value, $code);
                // $result = false;
                $partner[] = $put_value;
                $update_failed[] = $partner;
              }
            }
            
          }
          //not find record in system
          else if ($get_code === 400)
          {
            $log->failed++;
            $log->error_Log($get_value, json_encode($partner));
            $not_existed[] = $partner;
            //echo "Find in system: ".$code.PHP_EOL;
          }
          //Internal Server Error
          else
          {
            $log->internal_error++;
            $log->error_Log($get_value, json_encode($partner));
            $partner[] = $get_value;
            $update_failed[] = $partner;
            //echo "Internal Server Error";
          }
        }      

      }
      if (!empty($update_failed))
      {
        array_unshift($update_failed, self::header);
        File_Func::create_CSV(self::update_folder."update_contact_failed_".date("Y-m-d H.i.s").".csv", $update_failed);
      }
      if (!empty($not_existed))
      {
        // print_r($create_existed);
        // echo PHP_EOL;
        array_unshift($not_existed, self::header);
        // print_r($create_existed);
        File_Func::create_CSV(self::update_folder."update_contact_notexisted_".date("Y-m-d H.i.s").".csv", $not_existed);
      }
    }
    $log->endtime = date("d/m/Y H:i:s");
    $log->title = "Updating Contact Details ($alma_type)";
    return $log;
  }

  public static function format_contact($contact)
  {
    $contact_template = json_decode(file_get_contents(self::initial_contacttemplate), true);
    $contact_formated = array();
    foreach(self::header as $key => $val)
    {
      if ($val === "country")
      {
        $country = explode(" /-/ ", $contact[$key]);
        $contact_formated[$val] = array("desc" => $country[0],
                                        "value" => $country[1]);
      }
      else
      {
        $contact_formated[$val] = $contact[$key];
      }
    }

    $contact_formated = File_Func::array_Replacevalue($contact_template, $contact_formated);
    if (!$contact_formated["phone"][0]["phone_number"])
    {
      $contact_formated["phone"] = array();
    }
    if (!$contact_formated["email"][0]["email_address"])
    {
      $contact_formated["email"] = array();
    }
    // print_r($contact_formated);
    // echo PHP_EOL;
    return $contact_formated;
  }
}
?>
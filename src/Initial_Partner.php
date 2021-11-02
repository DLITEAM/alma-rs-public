<?php
/*
 * This file defines the class that initiate Partner's record for Alma Resources Sharing
 *  
 */

//require Initail_Const interface
require_once('Initial_Const.php');
require_once('Alma_API.php');
require_once('File_Func.php');
require_once('LogClass.php');

date_default_timezone_set('Australia/Sydney');

class Initial_Partner implements Initial_Const
{
  //store initial template record
  private $initial_template;
  //store initial fixed value
  private $initial_fixed;
  //store port number
  private $ill_port;
  //store API key
  private $apikey;
  //
  private $alma_type, $pro_type, $partner_code;
  //
  private $initlog;
  
  //class constructer
  public function __construct($variable)
  {
    $this->initial_template = file_get_contents(self::template_file);
    $this->initial_fixed = json_decode(file_get_contents(self::template_fixedfile), true);
    $this->ill_port = self::ill_port[$variable["Alma"]];
    $this->apikey = self::api_key[$variable["Alma"]];
    $this->pro_type = $variable["Progress"];
    $this->partner_code = $variable["Code"];
    $this->alma_type = $variable["Alma"];
    $this->initlog = new LogClass();
        
  }

  // private function error_Log($error_msg, $code)
  // {
  //   $file = fopen($this->error_logfile, "a");
  //   fwrite($file, $code.PHP_EOL);
  //   fwrite($file, $error_msg.PHP_EOL);
  //   fwrite($file, PHP_EOL);
  //   fclose($file);
  // }

  // Private function log_Create($title)
  // {
  //   $filename = self::template_log;
  //   if (file_exists($filename))
  //   {
  //     $content = file_get_contents(self::template_log);
  //     $content = sprintf($content, $title, $this->username, $this->starttime, $this->endtime);
  //     foreach ($this->init_count as $key => $value)
  //     {
  //       $content .= $key.": ".$value."; ";
  //     }
  //     $content .= PHP_EOL;
  //     if (file_exists($this->error_logfile))
  //     {
  //       $content .= "Error Log: ".basename($this->error_logfile).PHP_EOL;
  //     }
  //     $content .= $this->error_str.PHP_EOL;
  //   }
  //   else
  //   {
  //     $content = "Please create a log template file first.".PHP_EOL;
  //   }
  //   return $content;
  // }

  // private function progress_Log($title)
  // {
  //   $file = fopen($this->progress_logfile, "a");

  //   $content = "";

  //   if ($title)
  //   {
  //     $content = $this->log_Create($title);
  //   }
            
  //   fwrite($file, $content);
  //   fwrite($file, PHP_EOL);
  //   //
  //   fclose($file);
  //   return $content;

  // }

  private function init_Email($content)
  {
    $filename = self::template_email;
    if (file_exists($filename))
    {
      $email_t = file_get_contents($filename);
      //$email_t = sprintf($email_t, $content);
      //echo $email_t;
      $email_t = json_decode($email_t, true);
      // if ($email_t)
      // {
      //   print_r($email_t);
      // }
      // else
      // {
      //   echo "error".PHP_EOL;
      // }
      // print_r($email_t["Headers"]);
      // echo PHP_EOL;
      if (mail($email_t["To"], sprintf($email_t["Subject"], $this->alma_type), sprintf($email_t["Body"], $content), $email_t["Headers"]))
      {
        echo "email sent".PHP_EOL;
      }
      else
      {
        echo "error".PHP_EOL;
      }
    }
    else
    {
      echo "Please create a email template file first.";

    }
  }

  //Create partner records in Alma record sharing
  private function init_Addrecord ()
  {

    //load partners' value
    $filename = self::init_csv;
    $init_values = File_Func::read_CSV($filename);
    
    $create_failed = array();
    $create_existed = array();
    //$create_failed[] = ["code", "name", "prefix"];

    //for each partner record, check the record in Alma based on code.
    //if not existing, create a new partner record. 
    foreach ($init_values as $partner)
    {
      $code = trim($partner[0]);
      $name = trim($partner[1]);
      $prefix = trim($partner[2]);
      $email = trim($partner[3]);
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

      $this->initlog->total++;

      //build get request url
      $get_url = self::server_path."/$code"."?apikey=".$this->apikey;

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
          $init_record = sprintf($this->initial_template, $code, $name, $iso_symbol, $this->ill_port);
          //replace fixed values
          $init_record = File_Func::array_Replacevalue(json_decode($init_record, true), $this->initial_fixed);

          //add email to initial data
          if ($email)
          {
            $init_record["contact_info"]["email"][] = Alma_API::email_Builder($email);
          }
          //encode in json format
          $init_record = json_encode($init_record);
          //echo $code.PHP_EOL;

          //build post url
          $post_url = self::server_path."?apikey=".$this->apikey;
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
              $this->initlog->successful++; 
            }
            //error
            else 
            {
              //bad request
              if ($post_code === 400)
              {
                $this->initlog->failed++;
              }
              //Internal Server Error
              else
              {
                $this->initlog->internal_error++;
              }
              $this->initlog->error_Log($post_value, $code);
              $create_failed[] = $partner;
            }
            
          }
        }
        //find record in system
        else if ($get_code === 200)
        {
          $this->initlog->existing++;
          $create_existed[] = $partner;
          //echo $response.PHP_EOL;
        }
        //Internal Server Error
        else
        {
          $this->initlog->internal_error++;
          $this->initlog->error_Log($get_value, $code);
          //echo "Internal Server Error";
        }
      }      
    }
    //print_r($this->init_count);
    
    //$this->progress_Log("Add Initial Partners");
    if (!empty($create_failed))
    {
      array_unshift($create_failed, array("code", "name", "Prefix", "email"));
      File_Func::create_CSV(self::init_folder."partner_failed_".date("Y-m-d H.i.s").".csv", $create_failed);
    }
    if (!empty($create_existed))
    {
      array_unshift($create_existed, array("code", "name", "Prefix", "email"));
      File_Func::create_CSV(self::init_folder."partner_existed_".date("Y-m-d H.i.s").".csv", $create_existed);
    }
  }

  private function init_Delrecord($code)
  {
    if ($code)
    {
      $del_array[] = array($code);
    }
    else
    {
      $del_file = self::init_folder."del_records.csv";
      if (file_exists($del_file))
      {
        $del_array = File_Func::read_CSV($del_file);
      }
      else
      {
        throw new Exception("not partner code or del file exists");
      }
      
    }
    foreach($del_array as $del_record)
    {
      $del = trim($del_record[0]);
      $this->initlog->total++;
      $del_url = self::server_path."/$del"."?apikey=".$this->apikey;
      //echo $del_url.PHP_EOL;
      $del_response = Alma_API::partner_APIdel($del_url);

      if (!empty($del_response))
      {
        //print_r($del_response);
        //echo PHP_EOL;
        $del_code = $del_response['code'];
        $del_value = $del_response['resp'];
        //create sucessfully
        //echo $del_code.PHP_EOL;
        if ($del_code === 204)
        {
          $this->initlog->successful++; 
        }
        //error
        else 
        {
          //bad request
          if ($del_code === 400)
          {
            $this->initlog->failed++;
          }
          //Internal Server Error
          else
          {
            $this->initlog->internal_error++;
          }
          $this->initlog->error_Log($del_value, $del);
        }
      }
    }
  }

  private function init_Test()
  {

    $code = $this->partner_code;
    //$code = "AWVH%20";
    echo $code.PHP_EOL;
    //build get request url
    $get_url = self::server_path."/$code"."?apikey=".$this->apikey;
    $get_response = Alma_API::partner_APIget($get_url);

    if (!empty($get_response))
    {
      $get_code = $get_response['code'];
      $get_value = $get_response['resp'];

      //not record, then create a new partner
      if ($get_code === 200)
      {
        echo "exist".PHP_EOL;
      }
      else
      {
        echo "not exist".PHP_EOL;
      }
      $record_json = json_decode($get_value, true);
      print_r($record_json);
    }
    // //replace record values
    // $init_record = sprintf($this->initial_template, "ANL", "National Library of Australia", "NLA", $this->ill_port);
    // //replace fixed values
    // $init_record = File_Func::array_Replacevalue(json_decode($init_record, true), $this->initial_fixed);
    // //encode in json format
    // $init_record = json_encode($init_record);
    // //echo $code.PHP_EOL;

    // //build post url
    // $post_url = self::server_path."?apikey=".$this->apikey;
    // //echo $post_url.PHP_EOL;
    // //call post function and have return with http code and response
    // $post_response = Alma_API::partner_APIpost($post_url, $init_record);

    // if (!empty($post_response))
    // {
    //   $post_code = $post_response['code'];
    //   $post_value = $post_response['resp'];
    //   //create sucessfully
    //   if ($post_code === 200)
    //   {
    //     $this->initlog->successful++; 
    //   }
    //   //error
    //   else 
    //   {
    //     //bad request
    //     if ($post_code === 400)
    //     {
    //       $this->initlog->failed++;
    //     }
    //     //Internal Server Error
    //     else
    //     {
    //       $this->initlog->internal_error++;
    //     }
    //     $this->initlog->error_Log($post_value, "ANL");
    //     $create_failed[] = array("ANL", "National Library of Australia", "NLA");
    //   }
      
    // }
    // if (!empty($create_failed))
    // {
    //   File_Func::create_CSV(self::init_folder."partner_failed_".date("Y-m-d H.i.s").".csv", $create_failed);
    // }
  }

  public function output_Init ()
  {
    try 
    {
      $this->initlog->starttime = date("d/m/Y H:i:s");
      if ($this->pro_type === "ADD")
      {
        $this->init_Addrecord();
      }
      else if ($this->pro_type === "DEL")
      {
        $this->init_Delrecord($this->partner_code);
      }
      else if ($this->pro_type === "TEST")
      {
        $this->init_Test();
      }

    }
    //catch error
    catch (Exception $e)
    {
      $this->initlog->error_str .= $e->getMessage();
    }
    finally
    {
      //end progress time
      $this->initlog->endtime = date("d/m/Y H:i:s");
      //add to log file
      $this->initlog->title = $this->pro_type." Initial Partners";
      $datetime = date("Y-m-d H.i.s");
      $this->initlog->progress_logfile .= "initialload_log_".$datetime.".log";
      $content = $this->initlog->progress_Log();
      //email
      LogClass::init_Email($content, $this->alma_type);
      echo $content;
    }
    //printf($this->initial_record);
    //echo $this->ill_port;
    //print_r(dirname(__DIR__));
  }

}

?>
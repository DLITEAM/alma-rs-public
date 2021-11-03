<?php

require_once 'Initial_Const.php';
require_once "Initial_Partner.php";
require_once "LogClass.php";
require_once "File_Func.php";

class Suspension implements Initial_Const{

  const status_list = array("a" => "active", "i" => "inactive", "p" => "preactive");
  public const header = array("code", "prefix", "received_date", "update_date", "status", "start", "end", "note");
    
  public $code;
  public $prefix;
  public $received_date;
  public $update_date;
  public $status;
  public $start;
  public $end;
  public $note;

  public function __construct($suspension = null)
	{
		if(isset($suspension) && $suspension instanceof Suspension)
		{
      $this->code          = $suspension->code;
      $this->prefix        = $suspension->prefix;
      $this->received_date = $suspension->received_date;
      $this->update_date   = $suspension->update_date;
			$this->status	 		   = $suspension->status;
			$this->start 		     = $suspension->start;
			$this->end 			     = $suspension->end;
			$this->note 			   = $suspension->note;
    }
    else if (isset($suspension) && is_array($suspension))
    {
      $this->code          = $suspension[0];
      $this->prefix        = $suspension[1];
      $this->received_date = $suspension[2];
      $this->update_date   = $suspension[3];
			$this->status	 		   = $suspension[4];
			$this->start 		     = $suspension[5];
			$this->end 			     = $suspension[6];
			$this->note 			   = $suspension[7];
    }
  }

  public function display_Susension()
  {
    echo $this->code.PHP_EOL;
    echo $this->prefix.PHP_EOL;
    echo $this->received_date.PHP_EOL;
    echo $this->update_date.PHP_EOL;
    echo $this->status.PHP_EOL;
    echo $this->start.PHP_EOL;
    echo $this->end.PHP_EOL;
    echo $this->note.PHP_EOL;
  }

  public static function get_Suspensions()
  {
    $filename = self::update_suspension;
    $list = File_Func::read_CSV($filename);
    foreach($list as $v)
    {
      $suspension = new Suspension($v);
      $return_list[] = $suspension;
    }
    return $return_list;
  }

  public static function toarray_Suspension($suspension = null)
  {
    if(isset($suspension) && $suspension instanceof Suspension)
		{
      return array($suspension->code, $suspension->prefix, $suspension->received_date, $suspension->update_date, $suspension->status, $suspension->start, $suspension->end, $suspension->note);
    }
  }
  
  public function saveto_CSV($filename = null)
  {
    if (!$filename)
    {
      $filename = self::update_suspension;
    }
    if (!file_exists($filename))
    {
      $file = fopen($filename, "w");
      $field = array("code", "prefix", "received_date", "update_date", "status", "start", "end", "note");
      fputcsv($file, $field);
      fclose($file);
    }
    if ($this->prefix !== "NLA" && strpos($this->code, $this->prefix) === false)
    {
      $this->code = $this->prefix.":".$this->code;
    }
    $file = fopen($filename, "a");
    $field = array($this->code, $this->prefix, $this->received_date, $this->update_date, $this->status, $this->start, $this->end, $this->note);
    fputcsv($file, $field);
    fclose($file);
  }

  public function suspension_toarray()
  {
    return array($this->code, $this->prefix, $this->received_date, $this->update_date, $this->status, $this->start, $this->end, $this->note);
  }

  private function check_Suspension()
  {
    $start_s = strtotime("00:00:00 ".$this->start);
    $end_s = strtotime("23:59:59 ".$this->end);
    $now_s = strtotime("now");
    $update = false;
    if ($start_s <= $now_s && $end_s >= $now_s)
    {
      $this->status = self::status_list['a'];
      $update = true;
    }
    else if ($end_s < $now_s)
    {
      $update = true;
      $this->status = self::status_list['i'];
    }
    //echo "1234".PHP_EOL;
    return $update;
  }

  public static function del_updatefile()
  {
    $filename = self::update_suspension;
    File_Func::del_file($filename);
  }

  public static function update_Suspensions($alma_type)
  {
    $filename = self::update_suspension;
    $log = new LogClass();
    if (file_exists($filename))
    {
      $list = File_Func::read_CSV($filename);

      $suspension_list = array();
      $suspension_bycode = array();
      //$merged_suspension = array();
      $order = 0;

      //get records and put to array()
      foreach($list as $v)
      {
        $suspension = new Suspension($v);
        $suspension_list[] = $suspension;
        $suspension_bycode[$suspension->code][$order] = $suspension;
        $order++;
      }
      //merge records if their periods of suspension are overlap
      //alway trust latest email as true source for end date.
      foreach($suspension_bycode as $sublist)
      {
        
        while (count($sublist) > 1)
        {
          end($sublist);
          $key_last = key($sublist);
          $val_last = array_pop($sublist);
          $reverse = array_reverse($sublist);
          // echo $key_last.PHP_EOL;
          // print_r($val_last);
          // echo PHP_EOL;
          foreach($reverse as $k => $v)
          {
            // print_r($v);
            // echo PHP_EOL;
            $last_start = $val_last->start;
            $last_end = $val_last->end;
            $v_start = $v->start;
            $v_end = $v->end;
            if (strtotime($last_start)<=strtotime($v_end) && strtotime($last_end) >=strtotime($v_start))
            {
              $v->start = strtotime($last_start)<=strtotime($v_start)?$last_start:$v_start;
              $v->end = $last_end;
              $v->update_date = $val_last->update_date;
              $v->note = $v->note." || ".$val_last->note;
              // print_r($v);
              // echo $key_last;
              // echo PHP_EOL;
              unset($suspension_list[$key_last]);
              break;
            }
          } 
        }
      }

      //merge end

      // print_r($suspension_list);
      // echo PHP_EOL;

      Suspension::del_updatefile();
      
      
      $log->starttime = date("d/m/Y H:i:s");

      $suspension_fail = array();

      foreach($suspension_list as $suspension)
      {
        
        $result = true;
        if ($suspension->check_Suspension())
        {
          $log->total++;
          // echo PHP_EOL;
          // $this->display_Susension();

          // //build get request url
          $get_url = self::server_path."/".$suspension->code."?apikey=".self::api_key[$alma_type];

          //call get function and have return with http code and response
          $get_response = Alma_API::partner_APIget($get_url);
          if (!empty($get_response))
          {
            $get_code = $get_response['code'];
            $get_value = $get_response['resp'];
            // print_r($get_value);
            // echo PHP_EOL;

            //existing record, update
            if ($get_code === 200)
            {
              $record_json = json_decode($get_value, true);
              //inactive partner if in suspension period     
              if ($suspension->status === self::status_list['a'])
              {
                $record_json["partner_details"]["status"] = strtoupper(self::status_list['i']);
                $record_note = $suspension->note;
                if (strlen($record_note)>255)
                {
                  $record_note = substr($record_note, -255);
                }
                $record_json["note"][]["content"] = $record_note;
              }
              //active partner if out of suspension period
              else if ($suspension->status === self::status_list['i'])
              {
                $record_json["partner_details"]["status"] = strtoupper(self::status_list['a']);
                //$record_json["note"][] = $this->note;
              }

              $record_json = json_encode($record_json);
              // print_r($record_json);
              // echo PHP_EOL;
              //call APIput function to update record
              $put_response = Alma_API::partner_APIput($get_url, $record_json);
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
                  $fail = $suspension->suspension_toarray();
                  $log->error_Log($put_value, json_encode($fail));
                  $fail[] = $put_value;
                  $suspension_fail[] = $fail;
                  $result = false;
                  //$create_failed[] = $partner;
                }
              }
            }
            else
            {
              if ($get_code === 400)
              {
                $log->failed++;
              }
              else
              {
                $log->internal_error++;
              }
              $fail = $suspension->suspension_toarray();
              $log->error_Log($get_value, json_encode($fail));
              $fail[] = $get_value;
              $suspension_fail[] = $fail;
              $result = false;
            }
          }
        }  
        
        //put inactive record to inactive_suspension.csv
        if ($result && $suspension->status === self::status_list['i'])
        {
          File_Func::saveto_CSV(self::suspend_inactive, self::header, $suspension->suspension_toarray());
        }
        else
        {
          File_Func::saveto_CSV(self::update_suspension, self::header, $suspension->suspension_toarray());
        }

      }

      if (!empty($suspension_fail))
      {
        array_unshift($suspension_fail, self::header);
        File_Func::create_CSV(self::update_folder."update_suspension_failed_".date("Y-m-d H.i.s").".csv", $suspension_fail);
      }
    }
    $log->endtime = date("d/m/Y H:i:s");
    $log->title = "Updating Suspensions ($alma_type)";
    return $log;

  }

}
?>
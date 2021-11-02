<?php

require_once 'Initial_Const.php';
class LogClass implements Initial_Const{

  //for progress log file
  public $total;
  public $successful;
  public $failed;
  public $existing;
  public $internal_error;
  public $starttime;
  public $endtime;
  public $user;
  public $progress_logfile;
  public $type;
  Public $title;

  //for error log file
  public $error_logfile;
  public $error_str;

  //log template file
  public $log_template;

  public function __construct($logclass = null)
	{
		if(isset($logclass) && $logclass instanceof LogClass)
		{
      $this->total = $logclass->total;
      $this->successful = $logclass->successful;
      $this->failed = $logclass->failed;
      $this->existing = $logclass->existing;
      $this->internal_error = $logclass->internal_error;
      $this->starttime = $logclass->starttime;
      $this->endtime = $logclass->endtime;
      $this->user = $logclass->user;
      $this->progress_logfile = $logclass->progress_logfile;
      $this->type = $logclass->type;
      $this->title = $logclass->title;
      $this->error_logfile = $logclass->error_logfile;
      $this->error_str = $logclass->error_str;
      $this->log_template = $logclass->log_template;
    }
    else
    {
      $this->total = 0;
      $this->successful = 0;
      $this->failed = 0;
      $this->existing = 0;
      $this->internal_error = 0;
      $env = getenv();
      $this->user = $env['USER'];
      $this->progress_logfile = self::log_path;
      $this->error_logfile = self::log_path."error_log_".date("Y-m-d H.i.s").".log";
      $this->log_template = self::template_log;
    }
  }

  public function error_Log($error_msg, $code)
  {
    $file = fopen($this->error_logfile, "a");
    fwrite($file, $code.PHP_EOL);
    fwrite($file, $error_msg.PHP_EOL);
    fwrite($file, PHP_EOL);
    fclose($file);
  }

  public function log_Create()
  {
    if (file_exists($this->log_template))
    {
      $content = file_get_contents($this->log_template);
      $content = sprintf($content, $this->title, $this->user, $this->starttime, $this->endtime);

      if ($this->type !== "email")
      {
        $content .= "Total: ".$this->total."; Successful: ".$this->successful."; Failed: ".$this->failed."; Existing: ".$this->existing."; Internal Error: ".$this->internal_error.";";
        $content .= PHP_EOL;
        if (file_exists($this->error_logfile))
        {
          $content .= PHP_EOL."Error Log File: ".basename($this->error_logfile);
          $content .= PHP_EOL.file_get_contents($this->error_logfile).PHP_EOL;
        }
      }
      
      $content .= $this->error_str.PHP_EOL;
    }
    else
    {
      $content = "Please create a log template file first.".PHP_EOL;
    }
    return $content;
  }

  public function progress_Log()
  {
    $file = fopen($this->progress_logfile, "a");

    $content = "";

    if ($this->title)
    {
      $content = $this->log_Create();
    }
            
    fwrite($file, $content);
    fwrite($file, PHP_EOL);
    //
    fclose($file);
    return $content;

  }

  public static function init_Email($content, $alma_type)
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
      if (mail($email_t["To"], sprintf($email_t["Subject"], $alma_type), sprintf($email_t["Body"], $content), $email_t["Headers"]))
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
}
?>
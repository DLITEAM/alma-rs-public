<?php
require_once "ProcessEmail.php";
require_once "Suspension.php";
require_once "LogClass.php";
require_once "Contact.php";

date_default_timezone_set('Australia/Sydney');

$options = "a:";
$Alma_type = array("UAT", "PROD");
$type = "UAT";

$input = getopt($options);

if (!empty($input))
{
  try
  {
    //handle input options
    foreach ($input as $k => $v)
    {
      if ($k === "a")
      {
        //echo $v;
        if (in_array($v, $Alma_type))
        {
          $type = $v;
          //$this_type = $v;
        }
        else
        {
          echo "Unexpected input for option -a: UAT or PROD".PHP_EOL;
          throw new Exception("Unexpected input for option -a: UAT or PROD".PHP_EOL);
        }
      }
    }

    $log_list = array();
    //process email from gmail account
    // Contact::del_addfile();
    // Contact::del_contactfile();
    $pEmail = new ProcessEmail();
    $log_email = $pEmail->process();
    $log_list[] = $log_email;
    
    //process adding new partners.
    $log_add = Contact::add_location($type);
    $log_list[] = $log_add;
    Contact::del_addfile();

    //process updating contact details
    $log_update = Contact::update_contact($type);
    $log_list[] = $log_update;
    Contact::del_contactfile();


    //update suspension information to Alma partner records
    $log_suspension = Suspension::update_Suspensions($type);
    $log_list[] = $log_suspension;

  }
  catch (Exception $e)
  {
    $log->error_str .= $e->getMessage();
  }
  finally
  {
    $log_content = "";
    $datetime = date("Y-m-d H.i.s");
    foreach($log_list as $log_v)
    {
      $log_v->progress_logfile .= "update_log_".$datetime.".log";
      $log_content .= $log_v->progress_Log();
    }
    LogClass::init_Email($log_content, $type);
    echo $log_content.PHP_EOL;
  }
}
else
{
  echo "php Update_Progress.php [options]".PHP_EOL;
  echo "Options:".PHP_EOL;
  echo "  -a      Alma Type. Value: UAT or PROD - default value UAT".PHP_EOL;
}

?>
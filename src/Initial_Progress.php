<?php
require_once("Initial_Partner.php");

$options = "a:p:c:t";
$Alma_type = array("UAT", "PROD");
$progress = array("ADD", "DEL", "GET");

$variable = array("Alma" => "UAT", "Progress" => "ADD", "Code" => "");

//$this_type = "UAT";
//$this_pro = "ADD";

$input = getopt($options);
//print_r($input);
if (!empty($input))
{
  foreach ($input as $k => $v)
  {
    if ($k === "a")
    {
      //echo $v;
      if (in_array($v, $Alma_type))
      {
        $variable["Alma"] = $v;
        //$this_type = $v;
      }
      else
      {
        echo "Unexpected input for option -a: UAT or PROD".PHP_EOL;
      }
    }
    else if ($k === "p")
    {
      if (in_array($v, $progress))
      {
        $variable["Progress"] = $v;
        //$this_pro = $v;
      }
      else
      {
        echo "Unexpected input for option -p: ADD or DEL".PHP_EOL;
      }
    }
    else if($k === "c")
    {
      $variable["Code"] = $v;
    }
    else if ($k === 't')
    {
      $variable["Progress"] = "TEST";
    }
  }

  //$variable["Alma"] = "UAT";

  //$this_type = "UAT";
  //$this_pro = "ADD";

  $partner = new Initial_Partner($variable);
  $partner->output_Init();

}
else
{
  echo "php Initial_Progress.php [options]".PHP_EOL;
  echo "Options:".PHP_EOL;
  echo "  -a      Alma Type. Value: UAT or PROD - default value UAT".PHP_EOL;
  echo "  -p      Progress Type. Value: ADD or DEL - default value ADD".PHP_EOL;
  echo "  -c      Partner's Code.".PHP_EOL;
  //echo "  -t      Test Mode - default value UAT and ADD";
}

?>
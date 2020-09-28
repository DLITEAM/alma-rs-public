<?php
class File_Func {
  //read initial partners' values from CSV file
  static public function read_CSV ($filename)
  {
    $values = array();
    //echo $filename.PHP_EOL;

    if (!file_exists($filename))
    {
      throw new Exception("File is not existed: ".$filename);
    }

    if (($readfile = fopen($filename, "r")) !== false)
    {
      $i=0;
      while (($data = fgetcsv($readfile, 0, ",")) !== false)
      {
        if (!trim($data[0]) || $i === 0)
        {
          //echo $data[1];
          $i++;
          continue;
        }
        $values[] = $data;
      }

      fclose($readfile);
    }
    return $values;
  }

  static public function create_CSV($filename, $content)
  {
    // if (!file_exists($filename))
    // {
    //   throw new Exception("File is not existed: ".$filename);
    // }
    $file = fopen($filename, "w");
    foreach ($content as $field)
    {
      fputcsv($file, $field);
    }
    fclose($file);
  }

  public static function del_file($filename)
  {
    //$filename = self::update_suspension;
    if (file_exists($filename))
    {
      $copyname = $filename.date("Y-m-d H.i.s");
      if (copy($filename, $copyname))
      {
        unlink($filename);
      }
      else
      {
        throw new Exception("Cannot create a backup of file: ".$filename);
      }
    }
  }

  public static function saveto_CSV($filename, $header, $field)
  {
    if (!file_exists($filename))
    {
      $file = fopen($filename, "w");
      //$field = array("code", "name", "prefix");
      fputcsv($file, $header);
      fclose($file);
    }
    $file = fopen($filename, "a");
    //$field = array($this->code, $this->name, $this->prefix);
    fputcsv($file, $field);
    fclose($file);
  }

  //function to go through target array, search match key and value and replace with value in $array_value
  static public function array_Replacevalue($array_target, $array_value)
  {
    foreach ($array_target as $key => $val)
    {
      if (array_key_exists($key, $array_value))
      {
        
        $array_target[$key] = $array_value[$key];
        
      }
      else if (is_array($val))
      {
        $array_target[$key] = self::array_Replacevalue($val, $array_value);
      }
    }
    return $array_target;
  }
}
?>
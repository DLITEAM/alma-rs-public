<?php

require_once 'Initial_Const.php';

Class Alma_API implements Initial_Const
{
  static public function partner_APIget($get_url)
  {
    $conn_get = curl_init($get_url);
    curl_setopt($conn_get, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($conn_get, CURLOPT_HTTPHEADER, self::get_header);
    $get_response = curl_exec($conn_get);

    $response = array();
    
    if (!curl_errno($conn_get))
    {
      $get_code = curl_getinfo($conn_get, CURLINFO_HTTP_CODE);
      $response['code'] = $get_code;
      $response['resp'] = $get_response;
    }
    else
    {

    }

    curl_close($conn_get);
    
    return $response;
  }

  static public function partner_APIpost($post_url, $post_partner)
  {
    $conn_post = curl_init($post_url);
    curl_setopt($conn_post, CURLOPT_POST, 1);
    curl_setopt($conn_post, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($conn_post, CURLOPT_HTTPHEADER, self::http_header);
    curl_setopt($conn_post, CURLOPT_POSTFIELDS, $post_partner);
    $post_response = curl_exec($conn_post);
    //$post_response = curl_exec($conn_post);
    $response = array();

    if (!curl_errno($conn_post))
    {
      $post_code = curl_getinfo($conn_post, CURLINFO_HTTP_CODE);
      //echo $post_code.PHP_EOL;
      $response['code'] = $post_code;
      $response['resp'] = $post_response;
    }
    else
    {

    }

    curl_close($conn_post);

    return $response;
  }

  static public function partner_APIput($put_url, $put_data)
  {
    $conn_put = curl_init($put_url);
    curl_setopt($conn_put, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($conn_put, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($conn_put, CURLOPT_HTTPHEADER, self::http_header);
    curl_setopt($conn_put, CURLOPT_POSTFIELDS, $put_data);
    $put_response = curl_exec($conn_put);
    $response = array();

    if (!curl_errno($conn_put))
    {
      $put_code = curl_getinfo($conn_put, CURLINFO_HTTP_CODE);
      //echo $post_code.PHP_EOL;
      $response['code'] = $put_code;
      $response['resp'] = $put_response;
    }
    else
    {

    }

    curl_close($conn_put);

    return $response;
  }

  static public function partner_APIdel($del_url)
  {
    $conn_del = curl_init($del_url);
    curl_setopt($conn_del, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($conn_del, CURLOPT_CUSTOMREQUEST, "DELETE");
    $del_response = curl_exec($conn_del);
    if (!curl_errno($conn_del))
    {
      $del_code = curl_getinfo($conn_del, CURLINFO_HTTP_CODE);
      $response['code'] = $del_code;
      $response['resp'] = $del_response;
      //print_r($response);
    }
    else
    {

    }

    curl_close($conn_del);
    return $response;
  }

  public static function email_Builder($email)
  {
    $contact_template = json_decode(file_get_contents(self::initial_contacttemplate), true);
    $email_template = $contact_template["email"][0];
    $email_template["email_address"] = $email;
    return $email_template;
    //print_r($email_template);
  }

  public static function code_convert($code, $prefix)
  {
    if ($prefix !== "NLA" && strpos($code, $prefix) === false)
    {
      $code = $prefix.":".$code;
    }

    return $code;
  }
}
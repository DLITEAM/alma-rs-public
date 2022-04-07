<?php
/*
*This file define a interface which contain all constant values.
*
*
*
*/

define('ROOT_PATH', dirname(__DIR__) . '/');
date_default_timezone_set('Australia/Sydney');
interface Initial_Const
{

  /***************************************************************************
   * Parameters for all purposes: 
   * API
   * Folder path
   * File path
   **************************************************************************/
  //API server url
  const server_path = "https://api-ap.hosted.exlibrisgroup.com/almaws/v1/partners";
  //Port number for UAT and PROD
  const ill_port = array('UAT' => '1612', 'PROD' => '1611');
  //Get header: for JSON file
  const get_header = array('Accept: application/json');
  //Post header: for JSON file
  const http_header  = array('Accept: application/json', 'Content-Type: application/json');
  //API key for UAT and PROD
  const api_key = array('UAT' => "Enter the API Key for UAT here",
                        'PROD' => "Enter the API Key for PROD here");
  //root path
  const root = ROOT_PATH;
  //data path
  //const data_path = self::root."scr/data/";
  //template path
  const template_path = self::root."src/template/";
  //log path
  const log_path = self::root."src/log/";
  //log file template
  const template_log = self::template_path."progress_logtemplate.log";
  //progress log file
  const progress_log = self::log_path."progress_log.log";
  //email template
  const template_email = self::template_path."Initial_EmailTemplate.json";

  const initial_contacttemplate = self::template_path."Initial_ContactTemplate.json";

  //exception list of the librareis. those libraries will be excluded when create and update records.
  Const exception_list = array("NLA" => array("NU:CA", "NU:CU", "NU:HS", "NU:LA", "NU:MD", "NU:NR", "NU:ST"),
                               "NLNZ" => array());
  


  /*************************************************************************
   * Parameter for Initial partner load.
   * Json template and input files
   * 
   */
  //Initial partner record template file path
  const template_file = self::template_path."Initial_PartnerTemplate.json";
  //Initial partner record: fixed value in record
  const template_fixedfile = self::template_path."Initial_PartnerFixed.json";
  //File path for initial loading data
  const init_folder = self::root."src/data/initial/";
  const init_csv = self::init_folder."init_records.csv";
  

  /***********************************************************************
   * Parameter for Update partner records
   * 
   */
  //--debugging
  const verbose = false;

  //files
  const update_folder = self::root."src/data/update/";
  const suspend_inactive = self::update_folder."inactive_suspension.csv";
  const add_partner = self::update_folder."add_partners.csv";
  const update_suspension = self::update_folder."update_suspension.csv";
  const update_contact = self::update_folder."update_contact.csv";
  const partnerrecord_current = self::update_folder."partner_record_current.csv";
  
  //--imap email settings (gmail) //UNSW using Gmail account
  const email_domain   = '{imap.gmail.com:993/imap/ssl}INBOX';
  const email_address  = 'youraddress@gmail.com';
  const email_password = 'yourpassword';

  //Gmail setting
  const default_label = "INBOX";
  const processed_label = "processed";
  const ignore_label = "ignore";
  const other_label = "other";

  const expect_subject = "ISO-ILL Location Updates";
  const expect_source = "";
  const expect_content = array("ADD_LOCATION", //add a new library record if not existing
                               "STATUS",       //suspension update
                               "UPDATE");      //contact update
  const encode_delimiter = "Content-Transfer-Encoding: base64";

  const country_code = array("NLA" => "AUS", "NLNZ" => "NZL");
  const country_desc = array("NLA" => "Australia", "NLNZ" => "New Zealand");
  const tel_countrycode = array("NLA" => "61", "NLNZ" => "64");

}
?>
<?php
  class CloakerlyChecker {
    public $key = null;
    public $strictness = 0;
    public $user_agent = false;
    public $failure_redirect = null;
    public $success_redirect = null;
    public $campaign = null;
    public $referrer = null;
    const BASE_API_URL = "https://app.cloakerly.com/v2/integration/check/%s/%s/%s";

    public function SetKey($key = null){
      $this->key = $key;
    }

    public function SetCampaign($campaign=null){
      $this->campaign = $campaign;
    }

    public function SetStrictness($value = 0){
      $this->strictness = $value;
    }
    public function PassUserAgent($value = false){
      $this->user_agent = $value;
    }

    public function SetFailureRedirect($value = null){
      $this->failure_redirect = $value;
    }

    public function SetSuccessRedirect($value = null){
      $this->success_redirect = $value;
    }

    public function SetReferrer($value = null){
        $this->referrer = $value;
    }

    public function Precheck(){
      if($this->key === null){
        throw new InvalidParameter("No key was passed. Aborting.");
          }

          if(!is_numeric($this->strictness)){
        throw new InvalidParameter("Invalid strictness was passed. Aborting.");
      }

      if(!is_bool($this->user_agent)){
        throw new InvalidParameter("Invalid pass user agent. Aborting.");
      }

      return true;
    }

    public function CheckIP($ip){
      if($this->Precheck()){

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, sprintf(static::BASE_API_URL, $this->key, urlencode($ip),$this->campaign));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

        $parameters = array("strictness" => $this->strictness);
        $parameters["current_url"] =  $this->getCurrentURL();
        if($this->user_agent && isset($_SERVER["HTTP_USER_AGENT"])){
          $parameters["user_agent"] = $_SERVER["HTTP_USER_AGENT"];
        }
        if($this->referrer){
          $parameters["referrer"] = $this->referrer;
        }

        curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
        $result = curl_exec($curl);
        $data = json_decode($result, true);
        curl_close($curl);

        if($data === false){
          die(print_r($result, true));
        } else {
          return $data;
        }
      }
    }

    function getCurrentURL(){
        $pageURL = (isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on") ? "https://" : "http://";
        if($_SERVER["SERVER_NAME"]!="" && $_SERVER["SERVER_NAME"]!="_"){
          $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }else{
          $pageURL .= $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }

    public function ForceRedirect($type = "status"){
      $result = $this->CheckIP($this->GetIP());
      if(isset($result[$type])){
        $this->SetFailureRedirect($result["safe_page"]);
        $this->SetSuccessRedirect($result["money_page"]);
        if($result["proxy_bot"] === true){
          if($result["fr"] == 1){
            exit(header(sprintf("Location: %s", $this->failure_redirect)));
          }
          return true;
        }else if($result[$type] === false){
          if($this->failure_redirect !== null){
            if(strpos($this->getCurrentURL(),$this->failure_redirect) !== FALSE){
              return true;
            }else{
              if($result["fr"] == 1){
                exit(header(sprintf("Location: %s", $this->failure_redirect)));
              }
            }
          } else {
            exit;
          }
        } else {
          if($this->success_redirect !== null){
            exit(header(sprintf("Location: %s", $this->success_redirect)));
          }
        }
      } else {
        if(isset($result["errors"])){
          throw new CloakerlyException(implode(" - ", $result["errors"]));
        }
        throw new CloakerlyException("Force redirect check failed.");
      }
    }
      
    public static function GetIP() {
$ipaddress = "";
if (getenv("HTTP_CLIENT_IP"))
$ipaddress = getenv("HTTP_CLIENT_IP");
else if(getenv("HTTP_X_FORWARDED_FOR") && getenv("HTTP_X_FORWARDED_FOR")!=$_SERVER['SERVER_ADDR'])
$ipaddress = getenv("HTTP_X_FORWARDED_FOR");
else if(getenv("HTTP_X_FORWARDED"))
$ipaddress = getenv("HTTP_X_FORWARDED");
else if(getenv("HTTP_FORWARDED_FOR"))
$ipaddress = getenv("HTTP_FORWARDED_FOR");
else if(getenv("HTTP_FORWARDED"))
$ipaddress = getenv("HTTP_FORWARDED");
else if(getenv("REMOTE_ADDR"))
$ipaddress = getenv("REMOTE_ADDR");
else
$ipaddress = "UNKNOWN";
$ipaddress_pool = explode(",",$ipaddress);
return trim($ipaddress_pool[0]);
}
  }

  function PreventLoad(){
      $check = new CloakerlyChecker();
      $check->SetKey("5EPpFAcKsSuH7hZqK4jqD3vMJuJx0alMji9e8OFLtCOXvIBfQ6k9eikQRB2Rrmz1");
      $check->SetCampaign("29091");
      $check->SetStrictness("0");
      $check->SetReferrer(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "Direct");
      $check->PassUserAgent(true);
      $check->ForceRedirect("status");
  }
  PreventLoad();
?>

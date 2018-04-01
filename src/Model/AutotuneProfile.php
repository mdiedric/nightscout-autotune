<?php

namespace App\Model;

use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;

/**
 *
 */
class AutotuneProfile
{

  const NSAPI_PATH              = '/api/v1/';
  const NSAPI_PROFILE_ENDPOINT  = 'profile.json';
  const MGDL_PER_MMOLL          = 18;

  private $ns_url;
  private $ns_profile_api_response;
  private $ns_profile;
  private $units;

  function __construct($ns_url)
  {
    $this->ns_url = preg_replace('{/$}', '', $ns_url);
    $this->setNightscoutProfileFromURL();
    $this->setSelectedNightscoutProfile();
  }

  private function setNightscoutProfileFromURL()
  {
    if(!$this->ns_url){
      throw new \Exception("AutotuneProfile::ns_url must be defined", 1);
    }

    // TODO: create a NightScout API client (haven't I already done this?)
    $client = new Client(['base_uri' => $this->ns_url.self::NSAPI_PATH ]);
    $response = $client->get(self::NSAPI_PROFILE_ENDPOINT);
    $profile =  json_decode($response->getBody());
    $this->ns_profile_api_response = $profile[0];
    $this->units = $this->ns_profile_api_response->units;
  }

  private function setSelectedNightscoutProfile()
  {
    if(!$this->ns_profile_api_response){
      throw new \Exception("AutotuneProfile::$ns_profile_api_response must be defined", 1);
    }
    $profile = $this->ns_profile_api_response;

    // how many profiles were returned?
    $num_profiles = count((array)$profile->store);

    if($num_profiles == 0){
      throw new \Exception("No profiles were found", 1);
    }

    // if there's more than one profile, use default profile
    if($num_profiles > 0){
      $this->ns_profile = $profile->store->{$profile->defaultProfile};
      $this->units = $this->ns_profile->units;
    }
  }

  public function weightedISF()
  {
    return AutotuneProfile::timeWeightedValue($this->ns_profile->sens);
  }

  public function weightedCarbRatio()
  {
    return AutotuneProfile::timeWeightedValue($this->ns_profile->carbratio);
  }

  public function asJson()
  {
    return json_encode($this->asArray(), JSON_PRETTY_PRINT);
  }

  public function asArray()
  {
    $profile_array = array(
      "min_5m_carbimpact" => 3,
      "dia"               => $this->dia(),
      "basalprofile"      => $this->basalProfile(),
      "isfProfile"        => $this->isfProfile(),
      "carb_ratio"        => $this->weightedCarbRatio(),
      "autosens_max"      => 1.2,
      "autosens_min"      => 0.7
    );
    if($this->units == 'mmol/l') {
      $profile_array['isfProfile'] = $profile_array['isfProfile'] * self::MGDL_PER_MMOLL;
    }
    return $profile_array;
  }

  public function dia()
  {
    return (float)$this->ns_profile->dia;
  }

  public function basalProfile()
  {
    $basals = [];
    foreach ($this->ns_profile->basal as $basal) {
      $basals[] = array(
        "start"   => $basal->time.":00",
        "minutes" => $basal->timeAsSeconds/60,
        "rate"    => (float)$basal->value
      );
    }
    return $basals;
  }

  public function isfProfile()
  {
    return array(
      "sensitivities" => array(array(
        "i"             => 0,
        "start"         => "00:00:00",
        "sensitivity"   => $this->weightedISF(),
        "offset"        => 0,
        "x"             => 0,
        "endOffset"     => 1440
      ))
    );
  }

  public function createZipFile()
  {
    $profile_json = $this->asJson();

    $fileSystem = new Filesystem();
    $random_string = str_replace(['/','='],'', base64_encode(random_bytes(10)));
    $tmp_path = '/tmp/com.ella7.autotune/'.$random_string;
    $fileSystem->mkdir($tmp_path, 0755);

    $zip_filename = 'autotune_settings.zip';
    $zip_path     = $tmp_path.'/'.$zip_filename;
    $zip = new \ZipArchive;
    $res = $zip->open($zip_path, \ZipArchive::CREATE);
    if ($res === TRUE) {
        $zip->addFromString('/settings/profile.json',     $profile_json);
        $zip->addFromString('/settings/pumpprofile.json', $profile_json);
        $zip->addFromString('/settings/autotune.json',    $profile_json);
        $zip->close();
    } else {
        throw new \Exception("Error: Could not create the zip archive");
    }
    return $zip_path;
  }


  // TODO: this should go somewhere else - helper functions or something like that
  public static function timeWeightedValue($series)
  {
      $num_values = count($series);
      if($num_values == 0){
          // throw an error
      }

      if($num_values == 1){
        return $series[0]->value;
      }

      $seconds_per_day = 86400; // make constant
      if($num_values > 1){
        $weighted_values = array();
        for($i = 0; $i < $num_values; $i++){
          if($i+1 == $num_values){
            $duration = $seconds_per_day - $series[$i]->timeAsSeconds;
          } else {
            $duration = $series[$i+1]->timeAsSeconds - $series[$i]->timeAsSeconds;
          }
          $weight = $duration/$seconds_per_day;
          $weighted_values[$i] = $weight * $series[$i]->value;
        }
      return array_sum($weighted_values);
      }
  }

}

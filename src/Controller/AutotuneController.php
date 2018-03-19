<?php
// src/Controller/AutotuneController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use GuzzleHttp\Client;

class AutotuneController extends Controller
{
    /**
     * @Route("/nsprofile", name="nsprofile")
     */
    public function nsProfile()
    {

        // TODO: create a NightScout API client (haven't I already done this?)
        $ns_url = 'http://ella7ns.herokuapp.com';
        $api_endpoint = '/api/v1/';
        $client = new Client(['base_uri' => $ns_url.$api_endpoint ]);
        $profile_path = 'profile.json';

        $response = $client->get($profile_path);
        $profile = json_decode($response->getBody());
        $profile = $profile[0];

        // TODO: create profile handler class
        // how many profiles were returned?
        $num_profiles = count((array)$profile->store);

        if($num_profiles == 0){
          // throw an error
        }

        // get the default profile if there's more than one profile
        if($num_profiles > 0){
          $profile = $profile->store->{$profile->defaultProfile};
        }

        // get the timeweighted value for ISF
        $isf        = self::timeWeightedValue($profile->sens);
        $carb_ratio = self::timeWeightedValue($profile->carbratio);

        return $this->render('nsprofile.html.twig', array(
            'profile'     => $profile,
            'isf'         => $isf,
            'carb_ratio'  => $carb_ratio
        ));
    }

    public static function timeWeightedValue($series){
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

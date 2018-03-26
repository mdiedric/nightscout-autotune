<?php
// src/Controller/AutotuneController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use GuzzleHttp\Client;
use App\Model\AutotuneProfile;

class AutotuneController extends Controller
{
    /**
     * @Route("/",            name="home")
     * @Route("/nsprofile",   name="nsprofile")
     */
    public function nsProfile()
    {

        $ns_url = 'http://ella7ns.herokuapp.com';
        $profile = new AutotuneProfile($ns_url);

        // get the timeweighted value for ISF
        $isf        = $profile->weightedISF();
        $carb_ratio = $profile->weightedCarbRatio();

        return $this->render('nsprofile.html.twig', array(
            'profile'     => $profile,
            'isf'         => $isf,
            'carb_ratio'  => $carb_ratio,
            'json_output' => $profile->autotuneJson()
        ));
    }


}

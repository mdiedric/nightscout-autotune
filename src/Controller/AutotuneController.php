<?php
// src/Controller/AutotuneController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Model\AutotuneProfile;

class AutotuneController extends Controller
{
    /**
     * @Route("/",            name="home")
     * @Route("/nsprofile",   name="nsprofile")
     */
    public function nsProfile(SessionInterface $session)
    {
        $ns_url = $session->get('ns_url');
        return $this->render('nsprofile.html.twig', ['ns_url'=>$ns_url]);
    }

    /**
     * @Route("/profile.json",  name="profile-json")
     */
    public function profileJson(Request $request, SessionInterface $session)
    {
        if(!self::validateRequest($request)){
          throw new \Exception("Error: The request is invalid");
        }

        $request_body = json_decode($request->getContent(), true);
        $ns_url = $request_body['nsurl'];
        $session->set('ns_url', $ns_url);

        $profile = new AutotuneProfile($ns_url);
        return $this->json($profile->asArray());
    }

    /**
     * @Route("/nsurlvalidate",  name="nsurlvalidate")
     */
    public function nsUrlValidate(Request $request)
    {
        return $this->json(['valid_nsurl'=>self::validateRequest($request)]);
    }

    /**
     * @Route("/downloadprofile",  name="downloadprofile")
     */
    public function downloadProfile(Request $request, SessionInterface $session)
    {
      $ns_url = $session->get('ns_url');
      $profile = new AutotuneProfile($ns_url);
      $zip_path = $profile->createZipFile();
      return $this->file($zip_path);
    }

    private static function validateRequest(Request $request)
    {
      $request_body = json_decode($request->getContent(), true);

      if(!array_key_exists('nsurl', $request_body)){
          throw new \Exception("Error: nsurl not submitted");
      }
      //TODO Add URL validation code here
      return true;
    }

}

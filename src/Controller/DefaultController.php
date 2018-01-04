<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Entity\Profile;
use App\Service\Auth;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(Request $request, Auth $auth)
    {
        // If user is not logged in, redirect
        if( !$auth->requreLogin() )
        {
            return $this->redirectToRoute('profile_create');
        }

        // get all profiles
        $profiles = $this->getDoctrine()->getRepository('App:Profile')->findBy(['confirmed' => true]);
    
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'profiles' => $profiles,
            'user' => $auth->getUser(),
        ]);
    }
}

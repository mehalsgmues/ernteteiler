<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Entity\Profile;
use App\Entity\Resender;

class UserController extends Controller
{
    /**
     * @Route("/login/{key}", name="login")
     */
    public function login($key, Request $request, SessionInterface $session)
    {
        // try to login user with key
        $user = $this->getDoctrine()
            ->getRepository(Profile::class)
            ->findOneBy(['loginKey' => $key]);

        // Benutzer existiert nicht
        if ($user)
        {
            // Benutzer wenn nötig bestätigen
            if( !$user->getConfirmed() ) {
                $user->confirm();
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
            }
        
            // Benutzer einloggen
            $session->set('user', $key);
            
            // return new Response($session->get('user'));
            // redirect to page
            return new RedirectResponse($request->query->get('redirect','/'));
        }
        
        return $this->render('user/notFound.html.twig');
    }
    
    /**
     * @Route("/resend", name="resend")
     */ 
    public function resend(Request $request, \Swift_Mailer $mailer)
    {
    
        $email = $request->request->get('form')['email'];
        // try to find user with email
        $user = $this->getDoctrine()
            ->getRepository(Profile::class)
            ->findOneBy(['email' => $email]);
        
        // Benutzer existiert nicht
        if ($user)
        {
        
            // send email
            $message = (new \Swift_Message('Ernteteiler: Dein Login'))
                ->setFrom('server@mehalsgmues.ch')
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        // templates/emails/login.html.twig
                        'emails/login.html.twig',
                        array('user' => $user)
                    ),
                    'text/html'
                )
                /*
                 * If you also want to include a plaintext version of the message
                ->addPart(
                    $this->renderView(
                        'emails/registration.txt.twig',
                        array('name' => $name)
                    ),
                    'text/plain'
                )
                */
            ;

            $mailer->send($message);
        
            return $this->render('user/resent.html.twig');
        }
        
        return $this->render('user/notFound.html.twig');
    }
    
    /**
     * @Route("/logout", name="logout")
     */
    public function logout(SessionInterface $session)
    {
        $session->clear();
        
        return $this->redirectToRoute('homepage');
    }
    
    /**
     * @Route("/deleted", name="deleted")
     */
    public function deleted()
    {
        return $this->render('user/deleted.html.twig');
    }
}

<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Profile;
use App\Entity\Message;
use App\Entity\ConfirmDelete;
use App\Entity\Resender;
use App\Service\Auth;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ProfileController extends Controller
{
    /**
     * @Route("/profile/contact/{id}", name="profile_contact")
     */
    public function contact( Profile $profile, Request $request, Auth $auth, \Swift_Mailer $mailer )
    {
        // If user is not logged in, redirect
        if( !$auth->requreLogin() )
        {
            return $this->redirectToRoute('profile_create');
        }

        // get this profiles
        //$profiles = $this->getDoctrine()->getRepository('App:Profile')->findAll();
        $message = new Message();
        $form = $this->createFormBuilder($message) 
            ->add('message', TextareaType::class)
            ->add('send', SubmitType::class, array('label' => 'Senden'))
            ->getForm();
    
        $form->handleRequest($request);

        $message_sent = false;

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData();
            $user = $auth->getUser();
            
            // send email
            $email = (new \Swift_Message('Ernteverteiler: Anfrage'))
                ->setFrom('server@mehalsgmues.ch')
                ->setTo( $profile->getEmail() )
                ->setReplyTo( $user->getEmail() )
                ->setBody(
                    $this->renderView(
                        // templates/emails/registration.html.twig
                        'emails/message.html.twig',
                        array('profile' => $profile, 'user' => $user, 'message' => $message)
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

            $mailer->send($email);
            $message_sent = true;
        }
    
        // render page
        return $this->render('profile/contact.html.twig', [
            'profile' => $profile,
            'contact' => $form->createView(),
            'message_sent' => $message_sent,
        ]);
    }
    
    /**
     * @Route("/profile/create", name="profile_create")
     * @Route("/profile/update", name="profile_update")
     */
    public function create( Request $request, Auth $auth, \Swift_Mailer $mailer )
    {
        $em = $this->getDoctrine()->getManager();
    
        // If user is logged in, edit existing profile
        $loggedIn = $auth->requreLogin();
        if( $loggedIn )
        {
            $profile = $auth->getUser();
            $send_label = 'Änderungen speichern';
            
            $login_form = false;
        }
        else
        {
            // create a task and give it some dummy data for this example
            $profile = new Profile();
            $send_label = 'Steckbrief erstellen';
            
            // login form
            $resender = new Resender();
            $login_form = $this->createFormBuilder($resender)
                ->setAction($this->generateUrl('resend'))
                ->add('email', EmailType::class)
                ->add('send', SubmitType::class, array('label' => 'Erneut senden'))
                ->getForm();
        }

        $form = $this->createFormBuilder($profile)
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('description', TextareaType::class)
            ->add('create', SubmitType::class, array('label' => $send_label))
            ->getForm();

        $form->handleRequest($request);

        $form_success = false;

        if ($form->isSubmitted() && $form->isValid()) {
            // Safe profile
            $profile = $form->getData();
            
            // If new create key and send email to user
            if( !$loggedIn )
            {
                $profile->createKey();
                
                // send email
                $message = (new \Swift_Message('Ernteverteiler: Steckbrief erstellt'))
                    ->setFrom('server@mehalsgmues.ch')
                    ->setTo($profile->getEmail())
                    ->setBody(
                        $this->renderView(
                            // templates/emails/registration.html.twig
                            'emails/registration.html.twig',
                            array('profile' => $profile)
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
            }
            
            $em->persist($profile);
            $em->flush();            

            // show success message, if user is new and therefore not logged in yet.
            if( !$loggedIn )
            {
                return $this->redirectToRoute('profile_created_success');
                return $this->render('profile/success.html.twig');
            }
            
            $form_success = true;
        }

        $params = array(
            'form' => $form->createView(),
            'loggedIn' => $loggedIn,
            'form_success' => $form_success,
        );
        if( !$loggedIn )
        {
            $params['login_form'] = $login_form->createView();
        }

        return $this->render('profile/create.html.twig', $params);
    }
    
    /**
     * @Route("/profile/create/success", name="profile_create_success")
     */
    public function success( Request $request, Auth $auth, \Swift_Mailer $mailer )
    {
        if( $auth->requreLogin() )
        {
            return $this->redirectToRoute('homepage');
        }
        return $this->render('profile/success.html.twig');
    }
     
    /**
     * @Route("/profile/delete", name="profile_delete")
     */
    public function delete( Request $request, Auth $auth, \Swift_Mailer $mailer )
    {
        if( !$auth->requreLogin() )
        {
            return $this->redirectToRoute('homepage');
        }
    
        $confirm = new ConfirmDelete();

        $form = $this->createFormBuilder($confirm)
            ->add('confirm', CheckboxType::class)
            ->add('send', SubmitType::class, array('label' => 'Steckbrief endgültig löschen'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {  
            // Profil löschen
            $user = $auth->getUser();
            if( !$user->isAdmin() ) { // Der Admin ist unlöschbar
                $em = $this->getDoctrine()->getManager();
                $em->remove( $auth->getUser() );
                $em->flush();
            }
            
            // Ausloggen
            return $this->redirectToRoute('deleted');
        }
        
        return $this->render('profile/delete.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}

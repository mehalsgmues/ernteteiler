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

use Captcha\Bundle\CaptchaBundle\Form\Type\CaptchaType;
use Captcha\Bundle\CaptchaBundle\Validator\Constraints\ValidCaptcha;


class ProfileController extends Controller
{
    /**
     * @Route("/profile/contact/{id}", name="profile_contact")
     */
    public function contact( Profile $profile, Request $request, Auth $auth, \Swift_Mailer $mailer )
    {
        // If user is not logged in, redirect
        $loggedIn = $auth->requreLogin();
        if( !$loggedIn && !$profile->isPublic() )
        {
            return $this->redirectToRoute('profile_create');
        }

        // get this profiles
        //$profiles = $this->getDoctrine()->getRepository('App:Profile')->findAll();
        $message = new Message();
        $form_builder = $this->createFormBuilder($message);
        if( !$loggedIn ) {
            $form_builder->add('name', TextType::class);
            $form_builder->add('email', EmailType::class);
        }
        $form_builder->add('message', TextareaType::class);
        $form_builder->add('send', SubmitType::class, array('label' => 'Senden'));
        $form = $form_builder->getForm();
    
        $form->handleRequest($request);

        $message_sent = false;

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData();
            $user = $auth->getUser();
            
            // send email
            if( $loggedIn ) {
                $email = (new \Swift_Message('Ernteteiler: Anfrage'))
                    ->setFrom('teiler-server@mehalsgmues.ch')
                    ->setReplyTo( $user->getEmail() )
                    ->setTo( $profile->getEmail() )
                    ->setBody(
                        $this->renderView(
                            // templates/emails/message.html.twig
                            'emails/message.html.twig',
                            array('profile' => $profile, 'user' => $user, 'message' => $message)
                        ),
                        'text/html'
                    );
            } else {
                $email = (new \Swift_Message('Ernteteiler: Anfrage'))
                    ->setFrom('teiler-server@mehalsgmues.ch')
                    ->setReplyTo( $message->getEmail() )
                    ->setTo( $profile->getEmail() )
                    ->setBody(
                        $this->renderView(
                            // templates/emails/message_public.html.twig
                            'emails/message_public.html.twig',
                            array('profile' => $profile, 'message' => $message)
                        ),
                        'text/html'
                    );
            }
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
    
        $params = [
            'profile' => $profile,
            'contact' => $form->createView(),
            'message_sent' => $message_sent,
            'loggedIn' => $loggedIn,
            'admin' => false
        ];
        if($loggedIn) {
            $params['admin'] = $auth->getUser()->isAdmin();
        }
        // render page
        return $this->render('profile/contact.html.twig', $params);
    }
    
    /**
     * @Route("/profile/create", name="profile_create")
     * @Route("/profile/update", name="profile_update")
     * @Route("/profile/update/{userid}", name="profile_admin")
     */
    public function create( $userid = false, Request $request, Auth $auth, \Swift_Mailer $mailer )
    {
        $admin = false;
        $em = $this->getDoctrine()->getManager();
    
        // If user is logged in, edit existing profile
        $loggedIn = $auth->requreLogin();
        if( $loggedIn )
        {
            $profile = $auth->getUser();
            $send_label = 'Änderungen speichern';
            $login_form = false;
            // admin access
            if( $profile->isAdmin() && $userid !== false ) {
                $profile = $this->getDoctrine()
                    ->getRepository(Profile::class)
                    ->find($userid);
                if( !$profile ) {
                    throw $this->createNotFoundException('Benutzer existiert nicht');
                }
                $admin = true;
            }
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
            ->add('public', CheckboxType ::class, array('required' => false))
            ->add('captchaCode', CaptchaType::class, [
                  'captchaConfig' => 'ExampleCaptcha',
                  'label' => 'Spamschutz'
            ])
            ->add('create', SubmitType::class, array('label' => $send_label))
            ->getForm();

        $form->handleRequest($request);

        $form_success = false;

        if ($form->isSubmitted() && $form->isValid()) {
            // Safe profile
            $profile = $form->getData();
            
            // Safe date of last change
            $profile->setDateLastChanged(new \DateTime());
            
            // If new create key and send email to user
            if( !$loggedIn )
            {
                $profile->createKey();
                
                // safe date of creation
                $profile->setDateCreated(new \DateTime());
                
                // send email
                $message = (new \Swift_Message('Ernteteiler: Steckbrief erstellt'))
                    ->setFrom('teiler-server@mehalsgmues.ch')
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
                return $this->redirectToRoute('profile_create_success');
            }
            
            $form_success = true;
        }

        // get all public profiles
        $profiles = $this->getDoctrine()->getRepository('App:Profile')->findBy(['confirmed' => true, 'public' => true], array('dateLastChanged' => 'DESC'));

        $params = array(
            'form' => $form->createView(),
            'loggedIn' => $loggedIn,
            'form_success' => $form_success,
            'admin' => $admin,
            'userid' => $userid,
            'profiles' => $profiles,
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
     * @Route("/profile/delete/{userid}", name="profile_delete_admin")
     */
    public function delete( $userid = false, Request $request, Auth $auth, \Swift_Mailer $mailer )
    {
        if( !$auth->requreLogin() )
        {
            return $this->redirectToRoute('homepage');
        }
        
        $admin = false;
        $user = $auth->getUser();
        // admin access
        if( $user->isAdmin() && $userid !== false ) {
            $user = $this->getDoctrine()
                ->getRepository(Profile::class)
                ->find($userid);
            if( !$user ) {
                throw $this->createNotFoundException('Benutzer existiert nicht');
            }
            $admin = true;
        }

        $confirm = new ConfirmDelete();

        $form = $this->createFormBuilder($confirm)
            ->add('confirm', CheckboxType::class)
            ->add('send', SubmitType::class, array('label' => 'Steckbrief endgültig löschen'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {  
            // Profil löschen
            if( !$user->isAdmin() ) { // Der Admin ist unlöschbar
                $em = $this->getDoctrine()->getManager();
                $em->remove( $user );
                $em->flush();
            }
            
            // Ausloggen
            return $this->redirectToRoute('deleted');
        }
        
        return $this->render('profile/delete.html.twig', array(
            'form' => $form->createView(),
            'admin' => $admin,
            'userid' => $userid,
        ));
    }
}

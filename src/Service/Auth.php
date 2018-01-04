<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use \Doctrine\ORM\EntityManagerInterface;
use App\Entity\Profile;

class Auth
{
    private $session;
    private $em;

    public function __construct(SessionInterface $session, EntityManagerInterface $em)
    {
        $this->session = $session;
        $this->em = $em;
    }

    public function requreLogin()
    {
        // If user is not logged in, redirect
        return $this->getUser()? true : false;
    }
    
    public function getUser()
    {
        // If user is not logged in, redirect
        return $this->em->getRepository('App:Profile')->findOneBy(['loginKey' => $this->session->get('user')]);
    }
}

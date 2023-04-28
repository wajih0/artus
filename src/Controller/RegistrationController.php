<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Form\RegistrationFormType;
use App\Form\ForgetType;
use App\Security\LoginFormAuthenticator;
// use App\Security\LoginFormFrontAuthenticator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Repository\UserRepository;
class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, LoginFormAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $user->setRoles(['role' => 'admin']);

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('user/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
    
    #[Route('/home/register', name: 'app_register_front')]
    public function registerFront(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, LoginFormAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $user->setRoles(['roles' => 'user']);

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('home_front/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/recherche', name: 'app_liste_ordonne')]
    public function listOrdonne(Request $request, UserRepository $repo): Response
    {
        $lastname = $request->query->get('region');
        $list = $repo->findByTitre($lastname);
        return $this->render('home_front/afficher.html.twig', [
            'form' => $list,
        ]);
    }

   

   #[Route('/afficher', name: 'app_afficher')]
   public function read2(UserRepository $repository): Response
   {
    
       $list = $repository->findAll();
       return $this->render('home_front/afficher.html.twig', [
           'form' => $list,
       ]);
   }

   #[Route('/delete/{id}', name: 'app_user_delete')]

   public function delete(
    UserRepository $repository,
       $id,
       ManagerRegistry $doctrine
   ): Response {
       $em = $doctrine->getManager();
       $user = $repository->find($id);
       $em->remove($user);
       $em->flush();
      
       // update table (flush)
       return $this->redirectToRoute('app_afficher');  
   }

   #[Route('/editadmin/{id}', name: 'app_user_editadmin')]
   public function edit(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, LoginFormAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('home_front/modifier.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/modifier/{id}', name: 'app_modif')]
    public function  update(ManagerRegistry $doctrine,$id,  Request  $request) : Response
    { $user = $doctrine
        ->getRepository(User::class)
        ->find($id);
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->add('update', SubmitType::class) ;
        $form->handleRequest($request);
        if ($form->isSubmitted())
        { $em = $doctrine->getManager();
            $em->flush();
            return $this->redirectToRoute('app_afficher');
        }
        return $this->render("home_front/modifier.html.twig",
            ["form"=>$form->createView(),]) ;
   
   }


   #[Route('/oubli-pass', name:'forgotten_password')]
public function forgottenPassword(
    ManagerRegistry $doctrine,
    MailerInterface $mailer,
    Request $request,
    UserRepository $userRepository,
    EntityManagerInterface $entityManager
): Response {
 
    $form = $this->createForm(ForgetType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Récupérer l'utilisateur par l'adresse e-mail fournie
        $user = $userRepository->findOneByEmail($form->get('email')->getData());
        if ($user) {
            // Générer un nouveau mot de passe
            $newPassword = bin2hex(random_bytes(6)); // Génère une chaîne hexadécimale de 12 caractères aléatoires
            // Hasher le nouveau mot de passe
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            // Enregistrer le nouveau mot de passe dans la base de données
            $user->setPassword($passwordHash);
            $entityManager->persist($user);
            $entityManager->flush();

            // Envoyer un e-mail avec le nouveau mot de passe
            $email = (new Email())
                ->from('ahmedwajih.benhmida@esprit.tn')
                ->to($user->getEmail())
                ->subject('Nouveau mot de passe')
                ->text(sprintf('Voici votre nouveau mot de passe: %s', $newPassword));

            $mailer->send($email);

            // Afficher un message de succès
            $this->addFlash('success', 'Un nouveau mot de passe a été envoyé à votre adresse e-mail.');
        } else {
            // Si l'adresse e-mail n'est pas trouvée dans la base de données, afficher un message d'erreur
            $this->addFlash('error', 'L\'adresse e-mail fournie est invalide.');
        }
    }

    return $this->render('home_front/forgetpassword.html.twig', [
        'requestPassForm' => $form->createView()
    ]);
}

      
      
     
    

    

   }


   





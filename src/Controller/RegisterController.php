<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use App\Service\ApiTokenService;

class RegisterController extends AbstractController
{
    private $apiToken;
    private $userRepository;
    private $serializer;
    private $hash;
    private $em;
    public function __construct(ApiTokenService $apiToken,UserRepository $userRepository,
    SerializerInterface $serializerInterface,UserPasswordHasherInterface $hash, EntityManagerInterface $em) {
        $this->apiToken = $apiToken;
        $this->userRepository = $userRepository;
        $this->serializer = $serializerInterface;
        $this->hash = $hash;
        $this->em = $em;
    }
    //ajouter un utilisateur
    #[Route('/user/add', name: 'app_register_add')]
    public function addUser(Request $request): Response {
        $message = "";
        $code = 200;
        //récupération du json
        $json = $request->getContent();
        //test si le json est valide
        if ($json) {
            //encodage en tableau
            $data = $this->serializer->decode($json, 'json');
            //dd($data["password"]);
            if (empty($data["name"]) OR empty($data['firstname']) OR empty($data["email"]) OR empty($data["password"])) {
                $message = ["error" => "Veuillez remplir tous les champs"];
                $code = 400;
            }
            //test le compte existe déja
            else if ($this->userRepository->findOneBy(["email" => $data["email"]])) {
                $message = ["error" => "le compte existe déja"];
                $code = 400;
            }
            //test sinon
            else{
                //new user
                $user = new User();
                //set des valeurs
                $user->setName($data["name"]);
                $user->setFirstname($data["firstname"]);
                $user->setEmail($data["email"]);
                //création du hash
                $pass = $data["password"];
                $hash = $this->hash->hashPassword($user, $pass);
                $user->setPassword($hash);
                $user->setToken(md5("tk".$data["name"].$data["firstname"].$data["email"].rand()."2023"));
                $user->setActivated(true);
                $user->setRoles(["ROLE_USER"]);
                //persist
                $this->em->persist($user);
                $this->em->flush();
                $message = ["error" => "Le compte " . $user->getEmail() . " a été ajouté en BDD"];
            }
        } else {
            $message = ["error" => "Json invalide"];
            $code = 400;
        }
        return $this->json($message,$code,['Content-Type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    }
    //afficher un utilisateur par son id
    #[Route('/user/id/{id}', name: 'app_register_id')]
    public function getUserById($id): Response
    {   
        $message = "";
        $code = 200;
        $groupe = "";
        $user = $this->userRepository->find($id);
        if ($user) {
            $message = $user;
            $groupe = ['groups' => 'user'];

        } else {
            $message = ["error" => "Le compte n'existe pas"];
            $code = 400;
            $groupe = [];
        }
        return $this->json($message, $code, 
        ['Content-Type' => 'application/json','Access-Control-Allow-Origin' => '*'],$groupe);
    }
    //afficher la liste des utilisateurs
    #[Route('/user/all', name: 'app_register_all')]
    public function getAllUser(): Response
    {   $message = "";
        $code = 200;
        $groupe = [];
        $users = $this->userRepository->findAll();
        if ($users) {
            $message = $users;
            $groupe = ['groups' => 'user'];

        } else {
            $message = ["error" => "La base est vide"];
            $code = 400;
        }
        return $this->json($message,$code,[
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*'
        ],$groupe);
    }
    //recup token
    #[Route('/user/token', name: 'app_register_token')]
    public function getToken(Request $request){
        $json = $request->getContent();
        $message = "";
        $code = 0;
        if($json){
            $data = $this->serializer->decode($json,'json');
            $message = $this->apiToken->getToken($data['email'],$data['password']);
            $code = 200;
        }else{
            $message = ["error"=>"Json Invalide"];
            $code = 400;
        }
        return $this->json($message,$code,['Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*']); 
    }
    //mise à jour user (sauf password)
    #[Route('/user/update', name: 'app_register_update')]
    public function updateUser(Request $request): Response {
        $message = "";
        $code = 200;
        //recuperation du json
        $json = $request->getContent();
        //test le json est valide
        if ($json) {
            $data = $this->serializer->decode($json, 'json');
            $user = $this->userRepository->findOneBy(["token" => $data["token"]]);
            //test le compte existe
            if ($user) {
                //update
                $user->setName($data["name"]);
                $user->setFirstname($data["firstname"]);
                $user->setEmail($data["email"]);
                //update du compte en BDD
                $this->em->persist($user);
                $this->em->flush();
                $message = ["error" => "le compte ".$user->getEmail()." a été modifié"];
            }
            //test le compte n'existe pas
            else {
                $message = ["error" => "le compte n'existe pas"];
                $code = 400;
            }
        }
        //test le json est invalide
        else {
            $message = ["error" => "json invalide"];
            $code = 400;
        }
        return $this->json($message,$code, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*'
        ]);
    }
    //update du password
    #[Route('/user/update/password', name: 'app_register_update_password')]
    public function updatePassword(Request $request): Response {
        $message = "";
        $code = 200;
        //recupération du Json
        $json = $request->getContent();
        //test si le json est valide
        if ($json) {
            //sérialisation en Tableau
            $data = $this->serializer->decode($json, 'json');
            $user = $this->userRepository->findOneBy(["token" => $data["token"]]);
            //test si les champs sont bien vides
            if(empty($data["newpassword"]) OR empty($data["token"]) OR empty($data["oldpassword"])){
                $message = ["error" => "veuillez renseigner toutes les valeurs"];
                $code = 400;
            }
            //test si le compte existe et données non vide
            else if ($user) {
                //test l'ancien mot de passe est valide
                if($this->hash->isPasswordValid($user, $data["oldpassword"])){
                    $hash = $this->hash->hashPassword($user, $data["newpassword"]);
                    $user->setPassword($hash);
                    $this->em->persist($user);
                    $this->em->flush();
                    $message = ["error" => "Password update"];
                }
                //l'ancien mot de passe est invalide
                else{
                    $message = ["error" => "l'ancien Password est incorrect"];
                    $code = 400;
                }
            }
            //test le compte n'existe pas
            else {
                $message = ["error" => "Le compte n'existe pas"];
                $code = 400;
            }
        }
        //test si le json est invalide 
        else {
            $message = ["error" => "json invalide"];
            $code = 400;
        }
        //retour du json
        return $this->json($message,$code, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*'
        ]);
    }
    //méthode qui récupére les informations du compte par son nom et prénom
    #[Route('/user/info', name: 'app_register_info')]
    public function getUserByInfo(Request $request,): Response {
        $message = "";
        $code = 200;
        $groupe = [];
        $json = $request->getContent();
        //test si le json est valide
        if ($json) {
            $data = $this->serializer->decode($json, 'json');
            $user = $this->userRepository->findOneBy(["name" => $data["name"],"firstname" => $data["firstname"]]);
            //test si le compte existe
            if ($user) {
                $message = $user;
                $groupe = ['groups' => 'user'];
            } 
            //test le compte n'existe pas
            else {
                $message = ['error' => 'Le compte n\'existe pas'];
                $code = 400;
            }
        }
        //test si le json est invalide
        else {
            $message = ['error' => 'Json invalide'];
            $code = 200;
        }
        return $this->json($message,$code,
            ['Content-Type' => 'application/json', 'Access-Control-Allow-Origin' => '*'],$groupe);
    }
    //test password
    #[Route('/user/password/test', name: 'app_register_password_test')]
    public function testPassword(Request $request): Response {
        $message = "";
        $code = 200;
        $json = $request->getContent();
        //test json valide
        if ($json) {
            $data = $this->serializer->decode($json, 'json');
            $user = $this->userRepository->findOneBy(["token" => $data["token"]]);
            if($user){
                $password = $data["password"];
                $test = $this->hash->isPasswordValid($user, $password);
                //test password valide
                if ($test) {
                    $message = ['error' => 'Ok'];
                }
                //test passord invalide
                else{
                    $message = ['error' => 'Invalide'];
                    $code = 400;
                } 
            }
            //test password incorrect
            else {
                $message = ['error' => 'Le compte n\'existe pas'];
                $code = 400;
            }
        } 
        //test json invalide
        else {
            $message = ['error' => 'Json Invalide'];
            $code = 400;
        }
        return $this->json($message,$code,
        ['Content-Type' => 'application/json', 'Access-Control-Allow-Origin' => '*']);
    }

    //liste des utilisateurs trié par nom et prénom croissant
    #[Route('/user/all/{order}', name: 'app_register_all_order')]
    public function getAllUserOrder($order): Response
    {
        $message = "";
        $code = 200;
        $groupe = [];
        $users = $this->userRepository->findBy([], ["name" => $order, "firstname" => $order], null, null);
        //test si il y à des utilisateurs
        if ($users) {
            $message = $users;
            $groupe = ['groups' => 'user'];
        } 
        //test la base est vide
        else {
            $message = ["error" => "La base est vide"];
            $code = 400;
        }
        return $this->json($message,$code, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*'
        ],$groupe);
    }

    //testToken2
    #[Route('/user/token/v2',name:'app_register_token_v2')]
    public function getTokenV2(Request $request){
        $json = $request->getContent();
        $message = "";
        $code = 0;
        if($json){
            $data = $this->serializer->decode($json,'json');
            $message = $this->apiToken->getToken($data['email'],$data['password']);
            $code = 200;
        }else{
            $message = ["error"=>"Json Invalide"];
            $code = 400;
        }
        return $this->json($message,$code,['Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*']); 
    }
}

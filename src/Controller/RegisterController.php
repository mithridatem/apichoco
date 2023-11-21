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

class RegisterController extends AbstractController
{
    //ajouter un utilisateur
    #[Route('/user/add', name:'app_register_add')]
    public function addUser(Request $request, UserRepository $repo,
    UserPasswordHasherInterface $hash,EntityManagerInterface $em,
    SerializerInterface $serializerInterface):Response{
        //récupération du json
        $json =$request->getContent();
        //test si le json est valide
        if($json){
            //encodage en tableau
            $data = $serializerInterface->decode($json, 'json');
            //test le compte existe déja
            if($repo->findOneBy(["email"=>$data["email"]])){
                return $this->json(["error"=>"le compte existe déja"], 200,
                ['Content-Type'=>'application/json', 'Access-Control-Allow-Origin'=>'*']);
            }
            //new user
            $user = new User();
            //set des valeurs
            $user->setName($data['name']);
            $user->setFirstname($data['firstname']);
            $user->setEmail($data['email']);
            //création du hash
            $pass = $data['password'];
            $hash = $hash->hashPassword($user, $pass);
            $user->setPassword($hash);
            $user->setToken("tk".$data['name'].$data["firstname"]."2023");
            $user->setActivated(true);
            $user->setRoles(["ROLE_USER"]);
            //persist
            $em->persist($user);
            $em->flush();
            return $this->json(["error"=>"Le compte ".$user->getEmail()." a été ajouté en BDD"],200,
            ['Content-Type'=>'application/json', 'Access-Control-Allow-Origin'=>'*']);
        }
        else{
            return $this->json(["error"=>"Json invalide"],400,
            ['Content-Type'=>'application/json', 'Access-Control-Allow-Origin'=>'*']);
        }
    }
    //afficher un utilisateur par son id
    #[Route('/user/id/{id}', name:'app_register_id')]
    public function getUserById($id,UserRepository $repo):Response{
        $user = $repo->find($id);
        if($user){
            return $this->json($user,200,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*'], ['groups'=>'user']);
        }
        else{
            return $this->json(["error"=>"Le compte n'existe pas"],400,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*']);
        }
    }
    //afficher la liste des utilisateurs
    #[Route('/user/all', name:'app_register_all')]
    public function getAllUser(UserRepository $repo):Response{
        $users = $repo->findAll();
        if($users){
            return $this->json($users,200,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*'],['groups'=>'user']);
        }else{
            return $this->json(["error"=>"La base est vide"],400,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*']);
        }
    }
    //recup token
    #[Route('/user/token', name:'app_register_token')]
    public function getToken(Request $request, UserRepository $repo,
    UserPasswordHasherInterface $hash,SerializerInterface $serializerInterface):Response{
        $json = $request->getContent();
        //test le json est valide
        if($json){
            //décodage du json
            $data = $serializerInterface->decode($json,'json');
            //récupération du compte
            $user = $repo->findOneBy(["email"=>$data["email"]]);
            //test si le compte existe
            if($user){
                //test password valide
                if($hash->isPasswordValid($user, $data["password"])){
                    $token = $user->getToken();
                    return $this->json(["token"=>$token],200,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin'=>'*']);
                }
                //test le password est invalide
                else{
                    return $this->json(["error"=>"informations de connexion invalide"],400,
                    ['Content-Type'=>'application/json','Access-Control-Allow-Origin'=>'*']);
                }
            }
            //test le compte n'existe pas
            else{
                return $this->json(["error"=>"informations de connexion invalide"],400,
                ['Content-Type'=>'application/json','Access-Control-Allow-Origin'=>'*']);
            }
        }
        //test le json est invalide
        else{
            return $this->json(["error"=>"json invalide"],400,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*']);
        }
    }
    //mise à jour user (sauf password)
    #[Route('/user/update', name:'app_register_update')]
    public function updateUser(Request $request, UserRepository $repo,
    EntityManagerInterface $em,SerializerInterface $serializerInterface):Response{
        //recuperation du json
        $json = $request->getContent();
        //test le json est valide
        if($json){
            $data = $serializerInterface->decode($json, 'json');
            $user = $repo->findOneBy(["token"=>$data["token"]]);
            //test le compte existe
            if($user){
                //update
                $user->setName($data["name"]);
                $user->setFirstname($data["firstname"]);
                $user->setEmail($data["email"]);
                //update du compte en BDD
                $em->persist($user);
                $em->flush();
                //return json
                return $this->json(["error"=>"le compte a été modifié"],200,['Content-Type'=>'application/json',
                'Access-Control-Allow-Origin'=>'*']);
            }
            //test le compte n'existe pas
            else{
                return $this->json(["error"=>"le compte n'existe pas"],400,['Content-Type'=>'application/json',
                'Access-Control-Allow-Origin'=>'*']);
            }
        }
        //test le json est invalide
        else{
            return $this->json(["error"=>"json invalide"],400,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*']);
        }
    }
    //update du password
    #[Route('/user/update/password', name:'app_register_update_password')]
    public function updatePassword(Request $request, UserRepository $repo,
    UserPasswordHasherInterface $hash,EntityManagerInterface $em,
    SerializerInterface $serializerInterface):Response{
        $json = $request->getContent();
        if($json){
            $data = $serializerInterface->decode($json, 'json');
            //test si le compte existe
            $user = $repo->findOneBy(["token"=>$data["token"]]);
            if($user){
                $hash = $hash->hashPassword($user, $data["password"]);
                $user->setPassword($hash);
                $em->persist($user);
                $em->flush();
                return $this->json(["error"=>"Password update"],200,['Content-Type'=>'application/json',
                'Access-Control-Allow-Origin'=>'*']);
            }
            //test le compte n'existe pas
            else{
                return $this->json(["error"=>"Le compte n'existe pas"],400,['Content-Type'=>'application/json',
                'Access-Control-Allow-Origin'=>'*']);
            }
        }
        else{
            return $this->json(["error"=>"json invalide"],400,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*']);
        }
    }
}

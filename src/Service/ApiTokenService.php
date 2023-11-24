<?php
namespace App\Service;

use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
class ApiTokenService{
    private $hash;
    private $repo;
    private $serializer;
    public function __construct(UserPasswordHasherInterface $hash,UserRepository $userRepository,
    SerializerInterface $serializerInterface){
        $this->hash = $hash;
        $this->repo = $userRepository;
        $this->serializer = $serializerInterface;
    }
    public function getToken($email,$pass):array|bool{
        $tab = [];
        $user = $this->repo->findOneBy(['email'=>$email]);
        if($user){
            if($this->hash->isPasswordValid($user, $pass)){
                $tab = $this->serializer->normalize($user, null, ['groups' => 'user']);
                return $tab;
            }else{
                $tab = ["error"=>"Informations de connexion invalide pass"];
                return $tab;
            }
        }
        else{
            $tab = ["error"=>"Informations de connexion invalide email"];
            return $tab;
        }
    }
    public function verifToken($token):bool{
        if($this->repo->findOneBy(['token'=>$token])){
            return true;
        }
        else{
            return false;
        }
    }
}

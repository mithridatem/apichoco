<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Repository\ChocoblastRepository;
use App\Entity\User;
use App\Entity\Chocoblast;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class ChocoblastController extends AbstractController
{
    //méthode pour ajouter un chocoblast
    #[Route('/chocoblast/add', name:'app_chocoblast_add')]
    public function chocoblastAdd(Request $request,EntityManagerInterface $em,
    ChocoblastRepository $chocoblastRepository,UserRepository $userRepository,
    SerializerInterface $serializerInterface):Response{
        $json = $request->getContent();
        //test si le json est valide
        if($json){
            $data = $serializerInterface->decode($json, 'json');
            $choco = new Chocoblast();
            $choco->setTitle($data['title']);
            $choco->setContent($data['content']);
            $choco->setCreationDate(new \DateTimeImmutable($data['creation_date']));
            $choco->setAuthor($userRepository->find($data['author']['id']));
            $choco->setTarget($userRepository->find($data['target']['id']));
            $choco->setActivated(true);
            $em->persist($choco);
            $em->flush();
            return $this->json(['error'=>"le chocoblast a été ajouté en bdd"],200,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*']);
        }
        //test si le json est invalide
        else{
            return $this->json(['Error'=>'Json invalide'],400,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*']);
        }
    }
    //méthode pour afficher tous les chocoblasts
    #[Route('/chocoblast/all', name:'app_chocoblast_all')]
    public function getAllChocoblast(ChocoblastRepository $chocoblastRepository):Response{
        $chocos = $chocoblastRepository->findAll();
        //test si le chocoblast existe
        if($chocos){
            return $this->json($chocos,200,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*'],['groups'=>'choco']);
        }
        //test si le chocoblast n'existe pas
        else{
            return $this->json(['error'=>'Aucun chocoblast en BDD'],400,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*']);
        }
    }
    //méthode pour afficher un chocoblast par son id
    #[Route('/chocoblast/id/{id}', name:'app_chocoblast_id')]
    public function getChocoblastById($id,ChocoblastRepository $chocoblastRepository):Response{
        $choco = $chocoblastRepository->find($id);
        if($choco){
            return $this->json($choco,200,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*'],['groups'=>'choco']);
        }else{
            return $this->json(['error'=>'le chocoblast n\'existe pas'],400,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*']);
        }
    }
}

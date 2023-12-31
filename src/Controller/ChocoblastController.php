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
use App\Service\ApiTokenService;
use App\Service\UtilsService;
class ChocoblastController extends AbstractController
{
    private $serializer;
    private $chocoblastRepository;
    private $userRepository;
    private $em;
    public function __construct(ChocoblastRepository $chocoblastRepository,
    UserRepository $userRepository,SerializerInterface $serializerInterface,
    EntityManagerInterface $entityManagerInterface){
        $this->serializer = $serializerInterface;
        $this->chocoblastRepository = $chocoblastRepository;
        $this->userRepository = $userRepository;
        $this->em = $entityManagerInterface;
    }
    //méthode pour ajouter un chocoblast
    #[Route('/chocoblast/add', name:'app_chocoblast_add')]
    public function chocoblastAdd(Request $request):Response{
        $message = "";
        $code = 200;
        $json = $request->getContent();
        //test si le json est valide
        if($json){
            $data = $this->serializer->decode($json, 'json');
            $choco = new Chocoblast();
            $choco->setTitle(UtilsService::cleanInput($data["title"]));
            $choco->setContent(UtilsService::cleanInput($data["content"]));
            $choco->setCreationDate(new \DateTimeImmutable(UtilsService::cleanInput($data["creation_date"])));
            $choco->setAuthor($this->userRepository->find(UtilsService::cleanInput($data["author"]["id"])));
            $choco->setTarget($this->userRepository->find(UtilsService::cleanInput($data["target"]["id"])));
            $choco->setActivated(true);
            $this->em->persist($choco);
            $this->em->flush();
            $message = ['error'=>"le chocoblast : ".$choco->getTitle()." a été ajouté en bdd"];
        }
        //test si le json est invalide
        else{
            $message = ['Error'=>'Json invalide'];
            $code = 400;
        }
        return $this->json($message,$code,['Content-Type'=>'application/json',
        'Access-Control-Allow-Origin'=>'*']);
    }
    //méthode pour afficher tous les chocoblasts
    #[Route('/chocoblast/all', name:'app_chocoblast_all')]
    public function getAllChocoblast():Response{
        $message = "";
        $code = 200;
        $groupes = [];
        $chocos = $this->chocoblastRepository->findAll();
        //test si il existe des chocoblasts
        if($chocos){
            $message = $chocos;
            $groupes = ['groups'=>'choco'];
        }
        //test si le chocoblast n'existe pas
        else{
            $message = ['error'=>'Aucun chocoblast en BDD'];
            $code = 400;
        }
        return $this->json($message,$code,['Content-Type'=>'application/json',
            'Access-Control-Allow-Origin'=>'*'],$groupes);
    }
    //méthode pour afficher un chocoblast par son id
    #[Route('/chocoblast/id/{id}', name:'app_chocoblast_id')]
    public function getChocoblastById($id):Response{
        $message = "";
        $code = 200;
        $groupes = [];
        $choco = $this->chocoblastRepository->find($id);
        //test si le chocoblast existe
        if($choco){
            $message = $choco;
            $groupes = ['groups'=>'choco'];
        }else{
            $message = ['error'=>'le chocoblast n\'existe pas'];
            $code = 400;
        }
        return $this->json($message,$code,['Content-Type'=>'application/json',
        'Access-Control-Allow-Origin'=>'*'],$groupes);
    }
}

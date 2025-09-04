<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestController extends AbstractController
{
#[Route('/test', name: 'app_test')] 
    public function petle(): Response 
    {
    $array = [
            "1" => "PHP code tester Sandbox Online",
            "emoji" => "😀 😃 😄 😁 😆 👌❤ ✔(●'◡'●)👳🏻‍♀️,:",
            "5" => 89009 ,
            "Random number" => rand(100, 999),
            "PHP Version" => phpversion()
        ];
        $array2 =[
            "1" =>"To",
            "2" =>"jest",
            "3" =>"przykład."
        ];

        return $this->render('test/petle.html.twig', [
            // 'controller_name' => 'TestController',
            'data_array' => $array, // Przekazujemy całą tablicę do Twiga
            'data_array2'=> $array2,
        ]);
         
    }

    #[Route('/instrwar', name: 'app_instrwar')]
    public function instrwar() : Response{

        $var = false;
       

        return $this-> render('test/instrwar.html.twig', [
            'var' => $var,
        ]);
    }

}
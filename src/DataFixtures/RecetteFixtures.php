<?php

namespace App\DataFixtures;

use App\Entity\Recette;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RecetteFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');
        $bestsplats = array(
            ['Le Magret de canard','cat8'],
            ['Tarte Tatin','cat3'],
            ['Fondant au chocolat','cat3'],
            ['Quiche lorraine','cat5'],
            ['La blanquette de veau','cat8'],
            ['Bò bún','cat13'],
            ['Soufflé au fromage',null],
            ['Le steak-frites','cat8'],
            ['Le bœuf bourguignon','cat4'],
            ['Carbonnade flamande','cat4'],
            ['Lasagnes à la bolognaise','cat4'],
            ['Le croque-monsieur','cat12'],
            ['Le pot-au-feu','cat4'],
            ['Le hachis Parmentier','cat4'],
            ['Coq au vin',null],
            ['Nems','cat13'],
            ['Soupe à l\'oignon','cat9'],
            ['Salade César','cat11'],
            ['Mojito','cat10'],
            ['Oeuf mayonnaise','cat2'],
        );
        foreach ($bestsplats as $plat) {
            $recette = new Recette();
            $recette->setTitle($plat[0]);
            $recette->setTimeprepare($faker->numberBetween(0, 60));
            $recette->setDescription($faker->realTextBetween(50, 150));
            $recette->setConseil($faker->realTextBetween(10, 500));
            $recette->setTimecook($faker->numberBetween(0, 100));
            $recette->setCreatedAt(new \DateTimeImmutable());
            $recette->setDifficulty($faker->randomElement(['facile','moyen','difficile']));

            if($plat[1] != null) {
                $recette->setCategory($this->getReference($plat[1]));
            }
            $manager->persist($recette);
        }

        $manager->flush();
    }
}

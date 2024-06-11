<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');
        $bestscats = array(
            ['Cakes', 'cat1'],
            ['Entrées', 'cat2'],
            ['Desserts', 'cat3'],
            ['Plats', 'cat4'],
            ['Tartes', 'cat5'],
            ['Sauces', 'cat6'],
            ['Omelettes', 'cat7'],
            ['Viandes', 'cat8'],
            ['Soupes', 'cat9'],
            ['Boissons',  'cat10'],
            ['Salades', 'cat11'],
            ['Sandwiches, Burgers', 'cat12'],
            ['Asiatique', 'cat13'],
        );
        foreach ($bestscats as $category) {
            $cat = new Category();
            $cat->setTitle($category[0]);
            $cat->setContent($faker->realTextBetween(1, 150));
            $cat->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($cat);
            $this->addReference($category[1], $cat);
        }
        $manager->flush();
    }
}

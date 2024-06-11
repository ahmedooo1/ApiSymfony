<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategoryTest extends KernelTestCase
{

    use EntityTestTrait;
    public function getEntity(): Category
    {
        return (new Category())
            ->setTitle('category test')
            ->setContent('Bienvenue , voici une description de cette catégorie')
            ->setCreatedAt(new DateTimeImmutable('-10days'));
    }

    public function testValidEntity()
    {
        $cat = $this->getEntity();
        $this->assertHasErrors($cat);
    }


    public function testInvalidedTitleCat2() {
        $product = $this->getEntity()->setTitle('');
        $this->assertHasErrors($product,2);
    }

    public function testInvalidedTitleCat3() {
        $product = $this->getEntity()->setTitle('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla ante diam, scelerisque ut eleifend vel, efficitur sit amet nunc. Vivamus ornare condimentum rutrum. Nunc eu elit scelerisque, vehicula dolor vitae tincidunt.');
        $this->assertHasErrors($product,1);
    }

}

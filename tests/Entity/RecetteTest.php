<?php

namespace App\Tests\Entity;

use App\Entity\Recette;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RecetteTest extends KernelTestCase
{

    use EntityTestTrait;
    public function getEntity(): Recette
    {
        return (new Recette())
            ->setTitle('category test')
            ->setDescription('Bienvenue , voici une description de cette recette')
            ->setDifficulty('facile')
            ->setTimecook(12)
            ->setTimeprepare(10)
            ->setConseil('petit conseil de la recette')
            ->setCreatedAt(new DateTimeImmutable('-10days'));
    }

    public function testValidEntity()
    {
        $cat = $this->getEntity();
        $this->assertHasErrors($cat);
    }

    public function testInvalidedTitleCat1() {
        $product = $this->getEntity()->setTitle('Me');
        $this->assertHasErrors($product,1);
    }

    public function testInvalidedTitleCat2() {
        $product = $this->getEntity()->setTitle('');
        $this->assertHasErrors($product,2);
    }

    public function testInvalidedTitleCat3() {
        $product = $this->getEntity()->setTitle('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla ante diam, scelerisque ut eleifend vel, efficitur sit amet nunc. Vivamus ornare condimentum rutrum. Nunc eu elit scelerisque, vehicula dolor vitae tincidunt.');
        $this->assertHasErrors($product,1);
    }

    public function testInvalidedDescription() {
        $product = $this->getEntity()->setDescription('');
        $this->assertHasErrors($product,2);
    }


    public function testInvalidedDifficulty() {
        $product = $this->getEntity()->setDifficulty('');
        $this->assertHasErrors($product,2);
    }

    public function testInvalidedDifficulty2() {
        $product = $this->getEntity()->setDifficulty('dure');
        $this->assertHasErrors($product,1);
    }

    // etc +++

    public function testInvalidedTimePrepare() {
        $product = $this->getEntity()->setTimeprepare(-12);
        $this->assertHasErrors($product,1);
    }

    public function testInvalidedTimePrepare2() {
        $product = $this->getEntity()->setTimeprepare(400);
        $this->assertHasErrors($product,1);
    }
    public function testInvalidedTimePrepare3() {
        $product = $this->getEntity()->setTimeprepare(0);
        $this->assertHasErrors($product,1);
    }

}
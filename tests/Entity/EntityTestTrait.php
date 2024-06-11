<?php
namespace App\Tests\Entity;

trait EntityTestTrait
{
    public function assertHasErrors($entity, int $number = 0): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $errors = $container->get('validator')->validate($entity);
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error->getPropertyPath() . ' => ' . $error->getMessage();
        }
        $this->assertCount($number, $errors, implode(', ', $messages));
    }
}
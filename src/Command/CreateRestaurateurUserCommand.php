<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'app:create-restaurateur-user', description: 'Creates a new restaurateur user.')]

class CreateRestaurateurUserCommand extends Command
{
    protected static $defaultName = 'app:create-restaurateur-user';
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates a new restaurateur user.')
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the restaurateur user.')
            ->addArgument('password', InputArgument::REQUIRED, 'The password of the restaurateur user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_RESTAURATEUR']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('Restaurateur user created successfully!');

        return Command::SUCCESS;
    }
}

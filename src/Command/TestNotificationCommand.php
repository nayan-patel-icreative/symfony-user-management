<?php

namespace App\Command;

use App\Entity\AuthUser;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-notification',
    description: 'Creates a test notification for the first user'
)]
class TestNotificationCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userRepository = $this->entityManager->getRepository(AuthUser::class);
        $user = $userRepository->findOneBy([]);

        if (!$user) {
            $output->writeln('<error>No users found in database. Please register a user first.</error>');
            return Command::FAILURE;
        }

        $notification = new Notification();
        $notification->setTitle('Test Notification');
        $notification->setMessage('This is a test notification to verify the system is working.');
        $notification->setIsRead(false);
        $notification->setUser($user);
        $notification->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $output->writeln('<info>Test notification created successfully!</info>');
        $output->writeln(sprintf('User: %s (%s)', $user->getName(), $user->getEmail()));
        $output->writeln(sprintf('Notification ID: %d', $notification->getId()));

        return Command::SUCCESS;
    }
}

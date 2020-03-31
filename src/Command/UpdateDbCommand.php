<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateDbCommand extends Command
{
    protected static $defaultName = 'app:update-db';

    private $em;

    public function __construct(string $name = null, EntityManagerInterface $em)
    {
        parent::__construct($name);
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setDescription('mise à jour de différentes options de la base de données')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->em->getConnection()->exec("UPDATE `rcr` set number_of_records = (select count(*) from record where record.rcr_create_id = rcr.id)");
        $this->em->getConnection()->exec("UPDATE `rcr` set number_of_records_corrected = (select count(*) from record where record.rcr_create_id = rcr.id and status != 0)");
        $this->em->getConnection()->exec("UPDATE `record` set winnie = 0 where status = 0;");
        $this->em->getConnection()->exec("UPDATE `record` set winnie = 1 where status = 0 and id in (select record_id from link_error where id not in (select link_error_id from paprika_link))");
        return 1;
    }
}

<?php

namespace App\Command;

use App\Entity\Record;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
            ->setDescription('mise à jour de différentes options de la base de données');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        foreach ([Record::RECORD_TODO, Record::RECORD_VALIDATED, Record::RECORD_SKIPPED, Record::RECORD_FIXED_OUTSIDE] as $status) {
            $this->em->getConnection()->executeQuery("UPDATE `rcr` set records_status".$status." = (select count(*) from record where record.rcr_create_id = rcr.id and status = ".$status.")");
        }
        $this->em->getConnection()->executeQuery("UPDATE `record` set winnie = 0 where status = 0;");
        $this->em->getConnection()->executeQuery("UPDATE `record` set winnie = 1 where status = 0 and id in (select record_id from link_error where id not in (select link_error_id from paprika_link))");
        $io->success("Mise à jour terminée avec succès");
        return 1;
    }
}

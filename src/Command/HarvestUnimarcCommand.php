<?php

namespace App\Command;

use App\Entity\Iln;
use App\Entity\Record;
use App\Service\WsHarvester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class HarvestUnimarcCommand extends Command
{
    protected static $defaultName = 'app:harvest-unimarc';
    protected static $defaultDescription = 'Add a short description for your command';
    private $em;
    private $wsHarvester;

    public function __construct(EntityManagerInterface $em, WsHarvester $ws)
    {
        parent::__construct();

        $this->em = $em;
        $this->wsHarvester = $ws;
    }

    protected function configure()
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $iln = $this->em->getRepository(Iln::class)->find(1);
        $records = $this->em->getRepository(Record::class)->findMissingMarcForIln($iln);
        $io->title(sizeof($records) . " notices à compléter pour " . $iln->getLabel());
        foreach ($records as $record) {
            $record = $this->wsHarvester->populateRecordFromAbes($record);
            $this->em->persist($record);
            $this->em->flush();
            $io->writeln("<info>" . $record->getPpn() . " mis à jour</info>");
        }
        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}

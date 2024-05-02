<?php

namespace App\Command;

use App\Entity\Iln;
use App\Entity\Record;
use App\Service\WsHarvester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixLangCommand extends Command
{
    protected static $defaultName = 'app:fix-lang';

    private $em;
    private $wsHarvester = null;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setDescription('Cette fonction va identifier les notices pour lesquelles on peut trouver une langue');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var Iln $iln */
        $iln = $this->em->getRepository(Iln::class)->find(1);
        $io->title("Récupération pour " . $iln->getLabel());

        $records = $this->em->getRepository(Record::class)->findMissingLangForIln($iln);
        $io->title(sizeof($records) . " notices à traiter");

        $batchSize = 200;
        $i = 0;
        foreach ($records as $record) {
            /** @var Record $record */
            if ($record->getMarcBefore() == "") {
                if (is_null($this->wsHarvester)) {
                    $this->wsHarvester = new WsHarvester($this->em);
                }
                $record = $this->wsHarvester->populateRecordFromAbes($record);
            }
            $lang = $record->guessLang();
            if ($lang !== "") {
                $i++;
                print $record->getPpn()."#".$lang . "#\n";
                $record->setLang($lang);
                $this->em->persist($record);

                if (($i % $batchSize) === 0) {
                    $io->writeln("<info>FLUSH</info>");
                    $this->em->flush();
                }
            }
        }
        $this->em->flush();

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return 0;
    }
}

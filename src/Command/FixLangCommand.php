<?php

namespace App\Command;

use App\Entity\Iln;
use App\Entity\Record;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixLangCommand extends Command
{
    protected static $defaultName = 'app:fix-lang';

    private $em;

    public function __construct(string $name = null, EntityManagerInterface $em)
    {
        parent::__construct($name);
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
        $iln = $this->em->getRepository(Iln::class)->find(1);
        $io->title("Récupération pour " . $iln->getLabel());

        $records = $this->em->getRepository(Record::class)->findMissingLangForIln($iln);
        $io->title(sizeof($records) . " à traiter");

        $batchSize = 200;
        $i = 0;
        foreach ($records as $record) {
            $lang = $record->guessLang();
            if ($lang !== "") {
                $i++;
                print $lang . "\n";
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

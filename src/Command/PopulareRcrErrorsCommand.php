<?php

namespace App\Command;

use App\Entity\Iln;
use App\Entity\LinkError;
use App\Entity\Rcr;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PopulareRcrErrorsCommand extends Command
{
    protected static $defaultName = 'app:populate-rcr-errors';

    private $em;
    public function __construct(string $name = null, EntityManagerInterface $em)
    {
        parent::__construct($name);
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');
        $ilnNumber = $input->getArgument('arg1');
        if (!$ilnNumber) {
            $io->error("Manque le code de l'ILN en argument");
            exit;
        }

        $iln = $this->em->getRepository(Iln::class)->findOneBy(["number" => $ilnNumber]);
        if (!$iln) {
            $io->error("Aucun iln à ce numéro dans la base");
            exit;
        }

        $rcrs = $iln->getRcrs();
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

        foreach ($rcrs as $rcr) {
            $url = sprintf("https://www.idref.fr/AlgoLiens?rcr=%s&rownum=200", $rcr->getCode());
            $content = file_get_contents($url);

            $lines = preg_split("/\n/", $content);
            $lines = array_slice($lines, 3);

            $io->writeln("Start load for ".$rcr->getCode());
            $count = 0;
            foreach ($lines as $line) {
                $io->writeln($count);
                $error = preg_split("/\t/", $line);
                if (sizeof($error) > 1) {
                    $errorObject = new LinkError();
                    $errorObject->setPpn($error[0]);
                    $errorObject->setIlnCreate($iln);
                    $errorObject->setRcrCreate($rcr);
                    $errorObject->setRcrUpdate($this->em->getRepository(Rcr::class)->findOneBy(["code" => $error[2]]));
                    $errorObject->setTextError($error[3]);
                    $errorObject->setDateUpdate(new \DateTime($error[4]));
                    $errorObject->setCodeError($error[5]);
                    $errorObject->setTypeDoc($error[6]);
                    $errorObject->setTypeDocLabel($error[7]);

                    $this->em->persist($errorObject);
                    $count++;
                }
            }

            $this->em->flush();
            $io->writeln(sprintf("%s : %s errors loaded", $rcr->getCode(), $count));
        }


        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return 0;
    }
}

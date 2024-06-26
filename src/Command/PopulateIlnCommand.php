<?php

namespace App\Command;

use App\Entity\Iln;
use App\Entity\Rcr;
use App\Repository\IlnRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PopulateIlnCommand extends Command
{
    protected static $defaultName = 'app:populate-iln';
    protected static $defaultDescription = 'Based on an ILN NUMBER';

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->addArgument('ilnNumber', InputArgument::OPTIONAL, "Code de l'iln au format numérique")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ilnNumber = $input->getArgument('ilnNumber');
        if (!$ilnNumber) {
            $io->error("Manque le code de l'ILN en argument");
            exit;
        }

        $iln = $this->em->getRepository(Iln::class)->findOneBy(["number" => $ilnNumber]);
        if (!$iln) {
            $io->error("Aucun iln à ce numéro dans la base");
            exit;
        }

        # On va aller interroger le WS
        $rcrJson = file_get_contents("https://www.idref.fr/services/iln2rcr/".$ilnNumber."&format=text/json");
        $rcrArray = json_decode($rcrJson);

        $count = 0;
        foreach ($rcrArray->sudoc->query->result as $rcrDescription) {
            $rcr = new Rcr();
            $rcr->setCode($rcrDescription->library->rcr);
            $rcr->setLabel($rcrDescription->library->shortname);
            $rcr->setUpdated(new \DateTime());
            $rcr->setIln($iln);
            $rcr->setHarvested(0);
            $rcr->setActive(1);

            $this->em->persist($rcr);
            $count++;
        }
        $this->em->flush();
        $io->success($count . " RCR created");


        $io->success('Finish !');

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}

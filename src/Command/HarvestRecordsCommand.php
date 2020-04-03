<?php

namespace App\Command;

use App\Entity\BatchImport;
use App\Entity\Iln;
use App\Entity\Rcr;
use App\Repository\RcrRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

define("URL_RCR_LOCA", 1);
define("URL_RCR_CREA", 2);

class HarvestRecordsCommand extends Command
{
    protected static $defaultName = 'app:harvest-records';

    private $em;
    private $io;

    public function __construct(string $name = null, EntityManagerInterface $em)
    {
        parent::__construct($name);
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setDescription('download all records')
            ->addOption('iln', null, InputOption::VALUE_REQUIRED, "Code de l'ILN")
            ->addOption('clean-database', null, InputOption::VALUE_NONE, "Nettoyage de la base de données avant import")
            ->addOption('rcr-from-file', null, InputOption::VALUE_REQUIRED, "Le code d'un RCR que l'on souhaite charger depuis un fichier")
        ;
    }

    protected function emptyDatabase() {
        $this->em->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->em->getConnection()->exec("UPDATE `rcr` set number_of_records = 0;");
        $this->em->getConnection()->exec("UPDATE `rcr` set harvested = 0;");
        $this->em->getConnection()->exec("truncate link_error;");
        $this->em->getConnection()->exec("truncate paprika_link;");
        $this->em->getConnection()->exec("truncate record;");
        $this->em->getConnection()->exec("truncate batch_import;");
        $this->em->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function getWsFilename(Helper $helper, InputInterface $input, OutputInterface $output) {
        $finder = new Finder();
        $directory_files = "ws_files";
        $finder->files()->in($directory_files );
        $choices = [];
        foreach ($finder as $file) {
            $choices[] = $directory_files."/".$file->getFilename();
        }

        $question = new ChoiceQuestion(
            "Choisir le fichier contenant les résultats du WS Algoliens",
            $choices
        );
        $question->setErrorMessage('Fichier invalide.');

        $filename = $helper->ask($input, $output, $question);
        return $filename;
    }

    private function displayBatchResult(BatchImport $batchImport) {
        $this->io->writeln(sprintf("<info>Import terminé</info> : %s records / %s errors", $batchImport->getCountRecords(), $batchImport->getCountErrors()));
        $this->io->writeln("Durée : ".$batchImport->getDuration());
        $this->io->writeln("");
    }

    private function runBatch(Rcr $rcr, int $batchType) {
        if ($batchType == BatchImport::TYPE_UNICA) {
            $this->io->writeln("Récupération UNICA");
        } elseif ($batchType == BatchImport::TYPE_RCR_CREA) {
            $this->io->writeln("Récupération RCR CREATEUR");
        }

        if ($rcr->hasBatchRun($batchType)) {
            $this->io->writeln("<error>Déjà joué</error>");
        } else {
            $batchImport = new BatchImport($rcr, $batchType, $this->em);
            $batchImport->run();
            $this->displayBatchResult($batchImport);
        }
    }

    private function runBatches(Rcr $rcr) {
        $this->runBatch($rcr, BatchImport::TYPE_RCR_CREA);
        $this->em->getRepository(Rcr::class)->updateStats($rcr);
        exit;
        $this->runBatch($rcr, BatchImport::TYPE_UNICA);
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $ilnNumber = $input->getOption('iln');
        if (!$ilnNumber) {
            $this->io->error("Manque le code de l'ILN en argument");
            exit;
        }

        $iln = $this->em->getRepository(Iln::class)->findOneBy(["number" => $ilnNumber]);
        if (!$iln) {
            $this->io->error("Aucun iln à ce numéro dans la base");
            exit;
        }

        $cleandb = $input->getOption('clean-database');
        $helper = $this->getHelper('question');

        if ($cleandb) {
            $question = new ConfirmationQuestion("Êtes-vous sûr·e de vouloir vider la base de données avant import ?\n", false);

            if (!$helper->ask($input, $output, $question)) {
                $this->io->error("Fin de traitement en l'absence de validation");
                exit;
            }
            $this->emptyDatabase();
        }

        $rcrFromFile = $input->getOption('rcr-from-file');
        if ($rcrFromFile) {
            $rcr = $this->em->getRepository(Rcr::class)->findOneBy(["code" => $rcrFromFile]);
            $this->io->title("Chargement spécifique pour ".$rcr->getLabel()." (".$rcr->getCode().")");
            $filename = $this->getWsFilename($helper, $input, $output);
            $content = file_get_contents($filename);
            $this->processContent($rcr, 2, $content);
        } else {
            $rcrs = $iln->getRcrs();
            foreach ($rcrs as $rcr) {
                $this->io->title("Traitement RCR : ".$rcr->getCode()." - ".$rcr->getLabel());
                $this->runBatches($rcr);
            }
        }

        $this->io->success('Harvesting terminé avec succès !');

        return 0;
    }
}

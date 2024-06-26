<?php

namespace App\Command;

use App\Entity\BatchImport;
use App\Entity\Iln;
use App\Entity\Rcr;
use App\Entity\Record;
use App\Service\WsHarvester;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

define("URL_RCR_LOCA", 1);
define("URL_RCR_CREA", 2);

class HarvestRecordsCommand extends Command
{
    protected static $defaultName = 'app:harvest-records';
    protected static $defaultDescription = 'download all records';

    private $em;
    private $io;
    private $wsHarvester;
    private $input;
    private $output;
    private $logger;

    public function __construct(EntityManagerInterface $em, WsHarvester $wsHarvester, LoggerInterface $logger)
    {
        parent::__construct();
        $this->em = $em;
        $this->wsHarvester = $wsHarvester;
        $this->logger = $logger;

        ini_set("default_socket_timeout", 3600);

    }

    protected function configure()
    {
        $this
            ->addOption('iln', null, InputOption::VALUE_REQUIRED, "Code de l'ILN")
            ->addOption('clean-database', null, InputOption::VALUE_NONE, "Nettoyage de la base de données avant import")
            ->addOption('rcr-from-file', null, InputOption::VALUE_REQUIRED, "Le code d'un RCR que l'on souhaite charger depuis un fichier")
            ->addOption('refresh', null, InputOption::VALUE_NONE, "Refresh des batchs déjà récupérés");;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $this->io = new SymfonyStyle($this->input, $this->output);

        $cleandb = $this->input->getOption('clean-database');
        $helper = $this->getHelper('question');

        if ($cleandb) {
            $question = new ConfirmationQuestion("Êtes-vous sûr·e de vouloir vider la base de données avant import ?\n", false);

            if (!$helper->ask($this->input, $this->output, $question)) {
                $this->io->error("Fin de traitement en l'absence de validation");
                exit;
            }
            $this->emptyDatabase();
        }

        $rcrFromFile = $this->input->getOption('rcr-from-file');
        if ($rcrFromFile) {
            /** @var Rcr $rcr */
            $rcr = $this->em->getRepository(Rcr::class)->findOneBy(["code" => $rcrFromFile]);
            $this->io->title("Chargement spécifique pour " . $rcr->getLabel() . " (" . $rcr->getCode() . ")");
            $filename = $this->getWsFilename($helper);
            $content = file_get_contents($filename);

            $batchTypeQuestion = new ChoiceQuestion("Quel est le type de cet import ?",
                [
                    "RCR Créateur",
                    "UNICA",
                ]);

            $batchTypeLabel = $helper->ask($this->input, $this->output, $batchTypeQuestion);
            $batchTypeValue = null;
            switch ($batchTypeLabel) {
                case "RCR Créateur":
                    $batchTypeValue = BatchImport::TYPE_RCR_CREA;
                    break;
                case "UNICA":
                    $batchTypeValue = BatchImport::TYPE_UNICA;
                    break;
                default:
                    dd("Erreur de type de batch");
            }
            # On va lancer le traitement en le créant s'il n'en existe pas, ou en relançant sur un précédent sinon
            $batch = $this->em->getRepository(BatchImport::class)->findOneBy(["rcr" => $rcr, "type" => $batchTypeValue]);
            if ($batch) {
                $this->wsHarvester->runNewBatchAlreadyCreated($batch, $this->logger, $content);
            } else {
                $this->wsHarvester->runNewBatch($rcr, $batchTypeValue, $content);
            }
        } else {
            $ilnNumber = $this->input->getOption('iln');
            if (!$ilnNumber) {
                $this->io->error("Manque le code de l'ILN en argument");
                exit;
            }

            $iln = $this->em->getRepository(Iln::class)->findOneBy(["number" => $ilnNumber]);
            if (!$iln) {
                $this->io->error("Aucun iln à ce numéro dans la base");
                exit;
            }

            $rcrs = $iln->getRcrs();
            $rcrsId = [];
            foreach ($rcrs as $rcr) {
                $rcrsId[] = $rcr->getId();
            }
            foreach ($rcrsId as $rcrId) {
                /** @var Rcr $rcr */
                $rcr = $this->em->getRepository(Rcr::class)->find($rcrId);
                $this->io->title("Traitement RCR : " . $rcr->getCode() . " - " . $rcr->getLabel());
                $this->runBatches($rcr);
            }
        }

        $this->io->success('Harvesting terminé avec succès !');

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected function emptyDatabase()
    {
        $this->em->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->em->getConnection()->exec("UPDATE `rcr` set number_of_records = 0;");
        $this->em->getConnection()->exec("UPDATE `rcr` set harvested = 0;");
        $this->em->getConnection()->exec("truncate link_error;");
        $this->em->getConnection()->exec("truncate paprika_link;");
        $this->em->getConnection()->exec("truncate record;");
        $this->em->getConnection()->exec("truncate batch_import;");
        $this->em->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function getWsFilename(Helper $helper)
    {
        $finder = new Finder();
        $directory_files = "ws_files";
        $finder->files()->in($directory_files);
        $choices = [];
        foreach ($finder as $file) {
            $choices[] = $directory_files . "/" . $file->getFilename();
        }

        $question = new ChoiceQuestion(
            "Choisir le fichier contenant les résultats du WS Algoliens",
            $choices
        );
        $question->setErrorMessage('Fichier invalide.');

        $filename = $helper->ask($this->input, $this->output, $question);
        return $filename;
    }

    private function runBatches(Rcr $rcr): void
    {
        $this->runBatch($rcr, BatchImport::TYPE_RCR_CREA);
        $this->runBatch($rcr, BatchImport::TYPE_UNICA);
        $this->em->clear();
    }

    private function runBatch(Rcr $rcr, int $batchType): void
    {
        if ($batchType == BatchImport::TYPE_UNICA) {
            $this->io->writeln("Récupération UNICA");
        } elseif ($batchType == BatchImport::TYPE_RCR_CREA) {
            $this->io->writeln("Récupération RCR CREATEUR");
        }

        $batch = $rcr->hasBatchRun($batchType);
        if ($batch) {
            if ($this->input->getOption("refresh")) {
                $start = microtime(true);
                $this->io->writeln("BEFORE : ".$batch->getCountRecords()." records / ".$batch->getCountErrors()." errors");
                $this->em->getRepository(Record::class)->deactivateForBatch($batch);
                $batch->setStatus(BatchImport::STATUS_RUNNING);
                $batch->setStartDate(new DateTime());
                $batch->setEndDate(null);

                $this->wsHarvester->runNewBatchAlreadyCreated($batch, $this->logger);
                $batch = $rcr->hasBatchRun($batchType);
                $end = microtime(true);

                $this->io->writeln("AFTER : ".$batch->getCountRecords()." records / ".$batch->getCountErrors()." errors [".($end-$start)." s]");

            } else {
                $this->io->writeln("<error>Déjà joué (ajouter le paramètre --refresh pour mettre à jour</error>");
            }
        } else {
            if (!$this->input->getOption("refresh")) {
                $batchImport = $this->wsHarvester->runNewBatch($rcr, $batchType);
                $this->displayBatchResult($batchImport);
            } else {
                $this->io->writeln("On est en refresh, ce batch n'a jamais tourné, on le passe pour le moment");
            }
        }
    }

    private function displayBatchResult(BatchImport $batchImport): void
    {
        $this->io->writeln(sprintf("<info>Import terminé</info> : %s records / %s errors", $batchImport->getCountRecords(), $batchImport->getCountErrors()));
        $this->io->writeln("Durée : " . $batchImport->getDurationAsString());
        $this->io->writeln("");
    }
}

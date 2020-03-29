<?php

namespace App\Command;

use App\Entity\Iln;
use App\Entity\LinkError;
use App\Entity\PaprikaLink;
use App\Entity\Rcr;
use App\Entity\Record;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;

define("URL_RCR_LOCA"
    , 1);
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
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    private function getAlgoLienContent($baseurl, $rownum = 10000) {
        if ($rownum == 0) {
            // Si on arrive à 0, on abandonne
            $this->io->writeln("<error>Récupération impossible, on abandonne</error>");
            return null;
            return $this->getAlgoLienContent($baseurl, 1000);
        }
        $url = $baseurl."&rownum=".$rownum;
        $this->io->writeln("WS full URL : ".$url);
        $client = HttpClient::create();
        try {
            $response = $client->request('GET', $url."&rownum=".$rownum, [
                'max_duration' => 0
            ]);
            $content = $response->getContent();
        } catch (\Exception $e) {

            $this->io->writeln("<error>WS : erreur téléchargement, on repart pour ".intval($rownum / 2)."</error>");

            return $this->getAlgoLienContent($baseurl, intval($rownum / 2));
        }
        return $content;

    }

    private function processUrl(Rcr $rcr, string $url, int $urlCallType) {
        $startRcr = microtime(true);
        $this->io->writeln("WS : ".$url);
        $start = microtime(true);
        $content = $this->getAlgoLienContent($url, 10000);
        if (is_null($content)) {
            return null;
        }
        $this->io->writeln("WS : fin (".(microtime(true) - $start).")");

        $lines = preg_split("/\n/", $content);
        $lines = array_slice($lines, 3);

        $count = 0;
        $countRecordCreated = 0;

        $ppnPaprikaAlreadySet = [];
        foreach ($lines as $line) {
            $error = preg_split("/\t/", $line);
            if (sizeof($error) > 1) {
                $ppn = trim($error[0]);
                $record = $this->em->getRepository(Record::class)->findOneBy(["ppn" => $ppn, "rcrCreate" => $rcr]);

                if ( (!is_null($record)) && ($record->getUrlCallType() != $urlCallType) ) {
                    $this->io->write(".");
                } else
                {
                    if (is_null($record)) {
                        $countRecordCreated++;
                        $record = new Record();
                        $record->setPpn($ppn);
                        $record->setRcrCreate($rcr);
                        $record->setUrlCallType($urlCallType);

                        $date = trim($error[4]);
                        $date = \DateTime::createFromFormat('Y-m-j H:i:s', $date );
                        $record->setLastUpdate($date);
                        $record->setStatus(0);
                        $record->setDocTypeCode($error[6]);
                        $record->setDocTypeLabel($error[7]);

                        $this->em->persist($record);
                        $this->em->flush();
                    }

                    $errorObject = new LinkError();
                    $errorObject->setErrorText($error[3]);
                    $errorObject->setErrorCode($error[5]);
                    $paprikaUrl = trim($error[8]);
                    if ($paprikaUrl) {
                        if (!isset($ppnPaprikaAlreadySet[$ppn])) {
                            $ppnPaprikaAlreadySet[$ppn] = 1;

                            $paprikaUrls = preg_split("/#/", $paprikaUrl);
                            foreach ($paprikaUrls as $tmpUrl) {
                                $paprikaLink = new PaprikaLink();
                                $paprikaLink->setUrl($tmpUrl);
                                $this->em->persist($paprikaLink);
                                $errorObject->addPaprikaLink($paprikaLink);
                            }
                        }
                    }
                    $errorObject->setRecord($record);
                    $this->em->persist($errorObject);
                    $count++;
                }
            }
        }

        $rcr->setNumberOfRecords($countRecordCreated);
        $this->em->persist($rcr);
        $this->em->flush();
        $this->io->writeln(sprintf("\n<info>%s erreurs importées / %s notices créées</info> (durée totale : %s)", $count, $countRecordCreated, (microtime(true) - $startRcr)));
    }

    protected function cleanDatabaseFromURL2() {
        $this->io->title("Nettoyage de la base de données");
        // "SELECT id from record where url_call_type = 2"
        $this->em->getConnection()->exec("DELETE from paprika_link where link_error_id in (SELECT link_error.id from record, link_error where link_error.record_id = record.id and url_call_type = 2);");
        $this->em->getConnection()->exec("DELETE from link_error where record_id in (SELECT id from record where url_call_type = 2);");
        $this->em->getConnection()->exec("DELETE from record where url_call_type = 2;");
        $this->io->success("effectuée avec succès");
    }

    protected function emptyDatabase() {
        $this->em->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->em->getConnection()->exec("UPDATE `rcr` set number_of_records = 0;");
        $this->em->getConnection()->exec("UPDATE `rcr` set harvested = 0;");
        $this->em->getConnection()->exec("truncate link_error;");
        $this->em->getConnection()->exec("truncate paprika_link;");
        $this->em->getConnection()->exec("truncate record;");
        $this->em->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $ilnNumber = $input->getArgument('arg1');
        if (!$ilnNumber) {
            $this->io->error("Manque le code de l'ILN en argument");
            exit;
        }

        $iln = $this->em->getRepository(Iln::class)->findOneBy(["number" => $ilnNumber]);
        if (!$iln) {
            $this->io->error("Aucun iln à ce numéro dans la base");
            exit;
        }

        print "Clean DATABASE !!!";
        exit;
        $this->emptyDatabase();
        $this->cleanDatabaseFromURL2();

        $rcrs = $iln->getRcrs();
        $nbCalls = 0;
        foreach ($rcrs as $rcr) {
            // $rcr = $this->em->getRepository(Rcr::class)->findOneBy(["code" => '243222202']);
            $this->io->title("Traitement RCR : ".$rcr->getCode()." - ".$rcr->getLabel());
            if ($rcr->getHarvested() == 2) {
                $this->io->writeln("<info>URL 1 & 2 OK</info>");
            } else {
                if ($rcr->getHarvested() == 1) {
                    $this->io->writeln("<info>URL 1 ok</info>");
                } else {
                    // URL_RCR_CREA
                    $url = sprintf("https://www.idref.fr/AlgoLiens?rcr=%s&paprika=1", $rcr->getCode());
                    $this->processUrl($rcr, $url, URL_RCR_CREA);
                    $nbCalls++;
                    $rcr->setHarvested(1);
                    $this->em->persist($rcr);
                    $this->em->flush();
                }

                $this->io->writeln("");

                $url = sprintf("https://www.idref.fr/AlgoLiens?localisationRcr=%s&unica=localisationRcr&paprika=1", $rcr->getCode());
                $this->processUrl($rcr, $url, URL_RCR_LOCA);
                $nbCalls++;
                $this->io->writeln("");
                $rcr->setHarvested(2);
                $this->em->persist($rcr);
                $this->em->flush();
            }

            // repos de 10 secondes pour laisser le serveur tranquille
            if ($nbCalls > 0) {
                $sleep = rand(0, 10);
                $this->io->writeln("Sleep for ".$sleep);
                //sleep($sleep);
            }

        }


        $this->io->success('Harvesting terminé avec succès !');

        return 0;
    }
}

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
        $this->em->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->em->getConnection()->exec("UPDATE `rcr` set number_of_records = 1;");
        $this->em->getConnection()->exec("truncate link_error;");
        $this->em->getConnection()->exec("truncate paprika_link;");
        $this->em->getConnection()->exec("truncate record;");
        $this->em->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 1;");
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
            // $rcr = $this->em->getRepository(Rcr::class)->findOneBy(["code" => '330632101']);
            $url = sprintf("https://www.idref.fr/AlgoLiens?rcr=%s&rownum=100000&paprika=1", $rcr->getCode());
            print $url."\n";
            $content = file_get_contents($url);
            $startRcr = microtime(true);
            $lines = preg_split("/\n/", $content);
            $lines = array_slice($lines, 3);

            $io->writeln("Start load for ".$rcr->getCode());
            $count = 0;
            $countRecordCreated = 0;
            $ppnPaprikaAlreadySet = [];
            foreach ($lines as $line) {
                $error = preg_split("/\t/", $line);
                if (sizeof($error) > 1) {
                    $ppn = trim($error[0]);
                    $record = $this->em->getRepository(Record::class)->findOneBy(["ppn" => $ppn]);
                    if (!$record) {
                        $countRecordCreated++;
                        $record = new Record();
                        $record->setPpn($ppn);
                        $record->setRcrCreate($rcr);

                        $date = trim($error[4]);
                        $date = \DateTime::createFromFormat('Y-m-j H:i:s', $date );
                        $record->setLastUpdate($date);
                        $record->setStatus(0);
                        $record->setDocTypeCode($error[6]);
                        $record->setDocTypeLabel($error[7]);

                        $this->em->persist($record);
                    }

                    $errorObject = new LinkError();
                    $errorObject->setErrorText($error[3]);
                    $errorObject->setErrorCode($error[5]);
                    $paprikaUrl = trim($error[8]);
                    if ($paprikaUrl) {
                        if (isset($ppnPaprikaAlreadySet[$ppn])) {
                            print "PPN ".$ppn." already done\n";
                        } else {
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

            $rcr->setNumberOfRecords($countRecordCreated);
            $this->em->persist($rcr);
            $this->em->flush();
            $io->writeln(sprintf("%s : %s errors loaded [%s]", $rcr->getCode(), $count, (microtime(true) - $startRcr)));
        }


        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return 0;
    }
}

<?php

namespace App\Command;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Writer\WriterInterface;
use Smalot\PdfParser\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use function Symfony\Component\String\u;
use Symfony\Component\Finder\SplFileInfo;
use PhpOffice\PhpWord\PhpWord;
//use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
//use Symfony\Component\Console\Input\InputDefinition;
//use Symfony\Component\Console\Input\InputOption;

class ConvertPdfCommand extends Command
{
    /**
     * the name of the command (the part after "bin/console")
     *
     * @var string
     */
    protected static $defaultName = 'convert:pdf';
    /**
     * @var string
     */
    protected $inputPath;
    /**
     * @var string
     */
    protected $outputPath;
    /**
     * @var Filesystem
     */
    protected $filesystem;
    /**
     * @var Finder
     */
    protected $finder;
    /**
     * @var Parser
     */
    protected $parser;
    /**
     * @var PhpWord
     */
    protected $word;
    /**
     * @var WriterInterface
     */
    protected $writer;

    protected function configure(): void
    {
        $this
            ->setDescription('Convert PDF to RTF')
            ->addArgument('input_path', InputArgument::REQUIRED, 'Input directory')
            ->addArgument('output_path', InputArgument::REQUIRED, 'Output directory');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->filesystem = new Filesystem();
        $this->finder = new Finder();
        $this->parser = new Parser();
        $this->word = new PhpWord();
        $this->writer = IOFactory::createWriter($this->word);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->inputPath = realpath($input->getArgument('input_path'));
        if (!$this->filesystem->exists($this->inputPath)) {
            throw new FileNotFoundException(null, 0, null, $this->inputPath);
        }

        $this->outputPath = realpath($input->getArgument('output_path'));
        if (!$this->filesystem->exists($this->outputPath)) {
            throw new FileNotFoundException(null, 0, null, $this->outputPath);
        }
        return Command::SUCCESS;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processFiles($output);
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }

    protected function getOutputName(string $inputPath): string
    {
        return u($inputPath)->replace($this->inputPath, $this->outputPath) . '.docx';
    }

    protected function processFiles(OutputInterface $output)
    {
        $this->finder->files()->in($this->inputPath)->name('*.pdf');
        if ($this->finder->hasResults()) {
            $progress = new ProgressBar($output, iterator_count($this->finder));
            $progress->start();
            foreach ($this->finder as $file) {
                try {
                    $this->processFile($file, $output);
                } catch (\Exception $ex) {
                    $output->writeln("{$file->getRealPath()} ({$ex->getMessage()})");
                }
                $progress->advance();
            }
            $progress->finish();
        }
    }

    protected function processFile(SplFileInfo $file, OutputInterface $output)
    {
        $outputPath = $this->getOutputName($file->getRealPath(),$this->outputPath);

        //$this->parser = new Parser();
        $pdf = $this->parser->parseFile($file->getRealPath());
        $text = $pdf->getText();
        $filters = [
            '@https:\/\/.*?\d\s\/\s\d@im',
            '@Автор.*?Алексеевич@im',
            '@Источник.*?\d\/@im',
        ];
        $text = preg_replace($filters, "", $text);

        $fontStyleName = 'def';
        $this->word->addFontStyle(
            $fontStyleName,
            array('name' => 'Times New Roman', 'size' => 12, 'color' => '000000', 'bold' => false)
        );
        $section = $this->word->addSection();
        $section->addText($text, $fontStyleName);
        $this->writer->save($outputPath);
    }
}

<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use function Symfony\Component\String\u;
use Symfony\Component\Finder\SplFileInfo;
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
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->inputPath = $input->getArgument('input_path');
        if (!$this->filesystem->exists($this->inputPath)) {
            throw new FileNotFoundException(null, 0, null, $this->inputPath);
        }

        $this->outputPath = $input->getArgument('output_path');
        if (!$this->filesystem->exists($this->outputPath)) {
            throw new FileNotFoundException(null, 0, null, $this->outputPath);
        }
        return Command::SUCCESS;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processDirectories($this->inputPath, $this->outputPath, $output);
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }

    protected function processDirectories(string $inputPath, string $outputPath, OutputInterface $output)
    {
        $this->processFiles($inputPath, $outputPath, $output);
    }

    protected function getOutputName(string $inputPath): string
    {
        return u($inputPath)->replace($this->inputPath, $this->outputPath);
    }

    protected function processFiles(string $inputPath, string $outputPath, OutputInterface $output)
    {
        $this->finder->files()->in($inputPath);
        if ($this->finder->hasResults()) {
            foreach ($this->finder as $file) {
                $this->processFile($file, $outputPath, $output);
            }
        }
    }

    protected function processFile(SplFileInfo $file, $outputPath, OutputInterface $output)
    {
        //$output->writeln($file->getRealPath());
        $output->writeln($file->getRelativePathname());
    }
}
